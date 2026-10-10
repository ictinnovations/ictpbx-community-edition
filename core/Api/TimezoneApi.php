<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\Timezone;
use ICT\Core\CoreException;
use ICT\Core\User\Permission;
use ICT\Core\Session;
use ICT\Core\Corelog;

#[\AllowDynamicProperties]
class TimezoneApi extends Api
{

  /**
   * List all Timezones
   *
   * @url GET /timezone
   */
  public function list_view()
  {
      return Timezone::getList();
  }

  /**
   * Gets the timezone by id
   *
   * @url GET /timezone/$timezone_id
   */
  public function read($timezone_id)
  {
    $oTimezone = new Timezone($timezone_id);
    return $oTimezone;
  }

}
