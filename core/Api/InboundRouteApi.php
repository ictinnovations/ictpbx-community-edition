<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\InboundRoute;

#[\AllowDynamicProperties]
class InboundRouteApi extends Api
{
  /**
   * @url GET /inbound_routes
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('inbound_routes');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    return InboundRoute::search($filter);
  }

  /**
   * @url GET /inbound_routes/$destination_uuid
   */
  public function read($destination_uuid)
  {
    $this->_authorize_pbx('inbound_routes');
    $oRoute = new InboundRoute($destination_uuid);
    $this->_assert_pbx_domain($oRoute);
    return $oRoute;
  }

  /**
   * @url POST /inbound_routes
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('inbound_routes');
    $oRoute = new InboundRoute();
    $oRoute->tenant_id = $this->oUser->tenant_id;
    $this->set($oRoute, $data);
    $uuid = $oRoute->save();
    if ($uuid) return $uuid;
    throw new CoreException(417, 'Inbound Route creation failed');
  }

  /**
   * @url PUT /inbound_routes/$destination_uuid
   */
  public function update($destination_uuid, $data = array())
  {
    $this->_authorize_pbx('inbound_routes');
    $oRoute = new InboundRoute($destination_uuid);
    $this->_assert_pbx_domain($oRoute);
    $this->set($oRoute, $data);
    if ($oRoute->save()) return $oRoute;
    throw new CoreException(417, 'Inbound Route update failed');
  }

  /**
   * @url DELETE /inbound_routes/$destination_uuid
   */
  public function remove($destination_uuid)
  {
    $this->_authorize_pbx('inbound_routes');
    $oRoute = new InboundRoute($destination_uuid);
    $this->_assert_pbx_domain($oRoute);
    return $oRoute->delete();
  }
}
