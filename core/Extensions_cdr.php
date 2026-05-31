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
    $esc = function ($v) { return mysqli_real_escape_string(DB::$link, $v); };
    $limit  = isset($aFilter['limit'])  ? (int) $aFilter['limit']  : 500;
    $offset = isset($aFilter['offset']) ? (int) $aFilter['offset'] : 0;
    if ($limit  < 1)    { $limit  = 500; }
    if ($limit  > 5000) { $limit  = 5000; }
    if ($offset < 0)    { $offset = 0; }
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'domain':
          $aWhere[] = "b.domain = '" . $esc($search_value) . "'";
          break;
        case 'hangup_cause':
          if ($search_value == 'NORMAL_CLEARING') {
            $aWhere[] = "b.hangup_cause = 'NORMAL_CLEARING'";
          } else {
            $aWhere[] = "(b.hangup_cause = 'USER_BUSY' OR b.hangup_cause = 'NO_ROUTE_DESTINATION')";
          }
          break;
        case 'extension':
          $ext = $esc($search_value);
          $aWhere[] = "(b.caller_number = '$ext' OR a.destination = '$ext')";
          break;
        case 'date_from':
          $aWhere[] = "b.start_time >= '" . $esc($search_value) . " 00:00:00'";
          break;
        case 'date_to':
          $aWhere[] = "b.start_time <= '" . $esc($search_value) . " 23:59:59'";
          break;
      }
    }
    $from_str = '';
    if (!empty($aWhere)) {
       $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }
    $user_time = user_time();
    $query = "SELECT b.id, b.call_id, b.caller_number, a.destination, b.context, (b.start_time + $user_time) as start_time, (b.answer_time + $user_time) as answer_time,
    (b.end_time + $user_time) as end_time, b.duration, b.billsec, b.hangup_cause, b.domain FROM cdr_legb b LEFT JOIN cdr_lega a ON a.call_id = b.call_id" . $from_str . " ORDER BY b.start_time DESC LIMIT $limit OFFSET $offset";
    Corelog::log("users-cdr search with $query", Corelog::ERROR, array('aFilter' => $aFilter));
    $result = DB::query(self::$table, $query);
    while ($data = mysqli_fetch_assoc($result)) {
        $aCDR[] = $data;
    }
    return $aCDR;
  }
}
