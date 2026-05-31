<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\MusicOnHold;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class MusicOnHoldApi extends Api
{
  /** @url GET /music_on_hold */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('music_on_hold');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    
    $result = array_map(fn($r) => (array)$r, (array)MusicOnHold::search($filter));
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

  /** @url GET /music_on_hold/$music_on_hold_uuid */
  public function read($music_on_hold_uuid)
  {
    $this->_authorize_pbx('music_on_hold');
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    return $o;
  }

  /** @url POST /music_on_hold */
  public function create($data = array())
  {
    $this->_authorize_pbx('music_on_hold');
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::MUSIC_ON_HOLD)) {
      throw new CoreException(409, 'Music on Hold quota limit reached for this tenant');
    }
    $o = new MusicOnHold();
    $o->tenant_id = $this->oUser->tenant_id;
    $this->set($o, $data);
    $uuid = $o->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::MUSIC_ON_HOLD);
      return $uuid;
    }
    throw new CoreException(417, 'Music on Hold creation failed');
  }

  /** @url PUT /music_on_hold/$music_on_hold_uuid */
  public function update($music_on_hold_uuid, $data = array())
  {
    $this->_authorize_pbx('music_on_hold');
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    $this->set($o, $data);
    if ($o->save()) { return $o; }
    throw new CoreException(417, 'Music on Hold update failed');
  }

  /** @url DELETE /music_on_hold/$music_on_hold_uuid */
  public function remove($music_on_hold_uuid)
  {
    $this->_authorize_pbx('music_on_hold');
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    $result = $o->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::MUSIC_ON_HOLD);
    return $result;
  }
}
