<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

class Country
{

  private static $table = 'country';
  private static $primary_key = 'country_id';
  private static $fields = array(
      'country_id',
      'name',
      'iso_code_2',
      'iso_code_3',
      'dialing_code',
      'ndd',
      'idd',
      'locallenght',
      'timezone_id',
      'timezone_dst',
      'language_id',
      'currency_id',
      'region_id'
  );
  private static $read_only = array(
      'country_id'
  );

  /**
   * @property-read integer $country_id
   * @var integer
   */
  public $country_id = NULL;

  /** @var string */
  public $name = NULL;

  /** @var string */
  public $iso_code_2 = NULL;

  /** @var integer */
  public $iso_code_3 = NULL;

  /** @var string */
  public $dialing_code = NULL;

  /** @var string */
  public $ndd = NULL;

  /** @var string */
  public $idd = NULL;

  /** @var integer */
  public $locallenght = NULL;

  /** @var integer */
  public $timezone_id = NULL;

  /** @var integer */
  public $timezone_dst = NULL;

  /** @var string */
  public $language_id = NULL;

  /** @var string */
  public $currency_id = NULL;

  /** @var string */
  public $region_id = NULL;

  public function __construct($country_id = NULL)
  {
    if (!empty($country_id) && $country_id > 0) {
      $this->country_id = $country_id;
      $this->load();
    }
  }

  public static function search($aFilter = array())
  {
    $aCountry = array();
    $from_str = self::$table;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'country_id':
        case 'timezone_id':
          $aWhere[] = "$search_field = $search_value";
          break;
        case 'region_id':
          // $aWhere[] = "$search_field = '$search_value'";
          $aWhere[] = "$search_field LIKE '%$search_value%'";
          break;
        case 'created_by':
          $aWhere[] = "created_by = $search_value";
          break;
        case 'before':
          $aWhere[] = "date_created <= $search_value";
          break;
        case 'after':
          $aWhere[] = "date_created >= $search_value";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }

    $query = "SELECT country_id, name, dialing_code, timezone_id, region_id FROM " . $from_str;
    $query .= " ORDER BY name ASC";
    Corelog::log("Country search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aCountry[] = $data;
    }
    return $aCountry;
  }

  private function load()
  {
    $query = "SELECT * FROM " . self::$table . " WHERE country_id='%country_id%' ";
    $result = DB::query(self::$table, $query, array('country_id' => $this->country_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->country_id = $data['country_id'];
      $this->name = $data['name'];
      $this->iso_code_2 = $data['iso_code_2'];
      $this->iso_code_3 = $data['iso_code_3'];
      $this->dialing_code = $data['dialing_code'];
      $this->ndd = $data['ndd'];
      $this->idd = $data['idd'];
      $this->locallenght = $data['locallenght'];
      $this->timezone_id = $data['timezone_id'];
      $this->timezone_dst = $data['timezone_dst'];
      $this->language_id = $data['language_id'];
      $this->currency_id = $data['currency_id'];
      $this->region_id = $data['region_id'];
      Corelog::log("Country loaded name: $this->name", Corelog::CRUD);
    } else {
      throw new CoreException('404', 'Country not found');
    }
  }

  public function delete()
  {
    Corelog::log("Country delete", Corelog::CRUD);
    return DB::delete(self::$table, 'country_id', $this->country_id);
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
    return $this->country_id;
  }

  public function save()
  {
    $data = array(
        'country_id' => $this->country_id,
        'name' => $this->name,  
        'iso_code_2' => $this->iso_code_2,
        'iso_code_3' => $this->iso_code_3,
        'dialing_code' => $this->dialing_code,
        'ndd' => $this->ndd,
        'idd' => $this->idd,
        'locallenght' => $this->locallenght,
        'timezone_id' => $this->timezone_id,
        'timezone_dst' => $this->timezone_dst,
        'language_id' => $this->language_id,
        'currency_id' => $this->currency_id,
        'region_id' => $this->region_id,
    );

    if (isset($data['country_id']) && !empty($data['country_id'])) {
      // update existing record
      $result = DB::update(self::$table, $data, 'country_id');
      Corelog::log("Country updated: $this->country_id", Corelog::CRUD);
    } else {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->country_id = $data['country_id'];
      Corelog::log("New Country created: $this->country_id", Corelog::CRUD);
    }
    return $result;
  }

}
