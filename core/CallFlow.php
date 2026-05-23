<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class CallFlow
{
  public $call_flow_uuid            = null;
  public $domain_uuid               = null;
  public $tenant_id                 = null;
  public $dialplan_uuid             = null;
  public $feature_code_dialplan_uuid = null; // transient: derived from v_dialplans, not stored in v_call_flows
  public $call_flow_name            = null;
  public $call_flow_extension       = null;
  public $call_flow_feature_code    = null;
  public $call_flow_context         = null;
  public $call_flow_status          = 'true';
  public $call_flow_pin_number      = null;
  public $call_flow_label           = null;
  public $call_flow_sound           = null;
  public $call_flow_app             = 'transfer';
  public $call_flow_data            = null;
  public $call_flow_alternate_label = null;
  public $call_flow_alternate_sound = null;
  public $call_flow_alternate_app   = 'transfer';
  public $call_flow_alternate_data  = null;
  public $call_flow_enabled         = 'true';

  public function __construct($call_flow_uuid = null)
  {
    if ($call_flow_uuid !== null) {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_call_flows WHERE call_flow_uuid = ?");
      $stmt->execute([$call_flow_uuid]);
      $row  = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) { throw new CoreException(404, 'Call Flow not found'); }
      foreach ($row as $k => $v) { $this->$k = $v; }

      if (!empty($this->call_flow_feature_code) && !empty($this->domain_uuid)) {
        $fstmt = $pdo->prepare(
          "SELECT dp.dialplan_uuid FROM v_dialplans dp
           JOIN v_dialplan_details dd ON dd.dialplan_uuid = dp.dialplan_uuid
           WHERE dp.domain_uuid = ? AND dp.dialplan_number = ?
           AND dd.dialplan_detail_type = 'call_flow' AND dd.dialplan_detail_data = ?
           LIMIT 1"
        );
        $fstmt->execute([$this->domain_uuid, $this->call_flow_feature_code, $this->call_flow_uuid]);
        $found = $fstmt->fetchColumn();
        if ($found) $this->feature_code_dialplan_uuid = $found;
      }
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
      "SELECT domain_uuid, call_flow_uuid, call_flow_name, call_flow_extension, call_flow_feature_code,
              call_flow_status, call_flow_enabled, call_flow_label, call_flow_alternate_label
       FROM v_call_flows " . $where . " ORDER BY call_flow_name"
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

    $domain_name  = $this->get_domain_name($pdo, $domain_uuid);
    $context      = $this->call_flow_context ?: $domain_name;
    $bool_enabled = ($this->call_flow_enabled === 'false' || $this->call_flow_enabled === false) ? 'false' : 'true';

    if (empty($this->call_flow_uuid)) {
      $this->call_flow_uuid = self::generate_uuid();
    }
    if (empty($this->dialplan_uuid)) {
      $this->dialplan_uuid = self::generate_uuid();
    }
    if (!empty($this->call_flow_feature_code) && empty($this->feature_code_dialplan_uuid)) {
      $this->feature_code_dialplan_uuid = self::generate_uuid();
    }

    $fields = [
      'call_flow_uuid'            => $this->call_flow_uuid,
      'domain_uuid'               => $domain_uuid,
      'dialplan_uuid'             => $this->dialplan_uuid,
      'call_flow_name'            => $this->call_flow_name,
      'call_flow_extension'       => $this->call_flow_extension,
      'call_flow_feature_code'    => $this->call_flow_feature_code ?: null,
      'call_flow_context'         => $context,
      'call_flow_status'          => $this->call_flow_status ?: 'true',
      'call_flow_pin_number'      => $this->call_flow_pin_number ?: null,
      'call_flow_label'           => $this->call_flow_label ?: 'Open',
      'call_flow_sound'           => $this->call_flow_sound ?: null,
      'call_flow_app'             => $this->call_flow_app ?: 'transfer',
      'call_flow_data'            => $this->call_flow_data ?: null,
      'call_flow_alternate_label' => $this->call_flow_alternate_label ?: 'Closed',
      'call_flow_alternate_sound' => $this->call_flow_alternate_sound ?: null,
      'call_flow_alternate_app'   => $this->call_flow_alternate_app ?: 'transfer',
      'call_flow_alternate_data'  => $this->call_flow_alternate_data ?: null,
      'call_flow_enabled'         => $bool_enabled,
    ];

    $conflict = FpbxDomain::extension_in_use($domain_uuid, $this->call_flow_extension, $this->call_flow_uuid);
    if ($conflict !== null) {
      throw new CoreException(409, "Extension number {$this->call_flow_extension} is already in use by a $conflict in this domain.");
    }

    try {
      $check = $pdo->prepare("SELECT 1 FROM v_call_flows WHERE call_flow_uuid = ?");
      $check->execute([$this->call_flow_uuid]);

      if (!$check->fetchColumn()) {
        $cols = implode(', ', array_keys($fields));
        $phs  = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO v_call_flows ($cols) VALUES ($phs)")
            ->execute(array_values($fields));
      } else {
        unset($fields['call_flow_uuid']);
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
        $pdo->prepare("UPDATE v_call_flows SET $set WHERE call_flow_uuid = ?")
            ->execute([...array_values($fields), $this->call_flow_uuid]);
      }

      $this->upsert_dialplan($pdo, $domain_uuid, $domain_name, $context);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Call Flow save failed: ' . $e->getMessage());
    }
    return $this->call_flow_uuid;
  }

  private function upsert_dialplan($pdo, $domain_uuid, $domain_name, $context)
  {
    $check = $pdo->prepare("SELECT 1 FROM v_dialplans WHERE dialplan_uuid = ?");
    $check->execute([$this->dialplan_uuid]);

    if (!$check->fetchColumn()) {
      $pdo->prepare(
        "INSERT INTO v_dialplans (dialplan_uuid, domain_uuid, dialplan_context, dialplan_name,
         dialplan_number, dialplan_order, dialplan_enabled, dialplan_description)
         VALUES (?, ?, ?, ?, ?, 100, 'true', ?)"
      )->execute([
        $this->dialplan_uuid, $domain_uuid, $context,
        $this->call_flow_name, $this->call_flow_extension,
        'Call Flow: ' . $this->call_flow_name,
      ]);
    } else {
      $pdo->prepare(
        "UPDATE v_dialplans SET dialplan_context=?, dialplan_name=?, dialplan_number=?,
         dialplan_description=? WHERE dialplan_uuid=?"
      )->execute([
        $context, $this->call_flow_name, $this->call_flow_extension,
        'Call Flow: ' . $this->call_flow_name, $this->dialplan_uuid,
      ]);
    }

    $pdo->prepare("DELETE FROM v_dialplan_details WHERE dialplan_uuid = ?")
        ->execute([$this->dialplan_uuid]);

    $ext = preg_quote($this->call_flow_extension ?? '', '/');
    $this->insert_detail($pdo, $this->dialplan_uuid, 'condition', 'destination_number', "^({$ext})$", '', 0, 10);
    $this->insert_detail($pdo, $this->dialplan_uuid, 'action',    'export', 'call_direction=inbound', 'true', 0, 20);
    $this->insert_detail($pdo, $this->dialplan_uuid, 'action',    'set',    'domain_uuid=' . $domain_uuid, 'true', 0, 30);
    $this->insert_detail($pdo, $this->dialplan_uuid, 'action',    'set',    'domain_name=' . $domain_name, 'true', 0, 40);
    $this->insert_detail($pdo, $this->dialplan_uuid, 'action',    'call_flow', $this->call_flow_uuid, '', 0, 50);

    if (!empty($this->call_flow_feature_code) && !empty($this->feature_code_dialplan_uuid)) {
      $this->upsert_feature_code_dialplan($pdo, $domain_uuid, $domain_name, $context);
    }
  }

  private function upsert_feature_code_dialplan($pdo, $domain_uuid, $domain_name, $context)
  {
    $fuuid = $this->feature_code_dialplan_uuid;
    $check = $pdo->prepare("SELECT 1 FROM v_dialplans WHERE dialplan_uuid = ?");
    $check->execute([$fuuid]);

    if (!$check->fetchColumn()) {
      $pdo->prepare(
        "INSERT INTO v_dialplans (dialplan_uuid, domain_uuid, dialplan_context, dialplan_name,
         dialplan_number, dialplan_order, dialplan_enabled, dialplan_description)
         VALUES (?, ?, ?, ?, ?, 100, 'true', ?)"
      )->execute([
        $fuuid, $domain_uuid, $context,
        $this->call_flow_name, $this->call_flow_feature_code,
        'Call Flow Feature: ' . $this->call_flow_name,
      ]);
    } else {
      $pdo->prepare(
        "UPDATE v_dialplans SET dialplan_context=?, dialplan_name=?, dialplan_number=?,
         dialplan_description=? WHERE dialplan_uuid=?"
      )->execute([
        $context, $this->call_flow_name, $this->call_flow_feature_code,
        'Call Flow Feature: ' . $this->call_flow_name, $fuuid,
      ]);
    }

    $pdo->prepare("DELETE FROM v_dialplan_details WHERE dialplan_uuid = ?")
        ->execute([$fuuid]);

    $fc = preg_quote($this->call_flow_feature_code, '/');
    $this->insert_detail($pdo, $fuuid, 'condition', 'destination_number', "^({$fc})$", '', 0, 10);
    $this->insert_detail($pdo, $fuuid, 'action',    'export', 'call_direction=inbound', 'true', 0, 20);
    $this->insert_detail($pdo, $fuuid, 'action',    'set',    'domain_uuid=' . $domain_uuid, 'true', 0, 30);
    $this->insert_detail($pdo, $fuuid, 'action',    'set',    'domain_name=' . $domain_name, 'true', 0, 40);
    $this->insert_detail($pdo, $fuuid, 'action',    'call_flow', $this->call_flow_uuid, '', 0, 50);
  }

  private function insert_detail($pdo, $dialplan_uuid, $tag, $type, $data, $inline, $group, $order)
  {
    $pdo->prepare(
      "INSERT INTO v_dialplan_details
       (dialplan_uuid, dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type,
        dialplan_detail_data, dialplan_detail_inline, dialplan_detail_group,
        dialplan_detail_order, dialplan_detail_enabled)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'true')"
    )->execute([
      $dialplan_uuid, self::generate_uuid(), $tag, $type, $data, $inline, $group, $order,
    ]);
  }

  private function get_domain_name($pdo, $domain_uuid)
  {
    $stmt = $pdo->prepare("SELECT domain_name FROM v_domains WHERE domain_uuid = ?");
    $stmt->execute([$domain_uuid]);
    return $stmt->fetchColumn() ?: $domain_uuid;
  }

  public function delete()
  {
    if (empty($this->call_flow_uuid)) { throw new CoreException(400, 'Missing uuid'); }
    $pdo   = FpbxDomain::fpbx_db();
    $uuid  = $this->call_flow_uuid;
    $label = $this->call_flow_name ?? $this->call_flow_extension ?? $uuid;
    // Guard: check IVR menu options and inbound routes referencing this call flow
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
    try {
      if ($this->dialplan_uuid) {
        $pdo->prepare("DELETE FROM v_dialplan_details WHERE dialplan_uuid = ?")
            ->execute([$this->dialplan_uuid]);
        $pdo->prepare("DELETE FROM v_dialplans WHERE dialplan_uuid = ?")
            ->execute([$this->dialplan_uuid]);
      }
      if ($this->feature_code_dialplan_uuid) {
        $pdo->prepare("DELETE FROM v_dialplan_details WHERE dialplan_uuid = ?")
            ->execute([$this->feature_code_dialplan_uuid]);
        $pdo->prepare("DELETE FROM v_dialplans WHERE dialplan_uuid = ?")
            ->execute([$this->feature_code_dialplan_uuid]);
      }
      $pdo->prepare("DELETE FROM v_call_flows WHERE call_flow_uuid = ?")
          ->execute([$this->call_flow_uuid]);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Call Flow delete failed: ' . $e->getMessage());
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
