<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class ConferenceCenter
{
  public $conference_center_uuid        = null;
  public $domain_uuid                   = null;
  public $tenant_id                     = null;
  public $conference_center_name        = null;
  public $conference_center_extension   = null;
  public $conference_center_pin_length  = 4;
  public $conference_center_greeting    = null;
  public $conference_center_description = null;
  public $conference_center_enabled     = true;

  public function __construct($conference_center_uuid = null)
  {
    if ($conference_center_uuid !== null) {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_conference_centers WHERE conference_center_uuid = ?");
      $stmt->execute([$conference_center_uuid]);
      $row  = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) { throw new CoreException(404, 'Conference Center not found'); }
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
      "SELECT domain_uuid, conference_center_uuid, conference_center_name, conference_center_extension,
              conference_center_pin_length, conference_center_enabled, conference_center_description
       FROM v_conference_centers " . $where . " ORDER BY conference_center_name"
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
  }

  public function save()
  {
    $domain_uuid        = FpbxDomain::get_domain_uuid($this->tenant_id);
    if ($domain_uuid === null) { /* null-domain-guard */
      throw new \ICT\Core\CoreException(409, 'No FusionPBX domain assigned to this tenant. Contact an administrator.');
    }
    $this->domain_uuid  = $domain_uuid;
    $pdo                = FpbxDomain::fpbx_db();

    $conflict = FpbxDomain::extension_in_use($domain_uuid, $this->conference_center_extension, $this->conference_center_uuid ?? null);
    if ($conflict !== null) {
      throw new CoreException(409, "Extension number {$this->conference_center_extension} is already in use by a $conflict in this domain.");
    }

    $enabled = ($this->conference_center_enabled === true || $this->conference_center_enabled === 'true'
                || $this->conference_center_enabled === 1 || $this->conference_center_enabled === '1')
               ? 'true' : 'false';

    $fields = [
      'domain_uuid'                  => $domain_uuid,
      'conference_center_name'       => $this->conference_center_name,
      'conference_center_extension'  => $this->conference_center_extension,
      'conference_center_pin_length' => $this->conference_center_pin_length ?: 4,
      'conference_center_greeting'   => $this->conference_center_greeting ?: null,
      'conference_center_description'=> $this->conference_center_description ?: null,
      'conference_center_enabled'    => $enabled,
    ];

    try {
      if (empty($this->conference_center_uuid)) {
        $this->conference_center_uuid = self::generate_uuid();
        $fields['conference_center_uuid'] = $this->conference_center_uuid;
        $cols = implode(', ', array_keys($fields));
        $phs  = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO v_conference_centers ($cols) VALUES ($phs)")
            ->execute(array_values($fields));
      } else {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
        $pdo->prepare("UPDATE v_conference_centers SET $set WHERE conference_center_uuid = ?")
            ->execute([...array_values($fields), $this->conference_center_uuid]);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Conference Center save failed: ' . $e->getMessage());
    }
    return $this->conference_center_uuid;
  }

  public function delete()
  {
    if (empty($this->conference_center_uuid)) { throw new CoreException(400, 'Missing uuid'); }
    $pdo   = FpbxDomain::fpbx_db();
    $uuid  = $this->conference_center_uuid;
    $label = $this->conference_center_name;
    // Guard: check IVR menu options and inbound routes referencing this conference
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
    $pdo->prepare("DELETE FROM v_conference_rooms WHERE conference_center_uuid = ?")
        ->execute([$uuid]);
    $pdo->prepare("DELETE FROM v_conference_centers WHERE conference_center_uuid = ?")
        ->execute([$uuid]);
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
