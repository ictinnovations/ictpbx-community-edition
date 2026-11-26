<?php

namespace ICT\Core\Account;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Account;
use ICT\Core\User;
use ICT\Core\Service\Voice;
use ICT\Core\Session;

class Extension extends Account
{

  /**
   * @property-read string $type
   * @var string
   */
  protected $type = 'extension';

  public static function search($aFilter = array())
  {
    $aFilter['type'] = 'extension';
    return parent::search($aFilter);
  }

  public function save()
  {
    $result = parent::save();

    // configuration update is required for accounts
    $oVoice = new Voice();
    $oVoice->config_update_account($this);
    $oVoice->config_update_did_assign();

    return $result;
  }

  public function delete()
  {
    $result = parent::delete();
    // configuration update is required for accounts
    $this->active = 0; // disable to delete, no save needed
    $files = glob('/usr/ictcore/etc/freeswitch/directory/'.$this->domain.'*');
    foreach ($files as $file) {
      if (is_file($file)) {
        unlink($file);
      }
    }
    $oVoice = new Voice();
    $oVoice->config_update_account($this);
    return $result;
  }

  public function associate($user_id, $aUser = array())
  {
    $result = parent::associate($user_id, $aUser);
    // configuration update is required for accounts
    $oVoice = new Voice();
    $oVoice->config_update_account($this);
    return $result;
  }

  public function dissociate()
  {

    $result = parent::dissociate();
    // configuration update is required for accounts
    $oVoice = new Voice();
    $oVoice->config_update_account($this);
    return $result;
  }
}
