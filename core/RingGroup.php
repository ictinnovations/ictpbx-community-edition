<?php

namespace ICT\Core;

class RingGroup
{
  public $ring_group_uuid         = null;
  public $domain_uuid             = null;
  public $tenant_id               = null;
  public $ring_group_name         = '';
  public $ring_group_extension    = '';
  public $ring_group_greeting     = null;
  public $ring_group_strategy     = 'simultaneous';
  public $ring_group_exit_key     = null;
  public $ring_group_call_timeout = 30;
  public $ring_group_caller_id_name    = null;
  public $ring_group_caller_id_number  = null;
  public $ring_group_cid_name_prefix   = null;
  public $ring_group_cid_number_prefix = null;
  public $ring_group_timeout_app  = null;
  public $ring_group_timeout_data = null;
  public $ring_group_forward_destination = null;
  public $ring_group_forward_enabled     = false;
  public $ring_group_distinctive_ring    = null;
  public $ring_group_ringback            = null;
  public $ring_group_call_screen_enabled  = false;
  public $ring_group_call_forward_enabled = false;
  public $ring_group_follow_me_enabled    = false;
  public $ring_group_missed_call_app  = null;
  public $ring_group_missed_call_data = null;
  public $ring_group_context     = null;
  public $ring_group_enabled     = true;
  public $ring_group_description = null;
  public $destinations = array();

  public function __construct($ring_group_uuid = null)
  {
    if (!empty($ring_group_uuid)) {
      $this->ring_group_uuid = $ring_group_uuid;
      $this->load();
    }
  }

  private function load()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_ring_groups WHERE ring_group_uuid = :uuid");
    $stmt->execute(['uuid' => $this->ring_group_uuid]);
    $row = $stmt->fetch();
    if (!$row) {
      throw new CoreException('404', 'Ring Group not found');
    }
    foreach ($row as $k => $v) {
      if (property_exists($this, $k)) {
        $this->$k = $v;
      }
    }
    $stmt2 = $pdo->prepare(
      "SELECT * FROM v_ring_group_destinations WHERE ring_group_uuid = :uuid ORDER BY destination_delay"
    );
    $stmt2->execute(['uuid' => $this->ring_group_uuid]);
    $this->destinations = $stmt2->fetchAll();
  }

  /**
   * @param array $aFilter  May include 'tenant_id' for multi-tenant scoping.
   */
  public static function search($aFilter = array())
  {
    $tenant_id   = $aFilter['tenant_id'] ?? null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter] : [];
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';
    $pdo         = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT rg.domain_uuid, rg.ring_group_uuid, rg.ring_group_name, rg.ring_group_extension,
              rg.ring_group_strategy, rg.ring_group_call_timeout,
              rg.ring_group_enabled, rg.ring_group_description,
              (SELECT COUNT(*) FROM v_ring_group_destinations d
               WHERE d.ring_group_uuid = rg.ring_group_uuid) AS destination_count
       FROM v_ring_groups rg
       " . $where . "
       ORDER BY rg.ring_group_name ASC"
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
    if (empty($this->ring_group_context)) {
      $this->ring_group_context = FpbxDomain::get_domain_name($this->domain_uuid);
    }

    $conflict = FpbxDomain::extension_in_use($this->domain_uuid, $this->ring_group_extension, $this->ring_group_uuid);
    if ($conflict !== null) {
      throw new CoreException(409, "Extension number {$this->ring_group_extension} is already in use by a $conflict in this domain.");
    }

    $fields = [
      'ring_group_name', 'ring_group_extension', 'ring_group_greeting',
      'ring_group_strategy', 'ring_group_exit_key', 'ring_group_call_timeout',
      'ring_group_caller_id_name', 'ring_group_caller_id_number',
      'ring_group_cid_name_prefix', 'ring_group_cid_number_prefix',
      'ring_group_timeout_app', 'ring_group_timeout_data',
      'ring_group_forward_destination', 'ring_group_forward_enabled',
      'ring_group_distinctive_ring', 'ring_group_ringback',
      'ring_group_call_screen_enabled', 'ring_group_call_forward_enabled',
      'ring_group_follow_me_enabled', 'ring_group_missed_call_app',
      'ring_group_missed_call_data', 'ring_group_context',
      'ring_group_enabled', 'ring_group_description'
    ];

    if (empty($this->ring_group_uuid)) {
      $this->ring_group_uuid = $this->generate_uuid();
      $all = array_merge(['domain_uuid', 'ring_group_uuid'], $fields);
      $cols = implode(', ', $all);
      $vals = implode(', ', array_map(function($f) { return ':' . $f; }, $all));
      $stmt = $pdo->prepare("INSERT INTO v_ring_groups ($cols) VALUES ($vals)");
    } else {
      $sets = implode(', ', array_map(function($f) { return "$f = :$f"; }, $fields));
      $stmt = $pdo->prepare("UPDATE v_ring_groups SET $sets WHERE ring_group_uuid = :ring_group_uuid");
    }

    $bool_fields = ['ring_group_forward_enabled', 'ring_group_call_screen_enabled',
                    'ring_group_call_forward_enabled', 'ring_group_follow_me_enabled',
                    'ring_group_enabled'];
    $params = ['ring_group_uuid' => $this->ring_group_uuid];
    if (strpos($stmt->queryString, ':domain_uuid') !== false) {
      $params['domain_uuid'] = $this->domain_uuid;
    }
    foreach ($fields as $f) {
      $v = $this->$f;
      if (in_array($f, $bool_fields, true)) {
        // PostgreSQL booleans: must be 'true'/'false' strings, not PHP bool/'' .
        if ($v === '' || $v === null) { $v = 'false'; }
        else { $v = ($v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 't') ? 'true' : 'false'; }
      }
      $params[$f] = $v;
    }
    $stmt->execute($params);

    if (!empty($this->destinations)) {
      $pdo->prepare("DELETE FROM v_ring_group_destinations WHERE ring_group_uuid = :uuid")
          ->execute(['uuid' => $this->ring_group_uuid]);
      $ins = $pdo->prepare(
        "INSERT INTO v_ring_group_destinations
         (ring_group_destination_uuid, ring_group_uuid, domain_uuid,
          destination_number, destination_delay, destination_timeout,
          destination_prompt, destination_enabled, destination_description)
         VALUES (gen_random_uuid(), :rg_uuid, :domain_uuid,
          :destination_number, :destination_delay, :destination_timeout,
          :destination_prompt, :destination_enabled, :destination_description)"
      );
      foreach ($this->destinations as $dest) {
        $ins->execute([
          'rg_uuid'               => $this->ring_group_uuid,
          'domain_uuid'           => $this->domain_uuid,
          'destination_number'    => $dest['destination_number'] ?? '',
          'destination_delay'     => (int)($dest['destination_delay'] ?? 0),
          'destination_timeout'   => (int)($dest['destination_timeout'] ?? 30),
          'destination_prompt'    => (int)($dest['destination_prompt'] ?? 0),
          'destination_enabled'   => (!isset($dest['destination_enabled']) || $dest['destination_enabled']) ? 'true' : 'false',
          'destination_description' => $dest['destination_description'] ?? null,
        ]);
      }
    }

    return $this->ring_group_uuid;
  }

  public function delete()
  {
    $pdo   = FpbxDomain::fpbx_db();
    $uuid  = $this->ring_group_uuid;
    $label = $this->ring_group_name;
    // Guard: check IVR menu options and inbound routes referencing this ring group
    $stmt = $pdo->prepare(
      "SELECT im.ivr_menu_name FROM v_ivr_menu_options o
       JOIN v_ivr_menus im ON im.ivr_menu_uuid = o.ivr_menu_uuid
       WHERE o.ivr_menu_option_param LIKE ? LIMIT 5"
    );
    $stmt->execute([$uuid . '%']);
    $ivrs = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    $stmt2 = $pdo->prepare(
      "SELECT destination_number FROM v_destinations WHERE destination_data LIKE ? LIMIT 5"
    );
    $stmt2->execute([$uuid . '%']);
    $routes = $stmt2->fetchAll(\PDO::FETCH_COLUMN);
    $msgs = [];
    if (!empty($ivrs))   $msgs[] = "IVR Menu(s): " . implode(', ', $ivrs);
    if (!empty($routes)) $msgs[] = "Inbound Route(s): " . implode(', ', $routes);
    if (!empty($msgs)) {
      throw new CoreException(409,
        "Cannot delete '{$label}': referenced by " . implode('; ', $msgs) . ". Remove those references first.");
    }
    $pdo->prepare("DELETE FROM v_ring_group_destinations WHERE ring_group_uuid = :uuid")
        ->execute(['uuid' => $uuid]);
    $pdo->prepare("DELETE FROM v_ring_groups WHERE ring_group_uuid = :uuid")
        ->execute(['uuid' => $uuid]);
    return true;
  }

  public function get_id() { return $this->ring_group_uuid; }

  private function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  }

  public function __get($f)  { return property_exists($this, $f) ? $this->$f : null; }
  public function __set($f, $v) { if (property_exists($this, $f)) $this->$f = $v; }
  public function __isset($f) { return isset($this->$f); }
}
