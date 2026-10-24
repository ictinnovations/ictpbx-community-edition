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
use ICT\Core\CoreException;
use ICT\Core\Tenant;
use ICT\Core\User;
use ICT\Core\User\Permission;
use ICT\Core\Conf;
use ICT\Core\Session;
use ICT\Core\Corelog;
use ICT\Core\PbxQuota;
use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use SplFileInfo;

#[\AllowDynamicProperties]
class UserApi extends Api
{

  /**
   * Create a new user
   *
   * @url POST /users
   */
  public function create($data = array())
  {
    $this->_authorize('user_create');

    // Prevent assigning admin role via API
    if (!empty($data['role_id']) && (int)$data['role_id'] === 2) {
      throw new CoreException(403, 'Admin role cannot be assigned via this API.');
    }

    // Default to end_user role (4) when caller is not super_admin
    if (empty($data['role_id']) && !\ICT\Core\can_access('super_admin', $this->oUser->user_id)) {
      $data['role_id'] = 4;
    }

    $oUser = new User();
    $this->set($oUser, $data);

    $this->_enforce_user_caps($oUser, false);

    if (!empty($data['role_id'])) {
      $oUser->role_assign((int)$data['role_id']);
    }

    if ($oUser->save()) {
      return $oUser->user_id;
    } else {
      throw new CoreException(417, 'User creation failed');
    }
  }

 /**
   * List all available users
   *
   * @url GET /users
   */
  public function list_view($query = array())
  {
    $this->_authorize('user_read');

    $filter = (array)$query;
    try {
      $this->_authorize('user_list');
      $filter = array_merge($filter, $this->_authorization_filter());
      return User::search($filter);
    } catch (CoreException $e) {
      // if user_list is not authorized then return a list containing logged in user
      $filter = array_merge($filter, $this->_authorization_filter());
      return User::search($filter);
    }
  }

  /**
   * List all available users
   *
   * @url GET /get_user
   */
  public function list_data($query = array())
  {
    $this->_authorize('user_read');

    $filter = (array)$query;
    try {
      $this->_authorize('user_list');
      $filter = array_merge($filter, $this->_tenant_filter());
      return User::get_data($filter);
    } catch (CoreException $e) {
      // if user_list is not authorized then return a list containing logged in user
      $filter = array_merge($filter, $this->_authorization_filter());
      return User::get_data($filter);
    }
  }

  /**
   * Gets the user by id
   *
   * @url GET /users/$user_id
   */
  public function read($user_id)
  {
    $this->_authorize('user_read');

    $oUser = new User($user_id);
    if($this->_authorization_filter($oUser)){
      return $oUser;
    }
  }


   /**
   * Gets the token of user
   *
   * @url GET /users/$user_id/token
   */
  public function getToken($user_id)
  {
    $this->_authorize('user_admin');

    $oUser = new User($user_id);
    $oUser->token = $oUser->generate_token();
    $oUser->access_token = $oUser->token;
    $oUser->expires_in = (60 * 60 * 24 * 30 * 12 * 1); // valid for one year
    $oUser->token_type = 'Bearer';
    $oUser->scope = 'All';
    return $oUser;
  }

  /**
   * Update existing user
   *
   * @url PUT /users/$user_id
   */
  public function update($user_id, $data = array())
  {
    $this->_authorize('user_update');

    // Prevent assigning admin role via API
    if (!empty($data['role_id']) && (int)$data['role_id'] === 2) {
      throw new CoreException(403, 'Admin role cannot be assigned via this API.');
    }

    $oUser = new User($user_id);
    $this->set($oUser, $data);

    if($this->_authorization_filter($oUser)){
      $this->_enforce_user_caps($oUser, true);

      if ($oUser->save()) {
        return $oUser;
      } else {
        throw new CoreException(417, 'User update failed');
      }
    }
  }

  /**
   * Update user passwd
   *
   * @url PUT /users/$user_id/password
   */
  public function update_password($user_id, $data = array())
  {
    $this->_authorize('user_password');

    $oUser = new User($user_id);
    $oUser->password = $data['password'];

    if ($oUser->save()) {
      return $oUser;
    } else {
      throw new CoreException(417, 'User password update failed');
    }
  }

  /**
   * Update user passwd
   *
   * @url PUT /password/users
   */
  public function update_password_enduser($data = array())
  {
    $this->_authorize('enduser_password');

    $oSession = Session::get_instance();
    $oUser = new User($oSession->user->user_id);
    $oUser->password = $data['password'];

    if ($oUser->save()) {
      return $oUser;
    } else {
      throw new CoreException(417, 'User password update failed');
    }
  }

  /**
   * Update user credit
   *
   * @url PUT /users/$user_id/credit
   */
  public function update_credit($user_id, $data = array())
  {
    $this->_authorize('user_admin');

    $oUser = new User($user_id);
    $oUser->credit = $data['credit'] + $oUser->credit;

    if ($oUser->save()) {
      return $oUser;
    } else {
      throw new CoreException(417, 'User credit update failed');
    }
  }

  /**
   * Delete a user
   *
   * @url DELETE /users/$user_id
   */
  public function remove($user_id)
  {
    $this->_authorize('user_delete');

    if ((int)$user_id === 1) {
      throw new CoreException(403, 'The system admin account cannot be deleted.');
    }
    if ((int)$user_id === (int)$this->oUser->user_id) {
      throw new CoreException(403, 'You cannot delete your own account.');
    }

    $oUser = new User($user_id);

    // Block deletion of super admin accounts
    if (\ICT\Core\can_access('super_admin', $user_id)) {
      throw new CoreException(403, 'The super admin account cannot be deleted.');
    }

    if($this->_authorization_filter($oUser)){
      $result = $oUser->delete();

      if ($result) {
        return $result;
      } else {
        throw new CoreException(417, 'User delete failed');
      }
    }
  }

  /**
   * Permission list of user
   *
   * @url GET /users/$user_id/permissions
   */
  public function permission_list_view($user_id, $query = array())
  {
    $this->_authorize('user_list');
    $this->_authorize('permission_list');

    $oUser = new User($user_id);
    return $oUser->search_permission((array)$query);
  }

  /**
   * Allow / authorize user for a certain permission
   *
   * @url PUT /users/$user_id/permissions/$permission_id
   */
  public function allow($user_id, $permission_id)
  {
    $this->_authorize('user_update');
    $this->_authorize('permission_create');

    $oUser = new User($user_id);
    $oUser->permission_assign($permission_id);
    return $oUser->save();
  }

  /**
   * Disallow / prevent a user form using a certain permission
   *
   * @url DELETE /users/$user_id/permissions/$permission_id
   */
  public function disallow($user_id, $permission_id)
  {
    $this->_authorize('user_update');
    $this->_authorize('permission_delete');

    $oUser = new User($user_id);
    $oUser->permission_unassign($permission_id);
    return $oUser->save();
  }

  /**
   * Role list of user
   *
   * @url GET /users/$user_id/roles
   */
  public function role_list_view($user_id, $query = array())
  {
    $this->_authorize('user_list');
    $this->_authorize('role_list');

    $oUser = new User($user_id);
    return $oUser->search_role((array)$query);
  }

  /**
   *
   * @url GET /users/$user_id/status
   */
  public function getstatus($user_id){
    $user = new User();
    return $user->get_status($user_id);
  }
  /**
   * Assign a role to user
   *
   * @url PUT /users/$user_id/roles/$role_id
   */
  public function assign($user_id, $role_id)
  {
    $this->_authorize('user_update');
    $this->_authorize('role_update');

    $oUser = new User($user_id);
    $oUser->role_assign($role_id);
    return $oUser->save();
  }

  /**
   * Remove certain role from user
   *
   * @url DELETE /users/$user_id/roles/$role_id
   */
  public function unassign($user_id, $role_id)
  {
    $this->_authorize('user_update');
    $this->_authorize('role_update');

    $oUser = new User($user_id);
    $oUser->role_unassign($role_id);
    return $oUser->save();
  }

  protected static function rest_include()
  {
    return 'Api/User';
  }

  /**
   * List all account assigned to this user
   *
   * @url GET /users/$user_id/accounts
   */
  public function account_list($user_id, $query = array())
  {
    $this->_authorize('user_read');
    $this->_authorize('account_list');

    // Fetch accont detail
    $oUser = $this->read($user_id);

    $filter = (array)$query;
    // $filter['created_by'] = $user_id;
    $filter['tenant_id'] = $oUser->tenant_id;
    $filter = array_merge($filter , $this->_authorization_filter());
    return Account::search($filter);
  }

  /**
   * PUT User Configuration
   *
   * @url PUT /users/$user_id/config/$config_name
   */
  public function config_set($user_id, $config_name, $data)
  {
    $this->_authorize('user_update');

     $reference = array();
     $reference['created_by'] = $user_id;
     $reference['class']      = Conf::USER;

     $config_value = $data;

     Conf::set($config_name, $config_value, true, $reference, Conf::PERMISSION_USER_WRITE);
     return true;
  }

  /**
   * GET User Configuration
   *
   * @url GET /users/$user_id/config/$config_name
   */
  public function config_get($user_id, $config_name)
  {
    $this->_authorize('user_read');

    return Conf::get($config_name, '');
  }

  /**
   * Upload logo by user_id
   *
   * @url PUT /users/$user_id/media
   */
  public function upload($user_id, $data = null, $mime = 'image/jpeg')
  {
    $this->_authorize('user_read');

    global $path_data;
    if (!empty($data)) {
      if (in_array($mime, User::$media_supported)) {
        $extension = array_search($mime, User::$media_supported);
        $filename = $path_data . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $user_id . '.' . $extension;
        if (file_put_contents($filename, $data)) {

          // Save Configuration for logo
          $reference = array();
          $reference['created_by'] = $user_id;
          $reference['class']      = Conf::USER;

          $config_name = 'site:logo';
          $config_value = $filename;

          Conf::set($config_name, $config_value, true, $reference, Conf::PERMISSION_USER_WRITE);

          return $filename;
        } else {
          throw new CoreException(417, 'User media upload failed');
        }
      } else {
        throw new CoreException(415, 'User media upload failed, invalid file type');
      }
    } else {
      throw new CoreException(411, 'User media upload failed, no file uploaded');
    }
  }

  /**
   * GET logo by user_id
   *
   * @noAuth
   * @url GET /users/$user_id/media
   */
   public function get_media($user_id) {
     $oUser = new User($user_id);
     return $oUser->show_image($user_id);
   }

  /**
   * POST TOTP Qr code with secret (MFA)
   *
   * @noAuth
   * @url POST /user/totpqrcode
   */
   public function generate_totpqr($data) {

    $totp = TOTP::create();
    $totp->setLabel($data['username']);
    $totp->setIssuer('ICTFAX');
    $otpauth = $totp->getProvisioningUri();
    $builder = new Builder(
        writer: new PngWriter(),
        writerOptions: [],
        validateResult: false,
        data: $otpauth,
        encoding: new Encoding('UTF-8'),
        size: 300,
        margin: 10
    );

    $result = $builder->build();

    return [
        'secret'    => $totp->getSecret(),
        'qrCodeUrl' => $result->getDataUri(),
    ];
  }

   /**
   * verify TOTP (MFA)
   *
   * @url POST /user/totp/verify
   */
  public function verify_totp($data=array()) {
    try {
      $secret = $data['secret'];
      $code = $data['code'];
      // Initialize the TOTP instance with the secret
      $totp = TOTP::create($secret);
      // Verify the code
      $isValid = $totp->verify($code);
      return $isValid;
    } catch (Exception $e) {
      http_response_code(500);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Cap-cascade enforcement on user save.
   *
   *   - permissions:  user.user_permission must be a subset of caller's
   *                   user_permission (super_admin bypasses this check).
   *   - fax limits:   user.daily_limit / monthly_limit cannot exceed the
   *                   tenant's remaining bucket
   *                   (tenant.{daily,monthly}_limit minus already-assigned).
   *
   * Throws CoreException(409) on violation.
   */
  private function _enforce_user_caps(User $oUser, $isUpdate = false)
  {
    if (\ICT\Core\is_community_edition()) {
      return;
    }

    // Zero out PBX quota for any resource whose permission is not held by this user.
    $permStr = isset($oUser->user_permission) ? (string)$oUser->user_permission : '';
    $permList = array_filter(array_map('trim', explode(',', $permStr)));
    $quotaPermMap = [
      'quota_extensions'    => 'fpbx_extension',
      'quota_devices'       => 'devices',
      'quota_ring_groups'   => 'ring_groups',
      'quota_call_queues'   => 'call_queues',
      'quota_ivr_menus'     => 'ivr_menus',
      'quota_voicemail'     => 'voicemails',
      'quota_conference'    => 'conferences',
      'quota_music_on_hold' => 'music_on_hold',
    ];
    foreach ($quotaPermMap as $field => $perm) {
      if (!in_array($perm, $permList)) {
        $oUser->$field = 0;
      }
    }

    $callerIsAdmin = false;
    try {
      $this->_authorize('super_admin');
      $callerIsAdmin = true;
    } catch (CoreException $e) {
      $callerIsAdmin = false;
    }

    // 1) Permission cascade: caller is the cap unless they are super_admin.
    if (!$callerIsAdmin) {
      $oSession = Session::get_instance();
      $callerId = isset($oSession->user) && isset($oSession->user->user_id)
        ? $oSession->user->user_id : null;
      if ($callerId) {
        $oCaller  = new User($callerId);
        $callerPerms = $this->_split_perm($oCaller->user_permission);
        $userPerms   = $this->_split_perm($oUser->user_permission);
        $excess = array_diff($userPerms, $callerPerms);
        if (!empty($excess)) {
          throw new CoreException(
            409,
            'Permission cap exceeded; not granted to caller: ' . implode(', ', $excess)
          );
        }
      }
    }

    // 2) Fax + PBX quota cascade: skipped for admin, enforced for tenant/user.
    if (!$callerIsAdmin && !empty($oUser->tenant_id)) {
      try {
        $oTenant = new Tenant($oUser->tenant_id);
      } catch (CoreException $e) {
        return; // unknown tenant, skip cap check
      }

      $tenantDaily   = (int)$oTenant->daily_limit;
      $tenantMonthly = (int)$oTenant->monthly_limit;
      $userDaily     = (int)$oUser->daily_limit;
      $userMonthly   = (int)$oUser->monthly_limit;

      $existingDaily   = 0;
      $existingMonthly = 0;
      if ($isUpdate && !empty($oUser->user_id)) {
        try {
          $existing = new User($oUser->user_id);
          $existingDaily   = (int)$existing->daily_limit;
          $existingMonthly = (int)$existing->monthly_limit;
        } catch (CoreException $e) {
          // ignore
        }
      }

      if ($tenantDaily !== -1) {
        $assignedDaily = (int)$oTenant->assigned_daily - $existingDaily;
        $remainingDaily = $tenantDaily - $assignedDaily;
        if ($userDaily > $remainingDaily) {
          throw new CoreException(
            409,
            "Daily limit ($userDaily) exceeds remaining tenant cap ($remainingDaily of $tenantDaily)"
          );
        }
      }

      if ($tenantMonthly !== -1) {
        $assignedMonthly = (int)$oTenant->assigned_monthly - $existingMonthly;
        $remainingMonthly = $tenantMonthly - $assignedMonthly;
        if ($userMonthly > $remainingMonthly) {
          throw new CoreException(
            409,
            "Monthly limit ($userMonthly) exceeds remaining tenant cap ($remainingMonthly of $tenantMonthly)"
          );
        }
      }

      // PBX quota cascade: user's allocation cannot exceed remaining tenant pool
      $pbxFields = [
        'quota_ring_groups'   => ['assigned_ring_groups',   PbxQuota::RING_GROUP],
        'quota_call_queues'   => ['assigned_call_queues',   PbxQuota::CALL_QUEUE],
        'quota_voicemail'     => ['assigned_voicemail',     PbxQuota::VOICEMAIL],
        'quota_conference'    => ['assigned_conference',    PbxQuota::CONFERENCE],
        'quota_music_on_hold' => ['assigned_music_on_hold', PbxQuota::MUSIC_ON_HOLD],
        'quota_extensions'    => ['assigned_extensions',  PbxQuota::EXTENSIONS],
        'quota_devices'       => ['assigned_devices',      PbxQuota::DEVICES],
        'quota_ivr_menus'     => ['assigned_ivr_menus',    PbxQuota::IVR_MENU],
      ];
      foreach ($pbxFields as $field => [$assignedField, $rid]) {
        $userVal = $oUser->$field;
        if ($userVal === null || $userVal === '') continue; // NULL = no specific allocation
        $userVal = (int)$userVal;
        if ($userVal <= 0) continue;

        $tenantLimit = PbxQuota::getTenantLimit($oUser->tenant_id, $rid);
        if ($tenantLimit <= 0) continue; // no quota row, skip

        $existingVal = 0;
        if ($isUpdate && !empty($oUser->user_id)) {
          try {
            $ex = new User($oUser->user_id);
            $existingVal = (int)$ex->$field;
          } catch (CoreException $e) {}
        }
        $assignedToOthers = (int)$oTenant->$assignedField - $existingVal;
        $remaining = $tenantLimit - $assignedToOthers;
        if ($userVal > $remaining) {
          throw new CoreException(
            409,
            "PBX quota for $field ($userVal) exceeds remaining tenant cap ($remaining of $tenantLimit)"
          );
        }
      }
    }
  }

  private function _split_perm($raw)
  {
    if ($raw === null || $raw === '' || $raw === 'null') return array();
    $parts = array_map('trim', explode(',', (string)$raw));
    return array_values(array_filter($parts, function($p){ return $p !== ''; }));
  }
}
