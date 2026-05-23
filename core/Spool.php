<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Destination;
use ICT\Core\Route;
use ICT\Core\Provider;

class Spool
{

  /** @const */
  const STATUS_QUEUED = 'queued';
  const STATUS_STARTED = 'started';
  const STATUS_CONNECTED = 'connected';
  const STATUS_DONE = 'done'; // when we are not sure if failed or completed
  const STATUS_COMPLETED = 'completed';
  const STATUS_FAILED = 'failed';
  const STATUS_INVALID = 'invalid';

  private static $doneStatus = array(
    Spool::STATUS_COMPLETED,
    Spool::STATUS_FAILED,
    Spool::STATUS_INVALID
  );
  // **************************************************** Spool related data */
  private static $table = 'spool';
  private static $primary_key = 'spool_id';
  private static $fields = array(
    'spool_id',
    'time_spool',
    'time_start',
    'time_connect',
    'time_end',
    'call_id',
    'status',
    'response',
    'amount',
    'amount_net',
    'rate',
    'cost',
    'service_flag',
    'transmission_id',
    'destination_id',
    'provider_id',
    'rate_id',
    'quota_id',
    'node_id',
    'account_id'
  );
  private static $read_only = array(
    'spool_id',
    'time_spool',
    'time_start',
    'time_connect',
    'time_end',
    'amount_net',
    'rate',
    'cost'
  );

  /**
   * @property-read integer $spool_id
   * @var integer
   */
  public $spool_id = NULL;

  /**
   * @property-read integer $time_spool
   * @var integer
   */
  private $time_spool = NULL;

  /**
   * @property-read integer $time_start
   * @var integer
   */
  private $time_start = NULL;

  /**
   * @property-read integer $time_connect
   * @var integer
   */
  private $time_connect = NULL;

  /**
   * @property-read integer $time_end
   * @var integer
   */
  private $time_end = NULL;

  /** @var string */
  public $call_id = NULL;

  /**
   * @property string $status
   * @see Spool::set_status()
   * @var string */
  private $status = NULL;

  /** @var string */
  public $response = NULL;

  /** @var integer */
  public $amount = NULL;

  /**
   * @property-read integer $amount_net
   * @var integer
   */
  public $amount_net = NULL;

  /**
   * @property-read float $rate
   * @var float
   */
  public $rate = NULL;

  /**
   * @property-read float $cost
   * @var float
   */
  public $cost = NULL;

  /** @var integer */
  public $service_flag = NULL;

  /** @var integer */
  public $transmission_id = NULL;

  /** @var integer */
  public $destination_id = NULL;

  /** @var integer */
  public $provider_id = 0;

  /** @var integer */
  public $rate_id = NULL;

  /** @var integer */
  public $quota_id = NULL;

  /** @var integer */
  public $node_id = NULL;

  /** @var integer */
  public $account_id = NULL;

  public function __construct($spool_id = null)
  {
    if (!empty($spool_id) && ctype_digit(trim($spool_id))) {
      $this->spool_id = $spool_id;
      $this->load();
    } else {
      // create spool entry for this attempt
      $this->node_id = 1; // TODO
      $this->status = Spool::STATUS_QUEUED;
      $this->time_spool = time();
    }
  }

  public static function search($aFilter = array())
  {
    $aSpool = array();
    $from_str = self::$table;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'spool_id':
        case 'account_id':
        case 'transmission_id':
          $aWhere[] = "$search_field = $search_value";
          break;
        case 'service_flag':
          $aWhere[] = "($search_field & $search_value) = $search_value";
          break;
        case 'status':
        case 'response':
          $aWhere[] = "$search_field = '$search_value'";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }

    $query = "SELECT spool_id, account_id, transmission_id, status, response FROM " . $from_str;
    Corelog::log("spool search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query('spool', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aSpool[] = $data;
    }

    return $aSpool;
  }

  private function load()
  {
    $query = "SELECT * FROM " . self::$table . " WHERE spool_id='%spool_id%' ";
    $result = DB::query(self::$table, $query, array('spool_id' => $this->spool_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->spool_id = $data['spool_id'];
      $this->time_spool = $data['time_spool'];
      $this->time_start = $data['time_start'];
      $this->time_connect = $data['time_connect'];
      $this->time_end = $data['time_end'];
      $this->call_id = $data['call_id'];
      $this->status = $data['status'];
      $this->response = $data['response'];
      $this->amount = $data['amount'];
      $this->amount_net = $data['amount_net'];
      $this->rate = $data['rate'];
      $this->cost = $data['cost'];
      $this->service_flag = $data['service_flag'];
      $this->transmission_id = $data['transmission_id'];
      $this->destination_id = $data['destination_id'];
      $this->provider_id = $data['provider_id'];
      $this->rate_id = $data['rate_id'];
      $this->quota_id = $data['quota_id'];
      $this->node_id = $data['node_id'];
      $this->account_id = $data['account_id'];

      Corelog::log("Spool loaded spool_id: $this->spool_id", Corelog::CRUD);
    } else {
      throw new CoreException('404', 'Spool not found');
    }
  }

  public function delete()
  {
    Corelog::log("Spool delete", Corelog::CRUD);
    return DB::delete(self::$table, 'spool_id', $this->spool_id);
  }

  public function is_done()
  {
    if (in_array($this->status, self::$doneStatus)) {
      return TRUE;
    } else {
      return FALSE;
    }
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
    return $this->spool_id;
  }

  private function set_status($status)
  {
    // prevent updating already done spool
    if ($this->is_done()) {
      return;
    }
    switch ($status) {
      case Spool::STATUS_STARTED:
        if (empty($this->time_start)) {
          $this->time_start = time();
        }
        break;
      case Spool::STATUS_CONNECTED:
        if (empty($this->time_connect)) {
          $this->time_connect = time();
        }
        $this->do_billing();
        break;
      case Spool::STATUS_COMPLETED:
        if (empty($this->time_end)) {
          $this->time_end = time();
        }
        if (empty($this->amount)) {
          $this->amount = $this->time_end - $this->time_connect;
        }
        $this->do_billing();
        break;
      case Spool::STATUS_FAILED:
      case Spool::STATUS_INVALID:
        if (empty($this->time_end)) {
          $this->time_end = time();
        }
        break;
      case Spool::STATUS_DONE:
        // decide if spool failed or completed ?
        if (Spool::STATUS_CONNECTED == $this->status) {
          if (empty($this->amount)) {
            $this->amount = $this->time_end - $this->time_connect;
          }
          $this->__set('status', Spool::STATUS_COMPLETED);
        } else {
          $this->__set('status', Spool::STATUS_FAILED);
        }
        return; // no further processing needed, so exist
    }
    $this->status = $status;
  }

  public function save()
  {
    $data = array(
      'spool_id' => $this->spool_id,
      'time_spool' => $this->time_spool,
      'time_start' => $this->time_start,
      'time_connect' => $this->time_connect,
      'time_end' => $this->time_end,
      'call_id' => $this->call_id,
      'status' => $this->status,
      'response' => $this->response,
      'amount' => $this->amount,
      'amount_net' => $this->amount_net,
      'rate' => $this->rate,
      'cost' => $this->cost,
      'transmission_id' => $this->transmission_id,
      'service_flag' => $this->service_flag,
      'destination_id' => $this->destination_id,
      'provider_id' => $this->provider_id,
      'rate_id' => $this->rate_id,
      'quota_id' => $this->quota_id,
      'node_id' => $this->node_id,
      'account_id' => $this->account_id
    );

    if (isset($data['spool_id']) && !empty($data['spool_id'])) {
      // update existing record, no authentication needed
      $result = DB::update(self::$table, $data, 'spool_id');
      Corelog::log("Spool updated: $this->spool_id", Corelog::CRUD);
    } else {
      // add new, no authentication needed
      $result = DB::update(self::$table, $data, false);
      $this->spool_id = $data['spool_id'];
      Corelog::log("New Spool created: $this->spool_id", Corelog::CRUD);
    }

    return $result;
  }

  public static function list_cdr($aFilter = array())
  {
    $aSpool = array();
    $limitSql = '';
    $pageIndex = isset($aFilter['pageIndex']) ? (int)$aFilter['pageIndex'] : 0;
    $pageSize = isset($aFilter['pagesize']) ? (int)$aFilter['pagesize'] : 0;
    $totalrows = null;
    $from_str = '';
    $aWhere = array();
    $aFilter += array('status' => Spool::STATUS_COMPLETED); // default filter
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'tenant_id':
          $aWhere[] = "t.tenant_id = $search_value";
          break;
        case 'user_id':
        case 'created_by':
          $aWhere[] = "t.created_by = $search_value";
          break;
        case 'origin':
          $aWhere[] = "t.origin = '$search_value'";
          break;
        case 'status':
          $aWhere[] = "s.status = '$search_value'";
          break;
        case 'campaign_id':
          $aWhere[] = "t.campaign_id = $search_value";
          break;
        case 'direction':
          $aWhere[] = "t.direction = '$search_value'";
          break;
        case 'monthly':
          $start_time = time() - 86400 * 30;
          $aWhere[] = "s.time_connect > '$start_time'";
          break;
        case 'from':
          $aWhere[] = "s.time_connect > $search_value";
          break;
        case 'to':
          $aWhere[] = "s.time_connect < ($search_value + 86400)";
          break;
        case 'totalrows':
          $totalrows = 1;
          break;
        case 'service_flag':
          $aWhere[] = "t.service_flag = '$search_value'";
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
    $user_time = user_time();
    $query = "SELECT s.spool_id, (s.time_start + $user_time) as time_start, (s.time_connect + $user_time) as time_connect, (s.time_end + $user_time) as time_end, tnt.company, a.first_name, t.created_by, s.amount, s.cost, s.status, t.transmission_id, t.direction,
                     t.contact_id, t.account_id, c.phone as contact_phone, u.username, a.phone as account_phone, COALESCE(sr.data, 0) as pages
              FROM spool s
                LEFT JOIN transmission t ON s.transmission_id = t.transmission_id
                LEFT JOIN tenant tnt ON t.tenant_id = tnt.tenant_id
                LEFT JOIN contact c ON t.contact_id = c.contact_id
                LEFT JOIN account a ON t.account_id = a.account_id
                LEFT JOIN usr u ON t.created_by = u.usr_id
                LEFT JOIN spool_result sr ON s.spool_id = sr.spool_id AND sr.name = 'pages'" .
      $from_str . " ORDER BY s.spool_id DESC $limitSql";
    if ($totalrows) return totalrows($query);
    Corelog::log("spool search with $query", Corelog::ERROR, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aSpool[] = $data;
    }

    return $aSpool;
  }

  public static function list_statistic($aFilter = array())
  {
    $aSpool = array();
    if (isset($aFilter['status']) && strpos($aFilter['status'], ',') !== false) {
      // Split into array
      $aFilter['status'] = explode(',', $aFilter['status']);
    }

    $from_str = '';

    $aWhere = array();
    $limitSql = '';
    $pageIndex = isset($aFilter['pageIndex']) ? (int)$aFilter['pageIndex'] : 0;
    $pageSize = isset($aFilter['pageSize']) ? (int)$aFilter['pageSize'] : 0;
    $aFilter += array('status' => Spool::STATUS_COMPLETED); // default filter
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'tenant_id':
          $aWhere[] = "t.tenant_id = $search_value";
          break;
        case 'user_id':
        case 'created_by':
          $aWhere[] = "t.created_by = $search_value";
          break;
        case 'account_id':
          $aWhere[] = "t.account_id In ($search_value)";
          break;
        case 'totalrows':
          $totalrows = 1;
          break;
        case 'origin':
          $aWhere[] = "t.origin = '$search_value'";
          break;
        case 'status':
          if (is_array($search_value)) {
            $statuses = array_map(function ($status) {
              return "'" . addslashes($status) . "'";
            }, $search_value);
            $aWhere[] = "t.status IN (" . implode(",", $statuses) . ")";
          } else {
            $aWhere[] = "t.status = '" . addslashes($search_value) . "'";
          }
          break;
        case 'campaign_id':
          $aWhere[] = "t.campaign_id = $search_value";
          break;
        case 'direction':
          $aWhere[] = "t.direction = '$search_value'";
          break;
        case 'monthly':
          $start_time = time() - 86400 * 30;
          $aWhere[] = "s.time_start > '$start_time'";
          break;
        case 'from':
          $aWhere[] = "t.date_created > $search_value";
          break;
        case 'to':
          $aWhere[] = "t.date_created < $search_value";
          break;
        case 'service_flag':
          $aWhere[] = "t.service_flag = '$search_value'";
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
    $user_time = user_time();
    $query = "SELECT s.spool_id, (s.time_start + $user_time) as time_start, (s.time_connect + $user_time) as time_connect, (s.time_end + $user_time) as time_end, tn.company, t.created_by, s.amount, s.cost, t.status, s.response, t.transmission_id, t.direction,
                     t.contact_id, t.account_id, c.phone as contact_phone, u.username as username,  a.phone as account_phone, COALESCE(sr.data, 0) as pages,
                     t.try_allowed, t.try_done
              FROM spool s
                LEFT JOIN transmission t ON s.transmission_id = t.transmission_id
                LEFT JOIN account a ON a.account_id = t.account_id
                LEFT JOIN contact c ON c.contact_id = t.contact_id
                LEFT JOIN tenant tn ON t.tenant_id = tn.tenant_id
                LEFT JOIN usr u ON u.usr_id = t.created_by
                LEFT JOIN spool_result sr ON s.spool_id = sr.spool_id AND sr.name = 'pages'
             $from_str GROUP BY t.transmission_id ORDER BY s.spool_id DESC " . $limitSql;
    if ($totalrows) return totalrows($query);
    Corelog::log("spool search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);

    while ($data = mysqli_fetch_assoc($result)) {
      if (!empty($data['response'])) {
        $data['mapped_response'] = Spool::getMappedResponse($data['response']);
      }
      $aSpool[] = $data;
    }
    return $aSpool;
  }


  public static function getMappedResponse($raw_response)
  {
    $responseMap = array(
      'NORMAL_CLEARING'             => 'Completed',
      'ORIGINATOR_CANCEL'           => 'Cancelled',
      'NO_ANSWER'                   => 'No Answer',
      'USER_BUSY'                   => 'Busy',
      'CALL_REJECTED'               => 'Rejected',
      'NO_ROUTE_DESTINATION'        => 'No Route',
      'RECOVERY_ON_TIMER_EXPIRE'    => 'No Answer',
      'UNALLOCATED_NUMBER'          => 'Invalid Number',
      'NORMAL_TEMPORARY_FAILURE'    => 'Temporary Failure',
      'INCOMPATIBLE_DESTINATION'    => 'Incompatible',
      'SUBSCRIBER_ABSENT'           => 'Subscriber Absent',
      'NETWORK_OUT_OF_ORDER'        => 'Network Error',
      'DESTINATION_OUT_OF_ORDER'    => 'Destination Error',
      'FAILURE_TO_CONNECT'          => 'Connection Failed',
      'MANAGER_REQUEST'             => 'Terminated by Manager',
      'NORMAL_UNSPECIFIED'          => 'Unspecified',
      'MANDATORY_IE_MISSING'        => 'Protocol Error',
      'INVALID_IE_CONTENTS'         => 'Invalid Information',
      'PROTOCOL_ERROR'              => 'Protocol Error',
      'INTERWORKING'                => 'Interworking Error',
      'MEDIA_TIMEOUT'               => 'Media Timeout',
      'ALLOTTED_TIMEOUT'            => 'Timeout',
      'ORIGINATOR_TIMEOUT'          => 'Caller Timeout',
      'LOSE_RACE'                   => 'Race Condition',
      'EXCHANGE_ROUTING_ERROR'      => 'Routing Error',
      'SERVICE_NOT_IMPLEMENTED'     => 'Not Implemented',
      'SERVICE_NOT_AVAILABLE'       => 'Unavailable',
      'FACILITY_REJECTED'           => 'Facility Rejected',
      'BEARERCAPABILITY_NOTAVAIL'   => 'Capability Unavailable',
      'BEARERCAPABILITY_NOTIMPL'    => 'Capability Not Implemented',
      'CHANNEL_UNACCEPTABLE'        => 'Unacceptable Channel',
      'NORMAL_CIRCUIT_CONGESTION'   => 'Circuit Congestion',
      'SWITCH_CONGESTION'           => 'Switch Congestion',
      'ADMIN_RESET'                 => 'Admin Reset',
      'ADMIN_BLOCKED'               => 'Admin Blocked'
    );
    // TODO:: Map remaining response

    return $responseMap[$raw_response] ?? $raw_response; // fallback
  }

  public function set_route($contact, $program_id = NULL)
  {
    // find destination
    $program = new Program($program_id);
    if ($program->type == 'sendemail') {
      $filter = array('type' => 'smtp');
      $provider = Provider::search($filter);
      $this->provider_id = $provider[0]['provider_id'];
    } else {
      $oDestination = Destination::getRouteDestination($contact);
      $this->destination_id = $oDestination->destination_id;
      // find routes
      $providerIDs = Route::getRoutes($oDestination->destination_id, $this->service_flag);
      // find best provider
      $oProvider = Provider::getProvider($providerIDs);
      $this->provider_id = $oProvider['provider_id'];
    }
    // // find routes
    // $oRoute = Route::load($contact, $this->service_flag);
    // $this->destination_id = $oRoute->destination_id;

    // find rate
    // $oRate  = Rate::load($this->destination_id, $this->service_flag);
    // $this->rate_id = $oRate->rate_id;
    // $this->rate = $oRate->unit_block_rate;
  }


  public function do_billing()
  {
    // TODO: add plan_id support in rate
    if (empty($this->rate_id)) return;
    $oRate = new Rate($this->rate_id);
    $this->amount_net = $oRate->calculate_amount_net($this->amount);
    $this->cost = $oRate->calculate_cost($this->amount_net);
  }
}
