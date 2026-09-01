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
  private static $fs_domain_cache = null;

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
   * The domain name FreeSWITCH's directory actually declares.
   *
   * Every extension, whatever its FusionPBX domain, is included into the single
   * <domain name="..."> wrapper in fpbx_webrtc.xml. So `user/<ext>@<fusionpbx domain>`
   * only resolves for the tenant whose FusionPBX domain happens to match that wrapper
   * (normally tenant 1); for every sub-tenant it is a dead endpoint. Anything that
   * builds a `user/...@...` dial string must use this name, not get_domain_name().
   *
   * Read from the wrapper itself so it tracks the installer, falling back to
   * ictcore.conf and finally to whatever the caller already resolved.
   *
   * @param string|null $fallback used when the wrapper cannot be read
   * @return string|null
   */
  public static function fs_directory_domain($fallback = null)
  {
    if (self::$fs_domain_cache === null) {
      self::$fs_domain_cache = ''; // '' = looked and found nothing usable
      $file = '/etc/freeswitch/directory/fpbx_webrtc.xml';
      if (is_readable($file)) {
        $xml = @file_get_contents($file);
        if ($xml !== false && preg_match('/<domain\s+name\s*=\s*"([^"]+)"/i', $xml, $m)) {
          $name = trim($m[1]);
          // Skip unexpanded FreeSWITCH variables such as $${local_ip_v4}
          if ($name !== '' && strpos($name, '$') === false) {
            self::$fs_domain_cache = $name;
          }
        }
      }
      if (self::$fs_domain_cache === '' && is_readable(self::$conf_file)) {
        $ini = @parse_ini_file(self::$conf_file, true);
        foreach ([['domain', 'name'], ['website', 'host']] as [$section, $key]) {
          if (!empty($ini[$section][$key])) {
            $name = trim($ini[$section][$key]);
            if ($name !== '' && strpos($name, '$') === false) {
              self::$fs_domain_cache = $name;
              break;
            }
          }
        }
      }
    }

    return self::$fs_domain_cache !== '' ? self::$fs_domain_cache : $fallback;
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
  public static function extension_in_use($domain_uuid, $extension, $exclude_uuid = null): ?string
  {
    if (empty($domain_uuid) || empty($extension)) return null;
    $pdo = self::fpbx_db();
    $checks = [
      ['v_extensions',        'extension_uuid',         'extension',                   'Extension'],
      ['v_ring_groups',       'ring_group_uuid',         'ring_group_extension',        'Ring Group'],
      ['v_call_center_queues','call_center_queue_uuid',  'queue_extension',             'Call Queue'],
      ['v_ivr_menus',         'ivr_menu_uuid',           'ivr_menu_extension',          'IVR Menu'],
      ['v_conference_centers','conference_center_uuid',  'conference_center_extension', 'Conference'],
      ['v_voicemails',        'voicemail_uuid',          'voicemail_id',                'Voicemail'],
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
