<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Website : http://www.ictinnovations.com/                        *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\ApiKey;
use ICT\Core\CoreException;
use ICT\Core\DB;

#[\AllowDynamicProperties]
class ApiKeyApi extends Api
{

  /**
   * List the caller's API keys. The secret is never returned here.
   *
   * @url GET /api_keys
   */
  public function index($query = array())
  {
    $this->_authorize('api');
    $scope = $this->_key_scope();

    $sql = "SELECT api_key_id, tenant_id, usr_id, name, key_prefix, rate_limit,
                   active, expires, last_used, created
            FROM api_key
            WHERE $scope
            ORDER BY api_key_id DESC";

    $res  = DB::query('api_key', $sql);
    $rows = array();
    while ($res && $row = mysqli_fetch_assoc($res)) {
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Mint a new API key for the calling user. The plaintext key is returned
   * exactly once — only its hash is stored.
   *
   * @url POST /api_keys
   */
  public function create($data = array())
  {
    $this->_authorize('api');
    $this->_deny_key_auth();

    $name = isset($data['name']) ? trim((string) $data['name']) : '';
    if ($name === '') {
      throw new CoreException(412, 'A name is required');
    }
    if (strlen($name) > 64) {
      throw new CoreException(412, 'Name must be 64 characters or less');
    }

    $rate_limit = isset($data['rate_limit']) ? (int) $data['rate_limit'] : 60;
    if ($rate_limit < 0 || $rate_limit > 100000) {
      throw new CoreException(412, 'rate_limit must be between 0 and 100000');
    }

    $expires = $this->_clean_expires(isset($data['expires']) ? $data['expires'] : null);

    $key = ApiKey::generate();

    $sql = "INSERT INTO api_key
              (tenant_id, usr_id, name, key_prefix, key_hash, rate_limit, active, expires)
            VALUES
              (%tenant_id%, %usr_id%, '%name%', '%key_prefix%', '%key_hash%', %rate_limit%, 1, "
            . ($expires === null ? 'NULL' : "'%expires%'") . ")";

    $values = array(
      'tenant_id'  => (int) $this->oUser->tenant_id,
      'usr_id'     => (int) $this->oUser->user_id,
      'name'       => $name,
      'key_prefix' => $key['prefix'],
      'key_hash'   => $key['hash'],
      'rate_limit' => $rate_limit
    );
    if ($expires !== null) {
      $values['expires'] = $expires;
    }

    DB::query('api_key', $sql, $values);
    $api_key_id = mysqli_insert_id(DB::$link);

    return array(
      'api_key_id' => $api_key_id,
      'name'       => $name,
      'key_prefix' => $key['prefix'],
      'rate_limit' => $rate_limit,
      'expires'    => $expires,
      'api_key'    => $key['plain']
    );
  }

  /**
   * Enable / disable a key or change its name, rate limit or expiry.
   *
   * @url PUT /api_keys/$api_key_id
   */
  public function update($api_key_id, $data = array())
  {
    $this->_authorize('api');
    $this->_deny_key_auth();
    $api_key_id = $this->_owned_key_id($api_key_id);

    $set    = array();
    $values = array('api_key_id' => $api_key_id);

    if (isset($data['name'])) {
      $name = trim((string) $data['name']);
      if ($name === '' || strlen($name) > 64) {
        throw new CoreException(412, 'Name must be between 1 and 64 characters');
      }
      $set[] = "name = '%name%'";
      $values['name'] = $name;
    }

    if (isset($data['rate_limit'])) {
      $rate_limit = (int) $data['rate_limit'];
      if ($rate_limit < 0 || $rate_limit > 100000) {
        throw new CoreException(412, 'rate_limit must be between 0 and 100000');
      }
      $set[] = "rate_limit = %rate_limit%";
      $values['rate_limit'] = $rate_limit;
    }

    if (isset($data['active'])) {
      $set[] = "active = %active%";
      $values['active'] = empty($data['active']) ? 0 : 1;
    }

    if (array_key_exists('expires', $data)) {
      $expires = $this->_clean_expires($data['expires']);
      if ($expires === null) {
        $set[] = "expires = NULL";
      } else {
        $set[] = "expires = '%expires%'";
        $values['expires'] = $expires;
      }
    }

    if (empty($set)) {
      throw new CoreException(412, 'No updatable fields supplied');
    }

    $sql = "UPDATE api_key SET " . implode(', ', $set) . " WHERE api_key_id = %api_key_id%";
    DB::query('api_key', $sql, $values);

    return array('api_key_id' => $api_key_id, 'updated' => true);
  }

  /**
   * Permanently revoke a key.
   *
   * @url DELETE /api_keys/$api_key_id
   */
  public function remove($api_key_id)
  {
    $this->_authorize('api');
    $this->_deny_key_auth();
    $api_key_id = $this->_owned_key_id($api_key_id);

    DB::query('api_key', "DELETE FROM api_key WHERE api_key_id = %api_key_id%",
              array('api_key_id' => $api_key_id));

    return array('api_key_id' => $api_key_id, 'deleted' => true);
  }

  // ───────────────────────── helpers ─────────────────────────

  /**
   * Caller-scoped SQL WHERE fragment.
   * super_admin → all; tenant admin → own tenant; end user → own rows.
   */
  protected function _key_scope()
  {
    if (\ICT\Core\can_access('super_admin')) {
      return 'TRUE';
    }
    if (\ICT\Core\can_access('user_admin')) {
      $tid = (int) $this->oUser->tenant_id;
      return "tenant_id = $tid";
    }
    $uid = (int) $this->oUser->user_id;
    return "usr_id = $uid";
  }

  /**
   * Resolve a key id the caller is allowed to manage, or 404.
   */
  protected function _owned_key_id($api_key_id)
  {
    $api_key_id = (int) $api_key_id;
    if ($api_key_id <= 0) {
      throw new CoreException(404, 'API key not found');
    }
    $scope = $this->_key_scope();
    $found = DB::query_result(
      'api_key',
      "SELECT api_key_id FROM api_key WHERE api_key_id = %api_key_id% AND $scope",
      'api_key_id',
      array('api_key_id' => $api_key_id)
    );
    if (empty($found)) {
      throw new CoreException(404, 'API key not found');
    }
    return $api_key_id;
  }

  /**
   * A leaked key must not be able to mint replacements for itself or outlive
   * revocation, so key management is reachable only with an interactive login.
   */
  protected function _deny_key_auth()
  {
    if (ApiKey::is_key_auth()) {
      throw new CoreException(403, 'API keys cannot be managed using an API key');
    }
  }

  /**
   * Normalise an expiry date to 'Y-m-d H:i:s', or null when not set.
   */
  protected function _clean_expires($expires)
  {
    if ($expires === null || trim((string) $expires) === '') {
      return null;
    }
    $ts = strtotime((string) $expires);
    if ($ts === false) {
      throw new CoreException(412, 'Invalid expires date');
    }
    return gmdate('Y-m-d H:i:s', $ts);
  }

}
