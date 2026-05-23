<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class FollowMe
{
  public $follow_me_uuid          = null;
  public $domain_uuid             = null;
  public $tenant_id               = null;
  public $extension_uuid          = null;
  public $cid_name_prefix         = null;
  public $cid_number_prefix       = null;
  public $dial_string             = null;
  public $follow_me_enabled       = 'false';
  public $follow_me_ignore_busy   = 'false';
  public $destinations            = [];

  public function __construct($follow_me_uuid = null)
  {
    if ($follow_me_uuid !== null) {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_follow_me WHERE follow_me_uuid = ?");
      $stmt->execute([$follow_me_uuid]);
      $row  = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) { throw new CoreException(404, 'Follow Me not found'); }
      foreach ($row as $k => $v) { $this->$k = $v; }
      $this->destinations = $this->load_destinations($pdo);
    }
  }

  private function load_destinations($pdo)
  {
    $stmt = $pdo->prepare(
      "SELECT follow_me_destination_uuid, follow_me_destination, follow_me_delay,
              follow_me_timeout, follow_me_prompt, follow_me_order
       FROM v_follow_me_destinations WHERE follow_me_uuid = ? ORDER BY follow_me_order"
    );
    $stmt->execute([$this->follow_me_uuid]);
    return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
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
      "SELECT fm.follow_me_uuid, fm.follow_me_enabled, fm.follow_me_ignore_busy,
              fm.cid_name_prefix, fm.dial_string,
              e.extension_uuid, e.extension AS extension_number
       FROM v_follow_me fm
       LEFT JOIN v_extensions e ON e.follow_me_uuid = fm.follow_me_uuid
       " . ($domain_filter ? "WHERE fm.domain_uuid = :domain_uuid" : "") . " ORDER BY e.extension"
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

    $bool_enabled      = ($this->follow_me_enabled === 'true' || $this->follow_me_enabled === true) ? 'true' : 'false';
    $bool_ignore_busy  = ($this->follow_me_ignore_busy === 'true' || $this->follow_me_ignore_busy === true) ? 'true' : 'false';

    $fields = [
      'domain_uuid'           => $domain_uuid,
      'cid_name_prefix'       => $this->cid_name_prefix ?: null,
      'cid_number_prefix'     => $this->cid_number_prefix ?: null,
      'dial_string'           => $this->dial_string ?: null,
      'follow_me_enabled'     => $bool_enabled,
      'follow_me_ignore_busy' => $bool_ignore_busy,
    ];

    try {
      if (empty($this->follow_me_uuid)) {
        $this->follow_me_uuid = self::generate_uuid();
        $fields['follow_me_uuid'] = $this->follow_me_uuid;
        $cols = implode(', ', array_keys($fields));
        $phs  = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO v_follow_me ($cols) VALUES ($phs)")
            ->execute(array_values($fields));
      } else {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
        $pdo->prepare("UPDATE v_follow_me SET $set WHERE follow_me_uuid = ?")
            ->execute([...array_values($fields), $this->follow_me_uuid]);
      }

      $this->save_destinations($pdo);

      if (!empty($this->extension_uuid)) {
        $linkExt = $this->resolve_extension_uuid($pdo, $domain_uuid, $this->extension_uuid);
        if ($linkExt) {
          // Clear any previous link to this extension's follow_me so the column
          // join in search() reflects only the current owner.
          $pdo->prepare(
            "UPDATE v_extensions SET follow_me_uuid = ? WHERE extension_uuid = ?"
          )->execute([$this->follow_me_uuid, $linkExt]);
          $this->extension_uuid = $linkExt;
        }
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Follow Me save failed: ' . $e->getMessage());
    }
    return $this->follow_me_uuid;
  }

  private function save_destinations($pdo)
  {
    $pdo->prepare("DELETE FROM v_follow_me_destinations WHERE follow_me_uuid = ?")
        ->execute([$this->follow_me_uuid]);

    if (empty($this->destinations)) { return; }

    foreach ($this->destinations as $i => $dest) {
      $dest = (array)$dest;
      $dest_uuid = isset($dest['follow_me_destination_uuid']) && $dest['follow_me_destination_uuid']
        ? $dest['follow_me_destination_uuid']
        : self::generate_uuid();
      $pdo->prepare(
        "INSERT INTO v_follow_me_destinations
         (follow_me_uuid, follow_me_destination_uuid, follow_me_destination,
          follow_me_delay, follow_me_timeout, follow_me_prompt, follow_me_order)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
      )->execute([
        $this->follow_me_uuid,
        $dest_uuid,
        $dest['follow_me_destination'] ?? '',
        $dest['follow_me_delay'] ?? 0,
        $dest['follow_me_timeout'] ?? 30,
        $dest['follow_me_prompt'] ?? 'false',
        $dest['follow_me_order'] ?? (($i + 1) * 10),
      ]);
    }
  }

  public function delete()
  {
    if (empty($this->follow_me_uuid)) { throw new CoreException(400, 'Missing uuid'); }
    $pdo = FpbxDomain::fpbx_db();
    try {
      $pdo->prepare("UPDATE v_extensions SET follow_me_uuid = NULL WHERE follow_me_uuid = ?")
          ->execute([$this->follow_me_uuid]);
      $pdo->prepare("DELETE FROM v_follow_me_destinations WHERE follow_me_uuid = ?")
          ->execute([$this->follow_me_uuid]);
      $pdo->prepare("DELETE FROM v_follow_me WHERE follow_me_uuid = ?")
          ->execute([$this->follow_me_uuid]);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Follow Me delete failed: ' . $e->getMessage());
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

  /**
   * Accept either a v_extensions.extension_uuid or an extension number; return
   * the matching extension_uuid or null. Lets the form take either input
   * without round-tripping a PG uuid cast error.
   */
  private function resolve_extension_uuid($pdo, $domain_uuid, $value)
  {
    $value = trim((string)$value);
    if ($value === '') { return null; }
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
      $stmt = $pdo->prepare("SELECT extension_uuid FROM v_extensions WHERE extension_uuid = ? AND domain_uuid = ?");
      $stmt->execute([$value, $domain_uuid]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ? $row['extension_uuid'] : null;
    }
    $stmt = $pdo->prepare("SELECT extension_uuid FROM v_extensions WHERE extension = ? AND domain_uuid = ?");
    $stmt->execute([$value, $domain_uuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['extension_uuid'] : null;
  }

}
