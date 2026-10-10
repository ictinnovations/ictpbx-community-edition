<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Account;
use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Tenant;
use ICT\Core\User;
use ICT\Core\Transmission;
use ICT\Core\User\Permission;
use ICT\Core\Activity;
use ICT\Core\Conf;
use ICT\Core\Session;
use ICT\Core\Corelog;
use SplFileInfo;

#[\AllowDynamicProperties]
class ActivityApi extends Api
{


/**
   * Gets the user by id
   *@noAuth
   * @url GET /users/activity/$user_id
   */
  public function activity($user_id)
  { 
    return Activity::systemactivity(array('user_id' => $user_id));

  }

  /**
   * Gets the user by id
   *@noAuth
   * @url GET /users/activities/$user_id
   */
  public function activities($query = array())
  {
    $filter  = (array)$query;
   
    return Activity::systemactivity($filter); 
    }
  
     /**
   * Provide Activities Sample
   *
   * @url GET /users/act/csv/$userid
   */

    public function export_csv($query = array())
    {
      $filter = (array)$query;
      $listSpool = $this->activities($filter);
      if ($listSpool) {
        $file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export_cdr'.'.csv';
        $handle = fopen($file_path, 'w');
        if (!$handle) {
          throw new CoreException(500, "Unable to open file");
        }
        foreach($listSpool as $aValue) {
          $contact_row = '"'. $aValue['id'].'", "'. $aValue['user_id'].'", "'. $aValue['username'].'","'. $aValue['activities'].'","'.date("Y-m-d h:i:s A", $aValue['date']).'"'."\n";
          fwrite($handle, $contact_row);
        }
        fclose($handle);
        return new SplFileInfo($file_path);
      } else {
        throw new CoreException(404, "File not found");
      }
    }



     /**
   * Gets the transmission by id
   *noAuth
   * @url GET /transmissions/faxactivity/$transmission_id
   */
  public function faxactivity($transmission_id)
  {
    $oTransmission = new Transmission();
    return $oTransmission->getfaxactivity($transmission_id);
  }

  /**
   * Gets the transmission by id
   *noAuth
   * @url GET /transmissions/faxlogs/$transmission_id
   */
  public function faxlogs($transmission_id)
  {
    $oTransmission = new Transmission();
    return $oTransmission->getfaxlogs($transmission_id);
  }

  }