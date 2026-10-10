<?php

namespace ICT\Core\Api\Account;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api\AccountApi;

#[\AllowDynamicProperties]
class ExtensionApi extends AccountApi
{

  /**
   * Create a new account
   *
   * @url POST /extensions
   */
  public function create($data = array())
  {
    $data['type'] = 'extension';
    return parent::create($data);
  }

  /**
   * List all available accounts
   *
   * @url GET /extensions
   */
  public function list_view($query = array())
  {
    $query['type'] = 'extension';
    return parent::list_view($query);
  }

}
