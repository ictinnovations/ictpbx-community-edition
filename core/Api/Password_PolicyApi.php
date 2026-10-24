<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2017 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\Password_Policy;

#[\AllowDynamicProperties]
class Password_PolicyApi extends Api
{

 /**
   *
   * @url POST /password_policy
   */
  public function create($data = array())
  {
    $this->_authorize('password_policy');
    $oPasswd_policy = new Password_Policy();
    $oPasswd_policy->getPolicy();
    $this->set($oPasswd_policy, $data);
    $oPasswd_policy->save();
    return $oPasswd_policy;
  }

    /**
     * 
     * Get the user password policy
     *
     * @url GET /password_policy
     */
    public function get_password_policy()
    {
        // Optionally handle authorization
        $Password_Policy = new Password_Policy();
        $Password_Policy->getPolicy();
        return $Password_Policy;
    }
}
