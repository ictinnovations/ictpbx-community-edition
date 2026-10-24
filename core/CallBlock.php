<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class CallBlock
{
  public $call_block_uuid        = null;
  public $domain_uuid            = null;
  public $tenant_id              = null;
  public $call_block_name        = null;
  public $call_block_number      = null;
  public $call_block_direction   = 'inbound';
  public $call_block_action      = 'reject';
  public $call_block_app         = null;
  public $call_block_data        = null;
  public $call_block_country_code = null;
  public $call_block_count       = 0;
  public $extension_uuid         = null;
  public $call_block_enabled     = 'true';
  public $call_block_description = null;

  public function __construct($call_block_uuid = null)
  {
    if ($call_block_uuid !== null) {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_call_block WHERE call_block_uuid = ?");
      $stmt->execute([$call_block_uuid]);
      $row  = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) { throw new CoreException(404, 'Call Block not found'); }
      foreach ($row as $k => $v) { $this->$k = $v; }
    }
  }

  public static function search($aFilter = [])
  {
    $tenant_id   = isset($aFilter['tenant_id']) ? $aFilter['tenant_id'] : null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter] : []; /* params-fix-v3 */
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT call_block_uuid, call_block_name, call_block_number, call_block_direction,
              call_block_action, call_block_enabled, call_block_description
       FROM v_call_block " . $where . " ORDER BY call_block_name"
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

    $bool = ($this->call_block_enabled === 'false' || $this->call_block_enabled === false) ? 'false' : 'true';

    $fields = [
      'domain_uuid'             => $domain_uuid,
      'call_block_name'         => $this->call_block_name,
      'call_block_number'       => $this->call_block_number,
      'call_block_direction'    => $this->call_block_direction ?: 'inbound',
      'call_block_action'       => $this->call_block_action ?: 'reject',
      'call_block_app'          => $this->call_block_app ?: null,
      'call_block_data'         => $this->call_block_data ?: null,
      'call_block_country_code' => $this->call_block_country_code ?: null,
      'call_block_count'        => $this->call_block_count ?: 0,
      'extension_uuid'          => $this->extension_uuid ?: null,
      'call_block_enabled'      => $bool,
      'call_block_description'  => $this->call_block_description ?: null,
    ];

    try {
      if (empty($this->call_block_uuid)) {
        $this->call_block_uuid = self::generate_uuid();
        $fields['call_block_uuid'] = $this->call_block_uuid;
        $cols = implode(', ', array_keys($fields));
        $phs  = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO v_call_block ($cols) VALUES ($phs)")
            ->execute(array_values($fields));
      } else {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
        $pdo->prepare("UPDATE v_call_block SET $set WHERE call_block_uuid = ?")
            ->execute([...array_values($fields), $this->call_block_uuid]);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Call Block save failed: ' . $e->getMessage());
    }
    return $this->call_block_uuid;
  }

  public function delete()
  {
    if (empty($this->call_block_uuid)) { throw new CoreException(400, 'Missing uuid'); }
    try {
      FpbxDomain::fpbx_db()
        ->prepare("DELETE FROM v_call_block WHERE call_block_uuid = ?")
        ->execute([$this->call_block_uuid]);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Call Block delete failed: ' . $e->getMessage());
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
