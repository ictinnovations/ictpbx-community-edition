<?php

namespace ICT\Core;

class DeviceProfile
{
  public $device_profile_uuid        = null;
  public $domain_uuid                = null;
  public $tenant_id                  = null;
  public $device_profile_name        = null;
  public $device_profile_description = null;
  public $device_profile_enabled     = true;

  public function __construct($device_profile_uuid = null)
  {
    if (!empty($device_profile_uuid)) {
      $this->device_profile_uuid = $device_profile_uuid;
      $this->load();
    }
  }

  private function load()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_device_profiles WHERE device_profile_uuid = :uuid");
    $stmt->execute(['uuid' => $this->device_profile_uuid]);
    $row = $stmt->fetch();
    if (!$row) {
      throw new CoreException('404', 'Device Profile not found');
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
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id);
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter] : [];
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';
    $pdo    = FpbxDomain::fpbx_db();
    $stmt   = $pdo->prepare(
      "SELECT device_profile_uuid, device_profile_name, device_profile_description,
              device_profile_enabled
       FROM v_device_profiles
       " . $where . "
       ORDER BY device_profile_name ASC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function save()
  {
    $pdo = FpbxDomain::fpbx_db();
    if (empty($this->domain_uuid)) {
      $this->domain_uuid = FpbxDomain::get_domain_uuid($this->tenant_id);
      if ($this->domain_uuid === null) {
        throw new \ICT\Core\CoreException(409, 'No FusionPBX domain assigned to this tenant. Contact an administrator.');
      }
    }

    $fields = ['device_profile_name', 'device_profile_description', 'device_profile_enabled'];

    if (empty($this->device_profile_uuid)) {
      $this->device_profile_uuid = $this->generate_uuid();
      $all  = array_merge(['domain_uuid', 'device_profile_uuid'], $fields);
      $cols = implode(', ', $all);
      $vals = implode(', ', array_map(fn($f) => ':' . $f, $all));
      $stmt = $pdo->prepare("INSERT INTO v_device_profiles ($cols) VALUES ($vals)");
    } else {
      $sets = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
      $stmt = $pdo->prepare("UPDATE v_device_profiles SET $sets WHERE device_profile_uuid = :device_profile_uuid");
    }

    $params = ['device_profile_uuid' => $this->device_profile_uuid];
    if (strpos($stmt->queryString, ':domain_uuid') !== false) {
      $params['domain_uuid'] = $this->domain_uuid;
    }
    foreach ($fields as $f) {
      $v = $this->$f;
      if ($f === 'device_profile_enabled') {
        $v = ($v === true || $v === 'true' || $v === 1 || $v === '1') ? 'true' : 'false';
      }
      $params[$f] = $v;
    }

    try {
      $stmt->execute($params);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Device Profile save failed: ' . $e->getMessage());
    }
    return $this->device_profile_uuid;
  }

  public function delete()
  {
    $pdo = FpbxDomain::fpbx_db();
    $pdo->prepare("DELETE FROM v_device_profiles WHERE device_profile_uuid = :uuid")
        ->execute(['uuid' => $this->device_profile_uuid]);
    return true;
  }

  public function get_id() { return $this->device_profile_uuid; }

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
