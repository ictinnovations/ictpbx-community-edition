<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\ConferenceCenter;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class ConferenceCenterApi extends Api
{
  /** @url GET /conferences */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('conferences');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    
    $result = array_map(fn($r) => (array)$r, (array)ConferenceCenter::search($filter));
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

  /** @url GET /conferences/$conference_center_uuid */
  public function read($conference_center_uuid)
  {
    $this->_authorize_pbx('conferences');
    $o = new ConferenceCenter($conference_center_uuid);
    $this->_assert_pbx_domain($o);
    return $o;
  }

  /** @url POST /conferences */
  public function create($data = array())
  {
    $this->_authorize_pbx('conferences', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::CONFERENCE)) {
      throw new CoreException(409, 'Conference quota limit reached for this tenant');
    }
    $o = new ConferenceCenter();
    $o->tenant_id = $this->oUser->tenant_id;
    $this->set($o, $data);
    $uuid = $o->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::CONFERENCE);
      return $uuid;
    }
    throw new CoreException(417, 'Conference Center creation failed');
  }

  /** @url PUT /conferences/$conference_center_uuid */
  public function update($conference_center_uuid, $data = array())
  {
    $this->_authorize_pbx('conferences', true);
    $o = new ConferenceCenter($conference_center_uuid);
    $this->_assert_pbx_domain($o);
    $this->set($o, $data);
    if ($o->save()) { return $o; }
    throw new CoreException(417, 'Conference Center update failed');
  }

  /** @url DELETE /conferences/$conference_center_uuid */
  public function remove($conference_center_uuid)
  {
    $this->_authorize_pbx('conferences', true);
    $o = new ConferenceCenter($conference_center_uuid);
    $this->_assert_pbx_domain($o);
    $result = $o->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::CONFERENCE);
    return $result;
  }
}
