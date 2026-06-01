<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

class Destination
{

  private static $table = 'destination';
  private static $primary_key = 'destination_id';
  private static $fields = array(
      'destination_id',
      'prefix',
      'ocn',
      'carrier_id',
      'name',
      'carriertype_id',
      'timezone_id',
      'timezone_dst',
      'country_id'
  );
  private static $read_only = array(
      'destination_id'
  );

  /**
   * @property-read string $destination_id
   * @var string
   */
  public $destination_id = NULL;

  /** @var string */
  public $prefix = NULL;

  /** @var string */
  public $ocn = NULL;

  /** @var integer */
  public $carrier_id = NULL;

  /** @var string */
  public $name = NULL;

  /** @var integer */
  public $carriertype_id = NULL;

  /** @var integer */
  public $timezone_id = NULL;

  /** @var integer */
  public $timezone_dst = NULL;

  /** @var integer */
  public $country_id = NULL;

  public function __construct($destination_id = NULL)
  {
    if (!empty($destination_id)) {
      $this->destination_id = $destination_id;
      $this->load();
    }
  }

  public static function search($aFilter = array())
  {
    $aDestination = array();
    $from_str = self::$table;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'destination_id':
        case 'carrier_id':
        case 'timezone_id':
        case 'country_id':
          $aWhere[] = "$search_field = $search_value";
          break;
        case 'phone':
          $aPattern = array();
          $len = strlen($search_value);
          for ($i=1; $i <= ($len-1); $i++) {
            $aPattern[] = substr($search_value, 0, -$i);
          }
          $pattern_string = "'".implode("','", $aPattern)."'";
          if (!empty($pattern_string)) {
            $aWhere[] = "destination_id IN ($pattern_string)";
          }
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }

    $query = "SELECT destination_id, prefix, carrier_id, name, timezone_id, country_id FROM " . $from_str;
    $query .= ' ORDER BY name ASC';
    // $query .= ' ORDER BY LENGTH(destination_id) DESC';
    Corelog::log("Destination search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aDestination[] = $data;
    }
    return $aDestination;
  }

  private function load()
  {
    $query = "SELECT * FROM " . self::$table . " WHERE destination_id='%destination_id%' ";
    $result = DB::query(self::$table, $query, array('destination_id' => $this->destination_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->destination_id = $data['destination_id'];
      $this->prefix = $data['prefix'];
      $this->ocn = $data['ocn'];
      $this->carrier_id = $data['carrier_id'];
      $this->name = $data['name'];
      $this->carriertype_id = $data['carriertype_id'];
      $this->timezone_id = $data['timezone_id'];
      $this->timezone_dst = $data['timezone_dst'];
      $this->country_id = $data['country_id'];
      Corelog::log("Destination loaded name: $this->name", Corelog::CRUD);
    } else {
      throw new CoreException('404', 'Destination not found');
    }
  }

  public function delete()
  {
    Corelog::log("Destination delete", Corelog::CRUD);
    return DB::delete(self::$table, 'destination_id', $this->destination_id);
  }

  public static function getRouteDestination($phone)
  {
    $filter = array('phone' => $phone);
    $listDestination = self::search($filter);
    $destination = array_shift($listDestination);
    return new self($destination['destination_id']);
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
    return $this->destination_id;
  }

  public function save()
  {
    $data = array(
        'destination_id' => $this->destination_id,
        'prefix' => $this->prefix,  
        'ocn' => $this->ocn,
        'carrier_id' => $this->carrier_id,
        'name' => $this->name,
        'carriertype_id' => $this->carriertype_id,
        'timezone_id' => $this->timezone_id,
        'timezone_dst' => $this->timezone_dst,
        'country_id' => $this->country_id,
    );

    if (isset($data['destination_id']) && !empty($data['destination_id'])) {
      // update existing record
      $result = DB::update(self::$table, $data, 'destination_id');
      Corelog::log("Destination updated: $this->destination_id", Corelog::CRUD);
    } else {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->destination_id = $data['destination_id'];
      Corelog::log("New Destination created: $this->destination_id", Corelog::CRUD);
    }
    return $result;
  }

}
