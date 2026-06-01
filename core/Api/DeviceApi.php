<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Device;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class DeviceApi extends Api
{
  /**
   * @url GET /devices
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('devices');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    return Device::search($filter);
  }

  /**
   * @url GET /devices/$device_uuid
   */
  public function read($device_uuid)
  {
    $this->_authorize_pbx('devices');
    $oDevice = new Device($device_uuid);
    $this->_assert_pbx_domain($oDevice);
    return $oDevice;
  }

  /**
   * @url POST /devices
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('devices', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::DEVICES)) {
      throw new CoreException(409, 'Device quota limit reached for this tenant');
    }
    $oDevice = new Device();
    $oDevice->tenant_id = $this->oUser->tenant_id;
    $this->set($oDevice, $data);
    $uuid = $oDevice->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::DEVICES);
      return $uuid;
    }
    throw new CoreException(417, 'Device creation failed');
  }

  /**
   * @url PUT /devices/$device_uuid
   */
  public function update($device_uuid, $data = array())
  {
    $this->_authorize_pbx('devices', true);
    $oDevice = new Device($device_uuid);
    $this->_assert_pbx_domain($oDevice);
    $this->set($oDevice, $data);
    if ($oDevice->save()) return $oDevice;
    throw new CoreException(417, 'Device update failed');
  }

  /**
   * @url DELETE /devices/$device_uuid
   */
  public function remove($device_uuid)
  {
    $this->_authorize_pbx('devices', true);
    $oDevice = new Device($device_uuid);
    $this->_assert_pbx_domain($oDevice);
    $result = $oDevice->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::DEVICES);
    return $result;
  }
}
