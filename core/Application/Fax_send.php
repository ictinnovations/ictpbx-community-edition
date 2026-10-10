<?php

namespace ICT\Core\Application;

/* * ***************************************************************
 * Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

 use ICT\Core\Application;
 use ICT\Core\Result;
 use ICT\Core\Service\Fax;
 use ICT\Core\Spool;
 use ICT\Core\DB;
 use ICT\Core\Message\Document;
 use Dompdf\Dompdf;
 use ICT\Core\Token;
 use ICT\Core\Program;
 use ICT\Core\Session;
 use ICT\Core\Coverpage;
 use ICT\Core\Corelog;
 use ICT\Core\User;

class Fax_send extends Application
{

  /** @var string */
  public $name = 'fax_send';

  /**
   * @property-read string $type
   * @var string
   */
  protected $type = 'fax_send';

  /**
   * ************************************************ Application Parameters **
   */

  /**
   * file name of fax document
   * @var string $message
   */
  public $message = '[document:file_name]';

  /**
   * title for email document
   * @var string $header
   */
  public $header = '[document:name]';

    /**
   * id for fax document, needed for personalization
   * @var integer $document_id
   */
  public $document_id = '[document:document_id]';

  /**
   * message personalization
   * @var boolean $personalization_required
   */
  public $personalization_required = '[document:has_token]';

  /**
   * ******************************************** Default Application Values **
   */

  /**
   * All possible results to use
   * @var array
   */
  public static $supportedResult = array(
      'result' => array('success', 'error'),
      'pages' => 0,
      'error' => '' // empty message expected on success
  );

  /**
   * return a name value pair of all aditional application parameters which we need to save
   * @return array
   */

  public function parameter_save()
  {
    $aParameters = array(
        'message' => $this->message,
        'header' => $this->header,
        'document_id' => $this->document_id,
        'personalization_required' => $this->personalization_required
    );
    return $aParameters;
  }

  private function generate_tmp_filename()
  {
    global $path_cache;
    $oSession  = Session::get_instance();
    $transaction_id = $this->oTransmission->transmission_id;
    $application_id = $this->application_id;
    return $path_cache . DIRECTORY_SEPARATOR . "tid_$transaction_id.aid_$application_id.tif";
  }


  public function prepare()
  {
    // we are not processing a tiff file
    if ($this->personalization_required && !empty($this->document_id)) {
      $oDocument = new Document($this->document_id);
      $oSession  = Session::get_instance();

      // Prepare token object for current transmissions
      $oToken = new Token(Token::SOURCE_ALL);
      $oToken->add('program', $oSession->program);
      $oToken->add('transmission', $oSession->transmission);
      $oToken->add('account', $oSession->transmission->account);
      $oToken->add('contact', $oSession->transmission->contact);

      // Now replace all program related tokens in loaded template, but remember to keep missing tokens
      $oDocument->token_apply($oToken, Token::KEEP_ORIGNAL);
      // save tiff file to be used in execution
      $personalized_tiff = $this->generate_tmp_filename();
      rename($oDocument->file_name, $personalized_tiff);
    }
  }


  public function execute()
  {
      $oSession = Session::get_instance();
      $oSession->cover = new Coverpage($oSession->program->cover_id , 1);

      // AES-encrypted documents must be decrypted and converted to TIFF before fax send
      if (pathinfo($this->message, PATHINFO_EXTENSION) === 'aes') {
        global $path_cache;
        $tiff_file = tempnam($path_cache, 'fax_tiff_') . '.tif';
        $oDocument = new Document($this->document_id);
        $oDocument->create_tiff($this->message, 'aes', $tiff_file, $oDocument->quality);
        chmod($tiff_file, 0744);
        $this->message = $tiff_file;
      }

      // we are not processing a tiff file
      if ($this->personalization_required && !empty($this->document_id) && $oSession->transmission->personalize_fax) {
        $user_permission = User::fetch_permission($oSession->user->user_id);
        // Check if have Personalized_Fax permission
        if (strpos($user_permission, 'personalize_fax') == true) {
        $personalized_tiff = $this->generate_tmp_filename();
        $this->message = $personalized_tiff;  // get the personalized tiff file
      }}
      if($oSession->transmission->sendcover == 1 && $oSession->program->cover_id > 0 && $oSession->cover->coverpage_id > 0){
         global $path_cache;
         $coverpage_pdf = tempnam($path_cache, 'coverpage_') . '.pdf';
         $dompdf = new Dompdf();
         $cover_body = '[cover:description]';
         $resourceToken = new Token(Token::SOURCE_ALL, $oSession);
         $cover_body = $resourceToken->render_string($cover_body);
         $cover_body = $resourceToken->render_string($cover_body);

         $dompdf->loadHtml($cover_body);
         $dompdf->setPaper('A4', 'portrait'); // Setup the paper size and orientation
         $dompdf->render();                   // Render the HTML as PDF
         file_put_contents($coverpage_pdf, $dompdf->output());

          if (is_file($coverpage_pdf)) {
           // Conver cover page to tif
           $oDocument = new Document();
           $randomNumber = mt_rand(10000000, 99999999);
           $coverpage_tif = tempnam($path_cache, 'coverpage_') . $randomNumber . '.tif';
           $oDocument->create_tiff($coverpage_pdf, 'pdf', $coverpage_tif);
           $attachment = $this->message;
           // Read and write the cover page file and document file
           $cmd = \ICT\Core\sys_which('tiffcp', '/usr/bin') . " -x -a  '$this->message' '$coverpage_tif' ";
           exec($cmd);
           $this->message = $coverpage_tif;
          }
     }

    $oService = new Fax();
    $template_path = $oService->template_path('fax_send');
    $oService->application_execute($this, $template_path, 'template');
  }


  public function process()
  {
    if ($this->result['result'] == 'success') {
      // we delivered a fax, we need to save its pages
      $this->result_create($this->result['pages'], 'pages', Result::TYPE_INFO);
    } else {
      // fax delivery failed, we need to save the error message
      $this->result_create($this->result['error'], 'error', Result::TYPE_ERROR);
      $this->result['result'] = 'error';
    }

    return Spool::STATUS_CONNECTED;
    // return Spool::STATUS_DONE;
  }

}
