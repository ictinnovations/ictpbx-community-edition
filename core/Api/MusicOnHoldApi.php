<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\MusicOnHold;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class MusicOnHoldApi extends Api
{
  // Settable from the form; domain, uuid and stream are owned by the server.
  const EDITABLE = [
    'music_on_hold_name', 'music_on_hold_path', 'music_on_hold_rate', 'music_on_hold_shuffle',
    'music_on_hold_channels', 'music_on_hold_interval', 'music_on_hold_timer_name',
    'music_on_hold_chime_list', 'music_on_hold_chime_freq', 'music_on_hold_chime_max',
  ];

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
    $o->uses_uploads = $o->uses_managed_dir();
    return $o;
  }

  /** @url POST /music_on_hold */
  public function create($data = array())
  {
    $this->_authorize_pbx('music_on_hold', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::MUSIC_ON_HOLD)) {
      throw new CoreException(409, 'Music on Hold quota limit reached for this tenant');
    }
    $o = new MusicOnHold();
    $o->tenant_id = $this->oUser->tenant_id;
    $this->set($o, $this->_editable($data, null));
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
    $this->_authorize_pbx('music_on_hold', true);
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    $this->set($o, $this->_editable($data, $o->music_on_hold_path));
    if ($o->save()) { return $o; }
    throw new CoreException(417, 'Music on Hold update failed');
  }

  /** @url DELETE /music_on_hold/$music_on_hold_uuid */
  public function remove($music_on_hold_uuid)
  {
    $this->_authorize_pbx('music_on_hold', true);
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    $result = $o->delete();
    if ($result) PbxQuota::decrement($this->_owner_tenant_id($o), PbxQuota::MUSIC_ON_HOLD);
    return $result;
  }

  /** @url GET /music_on_hold/$music_on_hold_uuid/files */
  public function file_list($music_on_hold_uuid)
  {
    $this->_authorize_pbx('music_on_hold');
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    return $o->list_files();
  }

  /**
   * Upload one audio file (multipart/form-data, field "file").
   *
   * @url POST /music_on_hold/$music_on_hold_uuid/files
   */
  public function file_upload($music_on_hold_uuid, $data = array())
  {
    $this->_authorize_pbx('music_on_hold', true);
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      $too_big = !empty($_FILES['file']) && in_array($_FILES['file']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE]);
      throw new CoreException($too_big ? 413 : 400, $too_big ? 'File is larger than the server upload limit' : 'No valid file uploaded');
    }
    return $o->add_file($_FILES['file']['tmp_name'], $_FILES['file']['name']);
  }

  /** @url DELETE /music_on_hold/$music_on_hold_uuid/files/$file_id */
  public function file_remove($music_on_hold_uuid, $file_id)
  {
    $this->_authorize_pbx('music_on_hold', true);
    $o = new MusicOnHold($music_on_hold_uuid);
    $this->_assert_pbx_domain($o);
    return $o->delete_file($file_id);
  }

  /**
   * Keep only form fields. Only a super admin may point a category at an arbitrary
   * server path; anyone may clear it back to the managed upload directory.
   */
  private function _editable($data, $current_path)
  {
    $data = array_intersect_key((array)$data, array_flip(self::EDITABLE));
    $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);
    if (!$is_admin && isset($data['music_on_hold_path'])
        && $data['music_on_hold_path'] !== '' && $data['music_on_hold_path'] !== $current_path) {
      unset($data['music_on_hold_path']);
    }
    return $data;
  }
}
