<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\TimeCondition;

#[\AllowDynamicProperties]
class TimeConditionApi extends Api
{
  /** @url GET /time_conditions */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('time_conditions');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    return TimeCondition::search($filter);
  }

  /** @url GET /time_conditions/$dialplan_uuid */
  public function read($dialplan_uuid)
  {
    $this->_authorize_pbx('time_conditions');
    $o = new TimeCondition($dialplan_uuid);
    $this->_assert_pbx_domain($o);
    return $o;
  }

  /** @url POST /time_conditions */
  public function create($data = array())
  {
    $this->_authorize_pbx('time_conditions');
    $o = new TimeCondition();
    $o->tenant_id = $this->oUser->tenant_id;
    $this->set($o, $data);
    $uuid = $o->save();
    if ($uuid) { return $uuid; }
    throw new CoreException(417, 'Time Condition creation failed');
  }

  /** @url PUT /time_conditions/$dialplan_uuid */
  public function update($dialplan_uuid, $data = array())
  {
    $this->_authorize_pbx('time_conditions');
    $o = new TimeCondition($dialplan_uuid);
    $this->_assert_pbx_domain($o);
    $this->set($o, $data);
    if ($o->save()) { return $o; }
    throw new CoreException(417, 'Time Condition update failed');
  }

  /** @url DELETE /time_conditions/$dialplan_uuid */
  public function remove($dialplan_uuid)
  {
    $this->_authorize_pbx('time_conditions');
    $o = new TimeCondition($dialplan_uuid);
    $this->_assert_pbx_domain($o);
    return $o->delete();
  }
}
