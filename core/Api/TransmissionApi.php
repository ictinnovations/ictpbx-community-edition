<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Account;
use ICT\Core\Contact;
use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Program;
use ICT\Core\Result;
use ICT\Core\Schedule;
use ICT\Core\Service\Email;
use ICT\Core\Service\Fax;
use ICT\Core\Service\Sms;
use ICT\Core\Service\Voice;
use ICT\Core\Spool;
use ICT\Core\Contact_Dnc;
use ICT\Core\Transmission;
use ICT\Core\Message\Document;
use ZipArchive;
use SplFileInfo;

#[\AllowDynamicProperties]
class TransmissionApi extends Api
{

  /**
   * Create a new custom transmission
   *
   * @url POST /transmissions
   */
  public function create($data = array())
  {
    $this->_authorize('transmission_create');
    if (empty($data['program_id'])) {
      throw new CoreException(412, 'program_id is missing');
    }
    $oContact = new Contact($data['contact_id']);
    $phone = $oContact->phone;
    $oContact_dnc = new Contact_Dnc($query = array());
    $filter =  (array)$query;
    $listContact_dnc = Contact_Dnc::search($filter);
    foreach ($listContact_dnc as  $alistContact_dnc) {
      $list_dnc = $alistContact_dnc['phone'];
     if ($data['phone']){
    if($list_dnc === $data['phone']){
    $data['status'] = Transmission::FAILED_STATUS;
    }}
      else{
    if($list_dnc  === $phone) {
    $data['status'] = Transmission::FAILED_STATUS;
      }}
    }

    if (empty($data['contact_id'])) {
      if (!empty($data['phone']) || !empty($data['email'])) {
        $oContact = new Contact();
        $oContact->phone = empty($data['phone']) ? null : $data['phone'];
        $oContact->email = empty($data['email']) ? null : $data['email'];
        $oContact->save();

        $contact_id = $oContact->contact_id;
        unset($data['phone']);
        unset($data['email']);
      } else {
        throw new CoreException(412, 'contact is missing');
      }
    } else {
      $contact_id = $data['contact_id'];
    }
    unset($data['contact_id']);

    if (empty($data['account_id'])) {
      $oAccount = new Account(Account::USER_DEFAULT);
      $account_id = $oAccount->account_id;
    } else {
      $account_id = $data['account_id'];
    }
    unset($data['account_id']);

    $direction = empty($data['direction']) ? Transmission::OUTBOUND : $data['direction'];
    unset($data['direction']);

    $oProgram = Program::load($data['program_id']);
    $oTransmission = $oProgram->transmission_create($contact_id, $account_id, $direction);
    $this->set($oTransmission, $data);

    if ($oTransmission->save()) {
      return $oTransmission->transmission_id;
    } else {
      throw new CoreException(417, 'Transmission creation failed');
    }
  }

  /**
   * List all available transmissions
   *
   * @url GET /transmissions
   */
  public function list_view($query = array())
  {
    $this->_authorize('transmission_list');
    $filter  = (array)$query;
    $filter = array_merge($filter , $this->_authorization_filter());
    $listTransmission = Transmission::search($filter);
   if(is_array($listTransmission)){
   foreach ($listTransmission as $key => $aTransmission) {
      $listAccount = Account::search(array('account_id' => $aTransmission['account_id']));
      $listTransmission[$key]['account'] = array_shift($listAccount);
      $listContact = Contact::search(array('contact_id' => $aTransmission['contact_id']));
      $listTransmission[$key]['contact'] = array_shift($listContact);
    }
  }
    return $listTransmission;
  }

  /**
   * List all available calls
   *
   * @url GET /calls
   */
  public function call_list($data = array())
  {
    $data['service_flag'] = Voice::SERVICE_FLAG;
    return $this->list_view($data);
  }

  /**
   *
   * @url PUT /transmissions/retryinterval
  */
  public function retry_interval($data = array())
  {
    return Transmission::set_interval_fax($data);
  }

  /**
   *
   * @url GET /transmissions/getretryinterval
   */
  public function retry_get_interval()
  {
    return Transmission::get_interval_fax();
  }



  /**
   * List all available faxs
   *
   * @url GET /faxes
   */
  public function fax_list($data = array())
  {
    $data['service_flag'] = Fax::SERVICE_FLAG;
    return $this->list_view($data);
  }

  /**
   * List all available SMS messages
   *
   * @url GET /smses
   */
  public function sms_list($data = array())
  {
    $data['service_flag'] = Sms::SERVICE_FLAG;
    return $this->list_view($data);
  }


  /**
   * Download transmission data in fax document format.
   *
   * @url GET /bulk/filter
   */
  public function receive($query = array())
  {
    global $path_data;

    $Filter = (array) $query;
    $listTransmission = TransmissionApi::list_view($Filter);
    $faxReceiveDir = "$path_data/fax_receive";    // Create parrent directory for zip files
    if (!is_dir($faxReceiveDir)) {
      if (!mkdir($faxReceiveDir, 0777, true)) {
        throw new CoreException(500, 'Failed to create fax_receive directory.');
        }
      }
      $zipFileName = 'bulk_fax_receive' . time() . '.zip';
      $zipFilePath = $path_data . DIRECTORY_SEPARATOR . 'fax_receive' . DIRECTORY_SEPARATOR . $zipFileName;
      foreach ($listTransmission as $transmission) {
        $listdocument = $this->result_recent($transmission['transmission_id'], array('name' => 'document'));
        $oDocument = new Document($listdocument[0]['data']);
        $file_path = $oDocument->file_name;
        $zip = new ZipArchive(); // Create a new ZipArchive object for each file
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
          throw new CoreException(500, 'Failed to create ZIP archive.');
        }
        if (file_exists($file_path)) {
          $output_file = $oDocument->create_pdf($file_path, 'aes');
          $oTransmissionFileName = basename($output_file);
          $zip->addFile($output_file, $oTransmissionFileName);
          $zip->close();
        } else {
         // throw new CoreException(404, 'File not found: ' . $file_path);
        }
      }

      if (file_exists($zipFilePath)) {
        $oFile = new SplFileInfo($zipFilePath);
        return $oFile;
      } else {
        throw new CoreException(404, 'fax_receive download archive not found.');
      }
    }

  /**
   * List all available emails
   *
   * @url GET /emails
   */
  public function email_list($data = array())
  {
    $data['service_flag'] = Email::SERVICE_FLAG;
    return $this->list_view($data);
  }

  /**
   * Gets the transmission by id
   *
   * @url GET /transmissions/$transmission_id
   */
  public function read($transmission_id)
  {
    $this->_authorize('transmission_read');

    $oTransmission = new Transmission($transmission_id);
    if($this->_authorization_filter($oTransmission)){
      return $oTransmission;
    }
  }

  /**
   * Update existing transmission
   *
   * @url PUT /transmissions/$transmission_id
   */
   public function update($transmission_id, $data = array())
   {
    $this->_authorize('transmission_update');

    $oTransmission = new Transmission($transmission_id);
    $this->set($oTransmission, $data);
   if($this->_authorization_filter($oTransmission)){
    if ($oTransmission->save()) {
      return $oTransmission;
    } else {
      throw new CoreException(417, 'Transmission update failed');
    }
   }
  }

  /**
   * Delete a transmission
   *
   * @url DELETE /transmissions/$transmission_id
   */
  public function remove($transmission_id)
  {
    $this->_authorize('transmission_delete');

    $oTransmission = new Transmission($transmission_id);
    if ($this->_authorization_filter($oTransmission)) {
      $result = $oTransmission->delete($transmission_id);
      if ($result) {
        return $result;
      } else {
        throw new CoreException(417, 'Transmission delete failed');
      }
    }
  }

  /**
   * Send already saved transmission
   *
   * @url POST /transmissions/$transmission_id/send
   * @url POST /transmissions/$transmission_id/retry
   *
   * TODO / TBD: Replace it with POST or PUT as GET is not allowed to modify data
   */
  public function send($transmission_id)
  {
    $this->_authorize('transmission_send');

    $oTransmission = new Transmission($transmission_id);
    return $oTransmission->send();
  }

  /**
   * Schedule transmission
   *
   * @url PUT /transmissions/$transmission_id/schedule
   */
  public function schedule_create($transmission_id, $data = array())
  {
    $this->_authorize('transmission_send');
    $this->_authorize('task_create');

    $oSchedule = new Schedule();
    $this->set($oSchedule, $data);

    $oTransmission = new Transmission($transmission_id);
    $oSchedule->type = 'transmission';
    $oSchedule->action = 'send';
    $oSchedule->data = $oTransmission->transmission_id;
    $oSchedule->account_id = $oTransmission->account_id;
    $oSchedule->save();

    return $oSchedule->task_id;
  }

  /**
   * Cancel transmission schedule
   *
   * @url DELETE /transmissions/$transmission_id/schedule
   */
  public function schedule_cancel($transmission_id)
  {
    $this->_authorize('transmission_update');
    $this->_authorize('task_delete');

    $oTransmission = new Transmission($transmission_id);
    return $oTransmission->task_cancel();
  }

  /**
   * Create new transmission by cloning existing one
   *
   * @url GET /transmissions/$transmission_id/clone
   *
   * TBD / TODO: replace GET method with POST, as it voilate REST GET no data change rule
   */
  public function create_clone($transmission_id)
  {
    $this->_authorize('transmission_read');
    $this->_authorize('transmission_create');

    $oldTransmission = new Transmission($transmission_id);
    $newTransmission = clone $oldTransmission;
    if ($newTransmission->save()) {
      return $newTransmission->transmission_id;
    } else {
      throw new CoreException(417, 'Transmission cloning failed');
    }
  }

  /**
   * Get transmission status
   *
   * @url GET /transmissions/$transmission_id/status
   */
  public function status($transmission_id)
  {
    $this->_authorize('transmission_read');

    $oTransmission = new Transmission($transmission_id);
    return $oTransmission->status;
  }

  /**
   * Get transmission results
   *
   * @url GET /transmissions/$transmission_id/logs
   * @url GET /transmissions/$transmission_id/detail
   * @url GET /transmissions/$transmission_id/spools
   */
  public function detail($transmission_id, $query = array())
  {
    $this->_authorize('transmission_read');
    $this->_authorize('spool_read');

    $filter  = (array)$query;
    $filter += array('transmission_id' => $transmission_id);
    return Spool::search($filter);
  }

  /**
   * Get transmission details
   *
   * @url GET /transmissions/$transmission_id/result
   * @url GET /transmissions/$transmission_id/results
   */
  public function result_recent($transmission_id, $query = array())
  {
    $this->_authorize('result_read');

    $listSpool = $this->detail($transmission_id);
    $aSpool    = end($listSpool);

    $filter  = (array)$query;
    $filter += array('spool_id' => $aSpool['spool_id']);

    return Result::search($filter);
  }

}
