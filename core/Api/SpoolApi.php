<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\Result;
use ICT\Core\Spool;
use ICT\Core\CoreException;
use SplFileInfo;

#[\AllowDynamicProperties]
class SpoolApi extends Api
{

  /**
   * Get spool status
   *
   * @url GET /spools/$spool_id/status
   */
  public function status($spool_id)
  {
    $this->_authorize('spool_read');

    $oSpool = new Spool($spool_id);
    return $oSpool->status;
  }

  /**
   * Get spool details
   *
   * @url GET /spools/$spool_id/result
   * @url GET /spools/$spool_id/results
   */
  public function result($spool_id)
  {
    $this->_authorize('spool_read');
    $this->_authorize('result_read');

    return Result::search(array('spool_id' => $spool_id));
  }

  /**
   * List all available spool
   *
   * @url GET /spools
     * @url GET /spools_cdr_legacy
   */
  public function list_view($query = array())
  {
    $this->_authorize('contact_list');
    $filter  = (array)$query;
    $filter += $this->_authorization_filter();
    return Spool::list_cdr($filter);
  }

  /**
   * Export CDR
   *
   * @url GET /spools/csv
     * @url GET /spools_cdr_legacy/csv
   *
   */
  public function export_csv($query = array())
  {
    $filter = (array)$query;
    $listSpool = $this->list_view($filter);
    if ($listSpool) {
      $file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export_cdr' . '.csv';
      $handle = fopen($file_path, 'w');
      if (!$handle) {
        throw new CoreException(500, "Unable to open file");
      }
      foreach ($listSpool as $aValue) {
        $contact_row = '"' . date("Y-m-d h:i:s A", $aValue['time_start']) . '","' . date("Y-m-d h:i:s A", $aValue['time_connect']) . '","' . date("Y-m-d h:i:s A", $aValue['time_end']) . '",' .
          '"' . $aValue['username'] . '","' . $aValue['contact_phone'] . '","' . $aValue['direction'] . '","' . $aValue['company'] . '",' .
          '"' . $aValue['account_phone'] . '","' . $aValue['status'] . '","' . $aValue['pages'] . '"' . "\n";
        fwrite($handle, $contact_row);
      }
      fclose($handle);
      return new SplFileInfo($file_path);
    } else {
      throw new CoreException(404, "File not found");
    }
  }

  /**
   * List all available spool
   *
   * @url GET /spoolsstat
   * @url GET /stat
   */
  public function stat_list_view($query = array())
  {
    $this->_authorize('contact_list');
    $filter  = (array)$query;
    $filter += $this->_authorization_filter();
    return Spool::list_statistic($filter);
  }

  /**
   * Export Statistics
   *
   * @url GET /spools/stat
   * @url GET /stat/csv
   *
   */
  public function export_stat_csv($query = array())
  {
    $filter = (array)$query;
    $listSpool = $this->stat_list_view($filter);
    if ($listSpool) {
      $file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export_cdr' . '.csv';
      $handle = fopen($file_path, 'w');
      if (!$handle) {
        throw new CoreException(500, "Unable to open file");
      }
      // Add CSV header
      $header = '"Time Start","Time Connect","Time End","Duration","Username","Plan","Contact Phone","Cover Page Name","Direction","Codec","Company","Account Phone","Status","Pages","Response","Tags","User Tenant UID","Max Allowed Attempts","Attempts Done"' . "\n";
      fwrite($handle, $header);
      foreach ($listSpool as $aValue) {
        $duration = '';
        if (
          !is_null($aValue['time_start']) && !is_null($aValue['time_end']) &&
          is_numeric($aValue['time_start']) && is_numeric($aValue['time_end']) &&
          $aValue['time_end'] > $aValue['time_start']
        ) {
          $durationInSeconds = $aValue['time_end'] - $aValue['time_start'];
          $duration = gmdate("H:i:s", $durationInSeconds);
        } else {
          $duration = '';
        }

        $contact_row = '"' . date("Y-m-d h:i:s A", $aValue['time_start']) . '","'
          . date("Y-m-d h:i:s A", $aValue['time_connect']) . '","'
          . date("Y-m-d h:i:s A", $aValue['time_end']) . '","'
          . $duration . '","'
          . $aValue['username'] . '","'
          . $aValue['plan'] . '","'
          . $aValue['contact_phone'] . '","'
          . $aValue['coverpage'] . '","'
          . $aValue['direction'] . '","'
          . $aValue['codec'] . '","'
          . $aValue['company'] . '","'
          . $aValue['account_phone'] . '","'
          . $aValue['status'] . '","'
          . $aValue['pages'] . '","'
          . $aValue['mapped_response'] . '","'
          . $aValue['tags'] . '","'
          . $aValue['user_tenant_uid'] . '","'
          . $aValue['try_allowed'] . '","'
          . $aValue['try_done'] . '"' . "\n";

        fwrite($handle, $contact_row);
      }
      fclose($handle);
      return new SplFileInfo($file_path);
    } else {
      throw new CoreException(404, "File not found");
    }
  }
}
