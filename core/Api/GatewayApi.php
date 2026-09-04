<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Gateway;

#[\AllowDynamicProperties]
class GatewayApi extends Api
{
  /**
   * @url GET /gateways
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('gateways');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    return Gateway::search($filter);
  }

  /**
   * @url GET /gateways/$gateway_uuid
   */
  public function read($gateway_uuid)
  {
    $this->_authorize_pbx('gateways');
    $oGw = new Gateway($gateway_uuid);
    $this->_assert_pbx_domain($oGw);
    return $oGw;
  }

  /**
   * @url POST /gateways
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('gateways');
    $oGw = new Gateway();
    $oGw->tenant_id = $this->oUser->tenant_id;
    $this->set($oGw, $data);
    $uuid = $oGw->save();
    if ($uuid) return $uuid;
    throw new CoreException(417, 'Gateway creation failed');
  }

  /**
   * @url PUT /gateways/$gateway_uuid
   */
  public function update($gateway_uuid, $data = array())
  {
    $this->_authorize_pbx('gateways');
    $oGw = new Gateway($gateway_uuid);
    $this->_assert_pbx_domain($oGw);
    $this->set($oGw, $data);
    if ($oGw->save()) return $oGw;
    throw new CoreException(417, 'Gateway update failed');
  }

  /**
   * @url DELETE /gateways/$gateway_uuid
   */
  public function remove($gateway_uuid)
  {
    $this->_authorize_pbx('gateways');
    $oGw = new Gateway($gateway_uuid);
    $this->_assert_pbx_domain($oGw);
    return $oGw->delete();
  }
}
