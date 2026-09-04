<?php

namespace ICT\Core;

/**
 * Helper class: resolves ICTCore tenant_id ↔ FusionPBX domain_uuid.
 * Every PBX module (RingGroup, Voicemail, CallQueue, etc.) uses this.
 */
class FpbxDomain
{
  // FusionPBX PG creds live in [fusionpbx] section of ictcore.conf. We can't read
  // /etc/fusionpbx/config.conf directly because PHP open_basedir sandboxes us to
  // /usr/ictcore/. The installer mirrors the FusionPBX PG password into ictcore.conf
  // at install time.
  private static $conf_file = '/usr/ictcore/etc/ictcore.conf';
  private static $fpbx_conf_cache = null;

  private static function fpbx_conf()
  {
    if (self::$fpbx_conf_cache !== null) {
      return self::$fpbx_conf_cache;
    }
    $defaults = [
      'host' => '127.0.0.1',
      'port' => '5432',
      'name' => 'fusionpbx',
      'user' => 'fusionpbx',
      'pass' => 'fusionpbx',
    ];
    if (is_readable(self::$conf_file)) {
      $ini = @parse_ini_file(self::$conf_file, true);
      if (is_array($ini) && !empty($ini['fusionpbx']) && is_array($ini['fusionpbx'])) {
        foreach (['host','port','name','user','pass'] as $k) {
          if (isset($ini['fusionpbx'][$k]) && $ini['fusionpbx'][$k] !== '') {
            $defaults[$k] = $ini['fusionpbx'][$k];
          }
        }
      }
    }
    self::$fpbx_conf_cache = $defaults;
    return $defaults;
  }

  public static function fpbx_db()
  {
    $c = self::fpbx_conf();
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $c['host'], $c['port'], $c['name']);
    return new \PDO($dsn, $c['user'], $c['pass'],
      [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
  }

  /**
   * Get FusionPBX domain_uuid for a given ICTCore tenant_id.
   * Falls back to the first enabled domain if no mapping exists.
   */
  public static function get_domain_uuid($tenant_id = null)
  {
    if (!empty($tenant_id)) {
      $query = "SELECT fpbx_domain_uuid FROM tenant WHERE tenant_id = '$tenant_id'";
      $result = DB::query('tenant', $query);
      $row = mysqli_fetch_assoc($result);
      if (!empty($row['fpbx_domain_uuid'])) {
        return $row['fpbx_domain_uuid'];
      }
      // Tenant exists but has no FusionPBX domain — return null, do NOT fall back to
      // the first domain (which belongs to a different tenant).
      return null;
    }
    // No tenant_id = super-admin / CE context: fallback to first enabled domain.
    $pdo  = self::fpbx_db();
    $stmt = $pdo->query("SELECT domain_uuid FROM v_domains WHERE domain_enabled = true ORDER BY domain_name LIMIT 1");
    $uuid = $stmt->fetchColumn();
    if (empty($uuid)) {
      throw new CoreException('404', 'No FusionPBX domain configured. Please provision a PBX domain for this tenant.');
    }
    return $uuid;
  }

  /**
   * Get the domain_name (context) for a given domain_uuid.
   */
  public static function get_domain_name($domain_uuid)
  {
    $pdo  = self::fpbx_db();
    $stmt = $pdo->prepare("SELECT domain_name FROM v_domains WHERE domain_uuid = :uuid");
    $stmt->execute(['uuid' => $domain_uuid]);
    return $stmt->fetchColumn() ?: 'default';
  }

  /**
   * Reverse of get_domain_uuid(): which tenant owns this FusionPBX domain.
   *
   * Needed wherever an object's own tenant matters rather than the caller's --
   * an admin acting on a sub-tenant's record is not acting on their own tenant.
   *
   * @param string $domain_uuid
   * @return int|null null when the domain maps to no tenant
   */
  public static function get_tenant_id($domain_uuid)
  {
    if (empty($domain_uuid)) {
      return null;
    }
    $res = DB::query('tenant',
      "SELECT tenant_id FROM tenant WHERE fpbx_domain_uuid = '%uuid%' LIMIT 1",
      ['uuid' => $domain_uuid]);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return $row ? (int)$row['tenant_id'] : null;
  }

  /** Root of the ICTCore-generated FreeSWITCH directory tree. */
  const DIR_BASE = '/usr/ictcore/etc/freeswitch/directory';
  /**
   * The generated wrapper holding one <domain> block per tenant.
   *
   * Written inside ICTCore's own tree because /etc/freeswitch/directory is owned by
   * freeswitch:daemon and php-fpm cannot write there. FreeSWITCH reads it through
   * /etc/freeswitch/directory/fpbx_webrtc.xml, which is a symlink to this file -- the
   * same arrangement ictcore.xml already uses.
   */
  const DIR_WRAPPER = '/usr/ictcore/etc/freeswitch/directory/fpbx_webrtc.xml';

  /**
   * Per-tenant directory subdirectory for a domain, e.g. .../fpbx_extensions/acme.local.
   *
   * Extensions and voicemails are filed per domain so each tenant gets its own FreeSWITCH
   * <domain>. While every tenant shared one domain, two tenants could not both hold
   * extension 1001 -- they collided as duplicate <user id> entries and registration
   * resolved to whichever FreeSWITCH found first.
   *
   * @param string $group 'fpbx_extensions' or 'voicemails'
   */
  public static function directory_path($group, $domain_name)
  {
    $safe = preg_replace('/[^A-Za-z0-9._-]/', '', (string)$domain_name);
    return self::DIR_BASE . '/' . $group . '/' . ($safe !== '' ? $safe : 'default');
  }

  /**
   * Regenerate the directory wrapper with one <domain> block per FusionPBX domain.
   *
   * Called whenever an extension or voicemail is written, so a newly created tenant's
   * domain appears without anyone regenerating anything by hand. Rewriting the file also
   * gives FreeSWITCH the mtime change it needs to re-expand the include globs.
   */
  public static function write_directory_wrapper(): bool
  {
    try {
      $pdo  = self::fpbx_db();
      // DISTINCT: v_domains can hold several rows sharing a domain_name (repeated test
      // provisioning leaves them behind), and a repeated <domain> block is invalid.
      $rows = $pdo->query("SELECT DISTINCT domain_name FROM v_domains WHERE domain_name IS NOT NULL AND domain_name <> '' ORDER BY domain_name")
                  ->fetchAll(\PDO::FETCH_COLUMN);
    } catch (\Throwable $e) {
      Corelog::log('Directory wrapper: domain list failed: ' . $e->getMessage(), Corelog::WARNING);
      return false;
    }
    if (empty($rows)) {
      return false;
    }

    self::migrate_legacy_directory_files($pdo);

    $dial = '{presence_id=${dialed_user}@${dialed_domain}}${sofia_contact(*/${dialed_user}@${dialed_domain})}';
    $xml  = '<include>' . "\n";
    foreach ($rows as $domain_name) {
      $ext_dir = self::directory_path('fpbx_extensions', $domain_name);
      $vm_dir  = self::directory_path('voicemails', $domain_name);
      // The glob must resolve, so make sure both directories exist even when empty.
      foreach ([$ext_dir, $vm_dir] as $d) {
        if (!is_dir($d)) { @mkdir($d, 0755, true); }
      }
      $safe_name = htmlspecialchars((string)$domain_name, ENT_XML1, 'UTF-8');
      $xml .= '  <domain name="' . $safe_name . '">' . "\n"
            . '    <params>' . "\n"
            . '      <param name="dial-string" value="' . htmlspecialchars($dial, ENT_XML1, 'UTF-8') . '"/>' . "\n"
            . '    </params>' . "\n"
            . '    <groups>' . "\n"
            . '      <group name="default"><users>' . "\n"
            . '        <X-PRE-PROCESS cmd="include" data="' . $ext_dir . '/*.xml"/>' . "\n"
            . '      </users></group>' . "\n"
            . '      <group name="voicemails"><users>' . "\n"
            . '        <X-PRE-PROCESS cmd="include" data="' . $vm_dir . '/*.xml"/>' . "\n"
            . '      </users></group>' . "\n"
            . '    </groups>' . "\n"
            . '  </domain>' . "\n";
    }
    $xml .= '</include>' . "\n";

    if (@file_put_contents(self::DIR_WRAPPER, $xml) === false) {
      Corelog::log('Directory wrapper: write failed for ' . self::DIR_WRAPPER, Corelog::WARNING);
      return false;
    }
    return true;
  }

  /**
   * Move directory files left flat by the pre-isolation layout into their domain's
   * subdirectory, so an upgrade does not silently drop every extension out of the
   * directory until each one happens to be re-saved.
   *
   * A file whose row no longer exists is left where it is: it is already an orphan, and
   * the new globs simply stop serving it, which is the desired outcome.
   */
  private static function migrate_legacy_directory_files($pdo): void
  {
    $groups = [
      ['fpbx_extensions', 'v_extensions', 'extension_uuid'],
      ['voicemails',      'v_voicemails', 'voicemail_uuid'],
    ];
    foreach ($groups as [$group, $table, $pk]) {
      $flat = self::DIR_BASE . '/' . $group;
      if (!is_dir($flat)) {
        continue;
      }
      foreach ((array)glob($flat . '/*.xml') as $file) {
        $uuid = basename($file, '.xml');
        try {
          $stmt = $pdo->prepare(
            "SELECT d.domain_name FROM $table t
               JOIN v_domains d ON d.domain_uuid = t.domain_uuid
              WHERE t.$pk = :uuid LIMIT 1"
          );
          $stmt->execute(['uuid' => $uuid]);
          $domain_name = $stmt->fetchColumn();
        } catch (\Throwable $e) {
          continue; // not a uuid we own, or the lookup failed — leave it alone
        }
        if (empty($domain_name)) {
          continue;
        }
        $target_dir = self::directory_path($group, $domain_name);
        if (!is_dir($target_dir)) { @mkdir($target_dir, 0755, true); }
        @rename($file, $target_dir . '/' . basename($file));
      }
    }
  }

  /**
   * List all FusionPBX domains.
   */
  public static function list_domains()
  {
    $pdo = self::fpbx_db();
    return $pdo->query(
      "SELECT domain_uuid, domain_name, domain_enabled, domain_description FROM v_domains ORDER BY domain_name"
    )->fetchAll();
  }

  /**
   * Create a new FusionPBX domain and link it to an ICTCore tenant.
   * Returns the new domain_uuid.
   */
  public static function create_domain($tenant_id, $domain_name, $domain_description = '')
  {
    $pdo = self::fpbx_db();
    $domain_uuid = self::generate_uuid();
    $pdo->prepare(
      "INSERT INTO v_domains (domain_uuid, domain_name, domain_enabled, domain_description, insert_date)
       VALUES (:uuid, :name, true, :desc, NOW())"
    )->execute(['uuid' => $domain_uuid, 'name' => $domain_name, 'desc' => $domain_description]);

    // Link to ICTCore tenant
    DB::query('tenant',
      "UPDATE tenant SET fpbx_domain_uuid = '$domain_uuid' WHERE tenant_id = '$tenant_id'"
    );

    return $domain_uuid;
  }

  /**
   * Link an existing FusionPBX domain to an ICTCore tenant.
   */
  public static function link_domain($tenant_id, $domain_uuid)
  {
    DB::query('tenant',
      "UPDATE tenant SET fpbx_domain_uuid = '$domain_uuid' WHERE tenant_id = '$tenant_id'"
    );
    return true;
  }


  /**
   * Returns:  null  — admin context (no tenant filter; caller omits WHERE)
   *           uuid  — tenant's domain_uuid (use in WHERE domain_uuid = ?)
   *           false — tenant has no domain (caller returns empty result)
   */
  public static function get_domain_filter($tenant_id)
  {
    if ($tenant_id === null) return null;
    $uuid = self::get_domain_uuid($tenant_id);
    return ($uuid === null) ? false : $uuid;
  }
  /* domain-filter-v2 */

  /**
   * Check if an extension number is already in use by any resource type in a domain.
   * Returns the label of the conflicting resource (e.g. 'Ring Group') or null if free.
   * Pass $exclude_uuid to skip the current record on update.
   */
  /**
   * @param string      $domain_uuid
   * @param string      $extension
   * @param string|null $exclude_uuid the record being saved, so it never conflicts with itself
   * @param string      $scope 'number' for anything dialled as a bare number, 'voicemail'
   *                    for a mailbox id. Mailboxes live in their own number space: they are
   *                    reached at *99<id> (see Voicemail::sync_fs_dialplan), so a mailbox may
   *                    freely share a number with an extension -- that is the normal way to
   *                    give extension 1001 the mailbox 1001 -- and only collides with another
   *                    mailbox. Everything else shares the bare-number space.
   * @return string|null label of the colliding object type, or null when free
   */
  public static function extension_in_use($domain_uuid, $extension, $exclude_uuid = null, $scope = 'number'): ?string
  {
    if (empty($domain_uuid) || empty($extension)) return null;
    $pdo = self::fpbx_db();
    $checks = ($scope === 'voicemail') ? [
      ['v_voicemails',        'voicemail_uuid',          'voicemail_id',                'Voicemail'],
    ] : [
      ['v_extensions',        'extension_uuid',         'extension',                   'Extension'],
      ['v_ring_groups',       'ring_group_uuid',         'ring_group_extension',        'Ring Group'],
      ['v_call_center_queues','call_center_queue_uuid',  'queue_extension',             'Call Queue'],
      ['v_ivr_menus',         'ivr_menu_uuid',           'ivr_menu_extension',          'IVR Menu'],
      ['v_conference_centers','conference_center_uuid',  'conference_center_extension', 'Conference'],
      ['v_call_flows',        'call_flow_uuid',          'call_flow_extension',         'Call Flow'],
    ];
    foreach ($checks as [$table, $pk, $field, $label]) {
      $sql    = "SELECT COUNT(*) FROM $table WHERE domain_uuid = ? AND $field = ?";
      $params = [$domain_uuid, (string)$extension];
      if ($exclude_uuid) {
        $sql     .= " AND $pk != ?";
        $params[] = $exclude_uuid;
      }
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      if ((int)$stmt->fetchColumn() > 0) return $label;
    }
    return null;
  }

  /**
   * Given an array of domain_uuids, return map: domain_uuid => tenant company name
   */
  public static function get_tenant_names_by_domain_uuids(array $uuids): array
  {
    if (empty($uuids)) return [];
    $map = [];
    foreach (array_unique(array_filter($uuids)) as $uuid) {
      $res = DB::query('tenant',
        "SELECT company FROM tenant WHERE fpbx_domain_uuid = '%uuid%'",
        ['uuid' => $uuid]);
      $row = mysqli_fetch_assoc($res);
      $map[$uuid] = $row ? $row['company'] : null;
    }
    return $map;
  }

  private static function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  }
}
