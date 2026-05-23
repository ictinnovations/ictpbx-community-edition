<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class CallQueue
{
  public $call_center_queue_uuid   = null;
  public $domain_uuid              = null;
  public $tenant_id                = null;
  public $queue_name               = null;
  public $queue_extension          = null;
  public $queue_strategy           = 'ring-all';
  public $queue_moh_sound          = null;
  public $queue_record_template    = null;
  public $queue_time_base_score    = 'queue';
  public $queue_time_base_score_sec = 0;
  public $queue_max_wait_time      = 0;
  public $queue_max_wait_time_with_no_agent = 90;
  public $queue_max_wait_time_with_no_agent_time_reached = 30;
  public $queue_tier_rules_apply   = false;
  public $queue_tier_rule_wait_second = 300;
  public $queue_tier_rule_wait_multiply_level = true;
  public $queue_tier_rule_no_agent_no_wait = false;
  public $queue_discard_abandoned_after = 900;
  public $queue_abandoned_resume_allowed = false;
  public $queue_announce_sound     = null;
  public $queue_announce_frequency = 0;
  public $queue_announce_position_frequency = 0;
  public $queue_announce_round_seconds = 0;
  public $queue_announce_holdtime  = 'true';
  public $queue_cid_prefix         = null;
  public $queue_terminate_on_cancel = false;
  public $queue_agents_can_reject  = true;
  public $queue_ring_progressively_delay = 10;
  public $queue_timeout_action     = null;
  public $queue_description        = null;
  public $queue_enabled            = true;
  public $agents                   = [];

  public function __construct($call_center_queue_uuid = null)
  {
    if ($call_center_queue_uuid !== null) {
      $pdo = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_call_center_queues WHERE call_center_queue_uuid = ?");
      $stmt->execute([$call_center_queue_uuid]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) {
        throw new CoreException(404, 'Call Queue not found');
      }
      foreach ($row as $k => $v) {
        $this->$k = $v;
      }
      $this->agents = self::fetch_agents($call_center_queue_uuid);
    }
  }

  private static function fetch_agents($call_center_queue_uuid)
  {
    $pdo = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT t.call_center_tier_uuid, t.call_center_agent_uuid,
              a.agent_name, a.agent_type, a.agent_call_timeout, a.agent_contact,
              a.agent_status, t.tier_level, t.tier_position
       FROM v_call_center_tiers t
       JOIN v_call_center_agents a ON a.call_center_agent_uuid = t.call_center_agent_uuid
       WHERE t.call_center_queue_uuid = ?
       ORDER BY t.tier_level, t.tier_position, a.agent_name"
    );
    $stmt->execute([$call_center_queue_uuid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  public static function search($aFilter = [])
  {
    $tenant_id = isset($aFilter['tenant_id']) ? $aFilter['tenant_id'] : null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? [$domain_filter] : [];
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';

    $pdo = FpbxDomain::fpbx_db();
    $sql = "SELECT q.*,
              (SELECT COUNT(*) FROM v_call_center_tiers t WHERE t.call_center_queue_uuid = q.call_center_queue_uuid) AS agent_count
            FROM v_call_center_queues q
            " . ($domain_filter ? "WHERE domain_uuid = ?" : "") . "
            ORDER BY q.queue_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $row) {
      $obj = new \stdClass();
      foreach ($row as $k => $v) {
        $obj->$k = $v;
      }
      $result[] = $obj;
    }
    return $result;
  }

  public function save()
  {
    $domain_uuid = FpbxDomain::get_domain_uuid($this->tenant_id);
    if ($domain_uuid === null) { /* null-domain-guard */
      throw new \ICT\Core\CoreException(409, 'No FusionPBX domain assigned to this tenant. Contact an administrator.');
    }
    $this->domain_uuid = $domain_uuid;

    $conflict = FpbxDomain::extension_in_use($domain_uuid, $this->queue_extension, $this->call_center_queue_uuid);
    if ($conflict !== null) {
      throw new CoreException(409, "Extension number {$this->queue_extension} is already in use by a $conflict in this domain.");
    }

    $pdo = FpbxDomain::fpbx_db();

    $bool_fields = [
      'queue_tier_rules_apply', 'queue_tier_rule_wait_multiply_level',
      'queue_tier_rule_no_agent_no_wait', 'queue_abandoned_resume_allowed', 'queue_enabled',
    ];

    $fields = [
      'domain_uuid', 'queue_name', 'queue_extension', 'queue_strategy',
      'queue_moh_sound', 'queue_record_template', 'queue_time_base_score',
      'queue_time_base_score_sec', 'queue_max_wait_time',
      'queue_max_wait_time_with_no_agent',
      'queue_max_wait_time_with_no_agent_time_reached',
      'queue_tier_rules_apply', 'queue_tier_rule_wait_second',
      'queue_tier_rule_wait_multiply_level', 'queue_tier_rule_no_agent_no_wait',
      'queue_discard_abandoned_after', 'queue_abandoned_resume_allowed',
      'queue_announce_position', 'queue_announce_sound', 'queue_announce_frequency',
      'queue_cid_prefix', 'queue_timeout_action', 'queue_enabled',
      'queue_description',
    ];

    $values = [];
    foreach ($fields as $f) {
      $val = property_exists($this, $f) ? $this->$f : null;
      if (in_array($f, $bool_fields)) {
        $val = ($val === true || $val === 'true' || $val === 1 || $val === '1') ? 'true' : 'false';
      }
      $values[$f] = $val;
    }

    try {
      if (empty($this->call_center_queue_uuid)) {
        $this->call_center_queue_uuid = self::generate_uuid();
        $values['call_center_queue_uuid'] = $this->call_center_queue_uuid;
        $cols = implode(', ', array_keys($values));
        $phs  = implode(', ', array_fill(0, count($values), '?'));
        $pdo->prepare("INSERT INTO v_call_center_queues ($cols) VALUES ($phs)")
            ->execute(array_values($values));
      } else {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($values)));
        $pdo->prepare(
          "UPDATE v_call_center_queues SET $set WHERE call_center_queue_uuid = ?"
        )->execute([...array_values($values), $this->call_center_queue_uuid]);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Call Queue save failed: ' . $e->getMessage());
    }

    if (is_array($this->agents)) {
      $pdo->prepare("DELETE FROM v_call_center_tiers WHERE call_center_queue_uuid = ?")
          ->execute([$this->call_center_queue_uuid]);

      foreach ($this->agents as $agent) {
        $agent = (array)$agent;
        $agent_name = $agent['agent_name'] ?? '';
        if (empty($agent_name)) continue;

        $agent_uuid = $agent['call_center_agent_uuid'] ?? null;
        if (!$agent_uuid) {
          $stmt = $pdo->prepare(
            "SELECT call_center_agent_uuid FROM v_call_center_agents WHERE domain_uuid = ? AND agent_name = ?"
          );
          $stmt->execute([$domain_uuid, $agent_name]);
          $existing = $stmt->fetchColumn();
          if ($existing) {
            $agent_uuid = $existing;
          } else {
            $agent_uuid = self::generate_uuid();
            $pdo->prepare(
              "INSERT INTO v_call_center_agents
               (call_center_agent_uuid, domain_uuid, agent_name, agent_type,
                agent_call_timeout, agent_contact, agent_status,
                agent_max_no_answer, agent_wrap_up_time,
                agent_reject_delay_time, agent_busy_delay_time,
                agent_no_answer_delay_time)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([
              $agent_uuid, $domain_uuid,
              $agent_name,
              $agent['agent_type']         ?? 'callback',
              $agent['agent_call_timeout'] ?? 20,
              $agent['agent_contact']      ?? '',
              $agent['agent_status']       ?? 'Available',
              $agent['agent_max_no_answer'] ?? 3,
              $agent['agent_wrap_up_time']  ?? 10,
              $agent['agent_reject_delay_time']    ?? 30,
              $agent['agent_busy_delay_time']      ?? 60,
              $agent['agent_no_answer_delay_time'] ?? 10,
            ]);
          }
        }

        $tier_uuid = self::generate_uuid();
        $pdo->prepare(
          "INSERT INTO v_call_center_tiers
           (call_center_tier_uuid, domain_uuid, call_center_queue_uuid,
            call_center_agent_uuid, agent_name, queue_name, tier_level, tier_position)
           VALUES (?,?,?,?,?,?,?,?)"
        )->execute([
          $tier_uuid, $domain_uuid,
          $this->call_center_queue_uuid, $agent_uuid,
          $agent_name, $this->queue_name,
          $agent['tier_level']    ?? 1,
          $agent['tier_position'] ?? 1,
        ]);
      }
    }

    return $this->call_center_queue_uuid;
  }

  public function delete()
  {
    if (empty($this->call_center_queue_uuid)) {
      throw new CoreException(400, 'Missing call_center_queue_uuid');
    }
    $pdo   = FpbxDomain::fpbx_db();
    $uuid  = $this->call_center_queue_uuid;
    $label = $this->queue_name;
    // Guard: check IVR menu options and inbound routes referencing this queue
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
    $pdo->prepare("DELETE FROM v_call_center_tiers WHERE call_center_queue_uuid = ?")
        ->execute([$uuid]);
    $pdo->prepare("DELETE FROM v_call_center_queues WHERE call_center_queue_uuid = ?")
        ->execute([$uuid]);
    return true;
  }

  private static function generate_uuid()
  {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }
}
