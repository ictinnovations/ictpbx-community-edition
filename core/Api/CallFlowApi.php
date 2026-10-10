<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\CallFlow;

#[\AllowDynamicProperties]
class CallFlowApi extends Api
{
  /** @url GET /call_flows */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('call_flows');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    
    $result = array_map(fn($r) => (array)$r, (array)CallFlow::search($filter));
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

  /** @url GET /call_flows/$call_flow_uuid */
  public function read($call_flow_uuid)
  {
    $this->_authorize_pbx('call_flows');
    $o = new CallFlow($call_flow_uuid);
    $this->_assert_pbx_domain($o);
    return $o;
  }

  /** @url POST /call_flows */
  public function create($data = array())
  {
    $this->_authorize_pbx('call_flows');
    $o = new CallFlow();
    $o->tenant_id = $this->oUser->tenant_id;
    $this->set($o, $data);
    $uuid = $o->save();
    if ($uuid) { return $uuid; }
    throw new CoreException(417, 'Call Flow creation failed');
  }

  /** @url PUT /call_flows/$call_flow_uuid */
  public function update($call_flow_uuid, $data = array())
  {
    $this->_authorize_pbx('call_flows');
    $o = new CallFlow($call_flow_uuid);
    $this->_assert_pbx_domain($o);
    $this->set($o, $data);
    if ($o->save()) { return $o; }
    throw new CoreException(417, 'Call Flow update failed');
  }

  /**
   * Flip the flow between Open (Day) and Closed (Night). Called by the feature-code
   * script (call_flow_toggle.lua) and usable from the API. Body: {"pin": "1234"} when the
   * flow has a PIN.
   *
   * @url POST /call_flows/$call_flow_uuid/toggle
   */
  public function toggle($call_flow_uuid, $data = array())
  {
    $this->_authorize_pbx('call_flows', true);
    $o = new CallFlow($call_flow_uuid);
    $this->_assert_pbx_domain($o);
    $data = (array)$data;
    $status = $o->toggle(isset($data['pin']) ? $data['pin'] : null);
    return array('call_flow_uuid' => $call_flow_uuid, 'call_flow_status' => $status);
  }

  /** @url DELETE /call_flows/$call_flow_uuid */
  public function remove($call_flow_uuid)
  {
    $this->_authorize_pbx('call_flows');
    $o = new CallFlow($call_flow_uuid);
    $this->_assert_pbx_domain($o);
    return $o->delete();
  }
}
