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

class Process extends CoreThread
{

  function process()
  {
    // First parameter will be oRequest
    $oRequest = $this->getParam(0);
    sleep(1); // wait 1 second before processing
    Core::process($oRequest);
  }

}