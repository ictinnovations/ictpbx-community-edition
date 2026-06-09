<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use Exception;
use Firebase\JWT\JWT;
use ICT\Core\User\Permission;
use ICT\Core\User\Role;
use ICT\Core\Tenant;
use ICT\Core\Timezone;
use ICT\Core\Message\Document;
use phpseclib3\Crypt\Random;
use ICT\Core\Socket\SocketMessage;
use ICT\Core\Password_Policy;
use ICT\Core\CoreException;

#[\AllowDynamicProperties]
class User
{

  const GUEST = -1;

  const AUTH_TYPE_BASIC = 'basic';
  const AUTH_TYPE_DIGEST = 'digest';
  const AUTH_TYPE_BEARER = 'bearer';
  const AUTH_TYPE_NETWORK = 'network';
  const AUTH_TYPE_SAML = 'saml';

  private static $table = 'usr';
  private static $link_role = 'user_role';
  private static $link_permission = 'user_permission';
  private static $primary_key = 'usr_id';
  private static $fields = array(
    'user_id', // will be mapped to usr_id in database table
    'role_id',
    'tenant_id',
    'username',
    'passwd',
    'password_hash', // will be mapped to passwd in database table
    'password', // dummy field to hold plain password, will not be saved in database
    'first_name',
    'last_name',
    'phone',
    'email',
    'address',
    'company',
    'country_id',
    'cover',
    'timezone_id',
    'active',
    'credit',
    'daily_limit',
    'monthly_limit',
    'daily_sent',
    'monthly_sent',
    'quota_ring_groups',
    'quota_call_queues',
    'quota_voicemail',
    'quota_conference',
    'quota_music_on_hold',
    'quota_extensions',
    'quota_devices',
    'quota_ivr_menus',
    'allowed_timeslot',
    'allowed_days',
    'dashboard_cards',
    'auth_type',
    'verify',
    'appsecret',
    'status',
  );
  private static $read_only = array(
    'user_id',
    'role_id',
    'password_hash'
  );

  /**
   * @property-read integer $user_id
   * @var integer
   */
  public $user_id = NULL;

  /**
   * @property-read integer $role_id
   * not in use
   * @var integer
   */
  private $role_id = NULL;

  /** @var integer */
  public $tenant_id = null;


  /**
   * @property-read string $username
   * @see User::set_username
   * @var string
   */
  public $username = NULL;

  /** @var string */
  private $passwd = NULL;

  /** @var string */
  public $secret = NULL;

  /**
   * @property-write string $password
   * Accept plain password, which will be imediately converted into md5 hash
   * @see User::set_password
   */
  /**
   * @property-read string $password_hash
   * represent password hash value from database
   * @see User::get_password_hash
   */

  /** @var string */
  public $first_name = NULL;

  /** @var string */
  public $last_name = NULL;

  /** @var string */
  public $phone = NULL;

  /** @var string */
  public $contact = NULL;

  /** @var string */
  public $email = NULL;

  /** @var string */
  public $address = NULL;

  /** @var string */
  public $company = NULL;

  /** @var integer */
  public $country_id = NULL;


  /** @var integer */
  public $cover = NULL;

  /** @var string */
  public $language_id = NULL;

  /** @var integer */
  public $timezone_id = NULL;

  /** @var integer */
  public $active = 0;

  /** @var float */
  public $credit = 0;

  /** @var integer */
  public $daily_limit = 0;

  /** @var string */
  public $active_desc = NULL;

  /** @var integer */
  public $monthly_limit = 0;

  /** @var integer */
  public $daily_sent = 0;

  /** @var integer */
  public $monthly_sent = 0;

  /** @var integer */
  public $quota_ring_groups = NULL;

  /** @var integer */
  public $quota_call_queues = NULL;

  /** @var integer */
  public $quota_voicemail = NULL;

  /** @var integer */
  public $quota_conference = NULL;

  /** @var integer */
  public $quota_music_on_hold = NULL;

  /** @var integer */
  public $quota_extensions = NULL;

  /** @var integer */
  public $quota_devices = NULL;

  /** @var integer */
  public $quota_ivr_menus = NULL;

  /** @var string */
  public $allowed_timeslot = NULL;

  /** @var string */
  public $allowed_days = NULL;

  /** @var integer */
  public $is_admin = NULL;

  /** @var string */
  public $user_permission = NULL;

  /** @var integer */
  public $is_tenant = NULL;

  /** @var string */
  public $dashboard_cards = NULL;

  /** @var string */
  public $auth_type = NULL;

  /** @var integer */
  public $verify = 0;

  /** @var string */
  public $appsecret = NULL;

  /** @var string */
  public $status = NULL;


  /**
   * ***************************************************** Runtime Variables **
   */

  /**
   * @property-read string $aRole
   * list of Roles, to set role call role_assign
   * @see User::role_assign() and User::role_unassign()
   * @var Role[] $aRole
   */
  private $aRole = array();

  /** @var array $aPermission  */
  private $aPermission = array();

  /** @var bool tracks explicit role mutations so save() only touches user_role when intended */
  private $_roles_dirty = false;
  /** @var bool tracks explicit permission mutations so save() only touches user_permission */
  private $_permissions_dirty = false;

  public static $media_supported = array(
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/x-citrix-jpeg',
  );

  public function __construct($user_id = NULL)
  {
    if (!empty($user_id)) {
      if (!is_numeric($user_id)) {
        if (filter_var($user_id, FILTER_VALIDATE_EMAIL)) {
          $this->email = $user_id;
        } else {
          $this->username = $user_id;
        }
      } else {
        $this->user_id = $user_id;
        if (User::GUEST == $user_id) {
          Corelog::log("Guest user: creating instance", Corelog::CRUD);
          $this->user_id = User::GUEST;
          $this->username = 'guest';
          $this->first_name = 'Anonymous';
          $this->last_name = 'Guest';
          $this->email = 'no-reply@example.com';
          $this->phone = '1111111111';
          $this->address = Conf::get('company:address', 'PK');
          return $this->user_id; // don't proceed further
        }
      }
      $this->load();
    }
  }

  public static function search($aFilter = array())
  {
    $aUser = array();
    $totalrows = null;

    $from_str = '';
    $limitSql = '';
    $pageIndex = isset($aFilter['pageIndex']) ? (int)$aFilter['pageIndex'] : 0;
    $pageSize = isset($aFilter['pageSize']) ? (int)$aFilter['pageSize'] : 0;

    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'user_id':
          $aWhere[] = "u.usr_id = $search_value";
          break;
        case 'tenant_id':
          $aWhere[] = "u.tenant_id = $search_value";
          break;
        case 'username':
        case 'phone':
        case 'email':
        case 'first_name':
        case 'last_name':
          $aWhere[] = "u.$search_field = '$search_value'";
          break;
        case 'created_by':
          $aWhere[] = "u.created_by = '$search_value'";
          break;
        case 'totalrows':
          $totalrows = 1;
          break;
        case 'before':
          $aWhere[] = "u.date_created <= $search_value";
          break;
        case 'after':
          $aWhere[] = "u.date_created >= $search_value";
          break;
        case ($pageIndex > 0 && $pageSize > 0):
          $offset = ($pageIndex - 1) * $pageSize;
          $limit  = $pageSize * 5;
          $limitSql = " LIMIT $limit OFFSET $offset ";
          break;
        case ($pageSize > 0 && $pageIndex == 0):
          $offset = 0;
          $limit  = $pageSize * 5;
          $limitSql = " LIMIT $limit OFFSET $offset ";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }
    $query = "SELECT u.usr_id AS user_id,u.tenant_id,u.username,u.first_name,u.active_desc,u.last_name,u.phone,u.email,COALESCE(t.credit, 0) AS credit,u.dashboard_cards,u.user_permission,u.status,u.daily_limit,u.monthly_limit,u.daily_sent,u.monthly_sent,u.quota_ring_groups,u.quota_call_queues,u.quota_voicemail,u.quota_conference,u.quota_music_on_hold,u.quota_extensions,u.quota_devices,u.quota_ivr_menus,t.company,u.active,u.date_created,u.last_updated,u.pass_exp_in,u.email_send,SUM(CASE WHEN tr.origin = 'sendfax' THEN 1 ELSE 0 END) AS total_send,SUM(CASE WHEN tr.direction = 'inbound' THEN 1 ELSE 0 END) AS total_inbound,COALESCE(r.roles, '') AS roles FROM usr u LEFT JOIN tenant t ON t.tenant_id = u.tenant_id LEFT JOIN transmission tr ON tr.created_by = u.usr_id LEFT JOIN ( SELECT ur.usr_id, GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles FROM user_role ur JOIN role r ON r.role_id = ur.role_id GROUP BY ur.usr_id) r ON r.usr_id = u.usr_id $from_str
    GROUP BY u.usr_id, u.tenant_id, u.username, u.first_name, u.last_name,u.phone, u.email, u.dashboard_cards,u.daily_limit, u.monthly_limit, u.daily_sent,u.monthly_sent,u.quota_ring_groups,u.quota_call_queues,u.quota_voicemail,u.quota_conference,u.quota_music_on_hold,u.quota_extensions,u.quota_devices,u.quota_ivr_menus, t.company ORDER BY u.usr_id DESC $limitSql";

    if ($totalrows) return totalrows($query);
    Corelog::log("user search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query('user', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      // Add is_admin & is_tenant
      $data["is_admin"] = (can_access('super_admin', $data["user_id"])) ? "1" : "0";
      $data["is_tenant"] = (can_access('user_admin', $data["user_id"])) ? "1" : "0";
      $aUser[] = $data;
    }

    // if no user found, check for guest user
    if (empty($aUser) && isset($aFilter['user_id']) && $aFilter['user_id'] == User::GUEST) {
      $oUser = new User($aFilter['user_id']);
      $aUser[$oUser->user_id] = array(
        'user_id' => $oUser->user_id,
        'tenant_id' => $oUser->tenant_id,
        'username' => $oUser->username,
        'first_name' => $oUser->first_name,
        'last_name' => $oUser->last_name,
        'phone' => $oUser->phone,
        'email' => $oUser->email,
        'credit' => 0
      );
    }

    return $aUser;
  }

  public static function get_data($aFilter = array())
  {
    $aUser = array();
    $pagesize = null;
    $pageIndex = null;
    $totalrows = null;

    $from_str = '';
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'user_id':
          $aWhere[] = "u.usr_id = $search_value";
          break;
        case 'tenant_id':
          $aWhere[] = "u.tenant_id = $search_value";
          break;
        case 'username':
        case 'phone':
        case 'email':
        case 'first_name':
        case 'last_name':
          $aWhere[] = "u.$search_field = '$search_value'";
          break;

        case 'created_by':
          $aWhere[] = "u.created_by = '$search_value'";
          break;
        case 'pagesize':
          $pagesize = $search_value;
          break;
        case 'pageIndex':
          $pageIndex = $search_value;
          break;
        case 'totalrows':
          $totalrows = 1;
          break;
          break;
        case 'before':
          $aWhere[] = "u.date_created <= $search_value";
          break;
        case 'after':
          $aWhere[] = "u.date_created >= $search_value";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }

    $query = " SELECT u.usr_id AS user_id, u.tenant_id, u.username, u.first_name, u.last_name, u.phone, u.email, COALESCE(t.credit, 0) AS credit, u.dashboard_cards, u.daily_limit, u.monthly_limit, u.daily_sent, u.monthly_sent, u.quota_ring_groups, u.quota_call_queues, u.quota_voicemail, u.quota_conference, u.quota_music_on_hold, u.quota_extensions, u.quota_devices, u.quota_ivr_menus, t.company,u.active,
    SUM(CASE WHEN tr.origin = 'sendfax' THEN 1 ELSE 0 END) AS total_send,
    SUM(CASE WHEN tr.direction = 'inbound' THEN 1 ELSE 0 END) AS total_inbound,
    (SELECT GROUP_CONCAT(r.name) FROM user_role ur JOIN role r ON ur.role_id = r.role_id WHERE ur.usr_id = u.usr_id) AS roles
    FROM usr u LEFT JOIN tenant t ON t.tenant_id = u.tenant_id LEFT JOIN transmission tr ON tr.created_by = u.usr_id " . $from_str . "
    GROUP BY u.usr_id, u.tenant_id, u.username, u.first_name, u.last_name, u.phone, u.email, COALESCE(t.credit, 0) AS credit, u.dashboard_cards, u.daily_limit, u.monthly_limit, u.daily_sent, u.monthly_sent, u.quota_ring_groups, u.quota_call_queues, u.quota_voicemail, u.quota_conference, u.quota_music_on_hold, u.quota_extensions, u.quota_devices, u.quota_ivr_menus, t.company";

    Corelog::log("user search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query('user', $query);
    if ($totalrows) {
      return mysqli_num_rows($result);
    }
    while ($data = mysqli_fetch_assoc($result)) {
      // Add is_admin & is_tenant
      $data["is_admin"] = (can_access('super_admin', $data["user_id"])) ? "1" : "0";
      $data["is_tenant"] = (can_access('user_admin', $data["user_id"])) ? "1" : "0";
      $aUser[] = $data;
    }

    // if no user found, check for guest user
    if (empty($aUser) && isset($aFilter['user_id']) && $aFilter['user_id'] == User::GUEST) {
      $oUser = new User($aFilter['user_id']);
      $aUser[$oUser->user_id] = array(
        'user_id' => $oUser->user_id,
        'tenant_id' => $oUser->tenant_id,
        'username' => $oUser->username,
        'first_name' => $oUser->first_name,
        'last_name' => $oUser->last_name,
        'phone' => $oUser->phone,
        'email' => $oUser->email,
        'credit' => 0
      );
    }

    return $aUser;
  }


  public function get_status($user_id)
  {
    $query = "SELECT status FROM usr WHERE usr_id= $user_id";

    $result = DB::query('usr', $query);
    $data = mysqli_fetch_assoc($result);
    return $data['status'];
  }


  public function search_role($aFilter = array())
  {
    $aFilter['query'] = "SELECT ur.role_id FROM " . self::$link_role . " ur WHERE ur.usr_id=" . $this->user_id;
    return Role::search($aFilter);
  }

  public function search_permission($aFilter = array())
  {
    $aFilter['query'] = "SELECT up.permission_id FROM " . self::$link_permission . " up WHERE up.usr_id=" . $this->user_id;
    return Permission::search($aFilter);
  }

  private function load()
  {
    Corelog::log("Loading user with id:" . $this->user_id . ' name:' . $this->username, Corelog::CRUD);
    if (!empty($this->email)) {
      $search_field = 'u.email';
      $search_value = $this->email;
    } else if (!empty($this->username)) {
      $search_field = 'u.username';
      $search_value = $this->username;
    } else {
      $search_field = 'u.usr_id';
      $search_value = $this->user_id;
    }
    $query = "SELECT u.*, COALESCE(t.credit, 0) AS tenant_credit FROM " . self::$table . " u LEFT JOIN tenant t ON t.tenant_id = u.tenant_id WHERE %search_field%='%search_value%'";
    $result = DB::query(self::$table, $query, array('search_field' => $search_field, 'search_value' => $search_value));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->user_id = $data['usr_id'];
      $this->role_id = $data['role_id'];
      $this->tenant_id = $data['tenant_id'];
      $this->username = $data['username'];
      $this->secret = $data['secret'];
      $this->passwd = $data['passwd'];
      $this->first_name = $data['first_name'];
      $this->last_name = $data['last_name'];
      $this->phone = $data['phone'];
      $this->contact = $data['mobile'];
      $this->email = $data['email'];
      $this->status = $data['status'];
      $this->address = $data['address'];
      $this->company = $data['company'];
      $this->user_permission = $data['user_permission'];
      $this->country_id = $data['country_id'];
      $this->cover = $data['cover'];
      $this->language_id = $data['language_id'];
      $this->timezone_id = $data['timezone_id'];
      $this->active = $data['active'];
      $this->credit = $data['tenant_credit'];
      $this->daily_limit = $data['daily_limit'];
      $this->monthly_limit = $data['monthly_limit'];
      $this->daily_sent = $data['daily_sent'];
      $this->monthly_sent = $data['monthly_sent'];
      $this->quota_ring_groups   = isset($data['quota_ring_groups'])   ? $data['quota_ring_groups']   : NULL;
      $this->quota_call_queues   = isset($data['quota_call_queues'])   ? $data['quota_call_queues']   : NULL;
      $this->quota_voicemail     = isset($data['quota_voicemail'])     ? $data['quota_voicemail']     : NULL;
      $this->quota_conference    = isset($data['quota_conference'])    ? $data['quota_conference']    : NULL;
      $this->quota_music_on_hold = isset($data['quota_music_on_hold']) ? $data['quota_music_on_hold'] : NULL;
      $this->quota_extensions    = isset($data['quota_extensions'])    ? $data['quota_extensions']    : NULL;
      $this->quota_devices       = isset($data['quota_devices'])       ? $data['quota_devices']       : NULL;
      $this->quota_ivr_menus     = isset($data['quota_ivr_menus'])     ? $data['quota_ivr_menus']     : NULL;
      $this->allowed_timeslot = $data['allowed_timeslot'];
      $this->allowed_days = $data['allowed_days'];
      $this->dashboard_cards = $data['dashboard_cards'];
      $this->auth_type = $data['auth_type'];
      $this->verify = $data['verify'];
      $this->appsecret = $data['appsecret'];
      $this->last_updated = $data['last_updated'];
      $this->date_created = $data['date_created'];
      $this->pass_exp_in = $data['pass_exp_in'];
      $this->active_desc = $data['active_desc'];
      $this->load_role();
      $this->load_permission();
      $this->is_admin  = $this->authorize('super_admin') ? "1" : "0";
      // Use direct role_id=3 check — hierarchical authorize('user_admin') falsely flags end users who have 'user' base permission
      $_is_tenant = false; foreach ($this->aRole as $_r) { if ($_r->role_id == 3) { $_is_tenant = true; break; } }
      $this->is_tenant = $_is_tenant ? "1" : "0";
      // load tenant permission
      $assignTenant = new Tenant($data['tenant_id']);
      $this->tenant_permissions = $assignTenant->permissions;
    } else {
      throw new CoreException('404', 'User not found');
    }
  }

  private function load_role()
  {
    $this->aRole = array();
    $listRole = $this->search_role();
    foreach ($listRole as $aRole) {
      $role_id = $aRole['role_id'];
      $this->aRole[$role_id] = new Role($role_id);
    }
  }

  private function load_permission()
  {
    $this->aPermission = array();
    $listPermission = $this->search_permission();
    foreach ($listPermission as $aPermission) {
      $permission_id = $aPermission['permission_id'];
      $this->aPermission[$permission_id] = $aPermission['name'];
    }
  }

  public function delete()
  {
    Corelog::log("Deleting user: $this->user_id", Corelog::CRUD);
    // first remove roles assignements for current user
    $query = 'DELETE FROM ' . self::$link_role . ' WHERE usr_id=%user_id%';
    DB::query(self::$link_role, $query, array('user_id' => $this->user_id));
    // then remove permissions for current user
    $query = 'DELETE FROM ' . self::$link_permission . ' WHERE usr_id=%user_id%';
    DB::query(self::$link_permission, $query, array('user_id' => $this->user_id));
    // now delete user
    $activity = new Activity();
    $activity->userlogs("Delete User Name:($this->username) -ID:($this->user_id)");

    return DB::delete(self::$table, 'usr_id', $this->user_id);
  }

  public function __isset($field)
  {
    $method_name = 'isset_' . $field;
    if (method_exists($this, $method_name)) {
      return $this->$method_name();
    } else {
      return isset($this->$field);
    }
  }

  public function __get($field)
  {
    $method_name = 'get_' . $field;
    if (method_exists($this, $method_name)) {
      return $this->$method_name();
    } else if (!empty($field) && isset($this->$field)) {
      return $this->$field;
    }
    return NULL;
  }

  public function __set($field, $value)
  {
    $method_name = 'set_' . $field;
    if (method_exists($this, $method_name)) {
      $this->$method_name($value);
    } else if (empty($field) || in_array($field, self::$read_only)) {
      return;
    } else {
      $this->$field = $value;
    }
  }

  public function get_id()
  {
    return $this->user_id;
  }

  private function set_role_id($role_id)
  {
    if (!empty($role_id)) {
      $this->role_id = (int)$role_id;
    }
  }

  private function set_username($username)
  {
    if (empty($this->username)) {
      $this->username = $username;
    }
  }

  private function set_password($password)
  {
    $oPolicy = new Password_Policy();
    $use_id = $this->user_id;
    $oPolicy->check_passwd($use_id, $password);
    $oPolicy->getPolicy();
    $mU = (int)$oPolicy->min_uppercase; $mL = (int)$oPolicy->min_lowercase;
    $mN = (int)$oPolicy->min_numbers;   $mS = (int)$oPolicy->special_character;
    $mLen = max(1, (int)$oPolicy->min_length);
    $rege = "/^(?=(?:[^A-Z]*[A-Z]){" . $mU . "})(?=(?:[^a-z]*[a-z]){" . $mL . "})(?=(?:\D*\d){" . $mN . "})(?=(?:\w*\W){" . $mS . "}).{" . $mLen . ",}$/";

    if (preg_match($rege, $password, $matches)) {
      $this->passwd = md5($password);
    } else {
      throw new CoreException(417, 'Password Policy did not matched');
    }
  }

  private function get_password_hash()
  {
    return $this->passwd;
  }

  public function role_assign($role_id)
  {
    $oRole = new Role($role_id);
    $this->aRole[$oRole->role_id] = $oRole;
    $this->_roles_dirty = true;
  }

  public function role_unassign($role_id)
  {
    unset($this->aRole[$role_id]);
    $this->_roles_dirty = true;
  }

  public function permission_assign($permission_id)
  {
    $oPermission = new Permission($permission_id);
    $this->aPermission[$oPermission->permission_id] = $oPermission->name;
    $this->_permissions_dirty = true;
  }

  public function permission_unassign($permission_id)
  {
    unset($this->aPermission[$permission_id]);
    $this->_permissions_dirty = true;
  }

  public function save()
  {
    $data = array(
      'usr_id' => $this->user_id,
      'user_id' => $this->user_id,
      'role_id' => $this->role_id,
      'secret' => $this->secret,
      'tenant_id' => $this->tenant_id,
      'username' => $this->username,
      'passwd' => $this->password_hash,
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'status' => $this->status,
      'phone' => $this->phone,
      'mobile' => $this->contact,
      'email' => $this->email,
      'address' => $this->address,
      'company' => $this->company,
      'user_permission' => $this->user_permission,
      'country_id' => $this->country_id,
      'cover' => $this->cover,
      'language_id' => $this->language_id,
      'timezone_id' => $this->timezone_id,
      'active' => $this->active,
      'credit' => $this->credit,
      'active_desc' => ($this->active == 1) ? '' : 'Deactive',
      'daily_limit' => $this->daily_limit,
      'quota_ring_groups'   => $this->quota_ring_groups,
      'quota_call_queues'   => $this->quota_call_queues,
      'quota_voicemail'     => $this->quota_voicemail,
      'quota_conference'    => $this->quota_conference,
      'quota_music_on_hold' => $this->quota_music_on_hold,
      'quota_extensions'    => $this->quota_extensions,
      'quota_devices'       => $this->quota_devices,
      'quota_ivr_menus'     => $this->quota_ivr_menus,
      'monthly_limit' => $this->monthly_limit,
      'allowed_timeslot' => '00:00,23:59',
      'allowed_days' => '1,2,3,4,5,6,7',
      'dashboard_cards' => $this->dashboard_cards,
      'auth_type' => $this->auth_type,
      'verify' => $this->verify,
      'appsecret' => $this->appsecret,
      'last_updated' => $this->last_updated,
      'date_created' => $this->date_created,
      'pass_exp_in' => $this->pass_exp_in,
      'email_send' => "0"

    );
    $this->check_exists($data);
    if (isset($data['user_id']) && !empty($data['user_id'])) {
      // Only wipe link tables when caller explicitly mutated roles/permissions.
      if ($this->_roles_dirty) {
        $query = 'DELETE FROM ' . self::$link_role . ' WHERE usr_id=%user_id%';
        DB::query(self::$link_role, $query, array('user_id' => $this->user_id));
      }
      if ($this->_permissions_dirty) {
        $query = 'DELETE FROM ' . self::$link_permission . ' WHERE usr_id=%user_id%';
        DB::query(self::$link_permission, $query, array('user_id' => $this->user_id));
      }
      // update existing record
      $result = DB::update(self::$table, $data, 'usr_id');
      Corelog::log("User updated: $this->user_id", Corelog::CRUD);
      $currentuser = Session::get_instance()->user->user_id;
      $activity = new Activity();
      if ($currentuser == $this->user_id) {
        $activity->userlogs("Update Own Account");
      } else {
        $data = array(
          $activity->userlogs("Update User [$this->username -ID:$this->user_id]"),
        );
      }
      //$this->socket_handling($this->user_id);
    } else {
      // add new
      $data['secret'] = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
      $result = DB::update(self::$table, $data, false);
      $data['user_id'] = $data['usr_id']; // mapping
      $this->user_id = $data['user_id'];
      Corelog::log("New user created: $this->user_id", Corelog::CRUD);

      $currentuser = Session::get_instance()->user->user_id;
      $activity = new Activity();
      $activity->userlogs("Create New User Name:($currentuser) -ID:($this->user_id)");
    }

    // Only sync link tables when caller explicitly mutated them.
    if ($this->_roles_dirty) {
      foreach ($this->aRole as $oRole) {
        $query = "INSERT INTO " . self::$link_role . " (usr_id, role_id) VALUES (%user_id%, %role_id%)";
        $result = DB::query(self::$link_role, $query, array('user_id' => $this->user_id, 'role_id' => $oRole->role_id));
      }
      $this->_roles_dirty = false;
    }

    if ($this->_permissions_dirty) {
      foreach (array_keys($this->aPermission) as $permission_id) {
        $query = "INSERT INTO " . self::$link_permission . " (usr_id, permission_id) VALUES (" . $this->user_id . "," . $permission_id . ")";
        $result = DB::query(self::$link_permission, $query, array('user_id' => $this->user_id, 'permission_id' => $permission_id));
      }
      $this->_permissions_dirty = false;
    }

    return $result;
  }

  public function check_exists(array $data)
  {

    if (!empty($data['username'])) {
      $query = "SELECT COUNT(*) AS cnt FROM usr WHERE username = '%username%'";
      if ($this->user_id !== null) {
        $query .= " AND usr_id != %user_id%";
      }
      $params = ['username' => $data['username']];
      if ($this->user_id !== null) {
        $params['user_id'] = (int)$this->user_id;
      }
      $res = DB::query('usr', $query, $params);
      $row = mysqli_fetch_assoc($res);
      if ((int)($row['cnt'] ?? 0) > 0) {
        throw new CoreException(409, 'Username already exists');
      }
    }

    if (!empty($data['email'])) {
      $query = "SELECT COUNT(*) AS cnt FROM usr WHERE email = '%email%'";
      if ($this->user_id !== null) {
        $query .= " AND usr_id != %user_id%";
      }
      $params = ['email' => $data['email']];
      if ($this->user_id !== null) {
        $params['user_id'] = (int)$this->user_id;
      }
      $res = DB::query('usr', $query, $params);
      $row = mysqli_fetch_assoc($res);
      if ((int)($row['cnt'] ?? 0) > 0) {
        throw new CoreException(409, 'Email already exists');
      }
    }
  }

  public function socket_handling($user_id)
  {
    $filter = array(
      'user_id' => $user_id
    );
    $user_data = self::search($filter);
    $user_data['module'] = 'user';
    SocketMessage::Message($user_data);
  }

  public function generate_token()
  {
    $key_file = Conf::get('security:private_key', '/usr/ictcore/etc/ssh/ib_node');
    $private_key = file_get_contents($key_file);
    $isTenant = false;
    $isAdmin = false;
    foreach ($this->aRole as $role) {
      if (isset($role->name) && $role->name === 'tenant') {
        $isTenant = true;
      }
      if (isset($role->name) && $role->name === 'admin') {
        $isAdmin = true;
      }
    }
    $is_ce = is_community_edition();
    $token = array(
      "iss" => Conf::get('website:url'),
      "iat" => time(),
      "nbf" => time(),
      "exp" => time() + Conf::get('security:token_expiry', (60 * 60 * 24 * 30 * 12 * 1)), // valid for one year
      "user_id" => $this->user_id,
      "retention_permission" => ($is_ce ? '0' : (($isTenant == true && !$isAdmin && in_array('retention_enabled', $this->tenant_permissions)) ? '1' : '0')),
      "tenant_id" => $is_ce ? 1 : $this->tenant_id,
      "username" => $this->username,
      "user_permission" => $this->user_permission,
      // "is_admin" => can_access('user_admin', $this->user_id) ? "1" : "0",
      "is_admin" => $is_ce ? ((can_access('super_admin', $this->user_id) || can_access('user_admin', $this->user_id)) ? "1" : "0") : ((can_access('super_admin', $this->user_id)) ? "1" : "0"),
      "is_tenant" => $is_ce ? "0" : ($this->is_tenant),
      "api-version" => "1.0",
      "mfa_enabled" => $is_ce ? "0" : (in_array('mfa_enabled', $this->tenant_permissions) ? "1" : "0"),  // pass MFA permission
      // "auth_type"   => $this->auth_type,
      "auth_verify" => $this->verify,
      "credit" => $this->credit,
    );

    if ($token['user_id']) {
      $activity = new Activity();
      $activity->userlogs("Logged In", $this->user_id, $this->username, $this->tenant_id);
    }

    return JWT::encode($token, $private_key, Conf::get('security:hash_type', 'RS256'));
  }

  // Decode token
  public static function decode_token($token)
  {
    try {
      $key_file = Conf::get('security:public_key', '/usr/ictcore/etc/ssh/ib_node.pub');
      $hash_type = Conf::get('security:hash_type', 'RS256');
      $public_key = file_get_contents($key_file);
      return JWT::decode($token, $public_key, array($hash_type));
    } catch (Exception $e) {
      Corelog::log('Unable to validate token. error: ' . $e->getMessage(), Corelog::ERROR);
    }
  }

  public function cehck_allowed_timeslot($oUser = null)
  {
    if ($oUser->timezone_id) $timezone_id = $oUser->timezone_id;
    else $timezone_id = 0;

    $zone_time    = time() + $timezone_id;          // current time as per timezone
    $current_day  = date('w', $zone_time) + 1;      // day number as per current time
    $current_time = date('H:i', $zone_time);        // current day time as per timezone
    $current_time = strtotime($current_time);       // time stamp as per current day time

    $allowed_timeslot = $oUser->allowed_timeslot;   // allowed timeslot for current user
    $allowed_days = $oUser->allowed_days;           // allowed days for current user

    if (!$this->timeslot_check($allowed_timeslot, $current_time) || !$this->weekday_check($allowed_days, $current_day)) {
      Corelog::log("Restricted time by destination timeslot", Corelog::INFO);
      throw new CoreException('404', 'Restricted time by destination timeslot.');
    }
    return true; // no else, i.e if timezone_id is false or null then we can skip above check safely
  }

  // Check if current time is allowed to send fax
  public function timeslot_check($time_allowed, $current_datetime)
  {
    $time = explode(',', $time_allowed);
    $from_time = strtotime($time[0] . " UTC");
    $to_time   = strtotime($time[1] . " UTC");
    $current_label = gmdate('H:i', $current_datetime);
    $current_time  = strtotime($current_label . " UTC");
    if (($current_time >= $from_time) && ($current_time <= $to_time)) {
      return true;
    }
    return false;
  }

  // Check if current day is allowed to send fax
  public function weekday_check($day_allowed, $current_day)
  {
    $day_allowed = explode(',', $day_allowed);
    if (in_array($current_day, $day_allowed)) {
      return true;
    }
    return false;
  }

  public static function authenticate($access_key, $key_type = User::AUTH_TYPE_BASIC)
  {
    $oUser = null;
    switch ($key_type) {
      case User::AUTH_TYPE_BEARER:
        try {
          $key_file = Conf::get('security:public_key', '/usr/ictcore/etc/ssh/ib_node.pub');
          $hash_type = Conf::get('security:hash_type', 'RS256');
          $public_key = file_get_contents($key_file);
          $token = JWT::decode($access_key, $public_key, array($hash_type));
          if ($token) {
            // TODO check api-version
            if (!empty($token->user_id)) {
              $oUser = new self($token->user_id);
              return $oUser;
            }
          }
        } catch (Exception $e) {
          Corelog::log('Unable to parse bearer token. error: ' . $e->getMessage(), Corelog::ERROR);
        }
        Corelog::log('Bearer authentication failed', Corelog::ERROR);
        return false;

      case User::AUTH_TYPE_SAML;
        if (!empty($access_key['email'])) {
          try {
            // return self::search(array('email' => $access_key['email']));
            return new self($access_key['email']);
          } catch (Exception $e) {
            Corelog::log('SAML authentication failed', Corelog::ERROR);
          }
        }
        return false;

      case User::AUTH_TYPE_SAML;
        if (!empty($access_key['email'])) {
          $oUser = new self($access_key['email']);
          return $oUser;
        }
        Corelog::log('SAML authentication failed', Corelog::ERROR);
        return false;

      case User::AUTH_TYPE_NETWORK:
        return false; // TODO

      case User::AUTH_TYPE_DIGEST:
        if (!empty($access_key['username'])) {
          $oUser = new self($access_key['username']);
          if ($oUser->get_password_hash() == $access_key['password']) {
            return $oUser;
          }
        }
        Corelog::log('Basic authentication failed', Corelog::ERROR);
        return false;

      case User::AUTH_TYPE_BASIC:
      default:
        if (!empty($access_key['username'])) {
          $oUser = new self($access_key['username']);
          if ($oUser->get_password_hash() == md5($access_key['password'])) {
            $oUser->attempts($oUser->user_id, 'success');
            if ($oUser->active == '0') {
              throw new CoreException('429', "User account disableds");
            }
            return $oUser;
          }
        }
        Corelog::log('Network authentication has been failed', Corelog::ERROR);
        try {
          $filter = array(
            'email' => $oUser->email
          );
          $user = User::search($filter);
          if (!empty($user) && $user[0]['is_admin'] != 1) {
            if ($oUser !== null) {
              $attempts = $oUser->attempts($oUser->user_id, 'failed');

              if ($attempts) {
                $result = $attempts[0]['attempts'];
                $oPolicy = new Password_Policy();
                $oPolicy->getPolicy();
                $rege = $oPolicy->failed_attempts;
                if ($result >= $rege) {
                  $query = "UPDATE usr SET active='0' WHERE usr_id = $oUser->user_id";
                  $fetch = DB::query('usr', $query);
                  $deleteQuery = "DELETE FROM login_attempt WHERE user_id = $oUser->user_id";
                  $del = DB::query('login_attempt', $deleteQuery);
                  throw new CoreException('423', "Too many failed attempts: $result");
                }
              }
            }
          }
        } catch (CoreException $e) {
          throw $e;
        } catch (Exception $e) {
          Corelog::log('Failed-login bookkeeping error: ' . $e->getMessage(), Corelog::ERROR);
        }
    }
  }

  public static function attempts($user_id, $attempt = '')
  {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $search_query = "SELECT l.attempts FROM usr AS u LEFT JOIN login_attempt AS l ON u.usr_id = l.user_id  WHERE u.usr_id = $user_id";
    $result = DB::query('login_attempt', $search_query);
    $data = mysqli_fetch_assoc($result);
    if ($attempt == 'failed' && !empty($data['attempts'])) {
      $query = "UPDATE login_attempt SET attempts = attempts + 1 WHERE user_id = $user_id";
    } else if ($attempt == 'success' && !empty($data)) {
      $query = "DELETE FROM login_attempt WHERE user_id = $user_id";
    } else {
      $query = "INSERT INTO login_attempt (user_id, attempts, ip_address) VALUES ($user_id, 1, '$ip_address')";
    }

    DB::query('login_attempt', $query);
    $aData = array();
    $result = DB::query('login_attempt', $search_query);
    while ($row = mysqli_fetch_assoc($result)) {
      $aData[] = $row;
    }
    return $aData;
  }

  public function authorize($permission)
  {
    $aPart = explode('_', $permission);
    $level = count($aPart);
    $perm = '';

    // first check if parent permission exist and then try for sub permissions
    for ($i = 0; $i < $level; $i++) {
      $perm .= $aPart[$i];
      if (in_array($perm, $this->aPermission)) {
        return true;
      } else {
        $perm .= '_';
      }
    }

    // now try with role permissions
    foreach ($this->aRole as $oRole) {
      if ($oRole->authorize($permission)) {
        return true;
      }
    }

    // authorization fialed
    return false;
  }

  public function check_quota($document)
  {
    if (is_community_edition()) return true;
    // Get document pages
    $oDocument = new Document($document['document_id']);
    $doc_pages = $oDocument->pages;

    // Check daily and monthly limit
    if ((($this->daily_sent + $doc_pages) > $this->daily_limit) or (($this->monthly_sent + $doc_pages) > $this->monthly_limit)) {
      Corelog::log("Fax limit exceeded for user id : $this->user_id", Corelog::INFO);
      throw new CoreException('404', 'Fax limit exceeded.');
    }
    return true;
  }

  public function consume_credit($credit = 1)
  {
    $query = "UPDATE " . self::$table . " SET daily_sent = daily_sent + $credit, monthly_sent = monthly_sent + $credit
              WHERE usr_id = " . $this->user_id . " AND (daily_limit - daily_sent) >= $credit AND (monthly_limit - monthly_sent) >= $credit";
    $result = DB::query(self::$table, $query);
    return mysqli_affected_rows(DB::$link);
  }

  public function reset_daily_sent()
  {
    $reset_query = "UPDATE " . self::$table . " SET daily_sent = 0 WHERE usr_id = " . $this->user_id;
    $result = DB::query(self::$table, $reset_query);
  }

  public function reset_monthly_sent()
  {
    $reset_query = "UPDATE " . self::$table . " SET monthly_sent = 0 WHERE usr_id = " . $this->user_id;
    $result = DB::query(self::$table, $reset_query);
  }

  public static function fetch_permission($user_id)
  {
    $user = new self($user_id);
    return $user->user_permission;
  }
}
