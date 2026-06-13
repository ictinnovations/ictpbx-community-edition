<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\CoreException;
use ICT\Core\Corelog;

use ICT\Core\Transmission;
use ICT\Core\DB;
use ICT\Core\Program;
use ICT\Core\Session;
use ICT\Core\User;

class Coverpage 
{

  /** @const */
  private static $table = 'coverpage';
  private static $primary_key = 'coverpage_id';
  private static $fields = array(
    'coverpage_id',
    'title',
    'description',
    'created_by',
    'tenant_id'
  );
  private static $read_only = array(
    'coverpage_id',
  );
  /**
   * @property-read integer $coverpage_id
   * @var integer
   */
  public $coverpage_id = NULL;

  /**
   * @property-read integer $user_id
   * owner id of current record
   * @var integer
   */
  public $user_id = NULL;

  /** @var string */
  public $title = NULL;

  /** @var int */
  public $transmission = NULL;

  /** @var string */
  public $description = NULL;

  /** @var int */
  public $created_by = NULL;

  /** @var int */
  public $tenant_id = NULL;

  

  public function __construct($coverpage_id = NULL , $transmission = NULL)
  {
	  $this->transmission = $transmission;
    if ($coverpage_id != "") {
      $this->coverpage_id = $coverpage_id;
      $this->load();
    }
  }

  private function load()
  {
    $query = $this->transmission ? "SELECT c.* FROM coverpage as c WHERE c.coverpage_id = %coverpage_id%" : $this->query();
    $result = DB::query(self::$table, $query, array('coverpage_id' => $this->coverpage_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->coverpage_id = $data['coverpage_id'];
      $this->created_by = $data['created_by'];
      $this->tenant_id = $data['tenant_id'];
      $this->title = $data['title'];
      $this->description = $data['description'];
      $this->user_id = Session::get_instance()->user->user_id;
      Corelog::log("Coverpage loaded title: $this->title", Corelog::CRUD);
      } else 
      {
        throw new CoreException('404', 'Coverpage not found');
      }
    }

  public function query()
  {
    $user_id = Session::get_instance()->user->user_id;
    $tenant_id = Session::get_instance()->user->tenant_id;
    $is_admin = (can_access('super_admin', $user_id)) ? "1" : "0";
    $is_tenant = (can_access('user_admin', $user_id)) ? "1" : "0";
    if ($is_admin){
      $query = "SELECT c.* FROM coverpage c
       WHERE c.coverpage_id = %coverpage_id%";
    }
    else if ($is_tenant){
      $query = "SELECT c.* FROM coverpage c
       WHERE c.coverpage_id = %coverpage_id% AND tenant_id=$tenant_id";
    }
    else{
     $query = "SELECT c.* FROM coverpage c
       WHERE c.coverpage_id = %coverpage_id% AND created_by=$user_id";
    }
    
    return $query;
  }

    
  public function save()
  { 
   $data = array(
     'coverpage_id' => $this->coverpage_id,
     'title' => $this->title,
     'description' => $this->description,
     'tenant_id' => $this->tenant_id,
     'created_by' => $this->created_by
    );

    if (isset($data['coverpage_id']) && !empty($data['coverpage_id'])) 
    {
      // update existing record
      $result = DB::update(self::$table, $data, 'coverpage_id');
      Corelog::log("CoverPage updated: $this->coverpage_id", Corelog::CRUD);
     }
    else 
    {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->coverpage_id = $data['coverpage_id'];
      $this->user_id = $data['created_by'];
      $oUsername = Session::get_instance()->user->username;
      Corelog::log("New Coverpage created: $this->coverpage_id", Corelog::CRUD);
    }
    return $result;
  } 


  public static function search($aFilter = array())
  {
    $aCoverpage = array();
    $from_str = self::$table;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'coverpage_id':
          $aWhere[] = "coverpage_id = $search_value";
        break;
        case 'tenant_id':
          $aWhere[] = "tenant_id = '$search_value'";
        break;
        case 'created_by':
          $aWhere[] = "created_by = '$search_value'";
        break;
        case 'title':
        case 'description':  
          $aWhere[] = "$search_field LIKE '%$search_value'";
        break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }
    $query = "SELECT * FROM " . $from_str ;
    Corelog::log("coverpage search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aCoverpage[] = $data;
    }
    return $aCoverpage;
  }
  
  public function delete()
  {
    return DB::delete(self::$table, 'coverpage_id', $this->coverpage_id);
  }

}



