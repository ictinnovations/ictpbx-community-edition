<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\DB;
use ICT\Core\Tenant;
use ICT\Core\User;
use ICT\Core\Activity;
use ICT\Core\Corelog;
use ICT\Core\Password_Policy;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ICT\Core\Conf;

#[\AllowDynamicProperties]
class AuthenticateApi extends Api
{

  /**
   * Gets the user after authenticating provided credentials
   *
   * @noAuth
   * @url POST /authenticate
   */
  public function create($data = array())
  {
    $key_type = null;
    $credentials = null;

    if (isset($data['hash'])) {
      $key_type = User::AUTH_TYPE_DIGEST;
      $credentials = array('username' => null, 'password' => $data['hash']);
    } else if (isset($data['saml'])) {
      $key_type = User::AUTH_TYPE_SAML;
      $credentials = $data;
    } else if (isset($data['password_hash'])) {
      $key_type = User::AUTH_TYPE_DIGEST;
      $credentials = array('username' => null, 'password' => $data['password_hash']);
    } else {
      $key_type = User::AUTH_TYPE_BASIC;
      $credentials = array('username' => null, 'password' => $data['password']);
    }

    if (isset($data['email'])) {
      $credentials['username'] = $data['email'];
    } else if (isset($data['username'])) {
      $credentials['username'] = $data['username'];
    } else if (isset($data['user_id'])) {
      $credentials['username'] = $data['user_id'];
    } else {
      throw new CoreException(401, 'No valid username found');
      // throw new CoreException(401, $data);
    }

    // Validate User
    $oUser = User::authenticate($credentials, $key_type);
    if (empty($oUser)) {
      throw new CoreException(401, 'Invalid username or password');
    }

    //check password limit
    $username = array(
      'email' => $credentials['username']
    );
    $user = User::search($username);
    if (!$oUser->authorize('super_admin')) {
      $oPolicy = new Password_Policy();
      $oPolicy->getPolicy();
      $passwdExpLimit = $oPolicy->passwd_exp_limit;
      if ($oUser->pass_exp_in >= $passwdExpLimit) {
        throw new CoreException(426, 'User password has expired');
      }
    }

    if ($oUser) {
      $usr_id = $oUser->user_id;
      $query = "UPDATE usr SET status='login' WHERE usr_id=$usr_id";
      $result = DB::query('usr', $query);
      $oUser->token = $oUser->generate_token();
      $oUser->access_token = $oUser->token;
      $oUser->expires_in = (60 * 60 * 24 * 30 * 12 * 1); // valid for one year
      $oUser->token_type = 'Bearer';
      $oUser->scope = 'All';
      if (!\ICT\Core\is_community_edition()) {
        $oldTenant = new Tenant($oUser->tenant_id);
        $oUser->tenant_permissions = $oldTenant->permissions;
      }
      return $oUser;

      // $this->generateLoginToken($oUser);
    } elseif (!$oUser && isset($credentials['saml'])) {

      // Create Tenant
      $newTenant = new Tenant();
      $newTenant->first_name = $credentials['first_name'];
      $newTenant->last_name = $credentials['last_name'];
      $newTenant->email = $credentials['email'];

      // Check if Tenant created then create new user
      if ($newTenant->save()) {
        $newUser = new User();
        $newUser->tenant_id = $newTenant->tenant_id;
        $newUser->first_name = $credentials['first_name'];
        $newUser->last_name = $credentials['last_name'];
        $newUser->email = $credentials['email'];

        if ($newUser->save()) {
          // Add Roles
          $Roles = array(1, 3);
          foreach ($Roles as $role_id) {
            $query = "INSERT INTO user_role (usr_id, role_id) VALUES (%user_id%, %role_id%)";
            $result = DB::query('user_role', $query, array('user_id' => $newUser->user_id, 'role_id' => $role_id));
          }
          // Authnicate again 
          $oUser = User::authenticate($credentials, $key_type);
          if ($oUser) return generateLoginToken($oUser);

          throw new CoreException(401, 'SAML validation failed.');
          // self::create($credentials);
        }
      }
    }

    throw new CoreException(401, 'Invalid user name and password.');
    //   throw new CoreException(401, 'Invalid user name and password: '.$ex->getMessage());

  }

  // Return User data with token
  public function generateLoginToken($user)
  {
    $oUser->token = $user->generate_token();
    $oUser->access_token = $oUser->token;
    $oUser->expires_in = (60 * 60 * 24 * 30 * 12 * 1); // valid for one year
    $oUser->token_type = 'Bearer';
    $oUser->scope = 'All';
    return $oUser;
  }

  /**
   * Get token payload
   *
   * @noAuth
   * @url POST /token_payload
   */
  public function tokenPayload($token)
  {
    try {
      $key_file = Conf::get('security:public_key', '/usr/ictcore/etc/ssh/ib_node.pub');
      $hash_type = Conf::get('security:hash_type', 'RS256');
      $public_key = file_get_contents($key_file);
      $payload = JWT::decode($token, $public_key, array($hash_type));
      if ($payload) return $payload;
    } catch (Exception $e) {
      Corelog::log('Unable to validate token. error: ' . $e->getMessage(), Corelog::ERROR);
    }
  }

  /**
   * Cancel current authentication token
   *
   * @noAuth
   * @url POST /authenticate/cancel/$user_id
   */
  public function cancel($user_id)
  {
    $user = new User($user_id);
    $username = $user->username;
    $tenant_id = $user->tenant_id;
    $activity = new Activity();
    $activity->userlogs("Logged Out", $user_id, $username);
    $query = "UPDATE usr SET status = 'logout' WHERE usr_id = $user_id";
    $result = DB::query('usr', $query);
    return true;
  }
}
