<?php

namespace ICT\Core\Api;

/* * ***************************************************************
 * Copyright © 2017 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Api;
use ICT\Core\Conf;
use ICT\Core\Core;
use ICT\Core\FpbxDomain;
use ICT\Core\Gateway\Freeswitch;
use ICT\Core\Request;

#[\AllowDynamicProperties]
class CoreApi extends Api
{

  /**
   * Provide System statistics
   *
   * @url GET /statistics
   */
  public function statistics($query = array())
  {
    $this->_authorize('statistic_read');
    $filter = (array)$query;
    // $filter += $this->_authorization_filter('created_by', 'statistic_admin');
    $filter += $this->_authorization_filter();
    return Core::statistic($filter);
  }

  /**
   * post results / response from gateway activity
   *
   * @url POST /responses
   */
  public function process($gateway_flag, $spool_id, $application_id, $data = array())
  {
    $this->_authorize('transmission_create');
    $this->_authorize('transmission_update');

    // RestServer only fills the named 'data' param from POST body; all other POST params
    // (gateway_flag, spool_id, application_id) arrive inside $data, not as direct params.
    if (empty($gateway_flag) && isset($data['gateway_flag'])) $gateway_flag = $data['gateway_flag'];
    if (empty($spool_id)    && isset($data['spool_id']))    $spool_id    = $data['spool_id'];
    if (empty($application_id) && isset($data['application_id'])) $application_id = $data['application_id'];

    // application_data is posted as a JSON string; decode it into an array for Application::result.
    $application_data = $data;
    if (isset($data['application_data']) && is_string($data['application_data'])) {
      $decoded = json_decode($data['application_data'], true);
      if (is_array($decoded)) {
        $application_data = $decoded;
      }
    }

    // now process the main request
    $oResponse = $this->process_response($spool_id, $application_id, $application_data, $gateway_flag);
    // and publish output
    if (!empty($oResponse->application_data)) {
      echo $oResponse->application_data;
    }

    // after all process data from additional app if there is any, we need to proecess it after main application
    // so it can use main application result to calculate next action while processing program
    // normally it will be used with last application to collect results of originate like applications
    if (isset($data['extra']) && is_array($data['extra'])) {
      foreach ($data['extra'] as $aApp) {
        $aAppData = isset($aApp['application_data']) && is_string($aApp['application_data'])
          ? (json_decode($aApp['application_data'], true) ?: $aApp['application_data'])
          : ($aApp['application_data'] ?? array());
        // no need to collect any type of output
        $this->process_response($aApp['spool_id'], $aApp['application_id'], $aAppData, $aApp['gateway_flag']);
      }
    }
    exit();
  }

  function process_response($spool_id, $application_id, $application_data = array(), $gateway_flag = Freeswitch::GATEWAY_FLAG)
  {
    $oRequest = new Request();
    $oRequest->spool_id = $spool_id;
    $oRequest->application_id = $application_id;
    $oRequest->application_data = $application_data;
    $oRequest->gateway_flag = $gateway_flag;

    if (!empty($application_data['context'])) {
      $oRequest->context = $application_data['context'];
    }
    if (!empty($application_data['source'])) {
      if ($gateway_flag == Freeswitch::GATEWAY_FLAG) {
        $oRequest->source = preg_replace("/[^0-9]/", "", $application_data['source']);
      } else {
        $oRequest->source = $application_data['source'];
      }
    }
    if (!empty($application_data['destination'])) {
      if ($gateway_flag == Freeswitch::GATEWAY_FLAG) {
        $oRequest->destination = preg_replace("/[^0-9]/", "", $application_data['destination']);
      } else {
        $oRequest->destination = $application_data['destination'];
      }
    }

    return Core::process($oRequest);
  }

  /**
   * Provide FusionPBX object counts (extensions, ring groups, etc.)
   *
   * @url GET /pbx_statistics
   */
  public function pbx_statistics()
  {
    // Allow super_admin, tenant-admin (user_admin), or any user with a PBX permission
    $pbxPerms  = ['fpbx_extension','ring_groups','call_queues','ivr_menus','voicemails','conferences','devices','gateways'];
    $userPerms = $this->oUser ? array_map('trim', explode(',', $this->oUser->user_permission ?? '')) : [];
    if (!\ICT\Core\can_access('user_admin') && empty(array_intersect($pbxPerms, $userPerms))) {
      throw new CoreException(403, 'PBX statistics: insufficient permissions');
    }
    $tables = [
      'extensions'   => 'v_extensions',
      'devices'      => 'v_devices',
      'ring_groups'  => 'v_ring_groups',
      'call_queues'  => 'v_call_center_queues',
      'ivr_menus'    => 'v_ivr_menus',
      'voicemails'   => 'v_voicemails',
      'conferences'  => 'v_conference_centers',
      'gateways'     => 'v_gateways',
    ];
    $result = [];
    try {
      $pdo = FpbxDomain::fpbx_db();
      $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);
      $domain_uuid = null;
      if (!$is_admin) {
        $domain_uuid = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
      }
      if (!$is_admin && $domain_uuid === null) { /* null-domain-guard */
        foreach ($tables as $key => $_) { $result[$key] = 0; }
        return $result;
      }
      foreach ($tables as $key => $table) {
        $sql = "SELECT COUNT(*) AS c FROM $table";
        if ($domain_uuid && $table !== 'v_gateways') {
          $sql .= " WHERE domain_uuid = '$domain_uuid'";
        }
        $stmt = $pdo->query($sql);
        $result[$key] = (int)($stmt ? $stmt->fetchColumn() : 0);
      }
    } catch (\Exception $e) {
      foreach ($tables as $key => $_) { $result[$key] = 0; }
    }
    return $result;
  }

}
