<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api\ProgramApi;
use ICT\Core\Api\TransmissionApi;
use ICT\Core\Api\ContactApi;
use ICT\Core\Conf\File;
use ICT\Core\DB;
use ICT\Core\Message\Template;
use Firebase\JWT\JWT;
use ICT\Core\Corelog;
use ICT\Core\CoreException;
use Firebase\JWT\ExpiredException;
use ICT\Core\Conf;



class Forgot_password
{
  protected static $table = 'forgot_password';
  protected static $primary_key = 'forgot_password_id';
  protected static $fields = array(
    'usr_id',
    'email',
    'token',
    'created_at',

  );

  public function forgot($data)
  {
    $filter = array(
      'email' => $data['email']
    );
    $user = User::search($filter);
    $email = $user[0]['email'];
      if (!empty($email)) {
        $usr_id = $user[0]['user_id'];
        $usr = new User($usr_id);
        $token = $usr->generate_token();
        $data = [
          'usr_id' => $usr_id,
          'email' => $email,
          'token' => $token,
          'created_at' => time() + 600
        ];
        $result = DB::update(self::$table, $data, false);
        $link = $this->set_add($token);
        $this->send_email($link , $usr);
        return true;
     }
    else{
      throw new CoreException(404, 'No Email Found.');
  }
  }

  private function set_add($token) {
    $domainfile = Conf::get_instance();
    $domain = $domainfile['domain']['title'];
    $link = $domain ."/#/auth/reset-password?code=". $token; 
    return $link;
  }

  public function send_email($link , $usr){

    $oSession = Session::get_instance();
    $oSession->user = $usr;
    $email = $usr->email;
    $oSession->link = $link;
    $templatePath = 'Program/Emailtofax/data/email_pass.tpl.php';
    $oTemplate = Template::construct_from_file($templatePath);
    $oTemplate->save();
    $template_id = $oTemplate->template_id;
    $programData = array(
        'name' => 'sendemail',
        'template_id' => $template_id,
    );
    
    $oProgram = new ProgramApi();
    $program_id = $oProgram->create('sendemail', $programData);
    $contactData = array('email' => $email);
    $oContact = new ContactApi();
    $contact_id = $oContact->create($contactData);
    $oAccount = new Account();
    $accountData = array(
        'created_by' => $user_id
    );
    $account = $oAccount->search($accountData);
    $transmissionData = array(
        'title'        =>  'forgot_passwd',
        'origin'       =>  'sendemail',
        'service_flag' =>  '8',
        'program_id'   =>  $program_id,
        'contact_id'   =>  $contact_id,
        'program_data' => array(
            'program_id'   =>  $program_id,
            'type'        => 'sendemail',
            'template_id' => $template_id
        ),
        'transmission_data' => array(
            'direction'    =>  'outbound',
            'contact_id'   =>  $contact_id,
            'account_id'   =>  $account[0]['account_id'],
        ),
    );
    $oTransmission = new TransmissionApi();
    $transmission_id = $oTransmission->create($transmissionData);
    $transmission_send = $oTransmission->send($transmission_id);
  }

  public function reset_password($data)
  {
    $query = "SELECT * FROM " . self::$table . " WHERE token = '" . $data['code'] . "'";
    $result = DB::query(self::$table, $query);
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $rows[] = $row;
    }
    foreach ($rows as $singleRow) {
      $aRow = $singleRow;
    }
    if (!empty($aRow)) {
      $token = $this->decode_token($aRow['token']);
      if ($token !== false) {
        if (!empty($data['password'])) {
          $password_hash = md5($data['password']);
          $usr_table = 'usr';
          $usr_query = "UPDATE " . $usr_table . " SET passwd = '" . $password_hash . "' WHERE usr_id = '" . $aRow['usr_id'] . "'";
          $usr_result = DB::query($usr_table, $usr_query);
          if ($usr_result) {
            $del_query = "DELETE FROM " . self::$table . " WHERE usr_id = '" . $aRow['usr_id'] . "' AND token = '" . $data['code'] . "'";
            $del_result = DB::query(self::$table, $del_query, false);
            return $del_result;
          } else {
            throw new CoreException(417, 'Password reset failed.');
          }
        } else {
          throw new CoreException(400, 'Password field is empty.');
        }
      } else {
        throw new CoreException(401, 'Token has expired.');
      }
    } else {
      throw new CoreException(422, 'Invalid Token.');
    }
  }

  public function decode_token($token)
  {
    try {
      $key_file = Conf::get('security:public_key', '/usr/ictcore/etc/ssh/ib_node.pub');
      $hash_type = Conf::get('security:hash_type', 'RS256');
      $public_key = file_get_contents($key_file);
      return JWT::decode($token, $public_key, array($hash_type));   
    } catch (ExpiredException $e) {
      // Handle token expiration
      return false;
    } catch (SignatureInvalidException $e) {
      // Handle signature validation failure
      return false;
    }
  }
}
