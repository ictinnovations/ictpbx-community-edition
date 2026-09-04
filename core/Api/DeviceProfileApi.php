<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\DeviceProfile;

#[\AllowDynamicProperties]
class DeviceProfileApi extends Api
{
  /**
   * @url GET /device_profiles
   */
  public function list_view($query = array())
  {
    $this->_authorize('user_admin');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    return DeviceProfile::search($filter);
  }

  /**
   * @url GET /device_profiles/$device_profile_uuid
   */
  public function read($device_profile_uuid)
  {
    $this->_authorize('user_admin');
    return new DeviceProfile($device_profile_uuid);
  }

  /**
   * @url POST /device_profiles
   */
  public function create($data = array())
  {
    $this->_authorize('user_admin');
    $oProfile = new DeviceProfile();
    $oProfile->tenant_id = $this->oUser->tenant_id;
    $this->set($oProfile, $data);
    $uuid = $oProfile->save();
    if ($uuid) return $uuid;
    throw new CoreException(417, 'Device Profile creation failed');
  }

  /**
   * @url PUT /device_profiles/$device_profile_uuid
   */
  public function update($device_profile_uuid, $data = array())
  {
    $this->_authorize('user_admin');
    $oProfile = new DeviceProfile($device_profile_uuid);
    $this->set($oProfile, $data);
    if ($oProfile->save()) return $oProfile;
    throw new CoreException(417, 'Device Profile update failed');
  }

  /**
   * @url DELETE /device_profiles/$device_profile_uuid
   */
  public function remove($device_profile_uuid)
  {
    $this->_authorize('user_admin');
    $oProfile = new DeviceProfile($device_profile_uuid);
    return $oProfile->delete();
  }
}
