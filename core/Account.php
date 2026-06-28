<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\User;
use ICT\Core\FpbxDomain;

class Account
{

  /** @const */
  const USER_DEFAULT = -1;
  const COMPANY = -2;
  const ANONYMOUS = -3;

  protected static $table = 'account';
  protected static $primary_key = 'account_id';
  protected static $fields = array(
      'account_id',
      'tenant_id',
      'type',
      'username',
      'passwd',
      'passwd_pin',
      'first_name',
      'last_name',
      'phone',
      'email',
      'linkdid_id',
      'address',
      'settings',
      'caller_id_name',
      'domain',
      'active',
      'user_id'
  );
  protected static $read_only = array(
      'account_id',
      'type',
      'user_id'
  );

  /**
   * @property-read integer $account_id
   * @var integer
   */
  public $account_id = NULL;

  /**
   * @var integer
   */
  public $tenant_id = NULL;

  /**
   * @property-read string $type
   * @var string 
   */
  protected $type = 'account';

  /**
   * @property string $username
   * @see void function Account::set_username()
   * @var string 
   */
  public $username = NULL;

  /** @var string */
  public $passwd = NULL;

  /** @var string */
  public $passwd_pin = NULL;

  /** @var string */
  public $first_name = NULL;

  /** @var string */
  public $last_name = NULL;

  /** @var string */
  public $phone = NULL;

  /** @var string */
  public $email = NULL;

 /** @var integer */
  public $linkdid_id = NULL;

  /** @var string */
  public $address = NULL;

  /** @var array */
  public $settings = array();

  /** @var integer */
  public $active = 0;

    /** @var string */
  public $caller_id_name = NULL;

    /** @var string */
  public $domain = NULL;

  /**
   * @property-read integer $user_id
   * @see void function Account::associate()
   * @var integer
   */
  public $user_id = NULL;

  public function capabilities()
  {
    return (Transmission::INBOUND | Transmission::OUTBOUND);
  }

  public function __construct($account_id = NULL)
  {
    if (!empty($account_id)) {
      $this->account_id = $account_id;
      if (Account::ANONYMOUS == $account_id) {
        Corelog::log("Anonymous account: creating instance", Corelog::CRUD);
        $this->account_id = $account_id;
        $this->first_name = 'Anonymous';
        $this->last_name = 'User';
        $this->email = 'anonymous@unknown.com';
        $this->phone = '0000000000';
        $this->address = 'Unknown';
        return $account_id; // don't proceed further        
      } else if (Account::COMPANY == $account_id) {
        Corelog::log("Company account: creating instance", Corelog::CRUD);
        $this->account_id = $account_id;
        $title = Conf::get('company:name', 'ICTCore');
        $aTitle = explode(' ', $title, 2);
        $this->first_name = $aTitle[0];
        $this->last_name = isset($aTitle[1]) ? $aTitle[1] : '';
        $this->email = Conf::get('company:email', 'no-reply@example.com');
        $this->phone = Conf::get('company:phone', '1111111111');
        $this->address = Conf::get('company:address', 'PK');
        return $account_id; // don't proceed further
      } else if (Account::USER_DEFAULT == $account_id) {
        Corelog::log("Default account: creating instance", Corelog::CRUD);
        $oSession = Session::get_instance();
        $query = "SELECT account_id FROM " . self::$table . " WHERE created_by=%user_id% AND type IN ('account','child_account') 
                   ORDER BY account_id DESC LIMIT 1";
        $result = DB::query(self::$table, $query, array('user_id' => $oSession->user->user_id));
        $data = mysqli_fetch_assoc($result);
        $this->account_id = $data['account_id'];
      }
      $this->_load();
    }
  }

  public static function construct_from_array($aAccount)
  {
    $oAccount = new Account();
    foreach ($aAccount as $field => $value) {
      $oAccount->$field = $value;
    }
    return $oAccount;
  }

  public static function locate($search_value, $contactField = 'phone')
  {
    // locate an existing account
    $accountFilter = array($contactField => $search_value);
    $listAccount = static::search($accountFilter);
    if ($listAccount) {
      $aAccount = array_shift($listAccount);
      $oAccount = new static($aAccount['account_id']);
      return $oAccount;
    }
    return false; // no account found
  }
  
  public static function search($aFilter = array())
  {
    $aAccount = array();
    $from_str = '';
    $aWhere = array();
    if (!isset($aFilter['type'])){
      $aWhere[] = " type != 'child_account' ";
    }
    $totalrows = null;
    $limitSql = '';
    $pageIndex = isset($aFilter['pageIndex']) ? (int)$aFilter['pageIndex'] : 0;
    $pageSize = isset($aFilter['pageSize']) ? (int)$aFilter['pageSize'] : 0;
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'account_id':
        case 'tenant_id':
          case 'linkdid_id':
          $aWhere[] = "a.$search_field = $search_value";
          break;
        case 'type':
          $aWhere[] = "a.$search_field = '$search_value'";
          break;
        case 'username':
        case 'phone':
        case 'email':
        case 'passwd':
        case 'passwd_pin':
        case 'first_name':
        case 'last_name':
          $aWhere[] = "a.$search_field LIKE '%$search_value%'";
          break;

        case 'user_id':
        case 'created_by':
          $aWhere[] = "a.created_by = '$search_value'";
          break;
        case 'before':
          $aWhere[] = "a.date_created <= $search_value";
          break;
        case 'after':
          $aWhere[] = "a.date_created >= $search_value";
          break;
        case 'totalrows':
          $totalrows = 1;
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

    $query = "SELECT a.account_id, a.tenant_id, a.type, a.username, a.first_name, a.last_name, a.phone, a.email, a.caller_id_name, a.domain, a.created_by, t.company
              FROM account a LEFT JOIN tenant t ON t.tenant_id = a.tenant_id" . $from_str .  " ORDER BY a.account_id DESC " . $limitSql;
    if ($totalrows) return totalrows(null, 'account', 'a', $aWhere); 
    Corelog::log("account search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query('account', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aAccount[] = $data;
    }

    // if no account found, check for special accounts
    $special_accounts = array(Account::USER_DEFAULT, Account::COMPANY, Account::ANONYMOUS);
    if (empty($aAccount) && isset($aFilter['account_id']) && in_array($aFilter['account_id'], $special_accounts)) {
      $oAccount = new Account($aFilter['account_id']);
      $aAccount[$oAccount->account_id] = array(
          'account_id' => $oAccount->account_id,
          'tenant_id' => $oAccount->tenant_id,
          'type' => 'account',
          'username' => $oAccount->username,
          'first_name' => $oAccount->first_name,
          'last_name' => $oAccount->last_name,
          'phone' => $oAccount->phone,
          'email' => $oAccount->email
      );
    }

    return $aAccount;
  }

  public static function linkdid_accounts($account_id){
    $query = "SELECT a.account_id, a.tenant_id, a.type, a.username, a.first_name, a.last_name, a.phone, a.email, a.created_by, t.company
              FROM account a LEFT JOIN tenant t ON t.tenant_id = a.tenant_id WHERE a.linkdid_id = $account_id";
    $result = DB::query('account', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aAccount[] = $data;
    }
   return $aAccount;
  }


  public static function getClass(&$account_id, $namespace = 'ICT\\Core\\Account')
  {
    if (ctype_digit(trim($account_id))) {
      $query = "SELECT type FROM " . self::$table . " WHERE account_id='%account_id%' ";
      $result = DB::query(self::$table, $query, array('account_id' => $account_id));
      if ($result instanceof \mysqli_result) {
        while($row = mysqli_fetch_assoc($result)) {
          $account_type = $row['type'];
        }
      }
    } else {
      $account_type = $account_id;
      $account_id   = null;
    }
    $class_name = ucfirst(strtolower(trim($account_type)));
    if (!empty($namespace)) {
      $class_name = $namespace . '\\' . $class_name;
    }
    if (class_exists($class_name, true)) {
      return $class_name;
    } else {
      return false;
    }
  }

  public static function load($account_id)
  {
    if ($account_id < 0) {
      return new self($account_id);
    }
    $class_name = self::getClass($account_id);
    if ($class_name) {
      Corelog::log("Creating instance of : $class_name for account: $account_id", Corelog::CRUD);
      return new $class_name($account_id);
    } else {
      Corelog::log("$class_name class not found, Creating instance of : Account", Corelog::CRUD);
      return new self($account_id);
    }
  }

  protected function _load()
  {
    Corelog::log("Loading account: $this->account_id", Corelog::CRUD);
    $query = "SELECT * FROM " . self::$table . " WHERE account_id='%account_id%' ";
    $result = DB::query(self::$table, $query, array('account_id' => $this->account_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->account_id = $data['account_id'];
      $this->tenant_id = $data['tenant_id'];
      $this->type = $data['type'];
      $this->username = $data['username'];
      $this->passwd = $data['passwd'];
      $this->passwd_pin = $data['passwd_pin'];
      $this->first_name = $data['first_name'];
      $this->last_name = $data['last_name'];
      $this->phone = $data['phone'];
      $this->email = $data['email'];
      $this->address = $data['address'];
      $this->settings = json_decode($data['settings'], true);
      $this->active = $data['active'];
      $this->caller_id_name = $data['caller_id_name'];
      $this->domain = $data['domain'];
      $this->user_id = $data['created_by'];
      $this->linkdid_id = $data['linkdid_id'] ?? null;

      if (!is_array($this->settings)) {
        $this->settings = array();
      }
    } else {
      throw new CoreException('404', 'Account not found');
    }

  }

  public function delete()
  {
    Corelog::log("Deleting account: $this->account_id", Corelog::CRUD);
    if ($this->type === 'did') {
      // Guard: block if any fax accounts are linked to this DID
      $res = DB::query('account',
        "SELECT phone FROM account WHERE linkdid_id = %lid%",
        ['lid' => $this->account_id]);
      $phones = [];
      while ($row = mysqli_fetch_assoc($res)) { $phones[] = $row['phone']; }
      if (!empty($phones)) {
        throw new CoreException(409,
          "Cannot delete DID: Fax Account(s) " . implode(', ', $phones) . " are linked. Unlink them first.");
      }
      // Guard: block if an inbound dialplan route exists for this DID
      $res2 = DB::query('dialplan',
        "SELECT dialplan_id FROM dialplan WHERE destination = '%dest%'",
        ['dest' => $this->phone]);
      if (mysqli_fetch_assoc($res2)) {
        throw new CoreException(409,
          "Cannot delete DID [{$this->phone}]: an inbound dialplan route exists. Delete the inbound route first.");
      }
    }
    // also delete all installed program
    $this->remove_program('all');
    // now delete account
    return DB::delete(self::$table, 'account_id', $this->account_id);
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
    return $this->account_id;
  }

  protected function set_username($username)
  {
    if (empty($this->username)) {
      $this->username = $username;
    }
  }

  public function save()
  {
    $data = array(
        'account_id' => $this->account_id,
        'tenant_id' => $this->tenant_id,
        'type' => $this->type,
        'username' => $this->username,
        'passwd' => $this->passwd,
        'passwd_pin' => $this->passwd_pin,
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'phone' => $this->phone,
        'email' => $this->email,
        'linkdid_id' => $this->linkdid_id,
        'address' => $this->address,
        'settings' => json_encode($this->settings, JSON_NUMERIC_CHECK),
        'caller_id_name' => $this->caller_id_name,
        'domain' => $this->domain,
        'active' => $this->active
            // Note: user_id or created_by field can't be updated here, instead use associate method
    );
 
    $oSession = Session::get_instance();
    Corelog::log(print_r($oSession,true) , Corelog::ERROR);
    // Prevent duplicate type+username (DB UNIQUE KEY returns confusing 500 otherwise)
    if (!empty($this->username)) {
      $dup_query = "SELECT COUNT(*) AS cnt FROM " . self::$table . " WHERE type='%type%' AND username='%username%'";
      if (!empty($this->account_id)) { $dup_query .= " AND account_id != %account_id%"; }
      $dup_params = ['type' => $this->type, 'username' => $this->username];
      if (!empty($this->account_id)) { $dup_params['account_id'] = $this->account_id; }
      $dup_res = DB::query(self::$table, $dup_query, $dup_params);
      $dup_row = mysqli_fetch_assoc($dup_res);
      if ((int)($dup_row['cnt'] ?? 0) > 0) {
        throw new CoreException(409, 'A fax account with this username already exists.');
      }
    }

    // PBX existence check: fax accounts (type=account) must reference a real v_extensions row
    if (!empty($data['phone']) && $data['type'] === 'account') {
      $domain_uuid = FpbxDomain::get_domain_uuid($this->tenant_id);
      if ($domain_uuid) {
        $pdo  = FpbxDomain::fpbx_db();
        $stmt = $pdo->prepare(
          "SELECT COUNT(*) FROM v_extensions WHERE domain_uuid = ? AND extension = ?"
        );
        $stmt->execute([$domain_uuid, (string)$data['phone']]);
        if ((int)$stmt->fetchColumn() === 0) {
          throw new CoreException(409,
            'Selected extension does not exist as a PBX extension. Create a PBX Extension first.');
        }
      }
    }

    if (isset($data['account_id']) && !empty($data['account_id'])) {
      // update existing record
      $result = DB::update(self::$table, $data, 'account_id');
      Corelog::log("Account updated: $this->account_id", Corelog::CRUD);
    } else {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->account_id = $data['account_id'];
      Corelog::log("New account created: $this->account_id", Corelog::CRUD);
    }
    return $result;
  }

   public function check_exists(array $data)
    {
    if (!empty($data['username'])) {
      $query = "SELECT COUNT(*) AS cnt FROM ".self::$table."  WHERE username = '%username%'";
      if ($this->account_id !== null) {
        $query .= " AND account_id != %account_id%";
      }
      $params = ['username' => $data['username']];
      if ($this->account_id !== null) {
        $params['account_id'] = (int)$this->account_id;
      }
      $res = DB::query('account', $query, $params);
      $row = mysqli_fetch_assoc($res);
      if ((int)($row['cnt'] ?? 0) > 0) {
        throw new CoreException(409, 'Username already exists');
      }
    }
        if (!empty($data['phone']) && $data['type'] == 'extension') {
      $query = "SELECT COUNT(*) AS cnt FROM ".self::$table."  WHERE phone = '%phone%' AND type='%type%'";
      if ($this->account_id !== null) {
        $query .= " AND account_id != %account_id%";
      }
      $params = ['phone' => $data['phone']];
      $params = ['type' => $data['type']];
      if ($this->account_id !== null) {
        $params['account_id'] = (int)$this->account_id;
      }
      $res = DB::query('account', $query, $params);
      $row = mysqli_fetch_assoc($res);
      if ((int)($row['cnt'] ?? 0) > 0) {
        throw new CoreException(409, 'Phone already exists');
      }
    }
  }

  /**
   * Associate / assign current account to some user
   */
  public function associate($user_id, $aUser = array())
  {
    Corelog::log("Changing account owner for: $this->account_id from: $this->user_id to: $user_id", Corelog::CRUD);
    // Get user detail
    // we can change tenant_id and created_by field only via direct query
    if ($user_id !== 'NULL' && $user_id > 0 ){
      $this->user_id = $user_id; // also update the internal class variable
    }
    if ($user_id != 'NULL') {
      $aUser = User::search(array('user_id' => $user_id));
      if (!empty($aUser)) {
        $this->tenant_id = $aUser[0]['tenant_id'];
        $this->first_name = $aUser[0]['first_name'];
        $this->last_name = $aUser[0]['last_name'];
        $this->email = null;
      }
    }
    else {
      $this->tenant_id = 0;
    }
    $this->email = null;
    $query = "UPDATE " . self::$table . " SET tenant_id=%tenant_id%, created_by=%user_id%, email=NULL WHERE account_id=%account_id%";
    $result = DB::query(self::$table, $query, array('tenant_id' => $this->tenant_id, 'user_id' => $user_id, 'account_id' => $this->account_id));
    if ($result && !empty($aUser) && is_array($aUser)) {
      foreach ($aUser as $field => $value) {
        $this->__set($field, $value); // set function will do necessary validation
      }
      $this->save();
    }
    return true;
  }

  public function dissociate()
  {
    // first remove all associated programs
    $this->remove_program('all');
    self::associate('NULL');
    return true;
  }

  /**
   * Deploy given program with current account
   * ( only if given program support it )
   * @param \ICT\Core\Program $oProgram
   * @return int $program_id
   */
  public function install_program(Program $oProgram)
  {
    Corelog::log("Program installation for: $this->account_id Program: $oProgram->name", Corelog::CRUD);
    $oToken = new Token();
    $oToken->add('account', $this);
    $aParameter = $oProgram->parameter_save();
    foreach ($aParameter as $parameter_name => $parameter_value) {
      $oProgram->{$parameter_name} = $oToken->render_variable($parameter_value, Token::KEEP_ORIGNAL);
    }
    $oProgram->save();
    $oProgram->deploy();
    return $oProgram->program_id;
  }

  public function remove_program($program_name = 'all')
  {
    Corelog::log("Removing program from: $this->account_id Program: $program_name", Corelog::CRUD);
    $listProgram = Program::resource_search(array($this->type, 'account'), $this->account_id);

    if (!empty($listProgram)) { // no error / false
      foreach ($listProgram as $aProgram) {
        $program_id = $aProgram['program_id'];
        if (ctype_digit($program_name) && $program_name == $program_id) {
          $oProgram = Program::load($program_id);
          $oProgram->delete();
        } else {
          $oProgram = Program::load($program_id);
          if (empty($program_name) || 'all' == strtolower($program_name) || strtolower($program_name) == $oProgram->name) {
            $oProgram->delete();
          }
        }
      }
    }
    return true;
  }

  public function setting_read($name, $default = null) {
    if (isset($this->settings[$name])) {
      return $this->settings[$name];
    }
    return $default;
  }

}
