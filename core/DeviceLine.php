<?php

namespace ICT\Core;

class DeviceLine
{
  public $device_line_uuid         = null;
  public $device_uuid              = null;
  public $domain_uuid              = null;
  public $extension_uuid           = null; // helper — not in v_device_lines; used to auto-fill SIP fields
  public $line_number              = '1';
  public $server_address           = null;
  public $server_address_primary   = null;
  public $server_address_secondary = null;
  public $user_id                  = null;
  public $auth_id                  = null;
  public $password                 = null;
  public $display_name             = null;
  public $sip_port                 = '5060';
  public $sip_transport            = 'udp';
  public $register_expires         = '3600';
  public $enabled                  = true;

  public function __construct($device_line_uuid = null)
  {
    if (!empty($device_line_uuid)) {
      $this->device_line_uuid = $device_line_uuid;
      $this->load();
    }
  }

  private function load()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_device_lines WHERE device_line_uuid = :uuid");
    $stmt->execute(['uuid' => $this->device_line_uuid]);
    $row = $stmt->fetch();
    if (!$row) {
      throw new CoreException('404', 'Device line not found');
    }
    foreach ($row as $k => $v) {
      if (property_exists($this, $k)) {
        $this->$k = $v;
      }
    }
  }

  public static function search($aFilter = array())
  {
    $device_uuid = $aFilter['device_uuid'] ?? null;
    $pdo         = FpbxDomain::fpbx_db();
    $where       = $device_uuid ? 'WHERE device_uuid = :device_uuid' : '';
    $params      = $device_uuid ? ['device_uuid' => $device_uuid] : [];
    $stmt        = $pdo->prepare(
      "SELECT device_line_uuid, device_uuid, domain_uuid, line_number,
              server_address, user_id, auth_id, display_name,
              sip_port, sip_transport, register_expires, enabled
       FROM v_device_lines $where
       ORDER BY CAST(line_number AS INTEGER) ASC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function save()
  {
    $pdo = FpbxDomain::fpbx_db();

    if (empty($this->domain_uuid)) {
      $this->domain_uuid = FpbxDomain::get_domain_uuid(null);
      // fall back to device's domain_uuid
      if (empty($this->domain_uuid) && !empty($this->device_uuid)) {
        $s = $pdo->prepare("SELECT domain_uuid FROM v_devices WHERE device_uuid = :uuid");
        $s->execute(['uuid' => $this->device_uuid]);
        $this->domain_uuid = $s->fetchColumn();
      }
    }

    // Auto-fill SIP credentials from extension
    if (!empty($this->extension_uuid)) {
      $s = $pdo->prepare(
        "SELECT extension, password, effective_caller_id_name FROM v_extensions WHERE extension_uuid = :uuid"
      );
      $s->execute(['uuid' => $this->extension_uuid]);
      $ext = $s->fetch();
      if ($ext) {
        $this->user_id      = $ext['extension'];
        $this->auth_id      = $ext['extension'];
        $this->password     = $ext['password'];
        if (empty($this->display_name)) {
          $this->display_name = $ext['effective_caller_id_name'];
        }
      }
    }

    // Default server_address_primary to server_address if not set
    if (empty($this->server_address_primary)) {
      $this->server_address_primary = $this->server_address;
    }

    $fields = [
      'device_uuid', 'domain_uuid', 'line_number',
      'server_address', 'server_address_primary', 'server_address_secondary',
      'user_id', 'auth_id', 'password', 'display_name',
      'sip_port', 'sip_transport', 'register_expires', 'enabled',
    ];

    if (empty($this->device_line_uuid)) {
      $this->device_line_uuid = $this->generate_uuid();
      $all  = array_merge(['device_line_uuid'], $fields);
      $cols = implode(', ', $all);
      $vals = implode(', ', array_map(fn($f) => ':' . $f, $all));
      $stmt = $pdo->prepare("INSERT INTO v_device_lines ($cols) VALUES ($vals)");
    } else {
      $sets = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
      $stmt = $pdo->prepare("UPDATE v_device_lines SET $sets WHERE device_line_uuid = :device_line_uuid");
    }

    $params = ['device_line_uuid' => $this->device_line_uuid];
    foreach ($fields as $f) {
      $v = $this->$f;
      if ($f === 'enabled') {
        $v = ($v === true || $v === 'true' || $v === 1 || $v === '1') ? 'true' : 'false';
      }
      $params[$f] = $v;
    }

    try {
      $stmt->execute($params);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Device line save failed: ' . $e->getMessage());
    }
    return $this->device_line_uuid;
  }

  public function delete()
  {
    $pdo = FpbxDomain::fpbx_db();
    $pdo->prepare("DELETE FROM v_device_lines WHERE device_line_uuid = :uuid")
        ->execute(['uuid' => $this->device_line_uuid]);
    return true;
  }

  public function get_id() { return $this->device_line_uuid; }

  private function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  }

  public function __get($f)     { return property_exists($this, $f) ? $this->$f : null; }
  public function __set($f, $v) { if (property_exists($this, $f)) $this->$f = $v; }
  public function __isset($f)   { return isset($this->$f); }
}
