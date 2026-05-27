<?php

namespace ICT\Core;

use PDO;

class InboundRoute
{
  public $destination_uuid           = null;
  public $dialplan_uuid              = null;
  public $domain_uuid                = null;
  public $tenant_id                  = null;
  public $destination_number         = null;  // DID number
  public $destination_number_regex   = null;  // optional regex (auto-built if empty)
  public $destination_type           = 'inbound';
  public $destination_app            = 'transfer';
  public $destination_data           = null;  // transfer target, e.g. "1001 XML <pbx-host>"
  public $destination_context        = 'public';
  public $destination_order          = 100;
  public $destination_enabled        = true;
  public $destination_description    = null;
  // For UI convenience: the resolved target (extension / ring group number / etc.)
  public $destination_target_type    = 'extension';  // extension | ring_group | ivr | voicemail
  public $destination_target         = null;          // the actual extension/number to transfer to

  public function __construct($destination_uuid = null)
  {
    if (!empty($destination_uuid)) {
      $this->destination_uuid = $destination_uuid;
      $this->load();
    }
  }

  private function load()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_destinations WHERE destination_uuid = :uuid");
    $stmt->execute(['uuid' => $this->destination_uuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      throw new CoreException(404, 'Inbound Route not found');
    }
    foreach ($row as $k => $v) {
      if (property_exists($this, $k)) $this->$k = $v;
    }
    // Reverse-parse target from destination_data: "TARGET XML CONTEXT"
    if (!empty($this->destination_data)) {
      $parts = explode(' ', $this->destination_data);
      $this->destination_target = $parts[0] ?? null;
      if (!empty($this->destination_target)) {
        if (strpos($this->destination_target, '*99') === 0) {
          $this->destination_target_type = 'voicemail';
          $this->destination_target = substr($this->destination_target, 3);
        } else {
          // Cross-reference PG tables to determine correct type
          $this->destination_target_type = $this->detect_target_type(
            $this->destination_target, $this->domain_uuid, $pdo
          );
        }
      }
    }
  }

  private function detect_target_type(string $target, ?string $domain_uuid, PDO $pdo): string
  {
    if (empty($target) || empty($domain_uuid)) return 'extension';

    $stmt = $pdo->prepare(
      "SELECT 1 FROM v_ivr_menus WHERE ivr_menu_extension = :ext AND domain_uuid = :dom LIMIT 1"
    );
    $stmt->execute(['ext' => $target, 'dom' => $domain_uuid]);
    if ($stmt->fetch()) return 'ivr';

    $stmt = $pdo->prepare(
      "SELECT 1 FROM v_ring_groups WHERE ring_group_extension = :ext AND domain_uuid = :dom LIMIT 1"
    );
    $stmt->execute(['ext' => $target, 'dom' => $domain_uuid]);
    if ($stmt->fetch()) return 'ring_group';

    return 'extension';
  }

  public static function search($aFilter = [])
  {
    $tenant_id   = $aFilter['tenant_id'] ?? null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter] : [];
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';
    $pdo         = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT destination_uuid, destination_number, destination_type,
              destination_app, destination_data, destination_context,
              destination_order, destination_enabled, destination_description
       FROM v_destinations
       WHERE " . ($domain_filter ? "domain_uuid = :domain_uuid AND " : "") . "destination_type = 'inbound'
       ORDER BY destination_number ASC" /* params-fix-v3 */
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    $domain_name = FpbxDomain::get_domain_name($this->domain_uuid);

    // Build transfer target from target_type + target
    if (!empty($this->destination_target)) {
      $transfer_target = $this->destination_target;
      if ($this->destination_target_type === 'voicemail') {
        $transfer_target = '*99' . $this->destination_target;
      }
      $this->destination_data = $transfer_target . ' XML ' . $domain_name;
    }

    // Always rebuild regex from destination_number to ensure correct +? prefix
    $normalized = ltrim($this->destination_number, '+');
    $this->destination_number_regex = '^\+?' . preg_quote($normalized, '/') . '$';

    // Generate UUIDs
    if (empty($this->destination_uuid)) {
      $this->destination_uuid = $this->generate_uuid();
    }
    if (empty($this->dialplan_uuid)) {
      $this->dialplan_uuid = $this->generate_uuid();
    }

    $enabled_str = ($this->destination_enabled === true || $this->destination_enabled === 'true'
                   || $this->destination_enabled === 1 || $this->destination_enabled === '1')
                 ? 'true' : 'false';

    $dest_fields = [
      'destination_uuid', 'dialplan_uuid', 'domain_uuid',
      'destination_type', 'destination_number', 'destination_number_regex',
      'destination_context', 'destination_app', 'destination_data',
      'destination_order', 'destination_enabled', 'destination_description',
    ];
    $dest_values = [
      'destination_uuid'       => $this->destination_uuid,
      'dialplan_uuid'          => $this->dialplan_uuid,
      'domain_uuid'            => $this->domain_uuid,
      'destination_type'       => 'inbound',
      'destination_number'     => $this->destination_number,
      'destination_number_regex' => $this->destination_number_regex,
      'destination_context'    => $this->destination_context ?: 'public',
      'destination_app'        => 'transfer',
      'destination_data'       => $this->destination_data,
      'destination_order'      => $this->destination_order ?: 100,
      'destination_enabled'    => $enabled_str,
      'destination_description'=> $this->destination_description,
    ];

    // Build FreeSWITCH XML dialplan
    $dialplan_xml = $this->build_dialplan_xml(
      $this->destination_uuid,
      $this->destination_number,
      $this->destination_number_regex,
      $this->destination_data,
      $this->domain_uuid,
      $domain_name
    );

    $dialplan_fields = [
      'dialplan_uuid', 'domain_uuid', 'dialplan_context',
      'dialplan_name', 'dialplan_number', 'dialplan_destination',
      'dialplan_continue', 'dialplan_xml', 'dialplan_order',
      'dialplan_enabled', 'dialplan_description',
    ];
    $dialplan_values = [
      'dialplan_uuid'        => $this->dialplan_uuid,
      'domain_uuid'          => $this->domain_uuid,
      'dialplan_context'     => 'public',
      'dialplan_name'        => $this->destination_number,
      'dialplan_number'      => $this->destination_number,
      'dialplan_destination' => 'true',
      'dialplan_continue'    => 'false',
      'dialplan_xml'         => $dialplan_xml,
      'dialplan_order'       => $this->destination_order ?: 100,
      'dialplan_enabled'     => $enabled_str,
      'dialplan_description' => $this->destination_description,
    ];

    try {
      // Upsert destination
      $existing = $pdo->prepare("SELECT destination_uuid FROM v_destinations WHERE destination_uuid = :uuid");
      $existing->execute(['uuid' => $this->destination_uuid]);
      if ($existing->fetch()) {
        $sets = implode(', ', array_map(fn($f) => "$f = :$f", array_keys($dest_values)));
        $pdo->prepare("UPDATE v_destinations SET $sets WHERE destination_uuid = :destination_uuid")
            ->execute($dest_values);
      } else {
        $cols = implode(', ', array_keys($dest_values));
        $vals = implode(', ', array_map(fn($f) => ':' . $f, array_keys($dest_values)));
        $pdo->prepare("INSERT INTO v_destinations ($cols) VALUES ($vals)")->execute($dest_values);
      }

      // Upsert dialplan
      $existing_dp = $pdo->prepare("SELECT dialplan_uuid FROM v_dialplans WHERE dialplan_uuid = :uuid");
      $existing_dp->execute(['uuid' => $this->dialplan_uuid]);
      if ($existing_dp->fetch()) {
        $sets = implode(', ', array_map(fn($f) => "$f = :$f", array_keys($dialplan_values)));
        $pdo->prepare("UPDATE v_dialplans SET $sets WHERE dialplan_uuid = :dialplan_uuid")
            ->execute($dialplan_values);
      } else {
        $cols = implode(', ', array_keys($dialplan_values));
        $vals = implode(', ', array_map(fn($f) => ':' . $f, array_keys($dialplan_values)));
        $pdo->prepare("INSERT INTO v_dialplans ($cols) VALUES ($vals)")->execute($dialplan_values);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Inbound Route save failed: ' . $e->getMessage());
    }

    // Build static XML for FreeSWITCH using ictcore context, then write file
    $static_transfer_data = preg_replace('/\s+XML\s+\S+$/', ' XML ictcore', $this->destination_data);
    if ($static_transfer_data === $this->destination_data) {
      // destination_data had no "XML <context>" suffix — append ictcore context
      $static_transfer_data = $this->destination_data . ' XML ictcore';
    }
    $static_xml = $this->build_dialplan_xml(
      $this->destination_uuid,
      $this->destination_number,
      $this->destination_number_regex,
      $static_transfer_data,
      $this->domain_uuid,
      $domain_name
    );
    $this->sync_fs_dialplan($static_xml);

    return $this->destination_uuid;
  }

  private function build_dialplan_xml($dest_uuid, $did, $regex, $transfer_data, $domain_uuid, $domain_name)
  {
    $did_safe    = htmlspecialchars($did, ENT_XML1);
    $regex_safe  = htmlspecialchars($regex, ENT_XML1);
    $data_safe   = htmlspecialchars($transfer_data, ENT_XML1);
    $uuid_safe   = htmlspecialchars($this->dialplan_uuid, ENT_XML1);
    $domain_safe = htmlspecialchars($domain_name, ENT_XML1);
    $duuid_safe  = htmlspecialchars($domain_uuid, ENT_XML1);

    return "<extension name=\"{$did_safe}\" continue=\"false\" uuid=\"{$uuid_safe}\">\n"
         . "  <condition field=\"destination_number\" expression=\"{$regex_safe}\">\n"
         . "    <action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n"
         . "    <action application=\"set\" data=\"domain_uuid={$duuid_safe}\" inline=\"true\"/>\n"
         . "    <action application=\"set\" data=\"domain_name={$domain_safe}\" inline=\"true\"/>\n"
         . "    <action application=\"transfer\" data=\"{$data_safe}\"/>\n"
         . "  </condition>\n"
         . "</extension>";
  }

  public function delete()
  {
    $pdo = FpbxDomain::fpbx_db();
    if (!empty($this->dialplan_uuid)) {
      $pdo->prepare("DELETE FROM v_dialplans WHERE dialplan_uuid = :uuid")
          ->execute(['uuid' => $this->dialplan_uuid]);
    }
    $pdo->prepare("DELETE FROM v_destinations WHERE destination_uuid = :uuid")
        ->execute(['uuid' => $this->destination_uuid]);
    $this->sync_fs_dialplan(null, true);
    return true;
  }

  public function get_id() { return $this->destination_uuid; }

  private function sync_fs_dialplan($dialplan_xml = null, $delete = false)
  {
    $dir  = '/usr/ictcore/etc/freeswitch/dialplan/public';
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$this->destination_number);
    $file = $dir . '/' . $safe . '.xml';

    if ($delete) {
      if (file_exists($file)) {
        @unlink($file);
        Corelog::log("Inbound route XML removed: $file", Corelog::CRUD);
      }
    } else {
      if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
      }
      $xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
      $xml .= '<include>' . "\n";
      $xml .= '  ' . $dialplan_xml . "\n";
      $xml .= '</include>' . "\n";
      $written = file_put_contents($file, $xml);
      if ($written === false) {
        Corelog::log("Inbound route XML write FAILED: $file", Corelog::ERROR);
        throw new CoreException(500, "Failed to write dialplan XML to $file — check file permissions");
      }
      Corelog::log("Inbound route XML written: $file", Corelog::CRUD);
    }

    // Touch the parent ictcore.xml so FS re-expands the X-PRE-PROCESS glob on reloadxml
    @touch('/etc/freeswitch/dialplan/ictcore.xml');

    try {
      if (class_exists('\\ICT\\Core\\Realtime')) {
        \ICT\Core\Realtime::run_cmd('reloadxml');
      }
    } catch (\Throwable $e) {
      Corelog::log("reloadxml failed: " . $e->getMessage(), Corelog::WARNING);
    }
  }

  private function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
      mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
  }

  public function __get($f)     { return property_exists($this,$f) ? $this->$f : null; }
  public function __set($f,$v)  { if (property_exists($this,$f)) $this->$f = $v; }
  public function __isset($f)   { return isset($this->$f); }
}
