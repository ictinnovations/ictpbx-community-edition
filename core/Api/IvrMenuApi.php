<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\IvrMenu;
use ICT\Core\PbxQuota;

#[\AllowDynamicProperties]
class IvrMenuApi extends Api
{
  /**
   * @url GET /ivr_menus
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('ivr_menus');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    
    $result = array_map(fn($r) => (array)$r, (array)IvrMenu::search($filter));
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
   * @url GET /ivr_menus/$ivr_menu_uuid
   */
  public function read($ivr_menu_uuid)
  {
    $this->_authorize_pbx('ivr_menus');
    $oMenu = new IvrMenu($ivr_menu_uuid);
    $this->_assert_pbx_domain($oMenu);
    return $oMenu;
  }

  /**
   * @url POST /ivr_menus
   */
  public function create($data = array())
  {
    $this->_authorize_pbx('ivr_menus', true);
    if (!PbxQuota::check($this->oUser->tenant_id, PbxQuota::IVR_MENU)) {
      throw new CoreException(409, 'IVR Menu quota limit reached for this tenant');
    }
    $oMenu = new IvrMenu();
    $oMenu->tenant_id = $this->oUser->tenant_id;
    $this->set($oMenu, $data);
    $uuid = $oMenu->save();
    if ($uuid) {
      PbxQuota::increment($this->oUser->tenant_id, PbxQuota::IVR_MENU);
      return $uuid;
    }
    throw new CoreException(417, 'IVR Menu creation failed');
  }

  /**
   * @url PUT /ivr_menus/$ivr_menu_uuid
   */
  public function update($ivr_menu_uuid, $data = array())
  {
    $this->_authorize_pbx('ivr_menus', true);
    $oMenu = new IvrMenu($ivr_menu_uuid);
    $this->_assert_pbx_domain($oMenu);
    $this->set($oMenu, $data);
    if ($oMenu->save()) return $oMenu;
    throw new CoreException(417, 'IVR Menu update failed');
  }

  /**
   * @url DELETE /ivr_menus/$ivr_menu_uuid
   */
  public function remove($ivr_menu_uuid)
  {
    $this->_authorize_pbx('ivr_menus', true);
    $oMenu = new IvrMenu($ivr_menu_uuid);
    $this->_assert_pbx_domain($oMenu);
    $result = $oMenu->delete();
    if ($result) PbxQuota::decrement($this->oUser->tenant_id, PbxQuota::IVR_MENU);
    return $result;
  }
}
