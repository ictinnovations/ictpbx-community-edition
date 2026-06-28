<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Voicemail;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class VoicemailApi extends Api
{
  /**
   * List voicemails (tenant-scoped)
   *
   * @url GET /voicemails
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('voicemails');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    
    $result = array_map(fn($r) => (array)$r, (array)Voicemail::search($filter));
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
   * Get a voicemail by UUID
   *
   * @url GET /voicemails/$voicemail_uuid
   */
  public function read($voicemail_uuid)
  {
    $this->_authorize_pbx('voicemails');
    $oVoicemail = new Voicemail($voicemail_uuid);
    $this->_assert_pbx_domain($oVoicemail);
    return $oVoicemail;
  }

  /**
   * Create a new voicemail box
   *
   * @url POST /voicemails
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('voicemails', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::VOICEMAIL)) {
      throw new CoreException(409, 'Voicemail quota limit reached for this tenant');
    }
    $oVoicemail = new Voicemail();
    $oVoicemail->tenant_id = $this->oUser->tenant_id;
    $this->set($oVoicemail, $data);
    $uuid = $oVoicemail->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::VOICEMAIL);
      return $uuid;
    }
    throw new CoreException(417, 'Voicemail creation failed');
  }

  /**
   * Update a voicemail box
   *
   * @url PUT /voicemails/$voicemail_uuid
   */
  public function update($voicemail_uuid, $data = array())
  {
    $this->_authorize_pbx('voicemails', true);
    $oVoicemail = new Voicemail($voicemail_uuid);
    $this->_assert_pbx_domain($oVoicemail);
    $this->set($oVoicemail, $data);
    if ($oVoicemail->save()) {
      return $oVoicemail;
    }
    throw new CoreException(417, 'Voicemail update failed');
  }

  /**
   * Delete a voicemail box
   *
   * @url DELETE /voicemails/$voicemail_uuid
   */
  public function remove($voicemail_uuid)
  {
    $this->_authorize_pbx('voicemails', true);
    $oVoicemail = new Voicemail($voicemail_uuid);
    $this->_assert_pbx_domain($oVoicemail);
    $result = $oVoicemail->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::VOICEMAIL);
    return $result;
  }
}
