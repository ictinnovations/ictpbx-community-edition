<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\DB;

/**
 * DID Lifecycle Management
 *
 * Admin:        GET/POST/PUT /dids — create DIDs and assign to tenants
 * Tenant admin: GET /dids — see own tenant DIDs; PUT /dids/{id}/assign — link to fax account
 */
#[\AllowDynamicProperties]
class DidApi extends Api
{
  /**
   * List DIDs.
   * Admin sees all; tenant admin sees own tenant only.
   *
   * @url GET /dids
   */
  public function list_view($query = [])
  {
    $this->_authorize('account_list');
    $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);

    // Support ?totalrows for paginator length hint
    if (isset($query['totalrows'])) {
      $where = $is_admin ? "WHERE type='did'" : "WHERE type='did' AND tenant_id='" . (int)$this->oUser->tenant_id . "'";
      $res = DB::query('account', "SELECT COUNT(*) AS cnt FROM account $where");
      $r = mysqli_fetch_assoc($res);
      return (int)($r['cnt'] ?? 0);
    }

    if ($is_admin) {
      $sql = "SELECT a.account_id, a.account_id AS id, a.phone, a.domain, a.tenant_id, a.active,
                     a.first_name, a.first_name AS description, a.created_by,
                     t.company AS tenant_name,
                     la.account_id AS fax_account_id, la.username AS fax_account_name
              FROM account a
              LEFT JOIN tenant t ON t.tenant_id = a.tenant_id
              LEFT JOIN account la ON la.linkdid_id = a.account_id
              WHERE a.type = 'did'
              ORDER BY a.tenant_id, a.phone";
      $result = DB::query('account', $sql);
    } else {
      $tenant_id = (int)$this->oUser->tenant_id;
      $sql = "SELECT a.account_id, a.account_id AS id, a.phone, a.domain, a.tenant_id, a.active,
                     a.first_name, a.first_name AS description, a.created_by,
                     la.account_id AS fax_account_id, la.username AS fax_account_name
              FROM account a
              LEFT JOIN account la ON la.linkdid_id = a.account_id
              WHERE a.type = 'did' AND a.tenant_id = '$tenant_id'
              ORDER BY a.phone";
      $result = DB::query('account', $sql);
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Get a single DID by id (admin only).
   *
   * @url GET /dids/$account_id
   */
  public function read($account_id)
  {
    $this->_authorize('account_read');
    if (!\ICT\Core\can_access('super_admin', $this->oUser->user_id)) {
      throw new CoreException(403, 'Only administrators can view DID details.');
    }

    $account_id = (int)$account_id;
    $sql = "SELECT a.account_id AS id, a.phone, a.domain, a.tenant_id, a.active, a.first_name AS description,
                   t.company AS tenant_name
            FROM account a
            LEFT JOIN tenant t ON t.tenant_id = a.tenant_id
            WHERE a.account_id = '$account_id' AND a.type = 'did'";
    $result = DB::query('account', $sql);
    $row = mysqli_fetch_assoc($result);
    if (!$row) {
      throw new CoreException(404, 'DID not found.');
    }
    return $row;
  }

  /**
   * Create a new DID (admin only).
   *
   * @url POST /dids
   */
  public function create($data = [])
  {
    $this->_authorize('account_create');
    if (!\ICT\Core\can_access('super_admin', $this->oUser->user_id)) {
      throw new CoreException(403, 'Only administrators can create DIDs.');
    }

    $phone       = trim($data['phone'] ?? '');
    $tenant_id   = (int)($data['tenant_id'] ?? 1);
    $label       = trim($data['first_name'] ?? $data['description'] ?? $data['label'] ?? $phone);
    $domain      = trim($data['domain'] ?? '');

    if (empty($phone)) {
      throw new CoreException(400, 'phone is required.');
    }

    // Global uniqueness check
    $check = DB::query('account',
      "SELECT COUNT(*) AS cnt FROM account WHERE type='did' AND phone='%phone%'",
      ['phone' => $phone]
    );
    $row = mysqli_fetch_assoc($check);
    if ((int)($row['cnt'] ?? 0) > 0) {
      throw new CoreException(409, 'This DID number is already registered in the system.');
    }

    // Verify tenant exists
    if ($tenant_id > 1) {
      $tc = DB::query('tenant', "SELECT COUNT(*) AS cnt FROM tenant WHERE tenant_id='$tenant_id'");
      $tr = mysqli_fetch_assoc($tc);
      if ((int)($tr['cnt'] ?? 0) === 0) {
        throw new CoreException(404, 'Tenant not found.');
      }
    }

    // Insert DID account
    $result = DB::query('account',
      "INSERT INTO account (tenant_id, type, username, phone, first_name, domain, active)
       VALUES ('%tenant_id%', 'did', '%phone%', '%phone%', '%label%', '%domain%', 1)",
      ['tenant_id' => $tenant_id, 'phone' => $phone, 'label' => $label, 'domain' => $domain]
    );
    $account_id = mysqli_insert_id(DB::$link);

    // Auto-create dialplan row for inbound routing
    $prog = DB::query('account',
      "SELECT p.program_id FROM program p
       JOIN application a ON a.application_id = (
         SELECT app.application_id FROM application app WHERE app.name='inbound' LIMIT 1
       )
       WHERE p.name='faxtoemail' LIMIT 1"
    );
    $prog_row = mysqli_fetch_assoc($prog);
    $program_id = $prog_row['program_id'] ?? null;

    if ($program_id) {
      $app = DB::query('account',
        "SELECT application_id FROM application WHERE name='inbound' LIMIT 1"
      );
      $app_row = mysqli_fetch_assoc($app);
      $application_id = $app_row['application_id'] ?? null;
      if ($application_id) {
        $source = !empty($domain) ? $domain : '%';
        DB::query('account',
          "INSERT INTO dialplan (gateway_flag, source, destination, context, program_id, application_id, filter_flag)
           VALUES (8, '%source%', '%phone%', 'external', '$program_id', '$application_id', 31)",
          ['source' => $source, 'phone' => $phone]
        );
        DB::query('account',
          "INSERT IGNORE INTO destination (prefix, name) VALUES ('%phone%', '%label%')",
          ['phone' => $phone, 'label' => $label]
        );
      }
    }

    return ['id' => $account_id, 'phone' => $phone, 'tenant_id' => $tenant_id];
  }

  /**
   * Update DID — admin can reassign tenant; also used for label updates.
   *
   * @url PUT /dids/$account_id
   */
  public function update($account_id, $data = [])
  {
    $this->_authorize('account_update');
    if (!\ICT\Core\can_access('super_admin', $this->oUser->user_id)) {
      throw new CoreException(403, 'Only administrators can reassign DIDs.');
    }

    $account_id = (int)$account_id;
    $check = DB::query('account',
      "SELECT account_id, phone FROM account WHERE account_id='$account_id' AND type='did'"
    );
    $did = mysqli_fetch_assoc($check);
    if (!$did) {
      throw new CoreException(404, 'DID not found.');
    }

    $sets = [];
    $params = [];
    if (isset($data['tenant_id'])) {
      $sets[]              = "tenant_id='%new_tenant_id%'";
      $params['new_tenant_id'] = (int)$data['tenant_id'];
    }
    if (isset($data['first_name']) || isset($data['description']) || isset($data['label'])) {
      $sets[]           = "first_name='%label%'";
      $params['label']  = trim($data['first_name'] ?? $data['description'] ?? $data['label']);
    }
    if (isset($data['domain'])) {
      $sets[]            = "domain='%domain%'";
      $params['domain']  = trim($data['domain']);
    }
    if (isset($data['active'])) {
      $sets[]           = "active='%active%'";
      $params['active'] = (int)$data['active'];
    }
    if (empty($sets)) {
      throw new CoreException(400, 'No fields to update.');
    }

    DB::query('account',
      "UPDATE account SET " . implode(', ', $sets) . " WHERE account_id='$account_id'",
      $params
    );

    if (isset($data['domain'])) {
      $new_source = !empty($params['domain']) ? $params['domain'] : '%';
      DB::query('account',
        "UPDATE dialplan SET source='%src%' WHERE destination='%phone%'",
        ['src' => $new_source, 'phone' => $did['phone']]
      );
    }

    return true;
  }

  /**
   * Tenant admin assigns a DID to one of their fax accounts.
   * Sets linkdid_id on the fax account row.
   *
   * @url PUT /dids/$account_id/assign
   */
  public function assign($account_id, $data = [])
  {
    $this->_authorize('account_update');
    $is_admin  = \ICT\Core\can_access('super_admin', $this->oUser->user_id);
    $tenant_id = $this->oUser->tenant_id;

    $account_id = (int)$account_id;

    // Verify DID exists and belongs to caller's tenant (or admin)
    $sql = $is_admin
      ? "SELECT account_id, phone FROM account WHERE account_id='$account_id' AND type='did'"
      : "SELECT account_id, phone FROM account WHERE account_id='$account_id' AND type='did' AND tenant_id='$tenant_id'";
    $check = DB::query('account', $sql);
    $did = mysqli_fetch_assoc($check);
    if (!$did) {
      throw new CoreException(404, 'DID not found or not assigned to your tenant.');
    }

    $fax_account_id = isset($data['fax_account_id']) ? (int)$data['fax_account_id'] : null;

    if ($fax_account_id) {
      // Verify fax account belongs to same tenant (accept both account and child_account types)
      $scope_sql = $is_admin
        ? "SELECT account_id FROM account WHERE account_id='$fax_account_id' AND type IN ('account','child_account')"
        : "SELECT account_id FROM account WHERE account_id='$fax_account_id' AND type IN ('account','child_account') AND tenant_id='$tenant_id'";
      $fa_check = DB::query('account', $scope_sql);
      if (!mysqli_fetch_assoc($fa_check)) {
        throw new CoreException(404, 'Fax account not found or not in your tenant.');
      }
      // Set linkdid_id on fax account → points at DID
      DB::query('account',
        "UPDATE account SET linkdid_id='$account_id', type='child_account' WHERE account_id='$fax_account_id'"
      );
    } else {
      // Unassign: clear linkdid_id on any fax account currently pointing at this DID
      DB::query('account',
        "UPDATE account SET linkdid_id=NULL, type='account' WHERE linkdid_id='$account_id'"
      );
    }

    return true;
  }

  /**
   * Delete a DID (admin only). Also removes dialplan row.
   *
   * @url DELETE /dids/$account_id
   */
  public function remove($account_id)
  {
    $this->_authorize('account_delete');
    if (!\ICT\Core\can_access('super_admin', $this->oUser->user_id)) {
      throw new CoreException(403, 'Only administrators can delete DIDs.');
    }

    $account_id = (int)$account_id;
    $check = DB::query('account',
      "SELECT account_id, phone FROM account WHERE account_id='$account_id' AND type='did'"
    );
    $did = mysqli_fetch_assoc($check);
    if (!$did) {
      throw new CoreException(404, 'DID not found.');
    }

    // Remove dialplan row
    DB::query('account',
      "DELETE FROM dialplan WHERE destination='%phone%'",
      ['phone' => $did['phone']]
    );

    // Clear linkdid_id on any linked fax accounts
    DB::query('account',
      "UPDATE account SET linkdid_id=NULL, type='account' WHERE linkdid_id='$account_id'"
    );

    // Delete the DID
    DB::query('account', "DELETE FROM account WHERE account_id='$account_id'");

    return true;
  }
}
