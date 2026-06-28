<?php

namespace ICT\Core\Thread;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Core;
use ICT\Core\CoreThread;

class Send extends CoreThread
{

  function process()
  {
    // First parameter will be transmission
    $oTransmission = $this->getParam(0);
    sleep(1); // wait 1 second before processing
    Core::send($oTransmission);
  }

}