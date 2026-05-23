<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\CallQueue;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class CallQueueApi extends Api
{
  /**
   * List call queues (tenant-scoped)
   *
   * @url GET /call_queues
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('call_queues');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    
    $result = array_map(fn($r) => (array)$r, (array)CallQueue::search($filter));
    $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);
    if ($is_admin && !empty($result)) {
      $domain_uuids = array_column($result, 'domain_uuid');
      $tenant_map   = \ICT\Core\FpbxDomain::get_tenant_names_by_domain_uuids($domain_uuids);
      foreach ($result as &$row) {
        $row['tenant_name'] = $tenant_map[$row['domain_uuid'] ?? ''] ?? null;
      }
      unset($row);
    }
    return $result;
  }

  /**
   * Get a call queue by UUID
   *
   * @url GET /call_queues/$call_center_queue_uuid
   */
  public function read($call_center_queue_uuid)
  {
    $this->_authorize_pbx('call_queues');
    $oQueue = new CallQueue($call_center_queue_uuid);
    $this->_assert_pbx_domain($oQueue);
    return $oQueue;
  }

  /**
   * Create a new call queue
   *
   * @url POST /call_queues
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('call_queues', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::CALL_QUEUE)) {
      throw new CoreException(409, 'Call Queue quota limit reached for this tenant');
    }
    $oQueue = new CallQueue();
    $oQueue->tenant_id = $this->oUser->tenant_id;
    $this->set($oQueue, $data);
    $uuid = $oQueue->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::CALL_QUEUE);
      return $uuid;
    }
    throw new CoreException(417, 'Call Queue creation failed');
  }

  /**
   * Update an existing call queue
   *
   * @url PUT /call_queues/$call_center_queue_uuid
   */
  public function update($call_center_queue_uuid, $data = array())
  {
    $this->_authorize_pbx('call_queues', true);
    $oQueue = new CallQueue($call_center_queue_uuid);
    $this->_assert_pbx_domain($oQueue);
    $this->set($oQueue, $data);
    if ($oQueue->save()) {
      return $oQueue;
    }
    throw new CoreException(417, 'Call Queue update failed');
  }

  /**
   * Delete a call queue
   *
   * @url DELETE /call_queues/$call_center_queue_uuid
   */
  public function remove($call_center_queue_uuid)
  {
    $this->_authorize_pbx('call_queues', true);
    $oQueue = new CallQueue($call_center_queue_uuid);
    $this->_assert_pbx_domain($oQueue);
    $result = $oQueue->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::CALL_QUEUE);
    return $result;
  }
}
