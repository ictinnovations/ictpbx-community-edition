<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Account;
use ICT\Core\User\Permission;
use ICT\Core\Message\Document;

#[\AllowDynamicProperties]
class Tenant {

  const GUEST = -1;

  private static $table = 'tenant';
  private static $link_permission = 'tenant_permission';
  private static $primary_key = 'tenant_id';
  private static $fields = array(
      'tenant_id',
      'first_name',
      'last_name',
      'company',
      'phone',
      'email',
      'address',
      'country_id',
      'timezone_id',
      'active',
      'credit',
      'daily_limit',
      'monthly_limit'
  );
  private static $read_only = array(
      'tenant_id',
  );

  /**
   * @property-read integer $tenant_id
   * @var integer
   */
  public $tenant_id = NULL;

  /** @var string */
  public $first_name = NULL;

  /** @var string */
  public $last_name = NULL;

  /** @var string */
  public $company = NULL;

  /** @var string */
  public $phone = NULL;

  /** @var string */
  public $email = NULL;

  /** @var string */
  public $address = NULL;

  /** @var integer */
  public $country_id = NULL;

  /** @var integer */
  public $timezone_id = NULL;

  /** @var integer */
  public $active = 0;

  /** @var float */
  public $credit = 0;

  /** @var string */
  public $daily_limit = NULL;

  /** @var string */
  public $monthly_limit = NULL;

  /** @var integer */
  public $owner_id = null;

  /** @var array $permissions  */
  public $permissions = array();

  /** @var bool tracks whether permissions were explicitly assigned this request */
  private $_permissions_dirty = false;

  public static $media_supported = array(
      'png'  => 'image/png',
      'jpg'  => 'image/jpeg',
      'jpeg' => 'image/x-citrix-jpeg',
  );

  /**
   * ***************************************************** Runtime Variables **
   */

  /** @var array $aPermission  */
  private $aPermission = array();


  public function __construct($tenant_id = NULL)
  {
    if (!empty($tenant_id) && $tenant_id > 0) {
      $this->tenant_id = $tenant_id;
      $this->load();
    }
  }

  public static function search($aFilter = array())
  {
    $aTenant = array();
    $from_str = ''; //self::$table;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
         case 'tenant_id':
         case 'first_name':
         case 'last_name':
         case 'company':
         case 'phone':
         case 'email':
          $aWhere[] = "t.$search_field = '$search_value'";
          break;
        case 'created_by':
          $aWhere[] = "t.created_by = '$search_value'";
          break;
        case 'before':
          $aWhere[] = "t.date_created <= $search_value";
          break;
        case 'after':
          $aWhere[] = "t.date_created >= $search_value";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }

    $query = "SELECT t.tenant_id, t.first_name, t.last_name, t.company, t.email, t.daily_limit, t.monthly_limit,
              SUM(u.daily_limit) AS assigned_daily,
              SUM(u.monthly_limit) AS assigned_monthly,
              SUM(u.daily_sent) AS sent_daily,
              SUM(u.monthly_sent) AS sent_monthly
              FROM tenant t LEFT join usr u ON t.tenant_id = u.tenant_id " .
              $from_str . " GROUP BY t.tenant_id";
    Corelog::log("tenant search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query('tenant', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aTenant[] = $data;
    }

    return $aTenant;
  }

  public function search_permission($aFilter = array()) {
    $aFilter['query'] = "SELECT tp.permission_id FROM " . self::$link_permission . " tp WHERE tp.tenant_id=" . $this->tenant_id;
    return Permission::search($aFilter);
  }

  public function get_permission($name){
    $aFilter = array();
    $aFilter['name'] = $name;
    return Permission::search($aFilter);
  }

  protected function load()
  {
    $query = "SELECT t.*,
              SUM(u.daily_limit) AS assigned_daily,
              SUM(u.monthly_limit) AS assigned_monthly,
              SUM(u.daily_sent) AS sent_daily,
              SUM(u.monthly_sent) AS sent_monthly,
              SUM(COALESCE(u.quota_ring_groups,0))   AS assigned_ring_groups,
              SUM(COALESCE(u.quota_call_queues,0))   AS assigned_call_queues,
              SUM(COALESCE(u.quota_voicemail,0))     AS assigned_voicemail,
              SUM(COALESCE(u.quota_conference,0))    AS assigned_conference,
              SUM(COALESCE(u.quota_music_on_hold,0)) AS assigned_music_on_hold,
              SUM(COALESCE(u.quota_extensions,0))    AS assigned_extensions,
              SUM(COALESCE(u.quota_devices,0))       AS assigned_devices,
              SUM(COALESCE(u.quota_ivr_menus,0))     AS assigned_ivr_menus
              FROM tenant t left join usr u ON t.tenant_id = u.tenant_id
              WHERE t.tenant_id = '%tenant_id%'
              GROUP BY t.tenant_id";
    $result = DB::query(self::$table, $query, array('tenant_id' => $this->tenant_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->tenant_id = $data['tenant_id'];
      $this->first_name = $data['first_name'];
      $this->last_name = $data['last_name'];
      $this->company = $data['company'];
      $this->phone = $data['phone'];
      $this->email = $data['email'];
      $this->address = $data['address'];
      $this->country_id = $data['country_id'];
      $this->timezone_id = $data['timezone_id'];
      $this->active = $data['active'];
      $this->credit = $data['credit'];
      $this->daily_limit = $data['daily_limit'];
      $this->monthly_limit = $data['monthly_limit'];
      $this->daily_sent = $data['daily_sent'];
      $this->monthly_sent = $data['monthly_sent'];
      $this->assigned_daily = $data['assigned_daily'];
      $this->assigned_monthly = $data['assigned_monthly'];
      $this->assigned_ring_groups   = $data['assigned_ring_groups'];
      $this->assigned_call_queues   = $data['assigned_call_queues'];
      $this->assigned_voicemail     = $data['assigned_voicemail'];
      $this->assigned_conference    = $data['assigned_conference'];
      $this->assigned_music_on_hold = $data['assigned_music_on_hold'];
      $this->assigned_extensions    = $data['assigned_extensions'];
      $this->assigned_devices       = $data['assigned_devices'];
      $this->assigned_ivr_menus     = $data['assigned_ivr_menus'];
      Corelog::log("Tenant loaded company: $this->company", Corelog::CRUD);

      // Load Permissions
      $this->load_permission();
      $this->permissions = $this->aPermission;
    } else {
      throw new CoreException('404', 'Tenant not found');
    }
  }

  private function load_permission()
  {
    $this->aPermission = array();
    $listPermission = $this->search_permission();
    foreach($listPermission as $aPermission) {
      $permission_id = $aPermission['permission_id'];
      $this->aPermission[$permission_id] = $aPermission['name'];
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
    } else if (empty($field) || in_array($field, static::$read_only)) {
      return;
    } else {
      if ($field === 'permissions') {
        $this->_permissions_dirty = true;
      }
      $this->$field = $value;
    }
  }

  public function delete()
  {
    Corelog::log("Tenant delete", Corelog::CRUD);

    // Cascade-delete all accounts belonging to this tenant
    $tenant_accounts = Account::search(['tenant_id' => $this->tenant_id]);
    foreach ($tenant_accounts as $aAccount) {
      $oAccount = new Account($aAccount['account_id']);
      $oAccount->delete();
    }

    // Cascade-delete all users belonging to this tenant
    $tenant_users = User::search(['tenant_id' => $this->tenant_id]);
    foreach ($tenant_users as $aUser) {
      $oUser = new User($aUser['user_id']);
      $oUser->delete();
    }

    // Delete tenant-scoped supporting records
    DB::query('tenant_permission', 'DELETE FROM tenant_permission WHERE tenant_id=%tenant_id%', ['tenant_id' => $this->tenant_id]);
    DB::query('branding',          'DELETE FROM branding WHERE tenant_id=%tenant_id%',          ['tenant_id' => $this->tenant_id]);

    return DB::delete(self::$table, 'tenant_id', $this->tenant_id);
  }

  protected function set_logo_name($file_path)
  {
    global $path_data;
    $oSession = Session::get_instance();
    $user_id = empty(User::$user) ? 0 : $oSession->user->user_id;

    if (file_exists($file_path)) {
      $file_parts = explode('.', $file_path);
      $raw_type = strtolower(end($file_parts));
      $file_type = empty($raw_type) ? 'jpeg' : $raw_type;
      $logo_name = 'logo_' . $user_id . '_';
      $logo_name .= DB::next_record_id($logo_name);
      $img_file = $path_data . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $logo_name . '.' . $file_type;
      $file_data = file_get_contents($file_path);
      file_put_contents($img_file, $file_data);
    }
    $this->logo_name = $img_file;
  }

  public function save()
  {
    $data = array(
        'tenant_id' => $this->tenant_id,
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'company' => $this->company,
        'phone' => $this->phone,
        'email' => $this->email,
        'address' => $this->address,
        'country_id' => $this->country_id,
        'timezone_id' => $this->timezone_id,
        'active' => $this->active,
        'credit' => $this->credit,
        'daily_limit' => $this->daily_limit,
        'monthly_limit' => $this->monthly_limit
    );

    if (isset($data['tenant_id']) && !empty($data['tenant_id'])) {
      // update existing record
      $result = DB::update(self::$table, $data, 'tenant_id');
      Corelog::log("Tenant updated: $this->tenant_id", Corelog::CRUD);
      $activity = new Activity();
      $activity->userlogs("Update Tenant $this->first_name");
    } else {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->tenant_id = $data['tenant_id'];
      $activity = new Activity();
      $activity->userlogs("Add New Tenant $this->company");
      Corelog::log("New Tenant created: $this->tenant_id", Corelog::CRUD);
    }

    // Only sync permissions when the caller explicitly provided them.
    // Tenant form is org-only and does not send permissions; permission
    // cascade lives on usr.user_permission instead.
    if ($this->_permissions_dirty) {
      $query = 'DELETE FROM ' . self::$link_permission . ' WHERE tenant_id=%tenant_id%';
      DB::query(self::$link_permission, $query, array('tenant_id' => $this->tenant_id));
      if (!empty($this->permissions)){
        foreach ($this->permissions as $permission) {
          $permissionData = $this->get_permission($permission)[0];
          $pData = array(
            'tenant_id' => $this->tenant_id,
            'permission_id' => $permissionData['permission_id']
          );
          $pResult = DB::update(self::$link_permission, $pData, false);
        }
      }
    }

    return $result;
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
    // authorization fialed
    return false;
  }

}

?>
