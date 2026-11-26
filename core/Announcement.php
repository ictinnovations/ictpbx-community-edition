<?php
namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

 class Announcement 
 {
    public static $table = 'announcement';
    public $fields = array(
    'announcement_id',
    'tenant_id',
    'title',
    'message'
    );

    /**
     * @var integer
     */
    public $announcement_id = NULL;

    /**
     * @var integer
     */
    public $tenant_id = NULL;

     /**
     * @var string
     */

     public $title = NULL;

     /**
      * @var string
      */
      public $message = NULL;



    public function __construct($id = NULL) {
        if($id){
          $id = $id;
          return $this->load();
        }
    }

    public function load(){
     $query = "select * from announcement where id= $id";
     $result = DB::query(self::$table , $query , array('id' => $this->id));
     $data =  mysqli_fetch_assoc($result);
     if($data){
        $this->id = $data['id'];
        $this->tenant_id = $data['tenant_id'];
        $this->title = $data['title'];
        $this->message = $data['message'];
     }
    }

    public function get_announcement($tenant_id){
        $query = "SELECT * FROM announcement WHERE tenant_id = $tenant_id";
        $result = DB::query(self::$table , $query , array('tenant_id' => $tenant_id));
        $data = mysqli_fetch_assoc($result);
        if($data){
        return $data;
        }
        else{
          return new Announcement();
        }
    }

    public function save(){
      $data = array(
        'announcement_id' => $this->id,
        'tenant_id' => $this->tenant_id,
        'title' => $this->title,
        'message' => $this->message,
      );
      if($this->id){
        $result = DB::update(self::$table, $data , 'id');
      }
      else{
        $result = DB::update(self::$table, $data ,false);
      }
      return $result;
    }
 }