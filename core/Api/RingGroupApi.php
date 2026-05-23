<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\RingGroup;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class RingGroupApi extends Api
{
  /**
   * List ring groups (tenant-scoped)
   *
   * @url GET /ring_groups
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('ring_groups');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    
    $result = array_map(fn($r) => (array)$r, (array)RingGroup::search($filter));
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
   * Get a ring group by UUID
   *
   * @url GET /ring_groups/$ring_group_uuid
   */
  public function read($ring_group_uuid)
  {
    $this->_authorize_pbx('ring_groups');
    $oRingGroup = new RingGroup($ring_group_uuid);
    $this->_assert_pbx_domain($oRingGroup);
    return $oRingGroup;
  }

  /**
   * Create a new ring group
   *
   * @url POST /ring_groups
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('ring_groups', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::RING_GROUP)) {
      throw new CoreException(409, 'Ring Group quota limit reached for this tenant');
    }
    $oRingGroup = new RingGroup();
    $oRingGroup->tenant_id = $this->oUser->tenant_id;
    $this->set($oRingGroup, $data);
    $uuid = $oRingGroup->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::RING_GROUP);
      return $uuid;
    }
    throw new CoreException(417, 'Ring Group creation failed');
  }

  /**
   * Update an existing ring group
   *
   * @url PUT /ring_groups/$ring_group_uuid
   */
  public function update($ring_group_uuid, $data = array())
  {
    $this->_authorize_pbx('ring_groups', true);
    $oRingGroup = new RingGroup($ring_group_uuid);
    $this->_assert_pbx_domain($oRingGroup);
    $this->set($oRingGroup, $data);
    if ($oRingGroup->save()) {
      return $oRingGroup;
    }
    throw new CoreException(417, 'Ring Group update failed');
  }

  /**
   * Delete a ring group
   *
   * @url DELETE /ring_groups/$ring_group_uuid
   */
  public function remove($ring_group_uuid)
  {
    $this->_authorize_pbx('ring_groups', true);
    $oRingGroup = new RingGroup($ring_group_uuid);
    $this->_assert_pbx_domain($oRingGroup);
    $result = $oRingGroup->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::RING_GROUP);
    return $result;
  }
}
