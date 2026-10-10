<?php
/* * ***************************************************************
 * Copyright © 2014 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : nasir@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Service;
use ICT\Core\Task;
use ICT\Core\Corelog;
use ICT\Core\User;
use ICT\Core\Tenant;
use ICT\Core\Transmission;
use ICT\Core\Message\Document;
use ICT\Core\Spool;
use ICT\Core\Password_Policy;

// default include is /usr/ictcore/core
chdir(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'core');

include_once "Core.php";

function cron_process()
{
  // process all pending retries
  Task::process_all();

  // counting user passowrd expiration
  $Password_Policy = new Password_Policy(); 
  $Password_Policy->password_exp_limit();

  // execute reload method for all available services
  // it will restart gateway in case there are new configurations
  $listService = Service::load_all();
  foreach ($listService as $oService) {
    $oService->config_update();
  }

  // execute email fetch script
  // nothing special we just need to include it for execution
  include_once('../bin/sendmail/gateway.php');

  date_default_timezone_set('UTC');
  $newDateTime = date('h:i A');
  if ($newDateTime == '12:00 AM') {
    // Corelog::log('Reseting user limits at: '.date('r'), Corelog::INFO);
       Corelog::log('Reseting tenant limits at: '.date('r'), Corelog::INFO);

      $listUser = User::search();      
      foreach ($listUser as $aUser) {
      $oUser = new User($aUser['user_id']);
      $oUser->reset_daily_sent();
      if (date('d') == 1) { // if first day of month? then also reset monthly limit
        $oUser->reset_monthly_sent();
      }
      }
  }
}

cron_process();
exit();
