<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Extensions_cdr;
use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\FpbxDomain;
use SplFileInfo;



class Extensions_cdrApi extends Api
{

  /**
   * List all cdr regarding users
   *
   * @url GET /usersCDR
   */
  public function list_view($query = array())
  {
    $this->_authorize('cdr_list');
    $filter = (array)$query;

    // Scope non-admins to their tenant's FusionPBX domain
    if (!\ICT\Core\can_access('super_admin')) {
      $domain_uuid = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
      $domain_name = $domain_uuid ? FpbxDomain::get_domain_name($domain_uuid) : null;
      if ($domain_name) {
        $filter['domain'] = $domain_name;
      } else {
        return []; // tenant has no PBX domain — no CDR to show
      }
    }

    return Extensions_cdr::search($filter);
  }

  /**
   * Export extensions cdr
   *
   * @url GET /usersCDR/csv
   *
   */
  public function extension_csv($query = array())
  {
    $filter = (array)$query;
    $list = $this->list_view($filter);
    if ($list) {
      $file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export_ext_cdr' . '.csv';
      $handle = fopen($file_path, 'w');
      if (!$handle) {
        throw new CoreException(500, "Unable to open file");
      }
      // Add CSV header
      $csv_headers = '"Time", "Caller Number", "Destination", "Duration", "Hangup Cause", "Domain"' . "\n";
      fwrite($handle, $csv_headers);
      foreach ($list as $aValue) {
        $csv_rows = '"'
          . $aValue['start_time'] . '","'
          . $aValue['caller_number'] . '","'
          . $aValue['destination'] . '","'
          . $aValue['duration'] . '","'
          . $aValue['hangup_cause'] . '","'
          . $aValue['domain'] . '"' . "\n";
          fwrite($handle, $csv_rows);
        }
        fclose($handle);
        return new SplFileInfo($file_path);
      } else {
        throw new CoreException(404, "File not found");
      }
    }

}
