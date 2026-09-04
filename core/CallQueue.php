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

    // FusionPBX schema varies across versions (e.g. CE's 5.5.7 v_call_center_queues
    // lacks queue_enabled, which 6.6.0 has). Write only columns that actually exist
    // so the same code works on every node — never ALTER the FusionPBX-owned table.
    $existing_cols = $pdo->query(
      "SELECT column_name FROM information_schema.columns WHERE table_name = 'v_call_center_queues'"
    )->fetchAll(\PDO::FETCH_COLUMN);
    $values = array_intersect_key($values, array_flip($existing_cols));

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

    $this->sync_fs_dialplan($pdo);
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
    $this->sync_fs_dialplan($pdo, true);
    return true;
  }

  private function sync_fs_dialplan($pdo, $delete = false)
  {
    $dp_dir  = '/usr/ictcore/etc/freeswitch/dialplan/call_queues';
    $dp_file = $dp_dir . '/' . $this->call_center_queue_uuid . '.xml';

    if ($delete) {
      if (file_exists($dp_file)) @unlink($dp_file);
    } else {
      if (!is_dir($dp_dir)) @mkdir($dp_dir, 0755, true);
      $e        = fn($s) => htmlspecialchars((string)$s, ENT_XML1, 'UTF-8');
      $ext      = $e($this->queue_extension ?? '');
      $nm       = $e($this->queue_name ?? 'queue_' . $this->call_center_queue_uuid);
      $domain   = FpbxDomain::get_domain_name($this->domain_uuid) ?: 'localhost';
      $q_data   = $e($this->queue_name . '@' . $domain);

      $xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n<include>\n";
      $xml .= "  <extension name=\"{$nm}\" continue=\"false\" uuid=\"{$e($this->call_center_queue_uuid)}\">\n";
      $xml .= "    <condition field=\"destination_number\" expression=\"^{$ext}\$\">\n";
      $xml .= "      <action application=\"answer\"/>\n";
      $xml .= "      <action application=\"callcenter\" data=\"{$q_data}\"/>\n";
      $xml .= "    </condition>\n  </extension>\n</include>\n";

      file_put_contents($dp_file, $xml);
    }

    // Regenerate callcenter.conf.xml with all queues from PG so mod_callcenter
    // knows about the queue configuration after reload.
    $this->sync_callcenter_conf($pdo);

    @touch('/etc/freeswitch/dialplan/ictcore.xml');

    try {
      if (class_exists('\\ICT\\Core\\Realtime')) {
        \ICT\Core\Realtime::run_cmd('reloadxml');
        \ICT\Core\Realtime::run_cmd('reload mod_callcenter');
      }
    } catch (\Throwable $ex) {
      Corelog::log("CallQueue reload failed: " . $ex->getMessage(), Corelog::WARNING);
    }
  }

  private function sync_callcenter_conf($pdo)
  {
    $conf_file = '/etc/freeswitch/autoload_configs/callcenter.conf.xml';

    // Load all queues (all domains — mod_callcenter is global)
    $queues = $pdo->query(
      "SELECT q.*, d.domain_name
       FROM v_call_center_queues q
       LEFT JOIN v_domains d ON d.domain_uuid = q.domain_uuid
       ORDER BY q.queue_name"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Load all agents
    $agents = $pdo->query(
      "SELECT * FROM v_call_center_agents ORDER BY agent_name"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Load all tiers
    $tiers = $pdo->query(
      "SELECT * FROM v_call_center_tiers ORDER BY tier_level, tier_position"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $e = fn($s) => htmlspecialchars((string)$s, ENT_XML1, 'UTF-8');

    $xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $xml .= '<configuration name="callcenter.conf" description="CallCenter">' . "\n";
    $xml .= "\t<settings>\n\t</settings>\n";
    $xml .= "\t<queues>\n";

    foreach ($queues as $q) {
      $domain   = $q['domain_name'] ?: 'localhost';
      $q_name   = $e($q['queue_name'] . '@' . $domain);
      $strategy = $e($q['queue_strategy'] ?? 'ring-all');
      $moh      = $e($q['queue_moh_sound'] ?? 'local_stream://moh');
      $max_wait = (int)($q['queue_max_wait_time'] ?? 0);
      $max_no_a = (int)($q['queue_max_wait_time_with_no_agent'] ?? 90);
      $max_no_t = (int)($q['queue_max_wait_time_with_no_agent_time_reached'] ?? 30);
      $discard  = (int)($q['queue_discard_abandoned_after'] ?? 900);
      $time_bs  = $e($q['queue_time_base_score'] ?? 'queue');
      $ann_snd  = $e($q['queue_announce_sound'] ?? '');
      $ann_freq = (int)($q['queue_announce_frequency'] ?? 0);
      $cid_pfx  = $e($q['queue_cid_prefix'] ?? '');
      $tier_rul = ($q['queue_tier_rules_apply'] === 'true') ? 'true' : 'false';
      $tier_wt  = (int)($q['queue_tier_rule_wait_second'] ?? 300);
      $abnd_res = ($q['queue_abandoned_resume_allowed'] === 'true') ? 'true' : 'false';
      $ring_prg = (int)($q['queue_ring_progressively_delay'] ?? 10);

      $xml .= "\t\t<queue name=\"{$q_name}\">\n";
      $xml .= "\t\t\t<param name=\"strategy\" value=\"{$strategy}\"/>\n";
      $xml .= "\t\t\t<param name=\"moh-sound\" value=\"{$moh}\"/>\n";
      $xml .= "\t\t\t<param name=\"time-base-score\" value=\"{$time_bs}\"/>\n";
      $xml .= "\t\t\t<param name=\"max-wait-time\" value=\"{$max_wait}\"/>\n";
      $xml .= "\t\t\t<param name=\"max-wait-time-with-no-agent\" value=\"{$max_no_a}\"/>\n";
      $xml .= "\t\t\t<param name=\"max-wait-time-with-no-agent-time-reached\" value=\"{$max_no_t}\"/>\n";
      $xml .= "\t\t\t<param name=\"discard-abandoned-after\" value=\"{$discard}\"/>\n";
      $xml .= "\t\t\t<param name=\"abandoned-resume-allowed\" value=\"{$abnd_res}\"/>\n";
      $xml .= "\t\t\t<param name=\"tier-rules-apply\" value=\"{$tier_rul}\"/>\n";
      $xml .= "\t\t\t<param name=\"tier-rule-wait-second\" value=\"{$tier_wt}\"/>\n";
      $xml .= "\t\t\t<param name=\"ring-progressively-delay\" value=\"{$ring_prg}\"/>\n";
      if ($ann_snd !== '') {
        $xml .= "\t\t\t<param name=\"announce-sound\" value=\"{$ann_snd}\"/>\n";
        $xml .= "\t\t\t<param name=\"announce-frequency\" value=\"{$ann_freq}\"/>\n";
      }
      if ($cid_pfx !== '') {
        $xml .= "\t\t\t<param name=\"cid-prefix\" value=\"{$cid_pfx}\"/>\n";
      }
      $xml .= "\t\t</queue>\n";
    }

    $xml .= "\t</queues>\n\t<agents>\n";

    foreach ($agents as $a) {
      $a_name    = $e($a['agent_name']);
      $a_type    = $e($a['agent_type'] ?? 'callback');
      $a_contact = $e($a['agent_contact'] ?? '');
      $a_timeout = (int)($a['agent_call_timeout'] ?? 20);
      $a_status  = $e($a['agent_status'] ?? 'Available');
      $a_wrap    = (int)($a['agent_wrap_up_time'] ?? 10);
      $a_max_na  = (int)($a['agent_max_no_answer'] ?? 3);

      $xml .= "\t\t<agent name=\"{$a_name}\" type=\"{$a_type}\"";
      $xml .= " contact=\"{$a_contact}\" status=\"{$a_status}\"";
      $xml .= " call-timeout=\"{$a_timeout}\" wrap-up-time=\"{$a_wrap}\"";
      $xml .= " max-no-answer=\"{$a_max_na}\"/>\n";
    }

    $xml .= "\t</agents>\n\t<tiers>\n";

    foreach ($tiers as $t) {
      // Find queue domain for @domain suffix
      $q_domain = '';
      foreach ($queues as $q) {
        if ($q['call_center_queue_uuid'] === $t['call_center_queue_uuid']) {
          $q_domain = '@' . ($q['domain_name'] ?: 'localhost');
          break;
        }
      }
      $t_queue  = $e($t['queue_name'] . $q_domain);
      $t_agent  = $e($t['agent_name']);
      $t_level  = (int)($t['tier_level'] ?? 1);
      $t_pos    = (int)($t['tier_position'] ?? 1);

      $xml .= "\t\t<tier queue=\"{$t_queue}\" agent=\"{$t_agent}\"";
      $xml .= " level=\"{$t_level}\" position=\"{$t_pos}\"/>\n";
    }

    $xml .= "\t</tiers>\n</configuration>\n";

    file_put_contents($conf_file, $xml);
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
