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
    $username = trim($this->effective_caller_id_name ?: $this->extension);
    $email    = $this->fax_email ?: null;
    if ($acct) {
      if ($this->user_id !== null) {
        \ICT\Core\DB::query('account',
          "UPDATE account SET username = '%username%', email = '%email%', created_by = %uid% WHERE account_id = %id%",
          ['username' => $username, 'email' => $email, 'uid' => (int)$this->user_id, 'id' => $acct['account_id']]);
      } else {
        \ICT\Core\DB::query('account',
          "UPDATE account SET username = '%username%', email = '%email%' WHERE account_id = %id%",
          ['username' => $username, 'email' => $email, 'id' => $acct['account_id']]);
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
