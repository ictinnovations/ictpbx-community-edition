<?php

namespace ICT\Core;

/**
 * PBX object-slot quota helper (Tier 2 billing).
 * Tracks how many PBX objects (ring groups, call queues, etc.) a tenant has provisioned
 * against the limit set in their package subscription.
 *
 * Ships to BOTH editions: the PBX module API classes (FpbxExtension, RingGroup,
 * CallQueue, IvrMenu, Voicemail, ConferenceCenter, MusicOnHold, Device) call into
 * it unconditionally. On CE there are no billing/quota/resource tables, so every
 * method short-circuits via is_community_edition() before touching the DB —
 * quota is simply unlimited.
 */
class PbxQuota
{
  const RING_GROUP    = 7;
  const CALL_QUEUE    = 8;
  const VOICEMAIL     = 9;
  const CONFERENCE    = 10;
  const MUSIC_ON_HOLD = 11;
  const EXTENSIONS    = 15;
  const DEVICES       = 16;
  const IVR_MENU      = 17;

  private static $labels = [
    self::RING_GROUP    => 'Ring Groups',
    self::CALL_QUEUE    => 'Call Queues',
    self::VOICEMAIL     => 'Voicemail Boxes',
    self::CONFERENCE    => 'Conference Rooms',
    self::MUSIC_ON_HOLD => 'Music on Hold',
    12                  => 'Voice Minutes/mo',
    13                  => 'Fax Pages/mo',
    14                  => 'Conference Mins/mo',
    self::EXTENSIONS    => 'Extensions',
    self::DEVICES       => 'Devices',
    self::IVR_MENU      => 'IVR Menus',
  ];

  /**
   * Check whether a tenant can provision one more object of the given type.
   * Returns true if under limit or if no quota row exists (unlimited).
   */
  public static function check($tenant_id, int $resource_id): bool
  {
    if (is_community_edition()) return true;
    $row = self::_row($tenant_id, $resource_id);
    if (!$row) return true;
    return (int)$row['quota_used'] < (int)$row['quota_limit'];
  }

  /**
   * Increment quota_used after a successful INSERT.
   */
  public static function increment($tenant_id, int $resource_id): void
  {
    if (is_community_edition()) return;
    $tid = (int)$tenant_id;
    $rid = (int)$resource_id;
    DB::query('quota',
      "UPDATE quota SET quota_used = quota_used + 1
       WHERE tenant_id = $tid AND resource_id = $rid
       LIMIT 1"
    );
  }

  /**
   * Decrement quota_used after a successful DELETE.
   */
  public static function decrement($tenant_id, int $resource_id): void
  {
    if (is_community_edition()) return;
    $tid = (int)$tenant_id;
    $rid = (int)$resource_id;
    DB::query('quota',
      "UPDATE quota SET quota_used = GREATEST(0, quota_used - 1)
       WHERE tenant_id = $tid AND resource_id = $rid
       LIMIT 1"
    );
  }

  /**
   * Return all PBX slot quotas for a tenant as an array of resource summaries.
   */
  public static function getAll($tenant_id): array
  {
    if (is_community_edition()) return [];
    $tid = (int)$tenant_id;
    $result = DB::query('quota',
      "SELECT q.resource_id, q.quota_used, q.quota_limit, r.name
       FROM quota q
       JOIN resource r ON r.resource_id = q.resource_id
       WHERE q.tenant_id = $tid
         AND q.resource_id IN (" . self::RING_GROUP . "," . self::CALL_QUEUE . "," . self::VOICEMAIL . "," . self::CONFERENCE . "," . self::MUSIC_ON_HOLD . ",12,13,14," . self::EXTENSIONS . "," . self::DEVICES . "," . self::IVR_MENU . ")"
    );
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
      $rows[] = [
        'resource_id' => (int)$row['resource_id'],
        'name'        => self::$labels[(int)$row['resource_id']] ?? $row['name'],
        'used'        => (int)$row['quota_used'],
        'limit'       => (int)$row['quota_limit'],
        'available'   => max(0, (int)$row['quota_limit'] - (int)$row['quota_used']),
        'percent'     => ($row['quota_limit'] > 0)
                          ? round(($row['quota_used'] / $row['quota_limit']) * 100)
                          : 0,
      ];
    }
    return $rows;
  }

  /**
   * Seed default quota rows for a newly created tenant.
   * Called automatically by TenantApi after a successful tenant INSERT.
   * Uses INSERT IGNORE so safe to call multiple times.
   */
  public static function initDefaults($tenant_id): void
  {
    if (is_community_edition()) return;
    $tid  = (int)$tenant_id;
    $now  = time();

    // Ensure subscription exists
    $sub = DB::query('subscription',
      "SELECT subscription_id FROM subscription WHERE tenant_id = $tid AND package_id = 1 LIMIT 1"
    );
    $sub_row = mysqli_fetch_assoc($sub);
    if (!$sub_row) {
      DB::query('subscription',
        "INSERT INTO subscription (usr_id, tenant_id, package_id, time_start, active, auto_renew, date_created, created_by)
         VALUES (1, $tid, 1, $now, 1, 1, $now, 1)"
      );
      $res = DB::query('subscription', "SELECT LAST_INSERT_ID() AS id");
      $r   = mysqli_fetch_assoc($res);
      $sid = (int)$r['id'];
    } else {
      $sid = (int)$sub_row['subscription_id'];
    }

    $defaults = [
      self::RING_GROUP    => 10,
      self::CALL_QUEUE    => 5,
      self::VOICEMAIL     => 50,
      self::CONFERENCE    => 5,
      self::MUSIC_ON_HOLD => 20,
      self::EXTENSIONS    => 25,
      self::DEVICES       => 25,
      self::IVR_MENU      => 10,
      12                  => 1000, // voice_minutes
      13                  => 500,  // fax_pages
      14                  => 100,  // conference_minutes
    ];

    foreach ($defaults as $rid => $limit) {
      DB::query('quota',
        "INSERT IGNORE INTO quota (usr_id, tenant_id, subscription_id, resource_id, quota_limit, quota_offset, quota_locked, quota_used)
         VALUES (1, $tid, $sid, $rid, $limit, 0, 0, 0)"
      );
    }
  }

  public static function getTenantLimit($tenant_id, int $resource_id): int
  {
    if (is_community_edition()) return 0;
    $row = self::_row($tenant_id, $resource_id);
    return $row ? (int)$row['quota_limit'] : 0;
  }

  public static function setLimit($tenant_id, int $resource_id, int $limit): void
  {
    if (is_community_edition()) return;
    $tid = (int)$tenant_id;
    $rid = (int)$resource_id;
    DB::query('quota',
      "UPDATE quota SET quota_limit = $limit WHERE tenant_id = $tid AND resource_id = $rid LIMIT 1"
    );
  }

  private static function _row($tenant_id, int $resource_id): ?array
  {
    $tid = (int)$tenant_id;
    $rid = (int)$resource_id;
    $result = DB::query('quota',
      "SELECT quota_used, quota_limit FROM quota
       WHERE tenant_id = $tid AND resource_id = $rid
       LIMIT 1"
    );
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
  }
}
