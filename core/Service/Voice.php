<?php

namespace ICT\Core\Service;

/* * ***************************************************************
 * Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Account;
use ICT\Core\Application;
use ICT\Core\Gateway\Freeswitch;
use ICT\Core\Message\Recording;
use ICT\Core\Program;
use ICT\Core\Provider;
use ICT\Core\Service;
use ICT\Core\Token;
use ICT\Core\User;

class Voice extends Service
{

  /** @const */
  const SERVICE_FLAG = 1;
  const SERVICE_TYPE = 'voice';
  const CONTACT_FIELD = 'phone';
  const MESSAGE_CLASS = 'Recording';
  const GATEWAY_CLASS = 'Freeswitch';

  public static function capabilities()
  {
    $capabilities = array();
    $capabilities['application'] = array(
        'amd',
        'callerid_set',
        'dnc',
        'input',
        'play_menu',
        'record',
        'say_alpha',
        'say_date',
        'say_digit',
        'say_number',
        'say_time',
        'tts',
        'inbound',
        'originate',
        'connect',
        'disconnect',
        'voice_play',
        'transfer',
        'log'
    );
    $capabilities['account'] = array(
        'extension',
        'did'
    );
    $capabilities['provider'] = array(
        'sip'
    );
    return $capabilities;
  }

  /**
   * ******************************************* Default Gateway for service **
   */

  public static function get_gateway() {
    static $oGateway = NULL;
    if (empty($oGateway)) {
      $oGateway = new Freeswitch();
    }
    return $oGateway;
  }

  /**
   * ******************************************* Default message for service **
   */

  public static function get_message() {
    static $oMessage = NULL;
    if (empty($oMessage)) {
      $oMessage = new Recording();
    }
    return $oMessage;
  }

  /**
   * ***************************************** Application related functions **
   */

  public static function template_path($template_name = '')
  {
    $template_dir = Freeswitch::template_dir();
    $template_path = '';

    switch ($template_name) {
      case 'user':
        $template_path = 'user.twig';
        break;
      case 'did':
        $template_path = 'account/did.twig';
        break;
      case 'account':
      case 'extension':
        $template_path = 'account/extension.twig';
        break;
      case 'domain':
        $template_path = 'domain/domain.twig';
        break;
      case 'provider':
        $template_path = 'provider.twig';
        break;
      case 'sip':
        $template_path = 'provider/sip.twig';
        break;
      // applications
      case 'originate':
        $template_path = "application/originate/voice.json";
        break;
      case 'amd':
      case 'callerid_set':
      case 'dnc':
      case 'input':
      case 'play_menu':
      case 'record':
      case 'say_alpha':
      case 'say_date':
      case 'say_digit':
      case 'say_number':
      case 'say_time':
      case 'tts':
      case 'inbound':
      case 'connect':
      case 'disconnect':
      case 'voice_play':
      case 'transfer':
      case 'log':
        $template_path = "application/$template_name.json";
        break;
      //default:
      //   $template_path = "application/$template_name.json";
      //   break;
    }

    return "$template_dir/$template_path";
  }

  /**
   * *************************************** Configuration related functions **
   */

  public function config_update_account(Account $oAccount)
  {
    if ($oAccount->active && $oAccount->user_id > 0) {
      $oToken = new Token();
      $oToken->add('account', $oAccount);
      $this->config_save($oAccount->type, $oToken, 'account_' . $oAccount->account_id, $oAccount->domain);
      $this->config_save('domain', $oToken, $oAccount->domain);
    } else {
      $this->config_delete($oAccount->type, 'account_' . $oAccount->account_id, $oAccount->domain);
    }
    $oUser = new User($oAccount->user_id);
    $this->config_update_user($oUser);
    Voice::config_status(Voice::STATUS_NEED_RELOAD);
  }
  
   public function config_update_did_assign()
  {
    $filter = array('type' => 'forward',  'acitve'=> 'forward', 'phone' => '%');
    $Programs = Program::search($filter);
    foreach($Programs as &$Program){
     $params = json_decode($Program['data'], true);
     $extension_id = json_decode($params['extension_id'], true);
     $did_id = json_decode($params['did_id'], true);
     $Account = Account::search(['account_id' => $extension_id]);
     if($Account[0]['phone']){
       $Program['extension'] =  $Account[0]['phone'];
     }
     $Program['domain'] = $Account[0]['domain'];
     $Account = Account::search(['account_id' => $did_id]);
     if($Account[0]['phone']){
       $Program['did'] =  $Account[0]['phone'];
     }
    }
      $oToken = new Token();
      $oToken->add('programs', $Programs);
      $this->config_save('ictcore', $oToken, 'program');
  }

  public function config_update_user(User $oUser)
  {
    if ($oUser->active) {
      $account_filter = array('type' => 'extension', 'acitve'=> 1, 'phone' => '%');
      $listAccount = Account::search($account_filter);
      $oToken = new Token();
      $oToken->add('user', $oUser);
      $oToken->add('user_accounts', $listAccount);
      $this->config_save('user', $oToken, 'user_' . $oUser->user_id);
    } else {
      $this->config_delete('user', 'user_' . $oUser->user_id);
    }
    Voice::config_status(Voice::STATUS_NEED_RELOAD);
  }

  public function config_update_provider(Provider $oProvider)
  {
    if ($oProvider->active) {
      $oToken = new Token();
      $oToken->add('provider', $oProvider);
      // gateway XML is owned by Provider::sync_fs_xml() (sip_profiles/provider/<Name>.xml); only write the originate dialplan here
      $this->config_save('provider', $oToken, 'provider_' . $oProvider->provider_id);
    } else {
      $this->config_delete('provider', 'provider_' . $oProvider->provider_id);
    }
    Voice::config_status(Voice::STATUS_NEED_RELOAD);
  }

}
