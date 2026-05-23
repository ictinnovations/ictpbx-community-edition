<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Message\Document;
use ICT\Core\Message\Template;
use ICT\Core\Program\Sendemail;

class Internalfax
{
  /**
   * @var integer
   */
  public $account_id = NULL;

  /**
   * @var integer
   */
  public $contact_id = NULL;

  /**
   * @var integer
   */
  public $spool_id = NULL;

  /**
   * @var integer
   */
  public $program_id = NULL;

  /**
   * @var integer
   */
  public $created_by = NULL;

  /**
   * @var string
   */
  public $transmission_id = NULL;

  /**
   * @var string
   */
  public $type = NULL;

  /**
   * @var string
   */
  public $name = NULL;

  /**
   * @var string
   */
  public $r_acc_id = NULL;


  public function execute()
  {
    $contact_id = $this->contact_id;
    $acc_id = $this->account_id;
    if ($this->type == 'sendfax') {
      $this->type = 'faxtoemail';
      $this->name = 'internal';
      $oSession = Session::get_instance();
      $aprogram = Program::load('faxtoemail');
      $aprogram->account_id = $acc_id;
      $aprogram->save();
      $aprogram->deploy();
      $in_transmission = $aprogram->transmission_create($contact_id, $acc_id, Transmission::INBOUND);
      $in_transmission->status = Transmission::STATUS_COMPLETED;
      $in_transmission->program_id = $aprogram->program_id;
      $spool = new Spool();
      $this->oTransmission->oSpool->status = Transmission::STATUS_COMPLETED;
      $spool->time_spool = time();
      $spool->time_start = time();
      $spool->time_connect = time();
      $spool->status = Transmission::STATUS_COMPLETED;
      $spool->save();
      $query = "update spool set time_connect= $spool->time_spool where spool_id=$spool->spool_id";
      $result = DB::query('spool', $query);
      //  $in_transmission->phone = 'N/A';
      $in_transmission->last_run = time();
      $in_transmission->contact_id = $this->contact_id;
      $in_transmission->save();
      $this->transmission_id = $in_transmission->transmission_id;
      $this->spool_id = $spool->spool_id;
      $this->set_spool($in_transmission);
      $document_id = $this->internal_document();
      $this->document_id = $document_id;
      $oDocument = new Document($document_id);
      $this->create_spool_result($this->spool_id, 'document', $document_id);
      $this->create_spool_result($this->spool_id, 'pages', $oDocument->pages);
      // $this->send_email_notification('sendfax_faxsent');
      $this->send_email_notification_inbound($oDocument);
    }
  }

  public function set_spool($transmission)
  {
    $in_spool = new Spool($this->spool_id);
    $in_spool->transmission_id = $transmission->transmission_id;
    $in_spool->status = 'completed';
    $in_spool->service_flag = 2;
    $in_spool->node_id = 1;
    $in_spool->account_id = $this->account_id;
    $in_spool->save();
  }

  public function create_spool_result($spool_id, $name, $data)
  {
    $spool_result = new Result();
    $spool_result->spool_id = $spool_id;
    $spool_result->type = 'message';
    $spool_result->name = $name;
    $spool_result->data = $data;
    $spool_result->save();
    return $this->assign_to_owner();
  }

  public function assign_to_owner()
  {
    $query = "UPDATE transmission SET created_by = $this->created_by WHERE transmission_id = $this->transmission_id";
    return DB::query('transmission', $query);
  }

  public function internal_document()
  {
    $doc_id = Session::get_instance();
    $Documentid = $doc_id->program->document_id;
    // $Documentid = $this->aResource['document']->document_id;
    //$Documentid = Session::get_instance()->document->document_id;
    $document = new Document($Documentid);
    $originalFile = $document->file_name;
    $doctitle = $document->name;
    $directory = dirname($originalFile); // Get the directory path
    $fileName = basename($originalFile); // Get the file name
    $newFileName = $directory . '/internal_' . $fileName; // New file name with prefix
    if (copy($originalFile, $newFileName)) {
      $newDocument = new Document();
      $newDocument->name = 'Internal_Doc_' . $doctitle;
      $newDocument->file_name = $newFileName;
      $newDocument->save();
      return $newDocument->document_id;
    }
  }

  /**
   * **************************************************************************
   * Send Email Notification
   * **************************************************************************
   */
  public function send_email_notification($notification_type)
  {
    switch ($notification_type) {
      case 'sendfax_faxsent':
        $parent_alias = 'fax';
        $template_file = 'Program/Sendfax/data/sendfax_success.tpl.php';
        break;
      case 'sendfax_faxfailed':
        $parent_alias = 'fax';
        $template_file = 'Program/Sendfax/data/sendfax_error.tpl.php';
        break;
      case 'sendfax_faxfailed_alert':
      default:
        $parent_alias = 'fax';
        $template_file = 'Program/Sendfax/data/sendfax_error_notify.tpl.php';
        break;
    }

    // Create document object for attachment
    $oDocument = new Document($this->document_id);

    // Prepare token object for following transmissions
    $currentToken = new Token(Token::SOURCE_ALL);
    $currentToken->add('program', $this);
    $currentToken->add('document', $oDocument);
    $oToken = new Token();
    $oToken->add($parent_alias, $currentToken->token); // put everything into new sub group to avoid token conflicts    
    $oTemplate = Template::construct_from_file($template_file);
    // Now replace all program related tokens in loaded template, but remember to keep missing tokens
    $oTemplate->token_apply($oToken, Token::KEEP_ORIGNAL);
    if ($notification_type == 'sendfax_faxfailed') {
      $oTemplate->attachment = Document::create_pdf($oDocument->file_name, 'tif');
      $oTemplate->save();
    } else {
      $oTemplate->save();
    }
    // prepare data for new program
    $programData = array(
      'name' => $notification_type,
      'parent_id' => $this->program_id,
      'template_id' => $oTemplate->template_id
    );

    // prepare data for new transmission
    $reply_from = Conf::get('emailtofax:reply_account', 'default');

    // Replace account ID for fax failure alert to support
    $account_id = '';
    if ($notification_type == 'sendfax_faxfailed_alert') {
      // Get Support account information
      $oAccount = new Account();
      $support_account = $oAccount->search(['username' => 'system']);
      $account_id = $support_account[0]['account_id'];
    } else {
      $account_id = ($reply_from == 'company') ? Contact::COMPANY : $this->oTransmission->account_id;
    }

    $transmissionData = array(
      'contact_id' => $this->oTransmission->contact_id,
      // replace contact with company contact as per system configurations
      'account_id' => $account_id,
      'direction' => Transmission::INBOUND
    );

    // Now we are ready to create new transmission for email
    $emailTransmission = Sendemail::transmission_instant($programData, $transmissionData);
    $emailTransmission->task_create();
    //$emailTransmission->send();
  }

  /**
   * **************************************************************************
   * Send Email Notification
   * **************************************************************************
   */
  public function send_email_notification_inbound(Document $oDocument)
  {
    // Prepare token object for following transmissions
    $currentToken = new Token(Token::SOURCE_ALL);
    $currentToken->add('program', $this);
    $currentToken->add('document', $oDocument); // document is required by following template
    $oToken = new Token();
    $oToken->add('fax', $currentToken->token); // put everything into new sub group to avoid token conflicts

    $this->oTransmission->contact_id = $this->contact_id;
    $this->oTransmission->account_id = $this->account_id;
    $account = new Account($this->account_id);
    $contact = new Contact($this->contact_id);
    Session::get_instance()->transmission->contact->phone = $account->phone;
    Session::get_instance()->transmission->account->phone = $contact->phone;
    $oTemplate = Template::construct_from_file("Program/Faxtoemail/data/fax_internal_received.tpl.php");
    // Now replace all program related tokens in loaded template, but remember to keep missing tokens
    // Note: in template there is a attachment token, (see: token_create in transmission_done for oDocument)
    $oTemplate->token_apply($oToken, Token::KEEP_ORIGNAL);
    // Now convert tif file into pdf
    $oTemplate->attachment = $oDocument->create_pdf($oDocument->file_name, 'aes');
    $oTemplate->save();

    // prepare data for new program
    $programData = array(
      'name' => 'faxtoemail_faxreceived',
      'parent_id' => $this->program_id,
      'template_id' => $oTemplate->template_id
    );

    // prepare data for new transmission
    $reply_from = Conf::get('emailtofax:reply_account', 'default');
    $transmissionData = array(
      'contact_id' => $this->contact_id,
      // // replace contact with company contact as per system configurations
      'account_id' => ($reply_from == 'company') ? Contact::COMPANY : $this->account_id,
      'direction' => Transmission::INBOUND
    );

    $emailTransmission = Sendemail::transmission_instant($programData, $transmissionData);
    $emailTransmission->task_create();
  }
}
