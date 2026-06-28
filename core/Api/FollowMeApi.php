<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\FollowMe;

#[\AllowDynamicProperties]
class FollowMeApi extends Api
{
  /** @url GET /follow_me */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('follow_me');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    return FollowMe::search($filter);
  }

  /** @url GET /follow_me/$follow_me_uuid */
  public function read($follow_me_uuid)
  {
    $this->_authorize_pbx('follow_me');
    $o = new FollowMe($follow_me_uuid);
    $this->_assert_pbx_domain($o);
    return $o;
  }

  /** @url POST /follow_me */
  public function create($data = array())
  {
    $this->_authorize_pbx('follow_me');
    $o = new FollowMe();
    $o->tenant_id = $this->oUser->tenant_id;
    $this->set($o, $data);
    if (isset($data['destinations'])) { $o->destinations = $data['destinations']; }
    $uuid = $o->save();
    if ($uuid) { return $uuid; }
    throw new CoreException(417, 'Follow Me creation failed');
  }

  /** @url PUT /follow_me/$follow_me_uuid */
  public function update($follow_me_uuid, $data = array())
  {
    $this->_authorize_pbx('follow_me');
    $o = new FollowMe($follow_me_uuid);
    $this->_assert_pbx_domain($o);
    $this->set($o, $data);
    if (isset($data['destinations'])) { $o->destinations = $data['destinations']; }
    if ($o->save()) { return $o; }
    throw new CoreException(417, 'Follow Me update failed');
  }

  /** @url DELETE /follow_me/$follow_me_uuid */
  public function remove($follow_me_uuid)
  {
    $this->_authorize_pbx('follow_me');
    $o = new FollowMe($follow_me_uuid);
    $this->_assert_pbx_domain($o);
    return $o->delete();
  }
}
