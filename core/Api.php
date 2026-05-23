<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use Jacwright\RestServer\RestServer;

class Api
{

  /** @var boolean include_subfolder */
  private $include_subfolder = true;

  /** @var string #interface_type */
  private $interface_type = 'local';

  /** @var RestServer $oInterface */
  private $oInterface = null;

  /** @var User $oUser */
  protected $oUser = null;

  public function authenticate($credentials, $auth_type)
  {
    try {
      $oUser = User::authenticate($credentials, $auth_type);
      if ($oUser instanceof User) {
        do_login($oUser);
        $this->oUser = $oUser;
        return true;
      }
      return false;
    } catch (CoreException $e) {
      Corelog::log($e->getMessage(), Corelog::ERROR);
      return false;
    }
  }

  protected function _authorize($permission)
  {
    if (empty($permission) || can_access($permission) == false) {
      throw new CoreException(403, 'User not permitted ('.$permission.') to perform required action');
    }
    return true;
  }

  protected function _authorize_pbx($permission, $write_op = false)
  {
    if (can_access('user_admin')) return true;
    if (!$write_op) {
      $user = $this->oUser;
      if ($user && !empty($user->user_permission)) {
        $perms = array_map('trim', explode(',', $user->user_permission));
        if (in_array($permission, $perms)) return true;
      }
    }
    throw new CoreException(403, 'User not permitted ('.$permission.') to perform required action');
  }

  protected function _authorization_filter($data = array() , $filter_field = 'tenant_id', $permission = 'super_admin' ) {
    if (is_community_edition()) {
      return $data ? true : array();
    }
    if($data){
      if(can_access('super_admin')){
        return true;
      }
      else if(can_access('user_admin')){
      if($data->tenant_id == $this->oUser->tenant_id){
        return true; 
      }
      }else{
        $user_id =  $data->user_id ? $data->user_id : $data->created_by; 
        if($user_id == $this->oUser->user_id || $user_id == $this->oUser->user_id ){
          return true; 
        } 
      }
        throw new CoreException(401, 'You are Unauthorized for this information');
    }
    if (!can_access($permission)) {
      if (can_access('user_admin')) {
        return array($filter_field => $this->oUser->tenant_id);
      } else {
        $filter_field = ($filter_field == 'tenant_id') ? 'user_id' : $filter_field;
        return array($filter_field => $this->oUser->user_id);
      }
    }
    return array();
  }



  protected function _tenant_filter($filter_field = 'tenant_id', $permission = 'user_admin') {
    if (is_community_edition()) return array();
    if (can_access('super_admin')) return array();
    else return array($filter_field => $this->oUser->tenant_id);
  }

  protected function _assert_pbx_domain($obj) {
    if (is_community_edition() || can_access('super_admin')) return;
    $caller_domain = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
    if ($obj->domain_uuid !== $caller_domain) {
      throw new CoreException(403, 'Forbidden');
    }
  }

  protected function set($oEntity, $data)
  {
    foreach ($data as $key => $value) {
      try {
        $oEntity->$key = $value;
      } catch (CoreException $ex) {
        throw new CoreException(412, 'Data validation failed, for ' . $key, $ex);
      }
    }
  }

  public function create_interface($interface_type = null, $root_path = null)
  {
    global $path_cache;
    if (!empty($interface_type) && $interface_type = 'rest') {
      // Initialize the server
      $this->interface_type = 'rest';
      $realm = Conf::get('company:name', 'ICTCore') . ' :: REST API Server';
      $this->oInterface = new RestServer('production', $realm); // debug / production
      $this->oInterface->root = $root_path;
      $this->oInterface->cacheDir = $path_cache; // set folder for rest server url mapping
      $this->oInterface->jsonAssoc = true; // always get associated array for POST data
      // CORS support
      $origin_list = Conf::get('website:cors', '');
      if (!empty($origin_list) && !in_array(trim($origin_list), array('no', '0', 'disable', 'disabled'))) {
        $this->oInterface->useCors = true;
        $this->oInterface->allowedOrigin = explode(',', $origin_list);
      }
      $this->oInterface->authHandler = new Http(); // Authentication via HTTP interface
      self::rest_load($this->oInterface);
    }
  }

  public function get_request_url()
  {
    return $this->server->url;
  }

  public function get_request_method()
  {
    return $this->server->method;
  }

  public function get_request_format()
  {
    return $this->server->format;
  }

  public function send_error($code, $message)
  {
    // Coerce non-int codes (e.g. PDO SQLSTATE strings like '23505', 'HY093')
    // to a valid HTTP status int so http_response_code() does not throw.
    if (!is_int($code)) {
      error_log("ICTCore API non-int error code: " . var_export($code, true) . " message=" . $message);
      $code = 500;
    }
    $this->oInterface->handleError($code, $message);
  }

  protected static function rest_include()
  {
    if (property_exists (get_called_class(), 'include_subfolder')) {
      return 'Api'; // Api class return sub api folder
    }
    // in child class return null
    return null;
  }

  protected static function rest_load(&$restInterface)
  {
    $dir = static::rest_include();
    if (empty($restInterface) || empty($dir)) {
      return false;
    }

    include_once_directory($dir);
    $namespace = path_to_namespace($dir);
    $listClass = get_declared_classes();
    // escape slashes from namespace and add an extra slash to select child classes only
    $listApi   = preg_grep('!^'.addslashes($namespace.'\\').'!', $listClass);
    foreach ($listApi as $apiClass) {
      $restInterface->addClass($apiClass);
      if (method_exists($apiClass, 'rest_include')) {
        $apiClass::rest_load($restInterface);
      }
    }
  }

  public function process_request()
  {
    return $this->oInterface->handle();
  }

}
