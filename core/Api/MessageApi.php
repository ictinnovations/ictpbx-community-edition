<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;

#[\AllowDynamicProperties]
class MessageApi extends Api
{
  // empty class just to include classes from message folder

  protected static function rest_include()
  {
    return 'Api/Message';
  }

}
