<?php

namespace ICT\Core;

use ICT\Core\Contact;
use PDO;

class Gateway
{
  public $gateway_uuid        = null;
  public $domain_uuid         = null;
  public $tenant_id           = null;
  public $gateway             = null;  // gateway name
  public $username            = null;
  public $password            = null;
  public $realm               = null;
  public $from_user           = null;
  public $from_domain         = null;
  public $proxy               = null;
  public $outbound_proxy      = null;
  public $expire_seconds      = 3600;
  public $register            = true;
  public $register_transport  = 'udp';
  public $retry_seconds       = 30;
  public $extension           = null;
  public $ping                = null;
  public $ping_min            = null;
  public $ping_max            = null;
  public $caller_id_in_from   = false;
  public $codec_prefs         = null;
  public $context             = 'public';
  public $profile             = 'external';
  public $enabled             = true;
  public $description         = null;

  public function __construct($gateway_uuid = null)
  {
    if (!empty($gateway_uuid)) {
      $this->gateway_uuid = $gateway_uuid;
      $this->_load_by_uuid();
    }
  }

  private function _load_by_uuid()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_gateways WHERE gateway_uuid = :uuid");
    $stmt->execute(['uuid' => $this->gateway_uuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      throw new CoreException(404, 'Gateway not found');
    }
    foreach ($row as $k => $v) {
      if (property_exists($this, $k)) $this->$k = $v;
    }
  }

  public static function load($gateway_flag)
  {
    switch ((int)$gateway_flag) {
      case Gateway\Kannel::GATEWAY_FLAG:
        return new Gateway\Kannel();
      case Gateway\Sendmail::GATEWAY_FLAG:
        return new Gateway\Sendmail();
      case Gateway\Signalwire::GATEWAY_FLAG:
        return new Gateway\Signalwire();
      case Gateway\Freeswitch::GATEWAY_FLAG:
      default:
        return new Gateway\Freeswitch();
    }
  }

  public static function search($aFilter = [])
  {
    $tenant_id   = $aFilter['tenant_id'] ?? null;
    $domain_uuid = FpbxDomain::get_domain_uuid($tenant_id);
    $pdo         = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT gateway_uuid, gateway, username, realm, proxy,
              register, register_transport, profile, enabled, description
       FROM v_gateways
       WHERE domain_uuid = :domain_uuid
       ORDER BY gateway ASC"
    );
    $stmt->execute(['domain_uuid' => $domain_uuid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function save()
  {
    $pdo = FpbxDomain::fpbx_db();
    if (empty($this->domain_uuid)) {
      $this->domain_uuid = FpbxDomain::get_domain_uuid($this->tenant_id);
    }

    $bool_fields = ['register', 'caller_id_in_from', 'enabled'];
    $fields = [
      'gateway', 'username', 'password', 'realm', 'from_user', 'from_domain',
      'proxy', 'outbound_proxy', 'expire_seconds', 'register',
      'register_transport', 'retry_seconds', 'extension',
      'ping', 'ping_min', 'ping_max', 'caller_id_in_from',
      'codec_prefs', 'context', 'profile', 'enabled', 'description',
    ];

    $params = [];
    foreach ($fields as $f) {
      $v = $this->$f;
      if (in_array($f, $bool_fields)) {
        $v = ($v === true || $v === 'true' || $v === 1 || $v === '1') ? 'true' : 'false';
      }
      $params[$f] = $v;
    }

    try {
      if (empty($this->gateway_uuid)) {
        $this->gateway_uuid = $this->generate_uuid();
        $params['domain_uuid']  = $this->domain_uuid;
        $params['gateway_uuid'] = $this->gateway_uuid;
        $all  = array_keys($params);
        $cols = implode(', ', $all);
        $vals = implode(', ', array_map(fn($f) => ':' . $f, $all));
        $pdo->prepare("INSERT INTO v_gateways ($cols) VALUES ($vals)")->execute($params);
      } else {
        $params['gateway_uuid'] = $this->gateway_uuid;
        $sets = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
        $pdo->prepare("UPDATE v_gateways SET $sets WHERE gateway_uuid = :gateway_uuid")->execute($params);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Gateway save failed: ' . $e->getMessage());
    }
    return $this->gateway_uuid;
  }

  public function delete()
  {
    $pdo = FpbxDomain::fpbx_db();
    $pdo->prepare("DELETE FROM v_gateways WHERE gateway_uuid = :uuid")
        ->execute(['uuid' => $this->gateway_uuid]);
    return true;
  }

  public function get_id() { return $this->gateway_uuid; }

  private function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
      mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
  }

  public function __get($f)     { return property_exists($this,$f) ? $this->$f : null; }
  public function __set($f,$v)  { if (property_exists($this,$f)) $this->$f = $v; }
  public function __isset($f)   { return isset($this->$f); }
  public static function locate_contact($contact)
  {
    $field = defined('static::CONTACT_FIELD') ? static::CONTACT_FIELD : 'phone';
    return Contact::locate($contact, $field);
  }

  public static function template_dir()
  {
    global $path_template;
    return $path_template;
  }

}
