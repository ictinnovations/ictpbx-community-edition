<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\FpbxExtension;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class FpbxExtensionApi extends Api
{
  /**
   * @url GET /fpbx_extensions
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('fpbx_extension');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    $list   = FpbxExtension::search($filter);
    $list   = array_map(fn($r) => (array)$r, (array)$list);

    // Enrich with fax-account assignment + linked ICTCore user (cross-query MariaDB)
    foreach ($list as &$row) {
      $ext = $row['extension'] ?? '';
      if ($ext !== '') {
        $res = \ICT\Core\DB::query('account',
          "SELECT a.account_id, a.username, a.email, a.created_by,
                  u.username AS linked_username
           FROM account a
           LEFT JOIN usr u ON u.usr_id = a.created_by AND a.created_by > 0
           WHERE a.phone = '%phone%' AND a.type IN ('account','child_account')
           LIMIT 1",
          ['phone' => $ext]);
        $acct = mysqli_fetch_assoc($res);
        $row['assigned_account_id'] = $acct ? (int)$acct['account_id'] : null;
        $row['assigned_to']         = $acct ? $acct['username'] : null;
        $row['fax_email']           = $acct ? $acct['email'] : null;
        $row['linked_user_id']      = $acct ? ($acct['created_by'] > 0 ? (int)$acct['created_by'] : null) : null;
        $row['linked_username']     = $acct ? $acct['linked_username'] : null;
      } else {
        $row['assigned_account_id'] = null;
        $row['assigned_to']         = null;
        $row['fax_email']           = null;
        $row['linked_user_id']      = null;
        $row['linked_username']     = null;
      }
    }
    unset($row);

    // Admin: enrich with tenant name
    $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);
    if ($is_admin && !empty($list)) {
      $domain_uuids = array_column($list, 'domain_uuid');
      $tenant_map   = \ICT\Core\FpbxDomain::get_tenant_names_by_domain_uuids($domain_uuids);
      foreach ($list as &$row) {
        $row['tenant_name'] = $tenant_map[$row['domain_uuid'] ?? ''] ?? null;
      }
      unset($row);
    }

    return $list;
  }

  /**
   * Assign (or unassign) a fax account to a PBX extension.
   * @url POST /fpbx_extensions/$extension_uuid/assign
   */
  public function assign($extension_uuid, $data = [])
  {
    $this->_authorize_pbx('fpbx_extension', true);
    $pdo  = \ICT\Core\FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare("SELECT extension, domain_uuid FROM v_extensions WHERE extension_uuid = ?");
    $stmt->execute([$extension_uuid]);
    $ext = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$ext) throw new CoreException(404, 'Extension not found');

    $tenant_id  = $this->oUser->tenant_id;
    $account_id = isset($data['account_id']) ? (int)$data['account_id'] : null;

    if ($account_id) {
      $res = \ICT\Core\DB::query('account',
        "SELECT account_id FROM account WHERE account_id = %aid% AND tenant_id = %tid% AND type IN ('account','child_account') LIMIT 1",
        ['aid' => $account_id, 'tid' => $tenant_id]);
      if (!mysqli_fetch_assoc($res)) throw new CoreException(404, 'Fax account not found');

      // Block if extension already assigned to a different account
      $res2 = \ICT\Core\DB::query('account',
        "SELECT account_id FROM account WHERE phone = '%phone%' AND type IN ('account','child_account') AND account_id != %aid% LIMIT 1",
        ['phone' => $ext['extension'], 'aid' => $account_id]);
      if (mysqli_fetch_assoc($res2)) {
        throw new CoreException(409, "Extension {$ext['extension']} is already assigned to another fax account.");
      }
      // Unassign any current holder first
      \ICT\Core\DB::query('account',
        "UPDATE account SET phone = NULL WHERE phone = '%phone%' AND type IN ('account','child_account') AND account_id != %aid%",
        ['phone' => $ext['extension'], 'aid' => $account_id]);
      // Assign to new account
      \ICT\Core\DB::query('account',
        "UPDATE account SET phone = '%phone%' WHERE account_id = %aid%",
        ['phone' => $ext['extension'], 'aid' => $account_id]);
    } else {
      // Unassign: clear phone on current holder
      \ICT\Core\DB::query('account',
        "UPDATE account SET phone = NULL WHERE phone = '%phone%' AND tenant_id = %tid% AND type IN ('account','child_account')",
        ['phone' => $ext['extension'], 'tid' => $tenant_id]);
    }
    return ['success' => true];
  }

  /**
   * @url GET /fpbx_extensions/next_available
   */
  public function next_available()
  {
    $this->_authorize_pbx('fpbx_extension');
    $tenant_id = $this->oUser->tenant_id;
    $domain_uuid = \ICT\Core\FpbxDomain::get_domain_uuid($tenant_id);
    if (!$domain_uuid) {
      throw new CoreException(404, 'No domain configured for this tenant.');
    }
    $candidate = 1000;
    while ($candidate < 9999) {
      $conflict = \ICT\Core\FpbxDomain::extension_in_use($domain_uuid, (string)$candidate);
      if ($conflict === null) {
        return ['next_available' => (string)$candidate];
      }
      $candidate++;
    }
    throw new CoreException(409, 'No free extension numbers available in range 1000-9999.');
  }

  /**
   * @url GET /fpbx_extensions/check
   */
  public function check($query = [])
  {
    $this->_authorize_pbx('fpbx_extension');
    $extension = $query['extension'] ?? '';
    if (empty($extension)) {
      throw new CoreException(400, 'extension parameter required.');
    }
    $tenant_id = $this->oUser->tenant_id;
    $domain_uuid = \ICT\Core\FpbxDomain::get_domain_uuid($tenant_id);
    if (!$domain_uuid) {
      return ['available' => true];
    }
    $conflict = \ICT\Core\FpbxDomain::extension_in_use($domain_uuid, $extension);
    if ($conflict === null) {
      return ['available' => true];
    }
    return ['available' => false, 'used_by' => $conflict];
  }

  /**
   * @url GET /fpbx_extensions/available_for_fax
   */
  public function available_for_fax($query = [])
  {
    $this->_authorize_pbx('fpbx_extension');
    $tenant_id   = $this->oUser->tenant_id;
    $domain_uuid = \ICT\Core\FpbxDomain::get_domain_uuid($tenant_id);
    if (!$domain_uuid) return [];

    $exclude_account_id = isset($query['exclude_account_id']) ? (int)$query['exclude_account_id'] : 0;

    $excl_q = "SELECT phone FROM account WHERE tenant_id = %tid% AND type IN ('account','child_account')";
    $excl_p = ['tid' => $tenant_id];
    if ($exclude_account_id > 0) {
      $excl_q .= " AND account_id != %excl%";
      $excl_p['excl'] = $exclude_account_id;
    }
    $res  = \ICT\Core\DB::query('account', $excl_q, $excl_p);
    $used = [];
    while ($row = mysqli_fetch_assoc($res)) { $used[] = $row['phone']; }

    $pdo = \ICT\Core\FpbxDomain::fpbx_db();
    if (empty($used)) {
      $stmt = $pdo->prepare(
        "SELECT extension_uuid, extension FROM v_extensions
         WHERE domain_uuid = ? ORDER BY extension::int NULLS LAST, extension"
      );
      $stmt->execute([$domain_uuid]);
    } else {
      $placeholders = implode(',', array_fill(0, count($used), '?'));
      $stmt = $pdo->prepare(
        "SELECT extension_uuid, extension FROM v_extensions
         WHERE domain_uuid = ? AND extension NOT IN ($placeholders)
         ORDER BY extension::int NULLS LAST, extension"
      );
      $stmt->execute(array_merge([$domain_uuid], $used));
    }
    return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
  }

  /**
   * @url GET /fpbx_extensions/$extension_uuid
   */
  public function read($extension_uuid)
  {
    $this->_authorize_pbx('fpbx_extension');
    $oExt = new FpbxExtension($extension_uuid);
    $this->_assert_pbx_domain($oExt);
    return $oExt;
  }

  /**
   * @url POST /fpbx_extensions
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('fpbx_extension', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::EXTENSIONS)) {
      throw new CoreException(409, 'Extension quota limit reached for this tenant');
    }
    $oExt = new FpbxExtension();
    $oExt->tenant_id = $this->oUser->tenant_id;
    $this->set($oExt, $data);
    $uuid = $oExt->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::EXTENSIONS);
      return $uuid;
    }
    throw new CoreException(417, 'Extension creation failed');
  }

  /**
   * @url PUT /fpbx_extensions/$extension_uuid
   */
  public function update($extension_uuid, $data = array())
  {
    $this->_authorize_pbx('fpbx_extension', true);
    $oExt = new FpbxExtension($extension_uuid);
    $this->_assert_pbx_domain($oExt);
    $this->set($oExt, $data);
    if ($oExt->save()) return $oExt;
    throw new CoreException(417, 'Extension update failed');
  }

  /**
   * @url DELETE /fpbx_extensions/$extension_uuid
   */
  public function remove($extension_uuid)
  {
    $this->_authorize_pbx('fpbx_extension', true);
    $oExt = new FpbxExtension($extension_uuid);
    $this->_assert_pbx_domain($oExt);
    $result = $oExt->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::EXTENSIONS);
    return $result;
  }

}
