<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

/**
 * Time Conditions write to v_dialplans + v_dialplan_details.
 * FusionPBX uses app_uuid = '4b821450-926b-175a-af93-a03c441818b1' to
 * identify time condition dialplans (no separate v_time_conditions table).
 *
 * FreeSWITCH uses hour/wday conditions:
 *   <condition field="time_of_day"   break="never" data="08:00-17:00"/>
 *   <condition field="day_of_week"   break="never" data="mon-fri"/>
 *   <action application="transfer"   data="OPEN_DEST XML DOMAIN"/>
 *   <anti-action application="transfer" data="CLOSED_DEST XML DOMAIN"/>
 */

#[\AllowDynamicProperties]
class TimeCondition
{
  const APP_UUID = '4b821450-926b-175a-af93-a03c441818b1';

  public $dialplan_uuid        = null;
  public $domain_uuid          = null;
  public $tenant_id            = null;
  public $tc_name              = null;
  public $tc_extension         = null;
  public $tc_context           = null;
  public $tc_description       = null;
  public $tc_enabled           = 'true';
  public $tc_time_start        = '08:00';
  public $tc_time_stop         = '17:00';
  public $tc_wday_start        = 'mon';
  public $tc_wday_stop         = 'fri';
  public $tc_open_destination  = null;
  public $tc_closed_destination = null;

  private static $wday_map = [
    'sun' => 1, 'mon' => 2, 'tue' => 3, 'wed' => 4,
    'thu' => 5, 'fri' => 6, 'sat' => 7,
  ];

  public function __construct($dialplan_uuid = null)
  {
    if ($dialplan_uuid !== null) {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare(
        "SELECT * FROM v_dialplans WHERE dialplan_uuid = ? AND app_uuid = ?"
      );
      $stmt->execute([$dialplan_uuid, self::APP_UUID]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) { throw new CoreException(404, 'Time Condition not found'); }

      $this->dialplan_uuid  = $row['dialplan_uuid'];
      $this->domain_uuid    = $row['domain_uuid'];
      $this->tc_name        = $row['dialplan_name'];
      $this->tc_extension   = $row['dialplan_number'];
      $this->tc_context     = $row['dialplan_context'];
      $this->tc_description = $row['dialplan_description'];
      $this->tc_enabled     = $row['dialplan_enabled'];

      $this->load_details($pdo);
    }
  }

  private function load_details($pdo)
  {
    $stmt = $pdo->prepare(
      "SELECT dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data
       FROM v_dialplan_details WHERE dialplan_uuid = ? ORDER BY dialplan_detail_order"
    );
    $stmt->execute([$this->dialplan_uuid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
      $tag  = $r['dialplan_detail_tag'];
      $type = $r['dialplan_detail_type'];
      $data = $r['dialplan_detail_data'];

      if ($tag === 'condition') {
        if ($type === 'time_of_day' && strpos($data, '-') !== false) {
          [$start, $stop] = explode('-', $data, 2);
          $this->tc_time_start = trim($start);
          $this->tc_time_stop  = trim($stop);
        }
        if ($type === 'day_of_week' && strpos($data, '-') !== false) {
          [$ws, $we] = explode('-', $data, 2);
          $this->tc_wday_start = trim($ws);
          $this->tc_wday_stop  = trim($we);
        }
      }
      if ($tag === 'action' && $type === 'transfer') {
        $this->tc_open_destination = $data;
      }
      if ($tag === 'anti-action' && $type === 'transfer') {
        $this->tc_closed_destination = $data;
      }
    }
  }

  public static function search($aFilter = [])
  {
    $tenant_id   = isset($aFilter['tenant_id']) ? $aFilter['tenant_id'] : null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter, 'app_uuid' => self::APP_UUID] : ['app_uuid' => self::APP_UUID]; /* params-fix-v3 */
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT dialplan_uuid, dialplan_name AS tc_name, dialplan_number AS tc_extension,
              dialplan_enabled AS tc_enabled, dialplan_description AS tc_description
       FROM v_dialplans
       " . ($domain_filter ? "WHERE domain_uuid = :domain_uuid AND " : "WHERE ") . "app_uuid = :app_uuid
       ORDER BY dialplan_name"
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
  }

  public function save()
  {
    $domain_uuid       = FpbxDomain::get_domain_uuid($this->tenant_id);
    if ($domain_uuid === null) { /* null-domain-guard */
      throw new \ICT\Core\CoreException(409, 'No FusionPBX domain assigned to this tenant. Contact an administrator.');
    }
    $this->domain_uuid = $domain_uuid;
    $pdo               = FpbxDomain::fpbx_db();

    $domain_name  = $this->get_domain_name($pdo, $domain_uuid);
    $context      = $this->tc_context ?: $domain_name;
    $bool_enabled = ($this->tc_enabled === 'false' || $this->tc_enabled === false) ? 'false' : 'true';

    $nameCheck = $pdo->prepare(
      "SELECT COUNT(*) FROM v_dialplans WHERE domain_uuid = :du AND dialplan_name = :name AND app_uuid = :au AND dialplan_uuid != :excl"
    );
    $nameCheck->execute(['du' => $domain_uuid, 'name' => $this->tc_name, 'au' => self::APP_UUID, 'excl' => $this->dialplan_uuid ?? '00000000-0000-0000-0000-000000000000']);
    if ((int)$nameCheck->fetchColumn() > 0) {
      throw new CoreException(409, "A Time Condition named '{$this->tc_name}' already exists in this domain.");
    }

    try {
      if (empty($this->dialplan_uuid)) {
        $this->dialplan_uuid = self::generate_uuid();
        $pdo->prepare(
          "INSERT INTO v_dialplans
           (dialplan_uuid, domain_uuid, app_uuid, dialplan_context, dialplan_name,
            dialplan_number, dialplan_order, dialplan_enabled, dialplan_description)
           VALUES (?, ?, ?, ?, ?, ?, 200, ?, ?)"
        )->execute([
          $this->dialplan_uuid, $domain_uuid, self::APP_UUID, $context,
          $this->tc_name, $this->tc_extension, $bool_enabled,
          $this->tc_description ?: null,
        ]);
      } else {
        $pdo->prepare(
          "UPDATE v_dialplans SET dialplan_context=?, dialplan_name=?, dialplan_number=?,
           dialplan_enabled=?, dialplan_description=? WHERE dialplan_uuid=?"
        )->execute([
          $context, $this->tc_name, $this->tc_extension,
          $bool_enabled, $this->tc_description ?: null, $this->dialplan_uuid,
        ]);
      }

      $this->rebuild_details($pdo, $domain_uuid, $domain_name, $context);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Time Condition save failed: ' . $e->getMessage());
    }
    return $this->dialplan_uuid;
  }

  private function rebuild_details($pdo, $domain_uuid, $domain_name, $context)
  {
    $pdo->prepare("DELETE FROM v_dialplan_details WHERE dialplan_uuid = ?")
        ->execute([$this->dialplan_uuid]);

    $time_range = ($this->tc_time_start ?: '00:00') . '-' . ($this->tc_time_stop ?: '23:59');
    $wday_range = ($this->tc_wday_start ?: 'mon') . '-' . ($this->tc_wday_stop ?: 'fri');

    $open_dest   = $this->tc_open_destination;
    $closed_dest = $this->tc_closed_destination;

    $order = 10;
    $this->insert_detail($pdo, 'condition', 'destination_number',
      '^(' . preg_quote($this->tc_extension ?? '', '/') . ')$', '', 0, $order); $order += 10;
    $this->insert_detail($pdo, 'condition', 'time_of_day',
      $time_range, '', 1, $order, 'never'); $order += 10;
    $this->insert_detail($pdo, 'condition', 'day_of_week',
      $wday_range, '', 2, $order, 'never'); $order += 10;
    $this->insert_detail($pdo, 'action', 'export',
      'call_direction=inbound', 'true', 2, $order); $order += 10;
    $this->insert_detail($pdo, 'action', 'set',
      'domain_uuid=' . $domain_uuid, 'true', 2, $order); $order += 10;
    $this->insert_detail($pdo, 'action', 'set',
      'domain_name=' . $domain_name, 'true', 2, $order); $order += 10;
    if ($open_dest) {
      $this->insert_detail($pdo, 'action', 'transfer',
        $open_dest . ' XML ' . $context, '', 2, $order); $order += 10;
    }
    if ($closed_dest) {
      $this->insert_detail($pdo, 'anti-action', 'transfer',
        $closed_dest . ' XML ' . $context, '', 2, $order);
    }
  }

  private function insert_detail($pdo, $tag, $type, $data, $inline, $group, $order, $break = '')
  {
    $pdo->prepare(
      "INSERT INTO v_dialplan_details
       (dialplan_uuid, dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type,
        dialplan_detail_data, dialplan_detail_inline, dialplan_detail_break,
        dialplan_detail_group, dialplan_detail_order, dialplan_detail_enabled)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'true')"
    )->execute([
      $this->dialplan_uuid, self::generate_uuid(), $tag, $type,
      $data, $inline, $break, $group, $order,
    ]);
  }

  private function get_domain_name($pdo, $domain_uuid)
  {
    $stmt = $pdo->prepare("SELECT domain_name FROM v_domains WHERE domain_uuid = ?");
    $stmt->execute([$domain_uuid]);
    return $stmt->fetchColumn() ?: $domain_uuid;
  }

  public function delete()
  {
    if (empty($this->dialplan_uuid)) { throw new CoreException(400, 'Missing uuid'); }
    $pdo = FpbxDomain::fpbx_db();
    try {
      $pdo->prepare("DELETE FROM v_dialplan_details WHERE dialplan_uuid = ?")
          ->execute([$this->dialplan_uuid]);
      $pdo->prepare("DELETE FROM v_dialplans WHERE dialplan_uuid = ?")
          ->execute([$this->dialplan_uuid]);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Time Condition delete failed: ' . $e->getMessage());
    }
    return true;
  }

  private static function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
      mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
  }
}
