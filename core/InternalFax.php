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
   * Receiving tenant (the DID's)
   * @var integer
   */
  public $tenant_id = NULL;

  /**
   * Plain pdf of the delivered fax, for the email attachment
   * @var string
   */
  public $pdf_file = NULL;

  /**
   * Page count of the delivered fax
   * @var integer
   */
  public $pages = 0;

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
      $aprogram->tenant_id = $this->tenant_id;
      $aprogram->save();
      $this->assign_to_receiver('program', 'program_id', $aprogram->program_id);
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
      DB::query('transmission', "UPDATE transmission SET pages = " . (int)$this->pages . " WHERE transmission_id = " . (int)$this->transmission_id);
      $oDocument = new Document($document_id);
      $this->create_spool_result($this->spool_id, 'document', $document_id);
      $this->create_spool_result($this->spool_id, 'pages', $this->pages);
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
    return $this->assign_to_receiver('transmission', 'transmission_id', $this->transmission_id);
  }

  /**
   * Everything saved here is saved in the sender's session, so DB::update stamps it with the
   * sender's tenant and user. File it under the receiver instead: the DID's tenant, and the
   * owner of the fax account the DID is assigned to (none, rather than the sender, if unassigned).
   */
  private function assign_to_receiver($table, $key, $id)
  {
    if (empty($this->tenant_id) || empty($id)) {
      return true;
    }
    $owner = is_null($this->created_by) ? 'NULL' : (int)$this->created_by;
    return DB::query($table, "UPDATE $table SET tenant_id = " . (int)$this->tenant_id . ", created_by = $owner WHERE $key = " . (int)$id);
  }

  public function internal_document()
  {
    $oSession = Session::get_instance();
    $document = new Document($oSession->program->document_id);
    // Documents are encrypted with their owner's key. The file_name getter decrypts with the
    // sender's (this session's), so turn that into a plain pdf and save the copy as the receiver:
    // a byte copy of the sender's .aes could never be opened by the receiver.
    $pdf_file = $document->create_pdf($document->file_name, 'tif');
    $this->pdf_file = $pdf_file;
    $this->pages = (int)$document->pages; // counted by the getter's tiff conversion
    $sender = $oSession->user;
    if (!is_null($this->created_by)) {
      $oSession->user = new User($this->created_by);
    }
    try {
      $newDocument = new Document();
      $newDocument->name = 'Internal_Doc_' . $document->name;
      $newDocument->file_name = $pdf_file;
      $newDocument->save();
    } finally {
      $oSession->user = $sender;
    }
    $this->assign_to_receiver('document', 'document_id', $newDocument->document_id);
    DB::query('document', "UPDATE document SET pages = " . (int)$this->pages . " WHERE document_id = " . (int)$newDocument->document_id);
    return $newDocument->document_id;
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
    $contact = new Contact($this->contact_id, 1); // unscoped: the receiver's contact, loaded in the sender's session
    Session::get_instance()->transmission->contact->phone = $account->phone;
    Session::get_instance()->transmission->account->phone = $contact->phone;
    $oTemplate = Template::construct_from_file("Program/Faxtoemail/data/fax_internal_received.tpl.php");
    // Now replace all program related tokens in loaded template, but remember to keep missing tokens
    // Note: in template there is a attachment token, (see: token_create in transmission_done for oDocument)
    $oTemplate->token_apply($oToken, Token::KEEP_ORIGNAL);
    // The plain pdf made on delivery; the stored copy is encrypted with the receiver's key
    $oTemplate->attachment = $this->pdf_file;
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
    // The notification (and its template, which carries the fax as attachment) belongs to the receiver too
    $this->assign_to_receiver('template', 'template_id', $oTemplate->template_id);
    $this->assign_to_receiver('program', 'program_id', $emailTransmission->program_id);
    $this->assign_to_receiver('transmission', 'transmission_id', $emailTransmission->transmission_id);
    $emailTransmission->task_create();
  }
}
