<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\DeviceLine;

#[\AllowDynamicProperties]
class DeviceLineApi extends Api
{
  /**
   * @url GET /device_lines
   */
  public function list_view($query = array())
  {
    $this->_authorize('user_admin');
    return DeviceLine::search((array)$query);
  }

  /**
   * @url GET /device_lines/$device_line_uuid
   */
  public function read($device_line_uuid)
  {
    $this->_authorize('user_admin');
    return new DeviceLine($device_line_uuid);
  }

  /**
   * @url POST /device_lines
   */
  public function create($data = array())
  {
    $this->_authorize('user_admin');
    $oLine = new DeviceLine();
    $this->set($oLine, $data);
    $uuid = $oLine->save();
    if ($uuid) return $uuid;
    throw new CoreException(417, 'Device line creation failed');
  }

  /**
   * @url PUT /device_lines/$device_line_uuid
   */
  public function update($device_line_uuid, $data = array())
  {
    $this->_authorize('user_admin');
    $oLine = new DeviceLine($device_line_uuid);
    $this->set($oLine, $data);
    if ($oLine->save()) return $oLine;
    throw new CoreException(417, 'Device line update failed');
  }

  /**
   * @url DELETE /device_lines/$device_line_uuid
   */
  public function remove($device_line_uuid)
  {
    $this->_authorize('user_admin');
    $oLine = new DeviceLine($device_line_uuid);
    return $oLine->delete();
  }
}
