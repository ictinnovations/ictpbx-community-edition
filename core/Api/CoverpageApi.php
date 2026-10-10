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
use ICT\Core\Corelog;
use ICT\Core\Activity;
use ICT\Core\Coverpage;
use SplFileInfo;

#[\AllowDynamicProperties]
class CoverpageApi extends Api
{

  /**
   * Create a new coverpage
   *
   * @url POST /coverpage
   */
  public function create($data = array())
  {
    $oCover = new Coverpage();
    $this->set($oCover, $data);
    if ($oCover->save()) {
      return $oCover;
    } else {
      throw new CoreException(417, 'Coverpage Creation failed');
    }
  }

   /**
   * Update existing coverpage
   *
   * @url PUT /coverpage/$coverpageid
   */
  public function update($coverpageid, $data = array())
  {

    $oCoverpage = new Coverpage($coverpageid);
    $this->set($oCoverpage, $data);
   
    if($this->_authorization_filter($oCoverpage)){
    if ($oCoverpage->save()) {
      return $oCoverpage->save();
    } else {
      throw new CoreException(417, 'Coverpage update failed');
    }
  }
  }

  /**
   * GET list of all coverpages
   *
   * @url GET /coverpages

   */
  public function list_view($query = array() )
  {
  
    $filter  = (array)$query;
    $filter = array_merge($this->_authorization_filter() , $filter);
    return Coverpage::search($filter);
  }

   /**
   * Gets the coverpage by id
   *
   * @url GET /coverpage/$coverpage_id
   */
  public function read($coverpage_id)
  {
    $this->_authorize('contact_read');

    $oCoverpage = new CoverPage($coverpage_id);
    if($this->_authorization_filter($oCoverpage)){
      return $oCoverpage;
    }
  }

    /**
   * Delete Coverpage
   *
   * @url DELETE /coverpage/$coverpage_id
   */
  public function remove($coverpage_id)
  {
    $this->_authorize('contact_delete');

    $oCoverpage = new Coverpage($coverpage_id);
    if($this->_authorization_filter($oCoverpage)){
    $result = $oCoverpage->delete();
    if ($result) {
      return $result;
    } else {
      throw new CoreException(417, 'Coverpage delete failed');
    }
  }
  }
}
?>
