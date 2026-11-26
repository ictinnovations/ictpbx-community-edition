<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use Exception;

class Timezone
{

  private static $table = 'timezone';
  private static $fields = array(
      'timezone_id',
      'name'
  );
  private static $read_only = array(
      'timezone_id'
  );

  /**
   * @property-read integer $timezone_id
   * @var integer
   */
  public $timezone_id = NULL;

  /**
   * @property-read string $name
   * @var string
   */
  public $name = NULL;

  public function __construct($timezone_id = NULL)
  {
    if (!empty($timezone_id)) {
      $this->timezone_id = $timezone_id;
      $this->load();
    }
  }
  
  private function load()
  {
      Corelog::log("Loading timezone with id:" . $this->timezone_id, Corelog::CRUD);
      $query = "SELECT * FROM " . self::$table . " WHERE timezone_id='%timezone_id%'";
      $result = DB::query(self::$table, $query, array('timezone_id' => $this->timezone_id));
      $data = mysqli_fetch_assoc($result);
      if ($data) {
          $this->timezone_id = $data['timezone_id'];
          $this->name = $data['name'];
        } else {
            throw new CoreException('404', 'Timezone not found');
        }
    }
    
    public static function getList()
    {
      $query = "SELECT * FROM " . self::$table;
      Corelog::log("Fetched timezone list", Corelog::DEBUG);
      $result = DB::query('timezone', $query);
      $cur_time = time();
      while ($data = mysqli_fetch_assoc($result)) {
        $data['timezone'] = $data['name'] . " - " . gmdate('l, F d, Y H:i', $cur_time + $data['timezone_id']);
        $aTimezone[] = $data;
      }
      return $aTimezone;
    }

}
