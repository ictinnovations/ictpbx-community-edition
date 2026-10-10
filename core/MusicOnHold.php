<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class MusicOnHold
{
  public $music_on_hold_uuid       = null;
  public $domain_uuid              = null;
  public $tenant_id                = null;
  public $music_on_hold_name       = null;
  public $music_on_hold_path       = null;
  public $music_on_hold_rate       = 8000;
  public $music_on_hold_shuffle    = 'true';
  public $music_on_hold_channels   = 1;
  public $music_on_hold_interval   = 20;
  public $music_on_hold_timer_name = 'soft';
  public $music_on_hold_chime_list = null;
  public $music_on_hold_chime_freq = 0;
  public $music_on_hold_chime_max  = 0;

  public function __construct($music_on_hold_uuid = null)
  {
    if ($music_on_hold_uuid !== null) {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_music_on_hold WHERE music_on_hold_uuid = ?");
      $stmt->execute([$music_on_hold_uuid]);
      $row  = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) { throw new CoreException(404, 'Music on Hold not found'); }
      foreach ($row as $k => $v) { $this->$k = $v; }
    }
  }

  public static function search($aFilter = [])
  {
    $tenant_id   = isset($aFilter['tenant_id']) ? $aFilter['tenant_id'] : null;
    $domain_uuid = FpbxDomain::get_domain_uuid($tenant_id);
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT domain_uuid, music_on_hold_uuid, music_on_hold_name, music_on_hold_path, music_on_hold_shuffle
       FROM v_music_on_hold WHERE domain_uuid = ? ORDER BY music_on_hold_name"
    );
    $stmt->execute([$domain_uuid]);
    return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
  }

  public function save()
  {
    $domain_uuid       = FpbxDomain::get_domain_uuid($this->tenant_id);
    $this->domain_uuid = $domain_uuid;
    $pdo               = FpbxDomain::fpbx_db();

    $fields = [
      'domain_uuid'              => $domain_uuid,
      'music_on_hold_name'       => $this->music_on_hold_name,
      'music_on_hold_path'       => $this->music_on_hold_path ?: null,
      'music_on_hold_rate'       => $this->music_on_hold_rate ?: 8000,
      'music_on_hold_shuffle'    => $this->music_on_hold_shuffle === 'false' ? 'false' : 'true',
      'music_on_hold_channels'   => $this->music_on_hold_channels ?: 1,
      'music_on_hold_interval'   => $this->music_on_hold_interval ?: 20,
      'music_on_hold_timer_name' => $this->music_on_hold_timer_name ?: 'soft',
      'music_on_hold_chime_list' => $this->music_on_hold_chime_list ?: null,
      'music_on_hold_chime_freq' => $this->music_on_hold_chime_freq ?: 0,
      'music_on_hold_chime_max'  => $this->music_on_hold_chime_max ?: 0,
    ];

    $nameCheck = $pdo->prepare("SELECT COUNT(*) FROM v_music_on_hold WHERE domain_uuid = ? AND music_on_hold_name = ? AND music_on_hold_uuid IS DISTINCT FROM ?");
    $nameCheck->execute([$domain_uuid, $this->music_on_hold_name, $this->music_on_hold_uuid]);
    if ((int)$nameCheck->fetchColumn() > 0) {
      throw new CoreException(409, "A Music on Hold category named '{$this->music_on_hold_name}' already exists in this domain.");
    }

    try {
      if (empty($this->music_on_hold_uuid)) {
        $this->music_on_hold_uuid = self::generate_uuid();
        $fields['music_on_hold_uuid'] = $this->music_on_hold_uuid;
        $cols = implode(', ', array_keys($fields));
        $phs  = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO v_music_on_hold ($cols) VALUES ($phs)")
            ->execute(array_values($fields));
      } else {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
        $pdo->prepare("UPDATE v_music_on_hold SET $set WHERE music_on_hold_uuid = ?")
            ->execute([...array_values($fields), $this->music_on_hold_uuid]);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Music on Hold save failed: ' . $e->getMessage());
    }
    return $this->music_on_hold_uuid;
  }

  public function delete()
  {
    if (empty($this->music_on_hold_uuid)) { throw new CoreException(400, 'Missing uuid'); }
    // Guard: block deletion if any call queue uses this MoH
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT queue_name FROM v_call_center_queues WHERE domain_uuid = ? AND queue_moh_sound = ? LIMIT 5"
    );
    $stmt->execute([$this->domain_uuid, $this->music_on_hold_name]);
    $queues = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    if (!empty($queues)) {
      throw new CoreException(409,
        "Cannot delete: used by Call Queue(s): " . implode(', ', $queues) . ". Update those queues first.");
    }
    $pdo->prepare("DELETE FROM v_music_on_hold WHERE music_on_hold_uuid = ?")
        ->execute([$this->music_on_hold_uuid]);
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
