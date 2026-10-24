<?php

namespace ICT\Core\Provider;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Provider;
use ICT\Core\Service\Voice;
use ICT\Core\Service\Fax;
use ICT\Core\Gateway\Freeswitch;

class Sip extends Provider
{

  /**
   * @property-read string $type
   * @var string 
   */
  public $type = 'sip';

  /** @var string */
  public $port = '5060';

  /**
   * @property integer $service_flag
   * @var integer
   */
  public $service_flag = 3; // i.e (Voice::SERVICE_FLAG | Fax::SERVICE_FLAG);

  public static function search($aFilter = array())
  {
    $aFilter['type'] = 'sip';
    return parent::search($aFilter);
  }

  public function save()
  {
    $result = parent::save();

    // Voice config is always refreshed for SIP providers.
    try {
      $oVoice = new Voice();
      $oVoice->config_update_provider($this);
    } catch (\Throwable $e) {
      \ICT\Core\Corelog::log('Voice config_update_provider failed: ' . $e->getMessage(), \ICT\Core\Corelog::ERROR);
    }

    // Fax config refresh only when the fax service bit is set on this trunk.
    if (((int)$this->service_flag & Fax::SERVICE_FLAG) === Fax::SERVICE_FLAG) {
      try {
        $oFax = new Fax();
        $oFax->config_update_provider($this);
      } catch (\Throwable $e) {
        \ICT\Core\Corelog::log('Fax config_update_provider failed: ' . $e->getMessage(), \ICT\Core\Corelog::ERROR);
      }
    }

    return $result;
  }

  public function delete()
  {
    // configuration update is required for providers
    $this->active = 0; // disable to delete, no save needed
    $oVoice = new Voice();
    $oVoice->config_update_provider($this);

    if (((int)$this->service_flag & Fax::SERVICE_FLAG) === Fax::SERVICE_FLAG) {
      try {
        $oFax = new Fax();
        $oFax->config_update_provider($this);
      } catch (\Throwable $e) {
        \ICT\Core\Corelog::log('Fax config_update_provider failed during delete: ' . $e->getMessage(), \ICT\Core\Corelog::ERROR);
      }
    }

    // now it is safe to delete
    return parent::delete();
  }

  public static function _status($provider_name) {
    $oFreeswitch = new Freeswitch();
    return $oFreeswitch->provider_status($provider_name);
  }
}
