<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Session;
use ICT\Core\Tenant;
use ICT\Core\User;
use ICT\Core\Program;
use ICT\Core\Transmission;

#[\AllowDynamicProperties]
class ProgramApi extends Api
{

  /**
   * Create a new program
   *
   * @url POST /programs
   * @url POST /programs/$program_name
   */
  public function create($program_name = null, $data = array())
  {
    $this->_authorize('program_create');
    
// Check Quota
    $oSession = Session::get_instance();
    $oTenant = new Tenant($oSession->user->tenant_id);
    $oUser = new User($oSession->user->user_id);
    
    if ( $oUser->check_quota($data) && $oUser->cehck_allowed_timeslot($oUser)) {
        $oProgram = Program::load($program_name);
        $this->set($oProgram, $data);
        
        if ($oProgram->save()) {
            $oProgram->deploy();
            return $oProgram->program_id;
        } else {
            throw new CoreException(417, 'Program creation failed');
        }
         }
  }

  /**
   * Initiate a new transmission for given program
   *
   * @url POST /programs/$program_name/transmissions
   */
  public function transmission($program_name, $data = array())
  {
    $this->_authorize('program_create');
    $this->_authorize('transmission_create');

    $aProgram = empty($data['program_data']) ? array() : $data['program_data'];
    $aTransmission = empty($data['transmission_data']) ? array() : $data['transmission_data'];
    if (empty($aTransmission)) {
      throw new CoreException(412, 'Transmission data is missing');
    }

    $oProgram = Program::load($program_name);
    $oTransmission = $oProgram->transmission_instant($aProgram, $aTransmission);

    if ($oTransmission->save()) {
      return $oTransmission->transmission_id;
    } else {
      throw new CoreException(417, 'Transmission creation failed');
    }
  }

  /**
   * List transmissions after filtering by given program id
   *
   * @url GET /programs/$program_id/transmissions
   */
  public function list_transmission($program_id)
  {
    return Transmission::search(array('program_id' => $program_id));
  }

  /**
   * List all available programs
   *
   * @url GET /programs
   * @url GET /programs/$program_name
   * 
   * @toto: can't use multiple functions due to similar url pattern
   * Gets the program by id (will redirect to read function)
   * 
   * @url GET /programs/$program_id
   */
  public function list_view($query = array(), $program_name = null)
  {
    if (ctype_digit($program_name)) {
      return $this->read($program_name);
    }

    $this->_authorize('program_list');
    $filter  = (array)$query;
    if ($program_name) {
      $filter['type'] = $program_name; // add program_name i.e class name as filter
    }
    $filter += $this->_authorization_filter();
    return Program::search($filter);
  }

  public function read($program_id)
  {
    $this->_authorize('program_read');

    $oProgram = Program::load($program_id);
    if($this->_authorization_filter($oProgram)){
    return $oProgram;
  }
  }

  /**
   * Update existing program
   *
   * @url PUT /programs/$program_id
   */
  public function update($program_id, $data = array())
  {
    $this->_authorize('program_update');

    $oProgram = Program::load($program_id);
    $this->set($oProgram, $data);
   if($this->_authorization_filter($oProgram)){
    if ($oProgram->save()) {
      $oProgram->deploy();
      return $oProgram;
    } else {
      throw new CoreException(417, 'Program update failed');
    }
  }
  }

  /**
   * Remove a program
   *
   * @url DELETE /programs/$program_id
   */
  public function remove($program_id)
  {
    $this->_authorize('program_delete');

    $oProgram = Program::load($program_id);
   if($this->_authorization_filter($oProgram)){
    $result = $oProgram->delete();
    if ($result) {
      return $result;
    } else {
      throw new CoreException(417, 'Program delete failed');
    }
  }
}

}
