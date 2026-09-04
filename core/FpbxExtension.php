<?php

namespace ICT\Core;

class FpbxExtension
{
  public $extension_uuid                          = null;
  public $domain_uuid                             = null;
  public $tenant_id                               = null;

  // Identity
  public $extension                               = '';
  public $number_alias                            = null;
  public $password                                = null;
  public $description                             = null;
  public $enabled                                 = true;

  // Caller ID
  public $effective_caller_id_name                = null;
  public $effective_caller_id_number              = null;
  public $outbound_caller_id_name                 = null;
  public $outbound_caller_id_number               = null;
  public $emergency_caller_id_name                = null;
  public $emergency_caller_id_number              = null;

  // Directory
  public $directory_first_name                    = null;
  public $directory_last_name                     = null;
  public $directory_visible                       = true;
  public $directory_exten_visible                 = true;

  // Call behaviour
  public $call_timeout                            = 30;
  public $call_group                              = null;
  public $toll_allow                              = null;
  public $accountcode                             = null;
  public $hold_music                              = null;
  public $user_context                            = null;
  public $do_not_disturb                          = false;
  public $user_record                             = 'none';
  public $absolute_codec_string                   = null;
  public $follow_me_enabled                       = false;
  // Set by FusionPBX when a Follow Me is attached; read-only here, never written back to PG.
  public $follow_me_uuid                          = null;

  // Call forwarding
  public $forward_all_enabled                     = false;
  public $forward_all_destination                 = null;
  public $forward_busy_enabled                    = false;
  public $forward_busy_destination                = null;
  public $forward_no_answer_enabled               = false;
  public $forward_no_answer_destination           = null;
  public $forward_user_not_registered_enabled     = false;
  public $forward_user_not_registered_destination = null;

  // Fax account (auto-provisioned in ICTCore MariaDB)
  public $fax_email                               = null;
  public $extension_type                          = 'voice';

  // User assignment — transient, accepted from API, not stored in PG
  public $user_id                                 = null;
  // Resolved on load from MariaDB account.created_by
  public $linked_user_id                          = null;
  /** Number this extension was loaded with, so a renumber can follow its account row. */
  private $_orig_extension                        = null;

  public function __construct($extension_uuid = null)
  {
    if (!empty($extension_uuid)) {
      $this->extension_uuid = $extension_uuid;
      $this->load();
    }
  }

  private function load()
  {
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT * FROM v_extensions WHERE extension_uuid = :uuid");
    $stmt->execute(['uuid' => $this->extension_uuid]);
    $row = $stmt->fetch();
    if (!$row) {
      throw new CoreException('404', 'Extension not found');
    }
    foreach ($row as $k => $v) {
      if (property_exists($this, $k)) {
        $this->$k = $v;
      }
    }
    // Remember the number this extension was loaded with, so a renumber can still find
    // the ICTCore account row filed under the old one. See sync_ictcore_account().
    $this->_orig_extension = $this->extension;
    // Cross-query linked user from MariaDB account
    if (!empty($this->extension)) {
      $res  = \ICT\Core\DB::query('account',
        "SELECT created_by FROM account WHERE phone = '%phone%' AND type IN ('account','child_account') LIMIT 1",
        ['phone' => $this->extension]);
      $acct = mysqli_fetch_assoc($res);
      $this->linked_user_id = ($acct && !empty($acct['created_by'])) ? (int)$acct['created_by'] : null;
    }
    // Load extension_type from extension_config (MariaDB companion table)
    $cfg = \ICT\Core\DB::query('extension_config',
      "SELECT extension_type, fax_email FROM extension_config WHERE extension_uuid = '%uuid%'",
      ['uuid' => $this->extension_uuid]);
    if ($row = mysqli_fetch_assoc($cfg)) {
      $this->extension_type = $row['extension_type'];
      if (!empty($row['fax_email'])) $this->fax_email = $row['fax_email'];
    }
  }

  public static function search($aFilter = array())
  {
    $tenant_id     = $aFilter['tenant_id'] ?? null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter] : [];
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';
    $pdo    = FpbxDomain::fpbx_db();
    $stmt   = $pdo->prepare(
      "SELECT domain_uuid, extension_uuid, extension, number_alias, password,
              effective_caller_id_name, effective_caller_id_number,
              outbound_caller_id_name, outbound_caller_id_number,
              call_timeout, call_group, do_not_disturb,
              user_record, forward_all_enabled, forward_all_destination,
              follow_me_enabled, enabled, description
       FROM v_extensions
       " . $where . " /* sql-where-v2 */
       ORDER BY extension ASC"
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
    if (empty($this->user_context)) {
      $this->user_context = FpbxDomain::get_domain_name($this->domain_uuid);
    }

    $conflict = FpbxDomain::extension_in_use($this->domain_uuid, $this->extension, $this->extension_uuid);
    if ($conflict !== null) {
      throw new CoreException(409, "Extension number {$this->extension} is already in use by a $conflict in this domain.");
    }

    // B19: Cross-system check — extension must not conflict with a DID in MariaDB
    $b19_tid = $this->tenant_id;
    if (!$b19_tid && $this->domain_uuid) {
      $b19_r  = \ICT\Core\DB::query('account', "SELECT tenant_id FROM tenant WHERE fpbx_domain_uuid = '%uuid%'", ['uuid' => $this->domain_uuid]);
      $b19_tr = mysqli_fetch_assoc($b19_r);
      $b19_tid = $b19_tr ? (int)$b19_tr['tenant_id'] : null;
    }
    if ($b19_tid) {
      $b19_chk = \ICT\Core\DB::query('account',
        "SELECT COUNT(*) AS cnt FROM account WHERE phone = '%phone%' AND tenant_id = %tid% AND type = 'did'",
        ['phone' => $this->extension, 'tid' => (int)$b19_tid]);
      $b19_row = mysqli_fetch_assoc($b19_chk);
      if ((int)($b19_row['cnt'] ?? 0) > 0) {
        throw new CoreException(409, "Extension {$this->extension} is already assigned as a DID number in this tenant.");
      }
    }

    $fields = [
      'extension', 'number_alias', 'password', 'description', 'enabled',
      'effective_caller_id_name', 'effective_caller_id_number',
      'outbound_caller_id_name', 'outbound_caller_id_number',
      'emergency_caller_id_name', 'emergency_caller_id_number',
      'directory_first_name', 'directory_last_name',
      'directory_visible', 'directory_exten_visible',
      'call_timeout', 'call_group', 'toll_allow', 'accountcode',
      'hold_music', 'user_context', 'do_not_disturb',
      'user_record', 'absolute_codec_string', 'follow_me_enabled',
      'forward_all_enabled', 'forward_all_destination',
      'forward_busy_enabled', 'forward_busy_destination',
      'forward_no_answer_enabled', 'forward_no_answer_destination',
      'forward_user_not_registered_enabled', 'forward_user_not_registered_destination',
    ];

    if (empty($this->extension_uuid)) {
      $this->extension_uuid = $this->generate_uuid();
      $all  = array_merge(['domain_uuid', 'extension_uuid'], $fields);
      $cols = implode(', ', $all);
      $vals = implode(', ', array_map(fn($f) => ':' . $f, $all));
      $stmt = $pdo->prepare("INSERT INTO v_extensions ($cols) VALUES ($vals)");
    } else {
      $sets = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
      $stmt = $pdo->prepare("UPDATE v_extensions SET $sets WHERE extension_uuid = :extension_uuid");
    }

    $bool_fields = [
      'enabled', 'directory_visible', 'directory_exten_visible', 'do_not_disturb',
      'follow_me_enabled', 'forward_all_enabled', 'forward_busy_enabled',
      'forward_no_answer_enabled', 'forward_user_not_registered_enabled',
    ];

    $params = ['extension_uuid' => $this->extension_uuid];
    if (strpos($stmt->queryString, ':domain_uuid') !== false) {
      $params['domain_uuid'] = $this->domain_uuid;
    }
    foreach ($fields as $f) {
      $v = $this->$f;
      if (in_array($f, $bool_fields)) {
        $v = ($v === true || $v === 'true' || $v === 1 || $v === '1') ? 'true' : 'false';
      }
      $params[$f] = $v;
    }

    try {
      $stmt->execute($params);
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Extension save failed: ' . $e->getMessage());
    }

    $this->sync_ictcore_account();

    // Upsert extension_type into extension_config
    $ec_tid = $this->tenant_id;
    if (!$ec_tid && $this->domain_uuid) {
      $ec_r  = \ICT\Core\DB::query('account', "SELECT tenant_id FROM tenant WHERE fpbx_domain_uuid = '%uuid%'", ['uuid' => $this->domain_uuid]);
      $ec_tr = mysqli_fetch_assoc($ec_r);
      $ec_tid = $ec_tr ? (int)$ec_tr['tenant_id'] : 0;
    }
    \ICT\Core\DB::query('extension_config',
      "INSERT INTO extension_config (extension_uuid, tenant_id, extension_type, fax_email)
       VALUES ('%uuid%', %tid%, '%type%', '%email%')
       ON DUPLICATE KEY UPDATE extension_type='%type%', fax_email='%email%'",
      ['uuid'  => $this->extension_uuid,
       'tid'   => (int)$ec_tid,
       'type'  => $this->extension_type ?: 'voice',
       'email' => $this->fax_email ?? '']);

    $this->sync_fs_directory(false);
    try { \ICT\Core\Realtime::run_cmd('reloadxml'); } catch (\Throwable $e) { /* non-fatal */ }

    return $this->extension_uuid;
  }

  public function delete()
  {
    $tid = $this->tenant_id;
    if (!$tid && $this->domain_uuid) {
      $r = \ICT\Core\DB::query('account', "SELECT tenant_id FROM tenant WHERE fpbx_domain_uuid = '%uuid%'", ['uuid' => $this->domain_uuid]);
      $tr = mysqli_fetch_assoc($r);
      $tid = $tr ? (int)$tr['tenant_id'] : null;
    }
    $res = \ICT\Core\DB::query('account',
      "SELECT account_id FROM account WHERE phone = '%phone%' AND type IN ('account','child_account') LIMIT 1",
      ['phone' => $this->extension]);
    $acct = mysqli_fetch_assoc($res);
    if ($acct) {
      \ICT\Core\DB::query('account',
        "UPDATE account SET linkdid_id = NULL, type = 'account' WHERE linkdid_id = %id%",
        ['id' => $acct['account_id']]);
      \ICT\Core\DB::query('account',
        "DELETE FROM account WHERE account_id = %id%",
        ['id' => $acct['account_id']]);
    }
    \ICT\Core\DB::query('extension_config',
      "DELETE FROM extension_config WHERE extension_uuid = '%uuid%'",
      ['uuid' => $this->extension_uuid]);
    $pdo = FpbxDomain::fpbx_db();
    $pdo->prepare("DELETE FROM v_extensions WHERE extension_uuid = :uuid")
        ->execute(['uuid' => $this->extension_uuid]);

    $this->sync_fs_directory(true);

    // Reload FS XML so the deleted extension is no longer served from the directory
    try { \ICT\Core\Realtime::run_cmd('reloadxml'); } catch (\Throwable $e) { /* non-fatal */ }

    return true;
  }

  public function get_id() { return $this->extension_uuid; }

  private function sync_ictcore_account(): void
  {
    $tid = $this->tenant_id;
    if (!$tid && $this->domain_uuid) {
      $r  = \ICT\Core\DB::query('account', "SELECT tenant_id FROM tenant WHERE fpbx_domain_uuid = '%uuid%'", ['uuid' => $this->domain_uuid]);
      $tr = mysqli_fetch_assoc($r);
      $tid = $tr ? (int)$tr['tenant_id'] : null;
    }
    $res  = \ICT\Core\DB::query('account',
      "SELECT account_id FROM account WHERE phone = '%phone%' AND type IN ('account','child_account') LIMIT 1",
      ['phone' => $this->extension]);
    $acct     = mysqli_fetch_assoc($res);

    // A renumbered extension still owns the account row filed under its previous number.
    // Follow it, or the insert below would try to add a second row with the same username
    // and hit the unique (type, username) key -- renumbering used to fail with a raw 1062.
    if (!$acct && !empty($this->_orig_extension) && $this->_orig_extension !== $this->extension) {
      $res_old = \ICT\Core\DB::query('account',
        "SELECT account_id FROM account WHERE phone = '%phone%' AND type IN ('account','child_account') LIMIT 1",
        ['phone' => $this->_orig_extension]);
      $acct = mysqli_fetch_assoc($res_old);
    }

    $username = trim($this->effective_caller_id_name ?: $this->extension);
    $email    = $this->fax_email ?: null;
    if ($acct) {
      if ($this->user_id !== null) {
        \ICT\Core\DB::query('account',
          "UPDATE account SET username = '%username%', phone = '%phone%', email = '%email%', created_by = %uid% WHERE account_id = %id%",
          ['username' => $username, 'phone' => $this->extension, 'email' => $email, 'uid' => (int)$this->user_id, 'id' => $acct['account_id']]);
      } else {
        \ICT\Core\DB::query('account',
          "UPDATE account SET username = '%username%', phone = '%phone%', email = '%email%' WHERE account_id = %id%",
          ['username' => $username, 'phone' => $this->extension, 'email' => $email, 'id' => $acct['account_id']]);
      }
    } else {
      if ($this->user_id !== null) {
        \ICT\Core\DB::query('account',
          "INSERT INTO account (tenant_id, type, username, phone, email, active, created_by)
           VALUES (%tid%, 'account', '%username%', '%phone%', '%email%', 1, %uid%)",
          ['tid' => $tid, 'username' => $username, 'phone' => $this->extension, 'email' => $email, 'uid' => (int)$this->user_id]);
      } else {
        \ICT\Core\DB::query('account',
          "INSERT INTO account (tenant_id, type, username, phone, email, active)
           VALUES (%tid%, 'account', '%username%', '%phone%', '%email%', 1)",
          ['tid' => $tid, 'username' => $username, 'phone' => $this->extension, 'email' => $email]);
      }
    }
  }

  private function sync_fs_directory(bool $delete = false): void
  {
    global $path_etc;
    $ext_dir    = $path_etc . '/freeswitch/directory/fpbx_extensions';
    $domain_file = '/etc/freeswitch/directory/fpbx_webrtc.xml';
    $ext_file   = $ext_dir . '/' . $this->extension_uuid . '.xml';

    if ($delete) {
      if (file_exists($ext_file)) {
        @unlink($ext_file);
      }
      // Touch domain file so FS re-expands the glob on next reloadxml
      if (file_exists($domain_file)) {
        touch($domain_file);
      }
      return;
    }

    // Only voice extensions register via WebRTC; fax extensions use T.38
    if (($this->extension_type ?: 'voice') !== 'voice') {
      if (file_exists($ext_file)) {
        @unlink($ext_file);
      }
      // Touch domain file so FS re-expands the glob on next reloadxml
      if (file_exists($domain_file)) {
        touch($domain_file);
      }
      return;
    }

    // Ensure per-extension directory exists
    if (!is_dir($ext_dir)) {
      mkdir($ext_dir, 0755, true);
    }

    // Create domain wrapper file once if missing
    if (!file_exists($domain_file)) {
      file_put_contents($domain_file,
        '<include>' . "\n" .
        '  <domain name="$${local_ip_v4}">' . "\n" .
        '    <params>' . "\n" .
        '      <param name="dial-string" value="{presence_id=${dialed_user}@${dialed_domain}}${sofia_contact(*/${dialed_user}@${dialed_domain})}"/>' . "\n" .
        '    </params>' . "\n" .
        '    <groups><group name="default"><users>' . "\n" .
        '      <X-PRE-PROCESS cmd="include" data="' . $ext_dir . '/*.xml"/>' . "\n" .
        '    </users></group></groups>' . "\n" .
        '  </domain>' . "\n" .
        '</include>' . "\n"
      );
    }

    $ext  = htmlspecialchars($this->extension, ENT_XML1);
    $pass = htmlspecialchars($this->password ?? '', ENT_XML1);
    $name = htmlspecialchars($this->effective_caller_id_name ?: $this->extension, ENT_XML1);

    // Follow Me overrides how this user is reached: instead of the domain's default
    // sofia_contact lookup, ring the configured destinations. Absent or disabled, the
    // param is omitted and the user inherits the domain default.
    $follow_me = $this->follow_me_dial_string();
    $dial_param = ($follow_me === null) ? ''
      : '    <param name="dial-string" value="' . htmlspecialchars($follow_me, ENT_XML1) . '"/>' . "\n";

    file_put_contents($ext_file,
      '<user id="' . $ext . '">' . "\n" .
      '  <params>' . "\n" .
      '    <param name="password" value="' . $pass . '"/>' . "\n" .
      $dial_param .
      '  </params>' . "\n" .
      '  <variables>' . "\n" .
      '    <variable name="user_context" value="ictcore"/>' . "\n" .
      '    <variable name="effective_caller_id_name" value="' . $name . '"/>' . "\n" .
      '    <variable name="effective_caller_id_number" value="' . $ext . '"/>' . "\n" .
      '  </variables>' . "\n" .
      '</user>' . "\n"
    );

    // Touch domain file so FS re-expands the *.xml glob on next reloadxml.
    // When implementing per-tenant domains, change $domain_file to the tenant's domain wrapper.
    touch($domain_file);
  }

  /**
   * Rewrite this extension's directory entry and reload FreeSWITCH, without touching the
   * database. Follow Me lives in its own tables, so saving one has to refresh the owning
   * extension's <user> or the new dial-string never reaches FreeSWITCH.
   */
  public function resync_freeswitch(): void
  {
    $this->sync_fs_directory();
    try {
      Realtime::run_cmd('reloadxml');
    } catch (\Throwable $e) {
      Corelog::log('Follow Me reloadxml failed: ' . $e->getMessage(), Corelog::WARNING);
    }
  }

  /**
   * Build the FreeSWITCH dial-string that implements Follow Me for this extension.
   *
   * Follow Me was configured in the UI and stored in v_follow_me / v_follow_me_destinations
   * but never reached FreeSWITCH, so it did nothing. Rather than a standalone dialplan
   * extension, it belongs on the directory <user>: overriding that user's dial-string means
   * every path that reaches the extension -- a direct dial, a ring group, an IVR transfer --
   * follows the same list, which is what callers expect.
   *
   * Each destination becomes a bridge leg carrying its own delay and timeout. Legs are
   * comma-joined so FreeSWITCH starts them in parallel and leg_delay_start staggers them,
   * which is how a delay of 0 rings immediately and a later one rings only if the call is
   * still alive. Internal numbers ring the registered device directly; anything else goes
   * back through the dialplan as a loopback so outbound routes and billing still apply.
   *
   * @return string|null null when Follow Me is off or has no usable destination
   */
  private function follow_me_dial_string(): ?string
  {
    if (empty($this->follow_me_uuid)) {
      return null;
    }

    try {
      $pdo  = FpbxDomain::fpbx_db();
      // Gate on the follow_me row's own flag: that is what the Follow Me UI writes.
      // v_extensions.follow_me_enabled is never set by FollowMe::save(), so relying on
      // the extension column would leave Follow Me permanently off.
      $stmt = $pdo->prepare(
        "SELECT d.follow_me_destination, d.follow_me_delay, d.follow_me_timeout
           FROM v_follow_me f
           JOIN v_follow_me_destinations d ON d.follow_me_uuid = f.follow_me_uuid
          WHERE f.follow_me_uuid = :uuid
            AND f.follow_me_enabled = 'true'
            AND d.follow_me_destination IS NOT NULL
            AND d.follow_me_destination <> ''
          ORDER BY d.follow_me_order NULLS LAST, d.follow_me_delay"
      );
      $stmt->execute(['uuid' => $this->follow_me_uuid]);
      $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
      Corelog::log('Follow Me lookup failed: ' . $e->getMessage(), Corelog::WARNING);
      return null;
    }
    if (empty($rows)) {
      return null;
    }

    $dial_domain = FpbxDomain::fs_directory_domain(
      FpbxDomain::get_domain_name($this->domain_uuid)
    );

    $legs = [];
    foreach ($rows as $r) {
      $dest = trim((string)$r['follow_me_destination']);
      // Same validation the dialplan-facing APIs use; a bad value would land in a dial string.
      if ($dest === '' || !preg_match('/^\+?[0-9*#]{2,20}$/', $dest)) {
        continue;
      }
      $delay   = max(0, (int)$r['follow_me_delay']);
      $timeout = (int)$r['follow_me_timeout'] ?: 30;
      $target  = $this->is_local_extension($dest)
        ? sprintf('user/%s@%s', $dest, $dial_domain)
        : sprintf('loopback/%s/ictcore', $dest);
      $legs[] = sprintf('[leg_delay_start=%d,leg_timeout=%d]%s', $delay, $timeout, $target);
    }

    return empty($legs) ? null : '{ignore_early_media=true}' . implode(',', $legs);
  }

  /** Whether a Follow Me destination is an extension in this extension's own domain. */
  private function is_local_extension(string $dest): bool
  {
    try {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare(
        "SELECT 1 FROM v_extensions WHERE domain_uuid = :d AND extension = :e LIMIT 1"
      );
      $stmt->execute(['d' => $this->domain_uuid, 'e' => $dest]);
      return (bool)$stmt->fetchColumn();
    } catch (\Throwable $e) {
      return false;
    }
  }

  /** PostgreSQL hands booleans back as 't'/'f'; the API may send real bools or '1'. */
  private static function is_true($v): bool
  {
    return $v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true';
  }

  private function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  }

  public function __get($f)     { return property_exists($this, $f) ? $this->$f : null; }
  public function __set($f, $v) { if (property_exists($this, $f)) $this->$f = $v; }
  public function __isset($f)   { return isset($this->$f); }
}
