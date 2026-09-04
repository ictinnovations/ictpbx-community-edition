<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Account;
use ICT\Core\Api;
use ICT\Core\Conf;
use ICT\Core\CoreException;
use ICT\Core\Program;
use ICT\Core\User;
use stdClass;

#[\AllowDynamicProperties]
class AccountApi extends Api
{

  /** @var string #interface_type */
  private $include_subfolder = true;

  /**
   * Create a new account
   *
   * @url POST /accounts
   */
  public function create($data = array())
  {
    $this->_authorize('account_create');

    if (isset($data['type']) && !empty($data['type'])) {
      $oAccount = Account::load($data['type']);
    } else {
      $oAccount = new Account();
    }
    $aSetting = $oAccount->settings; // prepare a copy of default settings
    $this->set($oAccount, $data);
    if (isset($data['settings']) && !empty($data['settings'])) {
      // override default settings but preserve the unchanged settings
      $oAccount->settings = array_merge($aSetting, $oAccount->settings);
    }

    if ($oAccount->save()) {
      return $oAccount->account_id;
    } else {
      throw new CoreException(417, 'Account creation failed');
    }
  }

  /**
   * List all available accounts
   *
   * @url GET /accounts
   */
  public function list_view($query = array())
  {
    $this->_authorize('account_list');
    $filter  = (array)$query;
    $filter += $this->_authorization_filter();
    return Account::search($filter);
  }

  /**
   * List all available accounts
   *
   * @url GET /accounts/linkdid/$account_id
   */
  public function linkdid_accounts($account_id)
  {
    return Account::linkdid_accounts($account_id);
  }

  /**
   * Gets the account by id
   *
   * @url GET /accounts/$account_id
   */
  public function read($account_id)
  {
    if ($account_id === 'my') return $this->my_accounts();
    $this->_authorize('account_read');

    $oAccount = Account::load($account_id);
    if ($this->_authorization_filter($oAccount)) {
      $this->_enrich_account($oAccount);
      return $oAccount;
    }
  }

  /**
   * Returns all accounts (extensions + fax) belonging to the logged-in user
   *
   * @url GET /accounts/my
   */
  public function my_accounts()
  {
    $this->_authorize('account_read');

    $oSession = \ICT\Core\Session::get_instance();
    $user_id  = (int)$oSession->user->user_id;

    $result = \ICT\Core\DB::query('account',
      "SELECT account_id FROM account WHERE created_by = %uid% AND type IN ('account','child_account') ORDER BY account_id",
      ['uid' => $user_id]);

    $accounts = [];
    while ($row = mysqli_fetch_assoc($result)) {
      $oAccount = Account::load($row['account_id']);
      $this->_enrich_account($oAccount);
      $accounts[] = $oAccount;
    }
    return $accounts;
  }

  private function _enrich_account($oAccount)
  {
    // Enrich with PBX SIP credentials if fax account has a matching PBX extension
    if (in_array($oAccount->type, ['account', 'child_account']) && !empty($oAccount->phone)) {
      try {
        $domain_uuid = \ICT\Core\FpbxDomain::get_domain_uuid($oAccount->tenant_id);
        if ($domain_uuid) {
          $pdo  = \ICT\Core\FpbxDomain::fpbx_db();
          $stmt = $pdo->prepare(
            "SELECT extension_uuid, extension, password, user_context,
                    effective_caller_id_name, do_not_disturb,
                    forward_all_enabled, forward_all_destination,
                    forward_busy_enabled, forward_busy_destination,
                    forward_no_answer_enabled, forward_no_answer_destination
             FROM v_extensions
             WHERE domain_uuid = ? AND extension = ? LIMIT 1"
          );
          $stmt->execute([$domain_uuid, (string)$oAccount->phone]);
          $pbx = $stmt->fetch(\PDO::FETCH_ASSOC);
          if ($pbx) {
            $oAccount->pbx_extension_uuid                  = $pbx['extension_uuid'];
            $oAccount->pbx_extension                       = $pbx['extension'];
            $oAccount->pbx_password                        = $pbx['password'];
            $oAccount->pbx_domain                          = $pbx['user_context'];
            $oAccount->pbx_caller_id_name                  = $pbx['effective_caller_id_name'];
            $oAccount->pbx_do_not_disturb                  = $pbx['do_not_disturb'] === 't';
            $oAccount->pbx_forward_all_enabled             = $pbx['forward_all_enabled'] === 't';
            $oAccount->pbx_forward_all_destination         = $pbx['forward_all_destination'];
            $oAccount->pbx_forward_busy_enabled            = $pbx['forward_busy_enabled'] === 't';
            $oAccount->pbx_forward_busy_destination        = $pbx['forward_busy_destination'];
            $oAccount->pbx_forward_no_answer_enabled       = $pbx['forward_no_answer_enabled'] === 't';
            $oAccount->pbx_forward_no_answer_destination   = $pbx['forward_no_answer_destination'];
            // Load extension_type from MariaDB extension_config
            try {
              $ec = \ICT\Core\DB::query('extension_config',
                "SELECT extension_type, fax_email FROM extension_config WHERE extension_uuid = '%uuid%'",
                ['uuid' => $pbx['extension_uuid']]);
              $ec_row = mysqli_fetch_assoc($ec);
              $oAccount->pbx_extension_type = $ec_row ? ($ec_row['extension_type'] ?: 'voice') : 'voice';
              $oAccount->pbx_fax_email      = $ec_row ? ($ec_row['fax_email'] ?: '') : '';
            } catch (\Exception $e2) {
              $oAccount->pbx_extension_type = 'voice';
              $oAccount->pbx_fax_email      = '';
            }
          }
        }
      } catch (\Exception $e) {}
    }
    // Enrich with DID phone if this is a child account linked to a DID
    if (!empty($oAccount->linkdid_id)) {
      try {
        $oDid = Account::load($oAccount->linkdid_id);
        $oAccount->did_phone = $oDid->phone;
      } catch (\Exception $e) {}
    }
  }

  /**
   * Gets the provisioning information by account id
   *
   * @url GET /accounts/$account_id/provisioning
   */
  public function provisioning($account_id)
  {
    $this->_authorize('account_read');

    $oAccount = $this->read($account_id);

    $oProvisioning = new stdClass();
    $oProvisioning->username = $oAccount->username;
    $oProvisioning->password = $oAccount->passwd;
    $oProvisioning->callerid = $oAccount->phone;
    $aProvisioning = Conf::get('provisioning');
    foreach ($aProvisioning as $field => $value) {
      $oProvisioning->{$field} = $value;
    }
    $oProvisioning->dialplan = array(
      'agent_login' => '*'.$oAccount->phone,
      'voicemail' => '*78',
    );
    $oProvisioning->account = $oAccount; // all other account informations

    return $oProvisioning;
  }

  /**
   * Update existing account
   *
   * @url PUT /accounts/$account_id
   */
  public function update($account_id, $data = array())
  {
    $this->_authorize('account_update');

    $oAccount = Account::load($account_id);
    $aSetting = $oAccount->settings; // prepare a copy of old settings
    $this->set($oAccount, $data);
    if (isset($data['settings']) && !empty($data['settings'])) {
      // override old settings but preserve the unchanged settings
      $oAccount->settings = array_merge($aSetting, $oAccount->settings);
    }

    if ($oAccount->save()) {
      return $oAccount;
    } else {
      throw new CoreException(417, 'Account update failed');
    }
  }

  /**
   * Delete a account
   *
   * @url DELETE /accounts/$account_id
   */
  public function remove($account_id)
  {
    $this->_authorize('account_delete');

    $oAccount = Account::load($account_id);

    $result = $oAccount->delete();
    if ($result) {
      return $result;
    } else {
      throw new CoreException(417, 'Account delete failed');
    }
  }

  /**
   * Subscribe to selected program
   *
   * @url PUT /accounts/$account_id/programs/$program_name
   */
  public function subscribe($account_id, $program_name)
  {
    $this->_authorize('account_update');
    $this->_authorize('program_create');
    $this->_authorize('program_execute');
    $oAccount = Account::load($account_id);
    $oProgram = Program::load($program_name);

    return $oAccount->install_program($oProgram);
  }

  /**
   * Unsubscribe from selected program
   *
   * @url DELETE /accounts/$account_id/programs
   * @url DELETE /accounts/$account_id/programs/$program_name
   */
  public function unsubscribe($account_id, $program_name = 'all')
  {
    $this->_authorize('account_update');
    $this->_authorize('program_delete');
    $oAccount = Account::load($account_id);

    return $oAccount->remove_program($program_name);
  }

  /**
   * Associate account to selected user
   *
   * @url PUT /accounts/$account_id/users/$user_id
   */
  public function associate($account_id, $user_id)
  {
    $this->_authorize('account_create'); // instead of updated association is more like account creation
    $this->_authorize('user_update');
    $oAccount = Account::load($account_id);
    $oAccount->dissociate();
    return $oAccount->associate($user_id);
  }

  /**
   * Unsubscribe from selected program
   *
   * @url DELETE /accounts/$account_id/users
   */
  public function dissociate($account_id)
  {
    $this->_authorize('account_delete');
    $this->_authorize('user_update');
    $oAccount = Account::load($account_id);

    return $oAccount->dissociate();
  }

  /**
   * Read setting associated with this account
   *
   * @url GET /accounts/$account_id/settings/$name
   */
  public function setting_read($account_id, $name)
  {
    $this->_authorize('account_read');
    $oAccount = Account::load($account_id);

    if (isset($oAccount->settings[$name])) {
      $oAccount->settings[$name];
      return $oAccount->save();
    }
    throw new CoreException(404, 'Setting not found');
  }

  /**
   * Save setting for this account
   *
   * @url PUT /accounts/$account_id/settings/$name
   */
  public function setting_write($account_id, $name, $data = array())
  {
    $this->_authorize('account_update');
    $oAccount = Account::load($account_id);
    $is_updated = false;

    if (is_array($data)) {
      if (isset($data['value'])) {
        $oAccount->settings[$name] = $data['value'];
        $is_updated = true;
      } elseif (isset($data['data'])) {
        $oAccount->settings[$name] = $data['data'];
        $is_updated = true;
      }
    } elseif (!empty($data)) {
      $oAccount->settings[$name] = $data;
      $is_updated = true;
    }
    if ($is_updated) {
      return $oAccount->save();
    }
    throw new CoreException(417, 'Account setting update failed! no value or data parameter set');
  }

  /**
   * Delete a setting from given account
   *
   * @url DELETE /accounts/$account_id/settings/$name
   */
  public function setting_delete($account_id, $name)
  {
    $this->_authorize('account_update');
    $oAccount = Account::load($account_id);
    unset($oAccount->settings[$name]);
    return $oAccount->save();
  }

  // include classes from Account folder
  protected static function rest_include()
  {
    if (property_exists (get_called_class(), 'include_subfolder')) {
      return 'Api/Account'; // Api class return sub api folder
    }
    // in child class return null
    return null;
  }

}
