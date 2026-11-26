<?php

namespace ICT\Core\Program;

/* * ***************************************************************
 * Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Application\Disconnect;
use ICT\Core\Application\Fax_send;
use ICT\Core\Application\Originate;
use ICT\Core\Message\Document;
use ICT\Core\Message\Template;
use ICT\Core\Conf;
use ICT\Core\Program;
use ICT\Core\Result;
use ICT\Core\Scheme;
use ICT\Core\Service\Fax;
use ICT\Core\Transmission;
use Dompdf\Dompdf;
use ICT\Core\Token;
use ICT\Core\Account;
use ICT\Core\Tenant;
use ICT\Core\User;
use ICT\Core\Session;
use ICT\Core\Corelog;

class Sendfax extends Program
{

  /** @var string */
  public $name = 'sendfax';

  /**
   * @property-read string $type
   * @var string 
   */
  protected $type = 'sendfax';

  /**
   * **************************************************** Program Parameters **
   */

  /**
   * document_id of document being used as message in this program
   * @var int $document_id
   */
  public $document_id = '[document:document_id]';

  /**
   * return a name value pair of all additional program parameters which we need to save
   * @return array
   */
  public function parameter_save()
  {
    if (!is_numeric($this->document_id)) {
      throw new \ICT\Core\CoreException(412, 'A document must be selected before saving this fax program.');
    }
    $aParameters = array(
      'document_id' => $this->document_id
    );
    return $aParameters;
  }

  /**
   * Locate and load document
   * Use document_id or content or data from program parameters as reference
   * @return Document null or a valid document object
   */
  protected function resource_load_document()
  {
    if (isset($this->document_id) && !empty($this->document_id)) {
      $oDocument = new Document($this->document_id, 1);
      return $oDocument;
    } else if (isset($this->file_name) || isset($this->document_file)) {
      $file_name = !empty($this->file_name) ? $this->file_name : $this->document_file;
      if (!empty($file_name)) {
        $oDocument = Document::construct_from_array(array('file_name' => $file_name));
        $oDocument->save();
        // update document_id with new value, and remove all temporary parameters
        $this->document_id = $oDocument->document_id;
        unset($this->file_name);
        unset($this->document_file);
        return $oDocument;
      }
    }
  }

  /**
   * Function: scheme
   * Program scheme for primary transmission, application execution order and conditions
   */
  public function scheme()
  {
    $outboundCall = new Originate();
    $outboundCall->source = '[transmission:source:phone]';
    $outboundCall->destination = '[transmission:destination:phone]';

    $faxSend = new Fax_send();

    $attachment = $this->aResource['document']->file_name;
    $document_id = $this->aResource['document']->document_id;
    $has_token = $this->aResource['document']->has_token;

    $oSession = Session::get_instance();

    if ($oSession->user->cover == 1 && $oSession->program->cover_id < 1 && $oSession->template) {
      // Send Cover sheet with the Sender and Receiver name and current date
      global $path_cache;
      $coverpage_pdf = tempnam($path_cache, 'coverpage_') . '.pdf';
      // Generate Cover page PDF
      $dompdf = new Dompdf();
      $cover_body = '<center><h1> Fax Transmission </h1></center><div style="line-height: 1.6; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;"><table style="width: 100%;"><tr><th style="text-align: left; font-weight: normal;">Sender Info:</th><td style="text-align: right;">[user:first_name] [user:last_name]</td></tr><tr><th style="text-align: left; font-weight: normal;">Sender Number:</th><td style="text-align: right;">[user:phone]</td></tr><tr><th style="text-align: left; font-weight: normal;">Company:</th><td style="text-align: right;">[user:company]</td></tr><tr><th style="text-align: left; font-weight: normal;">Date:</th><td style="text-align: right;">' . date("Y-m-d") . '</td></tr></table></div> <hr> Subject: <br>[template:subject]<br>Body: <br> [template:body] ';
      $resourceToken = new Token(Token::SOURCE_ALL, $oSession);
      $cover_body = $resourceToken->render_string($cover_body);

      $dompdf->loadHtml($cover_body);
      $dompdf->setPaper('A4', 'portrait'); // Setup the paper size and orientation
      $dompdf->render();                   // Render the HTML as PDF
      file_put_contents($coverpage_pdf, $dompdf->output());

      if (is_file($coverpage_pdf)) {
        if (empty($attachment) || !is_file($attachment) || !$oSession->template->attachment) {
          $attachment = $coverpage_pdf;
        } else {
          $attachment = \ICT\Core\path_prepend($attachment, $coverpage_pdf);
        }
      }

      $oDocument = new Document();
      $oDocument->file_name = $attachment;
      $oDocument->save();

      $attachment = $oDocument->file_name;
    }

    $faxSend->message = $attachment;
    $faxSend->document_id = $document_id;
    $faxSend->personalization_required = $has_token;
    $faxSend->header = $this->aResource['document']->name;

    $hangupCall = new Disconnect();

    $oScheme = new Scheme($outboundCall);
    $oScheme->link($faxSend)->link($hangupCall);

    return $oScheme;
  }

  /**
   * Function: transmission_create
   * Creating transmission while using current program
   */
  public function transmission_create($contact_id, $account_id, $direction = Transmission::OUTBOUND)
  {
    $oTransmission = parent::transmission_create($contact_id, $account_id, $direction);
    $oTransmission->service_flag = Fax::SERVICE_FLAG;
    return $oTransmission;
  }

  /**
   * **************************************************************************
   * Send Email Notification
   * **************************************************************************
   */
  public function send_email_notification($notification_type)
  {
    if($this->oTransmission->try_done < $this->oTransmission->try_allowed){
     return;
    }
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
    $oDocument = new Document($this->document_id, 1);

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
   * Event: Transmission completed
   * Will be fired when first / initial transmission is done
   * here we will decide either its was completed or failed
   */
  protected function transmission_done()
  {
    if ($this->result['result'] == 'error') {
      // Send email notification to user, about fax failed
      $this->send_email_notification('sendfax_faxfailed');
      // Send email notification to support, about fax failed
      // $this->send_email_notification('sendfax_faxfailed_alert');
      return Transmission::STATUS_FAILED;
    }

    // after processing further, we can confirm if current transmission was completed
    $result = 'error';
    $pages = 0;
    $error = '';
    foreach ($this->oTransmission->aResult as $oResult) {
      switch ($oResult->type) {
        case Result::TYPE_APPLICATION:
          if ($oResult->name == 'fax_send' && $oResult->data == 'success') {
            $result = 'success';
            // Send email notification to user, about fax delivery
            $this->send_email_notification('sendfax_faxsent');
          }
          break;
        case Result::TYPE_INFO:
          if ($oResult->name == 'pages') {
            $pages = $oResult->data;
          }
          break;
        case Result::TYPE_ERROR:
          $result = 'error';
          $error = $oResult->data;
          break 2; // in case of error, also terminate foreach loop
      }
    }

    if ($result == 'success' && empty($error) && $pages > 0) {
      //if ($result == 'success' && empty($error)) {
      $this->result['pages'] = $pages;
      // Add in consume fax
      $oUsr = new User($this->user_id);
      $oUsr->consume_credit($pages);
      return Transmission::STATUS_COMPLETED;
    } else {
      $this->result['result'] = 'error';
      $this->result['error'] = $error;
      return Transmission::STATUS_FAILED;
    }
  }
}
