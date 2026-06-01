<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\Destination;
use ICT\Core\CoreException;

class DestinationApi extends Api
{

  /**
   * Create a new destination
   *
   * @url POST /destination
   */
  public function create($data = array())
  {
    $this->_authorize('destination_create');

    $oDestination = new Destination();
    $this->set($oDestination, $data);

    if ($oDestination->save()) {
      return $oDestination->destination_id;
    } else {
      throw new CoreException(417, 'Destination creation failed');
    }
  }

  /**
   * List all available destinations
   *
   * @url GET /destination
   */
  public function list_view($query = array())
  {
    $this->_authorize('destination_list');
    $filter  = (array)$query;
    $filter += $this->_authorization_filter();
    return Destination::search($filter);
  }

  /**
   * Gets the destination by id
   *
   * @url GET /destination/$destination_id
   */
  public function read($destination_id)
  {
    $this->_authorize('destination_read');

    $oDestination = new Destination($destination_id);
    return $oDestination;
  }

  /**
   * Update existing destination
   *
   * @url PUT /destination/$destination_id
   */
  public function update($destination_id, $data = array())
  {
    $this->_authorize('destination_update');

    $oDestination = new Destination($destination_id);
    $this->set($oDestination, $data);

    if ($oDestination->save()) {
      return $oDestination;
    } else {
      throw new CoreException(417, 'Destination update failed');
    }
  }

  /**
   * Delete a Destination
   *
   * @url DELETE /destination/$destination_id
   */
  public function remove($destination_id)
  {
    $this->_authorize('destination_delete');

    $oDestination = new Destination($destination_id);

    $result = $oDestination->delete();
    if ($result) {
      return $result;
    } else {
      throw new CoreException(417, 'Destination delete failed');
    }
  }

}
