<?php

namespace ICT\Core;

use PDO;

class IvrMenu
{
  public $ivr_menu_uuid           = null;
  public $domain_uuid             = null;
  public $tenant_id               = null;
  public $ivr_menu_name           = null;
  public $ivr_menu_extension      = null;
  public $ivr_menu_language       = 'en';
  public $ivr_menu_greet_long     = null;
  public $ivr_menu_greet_short    = null;
  public $ivr_menu_invalid_sound  = null;
  public $ivr_menu_exit_sound     = null;
  public $ivr_menu_timeout        = 10000;
  public $ivr_menu_exit_app       = null;
  public $ivr_menu_exit_data      = null;
  public $ivr_menu_max_failures   = 3;
  public $ivr_menu_max_timeouts   = 3;
  public $ivr_menu_direct_dial    = false;
  public $ivr_menu_context        = null;
  public $ivr_menu_enabled        = true;
  public $ivr_menu_description    = null;
  public $options                 = [];

  public function __construct($ivr_menu_uuid = null)
  {
    if (!empty($ivr_menu_uuid)) {
      $this->ivr_menu_uuid = $ivr_menu_uuid;
      $this->load();
    }
  }

  private function load()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_ivr_menus WHERE ivr_menu_uuid = :uuid");
    $stmt->execute(['uuid' => $this->ivr_menu_uuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      throw new CoreException(404, 'IVR Menu not found');
    }
    foreach ($row as $k => $v) {
      if (property_exists($this, $k)) $this->$k = $v;
    }
    $this->options = $this->fetch_options();
  }

  private function fetch_options()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT * FROM v_ivr_menu_options WHERE ivr_menu_uuid = :uuid ORDER BY ivr_menu_option_order ASC"
    );
    $stmt->execute(['uuid' => $this->ivr_menu_uuid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
      "SELECT domain_uuid, ivr_menu_uuid, ivr_menu_name, ivr_menu_extension, ivr_menu_language,
              ivr_menu_timeout, ivr_menu_enabled, ivr_menu_description
       FROM v_ivr_menus
       " . $where . " /* sql-where-v2 */
       ORDER BY ivr_menu_name ASC"
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
    if (empty($this->ivr_menu_context)) {
      $this->ivr_menu_context = FpbxDomain::get_domain_name($this->domain_uuid);
    }

    $conflict = FpbxDomain::extension_in_use($this->domain_uuid, $this->ivr_menu_extension, $this->ivr_menu_uuid ?? null);
    if ($conflict !== null) {
      throw new CoreException(409, "Extension number {$this->ivr_menu_extension} is already in use by a $conflict in this domain.");
    }

    $bool_fields = ['ivr_menu_direct_dial', 'ivr_menu_enabled'];
    $fields = [
      'ivr_menu_name', 'ivr_menu_extension', 'ivr_menu_language',
      'ivr_menu_greet_long', 'ivr_menu_greet_short',
      'ivr_menu_invalid_sound', 'ivr_menu_exit_sound',
      'ivr_menu_timeout', 'ivr_menu_exit_app', 'ivr_menu_exit_data',
      'ivr_menu_max_failures', 'ivr_menu_max_timeouts',
      'ivr_menu_direct_dial', 'ivr_menu_context',
      'ivr_menu_enabled', 'ivr_menu_description',
    ];

    $params = [];
    foreach ($fields as $f) {
      $v = $this->$f;
      if (in_array($f, $bool_fields)) {
        $v = ($v === true || $v === 'true' || $v === 1 || $v === '1') ? 'true' : 'false';
      }
      $params[$f] = $v;
    }

    try {
      if (empty($this->ivr_menu_uuid)) {
        $this->ivr_menu_uuid = $this->generate_uuid();
        $params['domain_uuid']   = $this->domain_uuid;
        $params['ivr_menu_uuid'] = $this->ivr_menu_uuid;
        $all  = array_keys($params);
        $cols = implode(', ', $all);
        $vals = implode(', ', array_map(fn($f) => ':' . $f, $all));
        $pdo->prepare("INSERT INTO v_ivr_menus ($cols) VALUES ($vals)")->execute($params);
      } else {
        $params['ivr_menu_uuid'] = $this->ivr_menu_uuid;
        $sets = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
        $pdo->prepare("UPDATE v_ivr_menus SET $sets WHERE ivr_menu_uuid = :ivr_menu_uuid")->execute($params);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'IVR Menu save failed: ' . $e->getMessage());
    }

    $this->save_options($pdo);
    $this->sync_fs_dialplan();
    return $this->ivr_menu_uuid;
  }

  private function save_options($pdo)
  {
    $pdo->prepare("DELETE FROM v_ivr_menu_options WHERE ivr_menu_uuid = :uuid")
        ->execute(['uuid' => $this->ivr_menu_uuid]);

    if (!is_array($this->options)) return;

    foreach ($this->options as $idx => $opt) {
      $opt = (array)$opt;
      $uuid = !empty($opt['ivr_menu_option_uuid']) ? $opt['ivr_menu_option_uuid'] : $this->generate_uuid();
      $enabled = ($opt['ivr_menu_option_enabled'] ?? true);
      $enabled = ($enabled === true || $enabled === 'true' || $enabled === 1 || $enabled === '1') ? 'true' : 'false';
      try {
        $pdo->prepare(
          "INSERT INTO v_ivr_menu_options
           (ivr_menu_option_uuid, ivr_menu_uuid, domain_uuid,
            ivr_menu_option_digits, ivr_menu_option_action, ivr_menu_option_param,
            ivr_menu_option_order, ivr_menu_option_description, ivr_menu_option_enabled)
           VALUES (:uuid, :menu_uuid, :domain_uuid,
                   :digits, :action, :param,
                   :order, :desc, :enabled)"
        )->execute([
          'uuid'        => $uuid,
          'menu_uuid'   => $this->ivr_menu_uuid,
          'domain_uuid' => $this->domain_uuid,
          'digits'      => $opt['ivr_menu_option_digits']      ?? '',
          'action'      => $opt['ivr_menu_option_action']      ?? 'transfer',
          'param'       => $opt['ivr_menu_option_param']       ?? '',
          'order'       => $opt['ivr_menu_option_order']       ?? (($idx + 1) * 10),
          'desc'        => $opt['ivr_menu_option_description'] ?? '',
          'enabled'     => $enabled,
        ]);
      } catch (\PDOException $e) {
        throw new CoreException(500, 'IVR option save failed: ' . $e->getMessage());
      }
    }
  }

  public function delete()
  {
    $pdo   = FpbxDomain::fpbx_db();
    $uuid  = $this->ivr_menu_uuid;
    $label = $this->ivr_menu_name ?? $uuid;
    // Guard: check other IVR menus and inbound routes referencing this IVR menu
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
    $pdo->prepare("DELETE FROM v_ivr_menu_options WHERE ivr_menu_uuid = :uuid")
        ->execute(['uuid' => $uuid]);
    $pdo->prepare("DELETE FROM v_ivr_menus WHERE ivr_menu_uuid = :uuid")
        ->execute(['uuid' => $uuid]);
    $this->sync_fs_dialplan(true);
    return true;
  }

  private function sync_fs_dialplan($delete = false)
  {
    $dp_dir   = '/usr/ictcore/etc/freeswitch/dialplan/ivr_menus';
    $dp_file  = $dp_dir . '/' . $this->ivr_menu_uuid . '.xml';
    $ivr_dir  = '/etc/freeswitch/ivr_menus';
    $ivr_file = $ivr_dir . '/ivr_' . $this->ivr_menu_uuid . '.xml';
    $menu_name = 'ivr_' . $this->ivr_menu_uuid;

    if ($delete) {
      if (file_exists($dp_file))  @unlink($dp_file);
      if (file_exists($ivr_file)) @unlink($ivr_file);
    } else {
      if (!is_dir($dp_dir)) @mkdir($dp_dir, 0755, true);
      $e   = fn($s) => htmlspecialchars((string)$s, ENT_XML1, 'UTF-8');
      $ext = $e($this->ivr_menu_extension);
      $nm  = $e($this->ivr_menu_name);
      $mn  = $e($menu_name);

      $dp_xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n<include>\n";
      $dp_xml .= "  <extension name=\"{$nm}\" continue=\"false\" uuid=\"{$e($this->ivr_menu_uuid)}\">\n";
      $dp_xml .= "    <condition field=\"destination_number\" expression=\"^{$ext}\$\">\n";
      $dp_xml .= "      <action application=\"answer\"/>\n";
      $dp_xml .= "      <action application=\"ivr\" data=\"{$mn}\"/>\n";
      $dp_xml .= "    </condition>\n  </extension>\n</include>\n";
      file_put_contents($dp_file, $dp_xml);

      if (!is_dir($ivr_dir)) @mkdir($ivr_dir, 0755, true);
      $greet_long  = $e($this->ivr_menu_greet_long  ?: 'ivr/ivr-welcome_to_freeswitch.wav');
      $greet_short = $e($this->ivr_menu_greet_short ?: 'ivr/ivr-please_enter_the_extension_number.wav');
      $invalid     = $e($this->ivr_menu_invalid_sound ?: 'ivr/ivr-that_was_an_invalid_entry.wav');
      $exit_snd    = $e($this->ivr_menu_exit_sound  ?: 'ivr/ivr-thank_you.wav');
      $timeout     = (int)($this->ivr_menu_timeout  ?: 10000);
      $max_fail    = (int)($this->ivr_menu_max_failures ?: 3);
      $max_to      = (int)($this->ivr_menu_max_timeouts ?: 3);

      $ivr_xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n<include>\n";
      $ivr_xml .= "  <menu name=\"{$mn}\"\n";
      $ivr_xml .= "      greet-long=\"{$greet_long}\"\n";
      $ivr_xml .= "      greet-short=\"{$greet_short}\"\n";
      $ivr_xml .= "      invalid-sound=\"{$invalid}\"\n";
      $ivr_xml .= "      exit-sound=\"{$exit_snd}\"\n";
      $ivr_xml .= "      timeout=\"{$timeout}\"\n";
      $ivr_xml .= "      max-failures=\"{$max_fail}\"\n";
      $ivr_xml .= "      max-timeouts=\"{$max_to}\"\n";
      $ivr_xml .= "      digit-len=\"1\">\n";

      $opts = is_array($this->options) ? $this->options : $this->fetch_options();
      foreach ($opts as $opt) {
        $opt = (array)$opt;
        if (($opt['ivr_menu_option_enabled'] ?? 'true') === 'false') continue;
        $digits = $e($opt['ivr_menu_option_digits'] ?? '');
        $action = $opt['ivr_menu_option_action'] ?? 'transfer';
        $param  = $opt['ivr_menu_option_param']  ?? '';
        $dest   = preg_replace('/\s+XML\s+\S+$/i', '', $param) ?: $param;
        if ($action === 'transfer') {
          $p = $e("transfer {$dest} XML ictcore");
          $ivr_xml .= "    <entry action=\"menu-exec-app\" digits=\"{$digits}\" param=\"{$p}\"/>\n";
        } elseif ($action === 'hangup') {
          $ivr_xml .= "    <entry action=\"menu-exit\" digits=\"{$digits}\"/>\n";
        } else {
          $ivr_xml .= "    <entry action=\"menu-exec-app\" digits=\"{$digits}\" param=\"{$e($param)}\"/>\n";
        }
      }
      $ivr_xml .= "  </menu>\n</include>\n";
      file_put_contents($ivr_file, $ivr_xml);
    }

    @touch('/etc/freeswitch/dialplan/ictcore.xml');

    try {
      if (class_exists('\\ICT\\Core\\Realtime')) {
        \ICT\Core\Realtime::run_cmd('reloadxml');
      }
    } catch (\Throwable $ex) {
      Corelog::log("IVR reloadxml failed: " . $ex->getMessage(), Corelog::WARNING);
    }
  }

  public function get_id() { return $this->ivr_menu_uuid; }

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
