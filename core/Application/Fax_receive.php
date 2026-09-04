<?php

namespace ICT\Core\Application;

/* * ***************************************************************
 * Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Application;
use ICT\Core\Message\Document;
use ICT\Core\Result;
use ICT\Core\Service\Fax;
use ICT\Core\Spool;

class Fax_receive extends Application
{

  /** @var string */
  public $name = 'fax_receive';

  /**
   * @property-read string $type
   * @var string
   */
  protected $type = 'fax_receive';

  /**
   * ************************************************ Application Parameters **
   */

  /**
   * a none existant file name to save new fax document
   * @var string $fax_file
   */
  public $fax_file = '/tmp/new_fax.tiff';

  /**
   * ******************************************** Default Application Values **
   */

  /**
   * default condition
   * @var array 
   */
  public static $defaultCondition = array('result' => 'success');

  /**
   * All possible results to use 
   * @var array 
   */
  public static $supportedResult = array(
      'result' => array('success', 'error'),
      'fax_file' => '/path/to/file',
      'pages' => 0,
      'error' => '' // empty message expected on success
  );

  public function __construct($application_id = null, $aParameter = null)
  {
    parent::__construct($application_id, $aParameter);
    $this->fax_file = tempnam(sys_get_temp_dir(), 'fax_') . '.tif';
  }

  public function execute()
  {
    $oService = new Fax();
    $template_path = $oService->template_path('fax_receive');
    $oService->application_execute($this, $template_path, 'template');
  }

  public function process()
  {
    // if we really have received a Fax
    if (isset($this->result['fax_file']) && file_exists($this->result['fax_file'])) {
      // we received a fax file, we need to save it
      $file_name = 'fax_' . $this->application_id . '_' . $this->oTransmission->oSpool->spool_id;
      $oDocument = new Document();
      $oDocument->name = $file_name;
      $oDocument->description = 'file received while processing transmission: ' . $this->oTransmission->transmission_id;
      $oDocument->file_name = $this->result['fax_file'];
      $oDocument->save();

      // Save result — use page count from FreeSWITCH (Document->pages is read_only)
      $pages = !empty($this->result['pages']) ? (int)$this->result['pages'] : 0;
      $this->result_create($oDocument->document_id, 'document', Result::TYPE_MESSAGE);
      $this->result_create($pages, 'pages', Result::TYPE_INFO);
      $this->result['result'] = 'success';
    } else {
      // if no valid file found then change result to with error
      $this->result['result'] = 'error';
      $this->result_create('invalid fax', 'error', Result::TYPE_ERROR);
    }

    return Spool::STATUS_CONNECTED;
    // return Spool::STATUS_COMPLETED;
  }

}
