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
class DidApi extends AccountApi
{

  /**
   * Create a new account (legacy — superseded by /usr/ictcore/core/Api/DidApi.php)
   *
   * @url POST /legacy_dids
   */
  public function create($data = array())
  {
    $data['type'] = 'did';
    return parent::create($data);
  }

  /**
   * List all available accounts (legacy — superseded by /usr/ictcore/core/Api/DidApi.php)
   *
   * @url GET /legacy_dids
   */
  public function list_view($query = array())
  {
    $query['type'] = 'did';
    return parent::list_view($query);
  }

}
