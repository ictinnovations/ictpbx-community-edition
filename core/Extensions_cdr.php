<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */
class Extensions_cdr
{
   private static $table = 'cdr_legb';


   public static function search($aFilter = array())
  {
    $aCDR = array();
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'domain':
          $aWhere[] = "b.$search_field = '$search_value'";
          break;
        case 'hangup_cause':
          if($search_value == 'NORMAL_CLEARING') {
            $aWhere[] = "b.$search_field = '$search_value'";
          } else {
            $aWhere[] = "b.$search_field = 'USER_BUSY' OR b.$search_field = 'NO_ROUTE_DESTINATION'";
          }
          break;
      }
    }
    $from_str = '';
    if (!empty($aWhere)) {
       $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }
    $user_time = user_time();
    $query = "SELECT b.id, b.call_id, b.caller_number, a.destination, b.context, (b.start_time + $user_time) as start_time, (b.answer_time + $user_time) as answer_time,
    (b.end_time + $user_time) as end_time, b.duration, b.billsec, b.hangup_cause, b.domain FROM cdr_lega a LEFT JOIN cdr_legb b ON a.call_id = b.call_id" . $from_str;
    Corelog::log("users-cdr search with $query", Corelog::ERROR, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);
    while ($data = mysqli_fetch_assoc($result)) {
        $aCDR[] = $data;
    }
    return $aCDR;
  }
}
