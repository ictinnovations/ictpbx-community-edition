<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\Country;
use ICT\Core\CoreException;

class CountryApi extends Api
{

  /**
   * Create a new country
   *
   * @url POST /country
   */
  public function create($data = array())
  {
    $this->_authorize('country_create');

    $oCountry = new Country();
    $this->set($oCountry, $data);

    if ($oCountry->save()) {
      return $oCountry->country_id;
    } else {
      throw new CoreException(417, 'Country creation failed');
    }
  }

  /**
   * List all available Countries
   *
   * @url GET /country
   */
  public function list_view($query = array())
  {
    $this->_authorize('country_list');
    $filter  = (array)$query;
    $filter += $this->_authorization_filter();
    return Country::search($filter);
  }

  /**
   * Gets the country by id
   *
   * @url GET /country/$country_id
   */
  public function read($country_id)
  {
    $this->_authorize('country_read');

    $oCountry = new Country($country_id);
    return $oCountry;
  }

  /**
   * Update existing country
   *
   * @url PUT /country/$country_id
   */
  public function update($country_id, $data = array())
  {
    $this->_authorize('country_update');

    $oCountry = new Country($country_id);
    $this->set($oCountry, $data);

    if ($oCountry->save()) {
      return $oCountry;
    } else {
      throw new CoreException(417, 'Country update failed');
    }
  }

  /**
   * Delete a country
   *
   * @url DELETE /country/$country_id
   */
  public function remove($country_id)
  {
    $this->_authorize('country_delete');

    $oCountry = new Country($country_id);

    $result = $oCountry->delete();
    if ($result) {
      return $result;
    } else {
      throw new CoreException(417, 'Country delete failed');
    }
  }

}
