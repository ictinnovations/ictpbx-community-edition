<?php

namespace ICT\Core;

class Device
{
  public $device_uuid               = null;
  public $domain_uuid               = null;
  public $tenant_id                 = null;
  public $device_address            = null; // MAC address
  public $device_label              = null;
  public $device_vendor             = null;
  public $device_model              = null;
  public $device_template           = null;
  public $device_firmware_version   = null;
  public $device_location           = null;
  public $device_username           = null;
  public $device_password           = null;
  public $device_description        = null;
  public $device_enabled            = true;
  public $device_profile_uuid       = null;
  public $device_serial_number      = null;

  public function __construct($device_uuid = null)
  {
    if (!empty($device_uuid)) {
      $this->device_uuid = $device_uuid;
      $this->load();
    }
  }

  private function load()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_devices WHERE device_uuid = :uuid");
    $stmt->execute(['uuid' => $this->device_uuid]);
    $row = $stmt->fetch();
    if (!$row) {
      throw new CoreException('404', 'Device not found');
    }
    foreach ($row as $k => $v) {
      if (property_exists($this, $k)) {
        $this->$k = $v;
      }
    }
  }

  public static function search($aFilter = array())
  {
    $tenant_id     = $aFilter['tenant_id'] ?? null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter] : [];
    $where  = $domain_filter ? 'WHERE d.domain_uuid = :domain_uuid' : '';
    $pdo    = FpbxDomain::fpbx_db();
    $stmt   = $pdo->prepare(
      "SELECT d.device_uuid, d.device_address, d.device_label, d.device_vendor,
              d.device_model, d.device_template, d.device_location,
              d.device_enabled, d.device_description,
              d.device_profile_uuid, d.device_serial_number,
              p.device_profile_name
       FROM v_devices d
       LEFT JOIN v_device_profiles p ON p.device_profile_uuid = d.device_profile_uuid
       " . $where . " /* sql-where-v2 */
       ORDER BY d.device_label ASC, d.device_address ASC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function save()
  {
    $pdo = FpbxDomain::fpbx_db();
    if (empty($this->domain_uuid)) {
      $this->domain_uuid = FpbxDomain::get_domain_uuid($this->tenant_id);
      if ($this->domain_uuid === null) { /* null-domain-guard */
        throw new \ICT\Core\CoreException(409, 'No FusionPBX domain assigned to this tenant. Contact an administrator.');
      }
    }

    if (!empty($this->device_address)) {
      // Normalize MAC: strip separators, lowercase — provision/index.php requires this format
      $this->device_address = strtolower(preg_replace('/[^a-fA-F0-9]/', '', $this->device_address));
      $macCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM v_devices WHERE device_address = ? AND device_uuid != ?"
      );
      $macCheck->execute([$this->device_address, $this->device_uuid ?? '00000000-0000-0000-0000-000000000000']);
      if ((int)$macCheck->fetchColumn() > 0) {
        throw new CoreException(409, "A device with MAC address {$this->device_address} already exists.");
      }
    }

    $fields = [
      'device_address', 'device_label', 'device_vendor', 'device_model',
      'device_template', 'device_firmware_version', 'device_location',
      'device_username', 'device_password', 'device_description', 'device_enabled',
      'device_profile_uuid', 'device_serial_number',
    ];

    if (empty($this->device_uuid)) {
      $this->device_uuid = $this->generate_uuid();
      $all  = array_merge(['domain_uuid', 'device_uuid'], $fields);
      $cols = implode(', ', $all);
      $vals = implode(', ', array_map(fn($f) => ':' . $f, $all));
      $stmt = $pdo->prepare("INSERT INTO v_devices ($cols) VALUES ($vals)");
    } else {
      $sets = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
      $stmt = $pdo->prepare("UPDATE v_devices SET $sets WHERE device_uuid = :device_uuid");
    }

    $params = ['device_uuid' => $this->device_uuid];
    if (strpos($stmt->queryString, ':domain_uuid') !== false) {
      $params['domain_uuid'] = $this->domain_uuid;
    }
    foreach ($fields as $f) {
      $v = $this->$f;
      if ($f === 'device_enabled') {
        $v = ($v === true || $v === 'true' || $v === 1 || $v === '1') ? 'true' : 'false';
      }
      $params[$f] = $v;
    }

    try {
      $stmt->execute($params);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Device save failed: ' . $e->getMessage());
    }
    return $this->device_uuid;
  }

  public function delete()
  {
    $pdo = FpbxDomain::fpbx_db();
    $pdo->prepare("DELETE FROM v_devices WHERE device_uuid = :uuid")
        ->execute(['uuid' => $this->device_uuid]);
    return true;
  }

  public function get_id() { return $this->device_uuid; }

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
