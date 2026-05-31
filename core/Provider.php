<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\freeSwitchEsl;
use ICT\Core\Gateway\Freeswitch;
use ICT\Core\FpbxDomain;
use PDO;
use PDOException;

class Provider
{

  /** @const */
  private static $table = 'provider';
  private static $primary_key = 'provider_id';
  private static $fields = array(
      'provider_id',
      'tenant_id',
      'fpbx_gateway_uuid',
      'name',
      'service_flag',
      'node_id',
      'host',
      'port',
      'username',
      'password',
      'dialstring',
      'prefix',
      'settings',
      'register',
      'weight',
      'type',
      'active',
      'encryption',
      'from_email',
      'description',
      // FusionPBX gateway parity
      'realm',
      'from_user',
      'from_domain',
      'proxy',
      'register_proxy',
      'outbound_proxy',
      'expire_seconds',
      'retry_seconds',
      'register_transport',
      'auth_username',
      'contact_params',
      'extension',
      'distinct_to',
      'caller_id_in_from',
      'supress_cng',
      'sip_cid_type',
      'codec_prefs',
      'extension_in_contact',
      'ping',
      'ping_min',
      'ping_max',
      'contact_in_ping',
      'channels',
      'hostname',
      'context',
      'profile',
      'enabled'
  );
  private static $read_only = array(
      'provider_id',
      'type'
  );

  /** @var integer */
  public $provider_id = NULL;
  /** @var integer */
  public $tenant_id = NULL;
  /** @var string */
  public $fpbx_gateway_uuid = NULL;
  /** @var string */
  public $name = NULL;
  /** @var string */
  public $type = 'provider';
  /** @var integer */
  public $service_flag = NULL;
  /** @var integer */
  public $node_id = NULL;
  /** @var string */
  public $host = NULL;
  /** @var string */
  public $port = NULL;
  /** @var string */
  public $from_email = NULL;
  /** @var string */
  public $encryption = NULL;
  /** @var string */
  public $username = NULL;
  /** @var string */
  public $password = NULL;
  /** @var string */
  public $dialstring = NULL;
  /** @var string */
  public $prefix = NULL;
  /** @var string */
  public $settings = NULL;
  /** @var integer */
  public $register = 0;
  /** @var integer */
  public $weight = NULL;
  /** @var integer */
  public $active = NULL;
  /** @var string */
  public $httpkey = NULL;
  /** @var string */
  public $description = NULL;

  // FusionPBX gateway parity columns
  public $realm = NULL;
  public $from_user = NULL;
  public $from_domain = NULL;
  public $proxy = NULL;
  public $register_proxy = NULL;
  public $outbound_proxy = NULL;
  public $expire_seconds = NULL;
  public $retry_seconds = NULL;
  public $register_transport = NULL;
  public $auth_username = NULL;
  public $contact_params = NULL;
  public $extension = NULL;
  public $distinct_to = NULL;
  public $caller_id_in_from = NULL;
  public $supress_cng = NULL;
  public $sip_cid_type = NULL;
  public $codec_prefs = NULL;
  public $extension_in_contact = NULL;
  public $ping = NULL;
  public $ping_min = NULL;
  public $ping_max = NULL;
  public $contact_in_ping = NULL;
  public $channels = NULL;
  public $hostname = NULL;
  public $context = NULL;
  public $profile = NULL;
  public $enabled = NULL;

  public function __construct($provider_id = NULL)
  {
    if (!empty($provider_id)) {
      $this->provider_id = $provider_id;
      $this->_load();
    }
  }

  public static function search($aFilter = array())
  {
    $aProvider = array();
    $from_str = self::$table;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'provider_id':
        case 'name':
        case 'node_id':
        case 'tenant_id':
        case 'host':
        case 'type':
        case 'active':
          $aWhere[] = "$search_field = '$search_value'";
          break;
        case 'service_flag':
          $aWhere[] = "($search_field & $search_value) = $search_value";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }
    $query = "SELECT provider_id, tenant_id, name, host, service_flag, node_id, type, httpkey, from_email, encryption, active FROM " . $from_str;
    Corelog::log("provider search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aProvider[] = $data;
    }
    return $aProvider;
  }

  public static function getProvider($provider_ids = array())
  {
    if(is_array($provider_ids)) {
      $aProvider = array();
      $from_str = self::$table . ' WHERE active = 1 ';
      $aWhere = array();
      foreach ($provider_ids as $search_value) {
        $aWhere[] = "provider_id = $search_value";
      }
      if (!empty($aWhere)) {
        $from_str .= ' AND (' . implode(' OR ', $aWhere) . ')'; 
      }

      $query = "SELECT provider_id FROM " . $from_str;
      $query .= ' ORDER BY weight DESC';
      Corelog::log("provider search with $query", Corelog::DEBUG, array('aFilter' => $provider_ids));
      $result = DB::query(self::$table, $query);
      while ($data = mysqli_fetch_assoc($result)) {
        $aProvider[] = $data;
      }
      $aProvider = array_shift($aProvider);
      return $aProvider;
    }
    return false;
  }

  public static function getClass(&$provider_id, $namespace = 'ICT\\Core\\Provider')
  {
    if (ctype_digit(trim($provider_id))) {
      $query = "SELECT type FROM " . self::$table . " WHERE provider_id='%provider_id%' ";
      $result = DB::query(self::$table, $query, array('provider_id' => $provider_id));
      if ($result instanceof \mysqli_result) {
        while($row = mysqli_fetch_assoc($result)) {
          $provider_type = $row['type'];
        }
      }
    } else {
      $provider_type = $provider_id;
      $provider_id   = null;
    }
    $class_name = ucfirst(strtolower(trim($provider_type)));
    if (!empty($namespace)) {
      $class_name = $namespace . '\\' . $class_name;
    }
    if (class_exists($class_name, true)) {
      return $class_name;
    } else {
      return false;
    }
  }

  public static function load($provider_id)
  {
    $class_name = self::getClass($provider_id);
    if ($class_name) {
      Corelog::log("Creating instance of : $class_name for provider: $provider_id", Corelog::CRUD);
      return new $class_name($provider_id);
    } else {
      Corelog::log("$class_name class not found, Creating instance of : Provider", Corelog::CRUD);
      return new self($provider_id);
    }
  }

  private function _load()
  {
    Corelog::log("Loading provider: $this->provider_id", Corelog::CRUD);
    $query = "SELECT * FROM " . self::$table . " WHERE provider_id='%provider_id%' ";
    $result = DB::query(self::$table, $query, array('provider_id' => $this->provider_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      foreach (self::$fields as $f) {
        if (array_key_exists($f, $data)) {
          $this->$f = $data[$f];
        }
      }
      // legacy / non-list fields
      if (array_key_exists('httpkey', $data)) {
        $this->httpkey = $data['httpkey'];
      }
      Corelog::log("Provider loaded name: $this->name", Corelog::CRUD);
    } else {
      throw new CoreException('404', 'Provider not found');
    }
  }

  public function delete()
  {
    Corelog::log("Provider delete", Corelog::CRUD);
    // Guard: block if any routes use this provider
    $res = DB::query('route',
      "SELECT name FROM route WHERE provider_id = %pid%",
      ['pid' => $this->provider_id]);
    $names = [];
    while ($row = mysqli_fetch_assoc($res)) { $names[] = $row['name']; }
    if (!empty($names)) {
      throw new CoreException(409,
        "Cannot delete provider: Route(s) '" . implode("', '", $names) . "' use this provider. Delete those routes first.");
    }
    // Remove the projected FusionPBX gateway row first (best-effort).
    try {
      $this->unpublish_from_fusionpbx();
    } catch (\Throwable $e) {
      Corelog::log("FusionPBX unpublish failed during delete: " . $e->getMessage(), Corelog::ERROR);
    }
    return DB::delete(self::$table, 'provider_id', $this->provider_id);
  }

  public function __isset($field)
  {
    $method_name = 'isset_' . $field;
    if (method_exists($this, $method_name)) {
      return $this->$method_name();
    } else {
      return isset($this->$field);
    }
  }

  public function __get($field)
  {
    $method_name = 'get_' . $field;
    if (method_exists($this, $method_name)) {
      return $this->$method_name();
    } else if (!empty($field) && isset($this->$field)) {
      return $this->$field;
    }
    return NULL;
  }

  public function __set($field, $value)
  {
    $method_name = 'set_' . $field;
    if (method_exists($this, $method_name)) {
      $this->$method_name($value);
    } else if (empty($field) || in_array($field, self::$read_only)) {
      return;
    } else {
      $this->$field = $value;
    }
  }

  public function get_id()
  {
    return $this->provider_id;
  }

  public function save()
  {
    $data = array();
    foreach (self::$fields as $f) {
      $data[$f] = $this->$f;
    }
    // Always carry httpkey (legacy column, not in $fields by design)
    $data['httpkey'] = $this->httpkey;

    if (isset($data['provider_id']) && !empty($data['provider_id'])) {
      // update existing record
      unset($data['type']); // don't allow to change type
      $result = DB::update(self::$table, $data, 'provider_id');
      Corelog::log("Provider updated: $this->provider_id", Corelog::CRUD);
    } else {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->provider_id = $data['provider_id'];
      Corelog::log("New Provider created: $this->provider_id", Corelog::CRUD);
    }

    // Project to FusionPBX v_gateways (best-effort; doesn't fail the save).
    try {
      $this->publish_to_fusionpbx();
    } catch (\Throwable $e) {
      Corelog::log("FusionPBX publish failed: " . $e->getMessage(), Corelog::ERROR);
    }

    return $result;
  }

  /**
   * Upsert the matching v_gateways row in FusionPBX so the PBX side
   * carries the same trunk definition as ICTCore. The ICTCore provider
   * row is the source of truth; the FusionPBX row is a derived projection.
   *
   * Codec list is driven by service_flag bit 2 (fax support):
   *   - fax_support  => PCMU,PCMA,T38
   *   - voice only   => PCMU,PCMA,OPUS,G722  (unless an explicit codec_prefs is set)
   */
  public function publish_to_fusionpbx()
  {
    // Only SIP providers project to FusionPBX; SMTP/HTTP don't.
    if (!empty($this->type) && $this->type !== 'sip' && $this->type !== 'provider') {
      return false;
    }

    if (!class_exists('\\ICT\\Core\\FpbxDomain')) {
      Corelog::log("FpbxDomain helper missing; skipping FusionPBX publish", Corelog::WARNING);
      return false;
    }

    $domain_uuid = FpbxDomain::get_domain_uuid($this->tenant_id);
    if (empty($domain_uuid)) {
      Corelog::log("No FusionPBX domain for tenant {$this->tenant_id}; skipping publish", Corelog::WARNING);
      return false;
    }

    // Compute effective codec list.
    $codec_prefs = $this->codec_prefs;
    $fax_support = ((int)$this->service_flag & 2) > 0;
    if (empty($codec_prefs)) {
      $codec_prefs = $fax_support ? 'PCMU,PCMA,T38' : 'PCMU,PCMA,OPUS,G722';
    }

    // Map ICTCore Provider fields into v_gateways columns.
    $row = array(
      'domain_uuid'          => $domain_uuid,
      'gateway'              => $this->name,
      'username'             => $this->username,
      'password'             => $this->password,
      'distinct_to'          => self::pgbool($this->distinct_to, false),
      'auth_username'        => $this->auth_username,
      'realm'                => $this->realm,
      'from_user'            => $this->from_user,
      'from_domain'          => $this->from_domain,
      'proxy'                => !empty($this->proxy) ? $this->proxy : $this->host,
      'register_proxy'       => $this->register_proxy,
      'outbound_proxy'       => $this->outbound_proxy,
      'expire_seconds'       => is_numeric($this->expire_seconds) ? $this->expire_seconds : 800,
      'register'             => self::pgbool($this->register, true),
      'register_transport'   => !empty($this->register_transport) ? $this->register_transport : 'udp',
      'contact_params'       => $this->contact_params,
      'retry_seconds'        => is_numeric($this->retry_seconds) ? $this->retry_seconds : 30,
      'extension'            => $this->extension,
      'ping'                 => $this->ping,
      'ping_min'             => $this->ping_min,
      'ping_max'             => $this->ping_max,
      'contact_in_ping'      => self::pgbool($this->contact_in_ping, false),
      'caller_id_in_from'    => self::pgbool($this->caller_id_in_from, false),
      'supress_cng'          => self::pgbool($this->supress_cng, false),
      'sip_cid_type'         => !empty($this->sip_cid_type) ? $this->sip_cid_type : 'none',
      'codec_prefs'          => $codec_prefs,
      'channels'             => is_numeric($this->channels) ? $this->channels : null,
      'extension_in_contact' => self::pgbool($this->extension_in_contact, false),
      'context'              => !empty($this->context) ? $this->context : 'ictcore',
      'profile'              => !empty($this->profile) ? $this->profile : 'external',
      'hostname'             => $this->hostname,
      'enabled'              => self::pgbool($this->enabled, ((int)$this->active === 1)),
      'description'          => $this->description ?: ('Trunk: ' . $this->name),
    );

    $pdo = FpbxDomain::fpbx_db();

    if (empty($this->fpbx_gateway_uuid)) {
      $gw_uuid = self::generate_uuid_v4();
      $row['gateway_uuid'] = $gw_uuid;
      $row['insert_date'] = gmdate('Y-m-d H:i:s');

      $cols = array_keys($row);
      $placeholders = array_map(function($c) { return ':' . $c; }, $cols);
      $sql = 'INSERT INTO v_gateways (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
      $ins = $pdo->prepare($sql);
      $ins->execute($row);

      // Persist link back to ictcore so subsequent saves update in place.
      // Use raw SQL — DB::update needs a Session context which is absent in CLI.
      $this->fpbx_gateway_uuid = $gw_uuid;
      DB::query(self::$table,
        "UPDATE " . self::$table . " SET fpbx_gateway_uuid='%uuid%' WHERE provider_id='%pid%'",
        array('uuid' => $gw_uuid, 'pid' => $this->provider_id));
      Corelog::log("Provider {$this->provider_id} projected to v_gateways {$gw_uuid}", Corelog::CRUD);
    } else {
      $sets = array();
      foreach (array_keys($row) as $c) {
        $sets[] = "$c = :$c";
      }
      $row['gateway_uuid'] = $this->fpbx_gateway_uuid;
      $row['update_date'] = gmdate('Y-m-d H:i:s');
      $sets[] = 'update_date = :update_date';
      $sql = 'UPDATE v_gateways SET ' . implode(', ', $sets) . ' WHERE gateway_uuid = :gateway_uuid';
      $upd = $pdo->prepare($sql);
      $upd->execute($row);
      Corelog::log("Provider {$this->provider_id} v_gateways row updated", Corelog::CRUD);
    }

    $this->sync_fs_xml();
    return true;
  }

  public function unpublish_from_fusionpbx()
  {
    if (empty($this->fpbx_gateway_uuid) || !class_exists('\\ICT\\Core\\FpbxDomain')) {
      return false;
    }
    $pdo = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare('DELETE FROM v_gateways WHERE gateway_uuid = :uuid');
    $stmt->execute(['uuid' => $this->fpbx_gateway_uuid]);
    Corelog::log("v_gateways row {$this->fpbx_gateway_uuid} removed", Corelog::CRUD);
    $this->sync_fs_xml(true);
    return true;
  }

  private function sync_fs_xml($delete = false)
  {
    $dir  = '/usr/ictcore/etc/freeswitch/sip_profiles/provider';
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->name);
    $file = $dir . '/' . $safe . '.xml';

    if ($delete) {
      if (file_exists($file)) {
        @unlink($file);
        Corelog::log("Gateway XML removed: $file", Corelog::CRUD);
      }
      // Unregister the live gateway so FreeSWITCH drops its REGISTER session
      // immediately; rescan alone leaves a stale registered gateway behind.
      try {
        if (class_exists('\\ICT\\Core\\Realtime')) {
          \ICT\Core\Realtime::run_cmd('sofia profile ictcore killgw ' . $safe);
        }
      } catch (\Throwable $e) {
        Corelog::log("sofia killgw failed: " . $e->getMessage(), Corelog::WARNING);
      }
    } else {
      $register    = ((int)$this->register === 1 || $this->register === 'true') ? 'true' : 'false';
      $proxy       = !empty($this->proxy)       ? $this->proxy       : $this->host;
      $realm       = !empty($this->realm)       ? $this->realm       : $this->host;
      $from_user   = !empty($this->from_user)   ? $this->from_user   : $this->username;
      $from_domain = !empty($this->from_domain) ? $this->from_domain : $this->host;
      $transport   = !empty($this->register_transport) ? $this->register_transport : 'udp';
      $expire      = is_numeric($this->expire_seconds) ? (int)$this->expire_seconds : 800;
      $retry       = is_numeric($this->retry_seconds)  ? (int)$this->retry_seconds  : 30;
      $fax_support = ((int)$this->service_flag & 2) > 0;
      $codecs      = !empty($this->codec_prefs) ? $this->codec_prefs
                       : ($fax_support ? 'PCMU,PCMA,T38' : 'PCMU,PCMA,OPUS,G722');

      $e = function($s) { return htmlspecialchars((string)$s, ENT_XML1, 'UTF-8'); };
      $xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
      $xml .= '<include>' . "\n";
      $xml .= '  <gateway name="' . $e($this->name) . '">' . "\n";
      $xml .= '    <param name="username"           value="' . $e($this->username) . '"/>' . "\n";
      $xml .= '    <param name="realm"              value="' . $e($realm)          . '"/>' . "\n";
      $xml .= '    <param name="from-user"          value="' . $e($from_user)      . '"/>' . "\n";
      $xml .= '    <param name="from-domain"        value="' . $e($from_domain)    . '"/>' . "\n";
      $xml .= '    <param name="password"           value="' . $e($this->password) . '"/>' . "\n";
      $xml .= '    <param name="proxy"              value="' . $e($proxy)          . '"/>' . "\n";
      $xml .= '    <param name="register"           value="' . $register           . '"/>' . "\n";
      $xml .= '    <param name="register-transport" value="' . $e($transport)      . '"/>' . "\n";
      $xml .= '    <param name="expire-seconds"     value="' . $expire             . '"/>' . "\n";
      $xml .= '    <param name="retry-seconds"      value="' . $retry              . '"/>' . "\n";
      $xml .= '    <param name="codec-prefs"        value="' . $e($codecs)         . '"/>' . "\n";
      $context_val = !empty($this->context) ? $this->context : 'ictcore';
      $xml .= '    <param name="context"          value="' . $e($context_val)    . '"/>' . "\n";
      $xml .= '  </gateway>' . "\n";
      $xml .= '</include>' . "\n";

      if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
      }
      file_put_contents($file, $xml);
      Corelog::log("Gateway XML written: $file", Corelog::CRUD);
    }

    try {
      if (class_exists('\\ICT\\Core\\Realtime')) {
        \ICT\Core\Realtime::run_cmd('sofia profile ictcore rescan');
      }
    } catch (\Throwable $e) {
      Corelog::log("sofia rescan failed: " . $e->getMessage(), Corelog::WARNING);
    }
  }


  private static function generate_uuid_v4()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
      mt_rand(0,0x0fff) | 0x4000, mt_rand(0,0x3fff) | 0x8000,
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
  }

  private static function pgbool($v, $default = false)
  {
    if ($v === null || $v === '') return $default ? 'true' : 'false';
    if ($v === true || $v === 1 || $v === '1') return 'true';
    if ($v === false || $v === 0 || $v === '0') return 'false';
    $s = strtolower((string)$v);
    return ($s === 'true' || $s === 'yes' || $s === 'on') ? 'true' : 'false';
  }

  public function status()
  {
    return $this->_status($this->name);
  }

  public static function _status($provider_name)
  {
    return false; // not supported here, see sub-classes
  }
}
