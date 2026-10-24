<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Realtime;

#[\AllowDynamicProperties]
class RealtimeApi extends Api
{
  /**
   * Get live FreeSWITCH channels and registrations
   *
   * @url GET /realtime
   */
  public function snapshot()
  {
    $this->_authorize_pbx('realtime');
    return Realtime::get_snapshot();
  }

  /**
   * Hangup, hold, or transfer a live channel
   *
   * @url POST /realtime/control
   */
  public function control($data = [])
  {
    $this->_authorize_pbx('realtime', true);
    $action = $data['action'] ?? '';
    $uuid   = trim($data['channel_uuid'] ?? '');
    if (empty($uuid)) throw new CoreException(400, 'channel_uuid required');

    switch ($action) {
      case 'hangup':
        Realtime::hangup($uuid);
        break;
      case 'hold':
        Realtime::hold($uuid);
        break;
      case 'transfer':
        $dest = trim($data['destination'] ?? '');
        if (empty($dest)) throw new CoreException(400, 'destination required');
        Realtime::transfer($uuid, $dest);
        break;
      default:
        throw new CoreException(400, 'Invalid action: ' . $action);
    }
    return ['status' => 'ok'];
  }
}
