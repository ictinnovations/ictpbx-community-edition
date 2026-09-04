<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\DB;
use ICT\Core\User;
use ICT\Core\Corelog;
use ICT\Core\Transmission;

class Activity
{
  private static $table = NULL;
  private static $fields = array(
    'activitylog_id',
    'user_id',
    'ip_address',
    'activities',
    'fax_id',
    'sourceid',
    'sourcename',
    'sourcephone',
    'callerid',
    'destination',
    'destinationname',
    'faxstatus',
    'duration',
    'pending',
    'processing',
    'origin',
    'date'
  );

  private static $read_only = array(
    'activity_id',
  );

  /** @var integer */
  public $activity_id = NULL;

  /** @var integer */
  public $user_id = NULL;

  /** @var string  */
  public $ip = NULL;

  /** @var string */

  public $log = NULL;

  /** @var integer */
  public $fax_id = NULL;

  /** @var integer */
  public $tenant_id = NULL;

  /** @var string */
  public $username = NULL;

  /** @var string */
  public $tenant = NULL;

  /** @var integer */
  public $phone = NULL;

  /** @var integer */
  public $callerid = NULL;

  /** @var integer */
  public $destinationphone = NULL;

  /** @var string */
  public $destinationname = NULL;

  /** @var string */
  public $faxstatus = NULL;

  /** @var string */
  public $response = NULL;

  /** @var string */
  public $coverpage = NULL;

  /** @var int */
  public $pages = NULL;

  /** @var string */
  public $duration = NULL;

  /** @var string */
  public $pending = NULL;

  /** @var string */
  public $processing = NULL;

  /** @var string */
  public $result = NULL;

  /** @var string */
  public $origin = NULL;

  /** @var string */
  public $date = NULL;

  public function userlogs($log, $userid = NULL, $username = NULL, $tenant_id = NULL)
  {
    $this->user_id = $userid;
    $this->username = $username;
    $this->tenant_id = $tenant_id;
    $this->set_essen($log);
    self::$table = 'activitylog';
    $this->usersave();
  }

  public function faxlogs($trans_id, $response)
  {
    $this->set_essen();
    $this->fax_id = $trans_id;
    $trans = new Transmission($trans_id);
    $tenant = new Tenant($trans->tenant_id);
    $this->tenant = $tenant->company;
    $this->faxstatus = $trans->status;
    $this->date = time();
    $this->origin = $trans->origin;
    $this->response = $response;
    self::$table = 'faxlog';
    $programid = $trans->program_id;
    $query = "SELECT s.*, a.application_id 
    FROM spool_result s 
    JOIN application a ON s.application_id = a.application_id
    WHERE a.program_id = $programid AND s.name = 'pages'";
    $result = DB::query('spool_result', $query);
    $data = mysqli_fetch_assoc($result);
    $this->pages = $data['data'];
    $this->faxsave();
  }

  public function faxactivity($log, $trans_id)
  {
    $this->set_essen($log);
    $this->fax_id = $trans_id;
    self::$table = 'faxactivity';
    $this->faxactivitysave();
  }

    public static function systemactivity($aFilter = array())
  {
    $activities = array();
    $from_str = 'activitylog as a';
    $totalrows = null;
    $limitSql = '';
    $pageIndex = isset($aFilter['pageIndex']) ? (int)$aFilter['pageIndex'] : 0;
    $pageSize = isset($aFilter['pageSize']) ? (int)$aFilter['pageSize'] : 0;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'activitylog_id':
          $aWhere[] = "a.activitylog_id = $search_value";
          break;
        case 'tenant_id':
          $aWhere[] = "a.tenant_id = '$search_value'";
          break;
        case 'user_id':
          $aWhere[] = "a.user_id = '$search_value'";
          break;
        case 'activities':
        case 'username':
          $aWhere[] = "$search_field LIKE '%$search_value'";
          break;
        case 'to':
          $aWhere[] = "a.date <= $search_value";
          break;
        case 'from':
          $aWhere[] = "a.date >= $search_value";
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
        case 'totalrows':
          $totalrows = 1;
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }

    $user_time = user_time();
    $query = "SELECT a.activities,a.ip_address,a.username, a.tenant_id, a.user_id, (a.date + $user_time) as date  FROM " . $from_str . " ORDER BY a.activitylog_id DESC" . $limitSql;
    if ($totalrows == 1) return totalrows($query);
    $result = DB::query('activitylog', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $activities[] = $data;
    }
    return $activities;
  }


  public function faxactivitysave()
  {
    $data = array(
      'faxid' => $this->fax_id,
      'faxactivity' => $this->log,
      'date' => $this->date
    );
    $result = DB::update(self::$table, $data, false);
    return $result;
  }

  public function set_essen($log = NULL)
  {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    if (empty($this->user_id)) {
      $this->user_id = Session::get_instance()->user->user_id;
    }
    if (empty($this->username)) {
      $this->username = Session::get_instance()->user->username;
    }
    if ($this->user_id == -1) {
      $this->username = "Retention";
    }
    $this->phone = Session::get_instance()->user->phone;
    $coverpage = Session::get_instance()->user->cover;
    if ($coverpage == 1) {
      $this->coverpage = "Yes";
    } else {
      $this->coverpage = "No";
    }
    if (Session::get_instance()->transmission) {
      $this->callerid = Session::get_instance()->transmission->source->phone;
      $this->destinationphone = Session::get_instance()->transmission->destination->phone;
      $this->destinationname = Session::get_instance()->transmission->destination->username;
    }
    if ($this->tenant_id == NULL) {
      $this->tenant_id = Session::get_instance()->user->tenant_id;
    }
    $this->date = time();
    $this->ip = $ip;
    $this->log = $log;
  }

  public function usersave()
  {
    $data = array(
      'activity_id' => $this->activity_id,
      'username' => $this->username,
      'user_id' => $this->user_id,
      'tenant_id' => $this->tenant_id,
      'ip_address' => $this->ip,
      'activities' => $this->log,
      'date' => $this->date
    );
    $result = DB::update(self::$table, $data, false);
    $userSessionData = array(
      'user_id' => $this->user_id,
      'username' => $this->username,
      'activities' => $this->log,
      'date' => date('Y/m/d H:i:s')
    );
    // Check if the user already exists in the usersession table
    $query = "SELECT * FROM usersession WHERE user_id = {$this->user_id}";
    $userSessionResult = DB::query('usersession', $query);
    if (mysqli_num_rows($userSessionResult) > 0) {
      // Update the existing record
      $result = DB::update('usersession', $userSessionData, 'user_id');
    } else {
      // Insert a new record
      $result = DB::update('usersession', $userSessionData, false);
    }
    return $result;
  }

  public function faxsave()
  {
    $data = array(
      'faxid' => $this->fax_id,
      'sourceid' => $this->user_id,
      'sourcename' => $this->username,
      'tenant' => $this->tenant,
      'sourcephone' => $this->phone,
      'callerid' => $this->callerid,
      'coverpage' => $this->coverpage,
      'destination' => $this->destinationphone,
      'destinationname' => $this->destinationname,
      'faxstatus' => $this->faxstatus,
      'duration' => $this->duration,
      'origin' => $this->origin,
      'date' => $this->date
    );
    if (isset($this->fax_id)) {
      $query = "select * from faxlog where faxid = $this->fax_id";
      $result = DB::query(self::$table, $query);
      if (mysqli_num_rows($result) > 0) {
        $data = array(
          'faxid' => $this->fax_id,
          'callerid' => $this->callerid,
          'destination' => $this->destinationphone,
          'destinationname' => $this->destinationname,
          'pages' => $this->pages,
          'faxstatus' => $this->faxstatus,
          'origin' => $this->origin,
          'response' => $this->response,
        );
        if ($this->faxstatus === 'processing') {
          $data['processing'] = time();
        }
        if ($this->faxstatus === 'completed') {
          $data['result'] = time();
        }
        $result = DB::update(self::$table, $data, 'faxid');
      } else {
        $data['pending'] = time();
        $result = DB::update(self::$table, $data, false);
      }
    }
    return $result;
  }
}
