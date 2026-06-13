<?php
namespace ICT\Core;

use PDO;

/* * ***************************************************************
 * Copyright © 2022 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */
class Route
{
  /** @const */
  private static $table = 'route';
  private static $tbl_destination = 'destination';
  private static $tbl_provider = 'provider';
  private static $primary_key = 'route_id';
  private static $fields = array(
      'route_id',
      'name',
      'destination_id',
      'provider_id',
      'service_flag',
      'fpbx_dialplan_uuid'
  );

  /** Directory of static outbound-route dialplan files, included by the ictcore context. */
  private static $fs_dialplan_dir = '/usr/ictcore/etc/freeswitch/dialplan/provider';

  private static $read_only = array(
      'route_id'
  );

  /**
   * @property-read integer $route_id
   * @var integer
   */
  public $route_id = NULL;

  /** @var string */
  public $name = NULL;

  /** @var string */
  public $destination_id = null;

  /** @var integer */
  public $provider_id = NULL;

  /** @var integer */
  public $service_flag = NULL;

  /** @var string FusionPBX v_dialplans.dialplan_uuid for the published projection */
  public $fpbx_dialplan_uuid = NULL;

  public function __construct($route_id = NULL)
  {
    if (!empty($route_id) && $route_id > 0) {
      $this->route_id = $route_id;
      $this->_load();
    }
  }

  public static function search($aFilter = array())
  {
    $aRoute = array();
    $from_str = null;
    $aWhere = array();
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'route_id':
        case 'provider_id':
          $aWhere[] = "route.$search_field = $search_value";
          break;
        case 'name':
          $aWhere[] = "route.$search_field LIKE '%$search_value%'";
          break;
        case 'destination_id':
          $aWhere[] = "route.$search_field = '$search_value'";
          break;
        case 'service_flag':
          $aWhere[] = "route.service_flag = $search_value";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }

    $query  = "SELECT route.route_id, route.name, route.destination_id, route.provider_id, route.service_flag, route.fpbx_dialplan_uuid, destination.name AS destination, destination.prefix, provider.name AS provider, service.name AS service FROM route";
    $query .= " LEFT JOIN destination ON destination.destination_id = route.destination_id";
    $query .= " LEFT JOIN provider ON provider.provider_id = route.provider_id";
    $query .= " LEFT JOIN service ON service.service_flag = route.service_flag";
    $query .= $from_str;
    Corelog::log("Route search with $query", Corelog::DEBUG, array('aFilter' => $aFilter));
    $result = DB::query('route', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aRoute[] = $data;
    }
    return $aRoute;
  }

  public static function load($phone)
  {
    $route_filter = array(
        'phone' => $phone
    );
    $listRoute = self::search($route_filter);
    $aRoute = array_shift($listRoute);
    return new self($aRoute['route_id']);
  }

  public static function getRoutes($destination_id, $service_flag = null)
  {
    $provide_ids = array();
    $filter = array('destination_id' => $destination_id);
    if ($service_flag !== null) {
      $filter['service_flag'] = $service_flag;
    }
    $oRoutes = self::search($filter);
    foreach($oRoutes as $route) {
      $provide_ids[] = $route['provider_id'];
    }
    return $provide_ids;
  }

  private function _load()
  {
    $query = "SELECT * FROM " . self::$table . " WHERE route_id='%route_id%' ";
    $result = DB::query(self::$table, $query, array('route_id' => $this->route_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->route_id = $data['route_id'];
      $this->name = $data['name'];
      $this->destination_id = $data['destination_id'];
      $this->provider_id = $data['provider_id'];
      $this->service_flag = $data['service_flag'];
      $this->fpbx_dialplan_uuid = $data['fpbx_dialplan_uuid'] ?? null;
      Corelog::log("Route loaded name: $this->name", Corelog::CRUD);
    } else {
      throw new CoreException('404', 'Route not found');
    }
  }

  public function delete()
  {
    Corelog::log("Route delete", Corelog::CRUD);
    // Remove the static FreeSWITCH dialplan file first so a dialed prefix never
    // keeps routing out after the route is gone.
    try {
      $this->remove_fs_dialplan();
    } catch (\Throwable $e) {
      Corelog::log("Route remove_fs_dialplan failed: " . $e->getMessage(), Corelog::ERROR);
    }
    // Remove the (legacy) FusionPBX projection too so an orphaned dialplan row never lingers.
    try {
      $this->unpublish_from_fusionpbx();
    } catch (\Throwable $e) {
      Corelog::log("Route unpublish_from_fusionpbx failed: " . $e->getMessage(), Corelog::ERROR);
    }
    return DB::delete(self::$table, 'route_id', $this->route_id);
  }

  public function __get($field)
  {
    $method_name = 'get_' . $field;
    if (method_exists($this, $method_name)) {
      return $this->$method_name();
    } else if (!empty($field) && isset($this->$field)) {
      return $this->$field;
    }
    return NULL;
  }

  public function __set($field, $value)
  {
    $method_name = 'set_' . $field;
    if (method_exists($this, $method_name)) {
      $this->$method_name($value);
    } else if (empty($field) || in_array($field, self::$read_only)) {
      return;
    } else {
      $this->$field = $value;
    }
  }

  public function get_id()
  {
    return $this->route_id;
  }

  public function save()
  {
    $data = array(
        'route_id' => $this->route_id,
        'name' => $this->name,
        'destination_id' => $this->destination_id,
        'provider_id' => $this->provider_id,
        'service_flag' => $this->service_flag,
        'fpbx_dialplan_uuid' => $this->fpbx_dialplan_uuid
    );

    if (isset($data['route_id']) && !empty($data['route_id'])) {
      // update existing record
      $result = DB::update(self::$table, $data, 'route_id');
      Corelog::log("route updated: $this->route_id", Corelog::CRUD);
    } else {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->route_id = $data['route_id']; // NOTE: DB::update suffixes table name with _id as primary key
      Corelog::log("New route created: $this->route_id", Corelog::CRUD);
    }

    // Static-XML outbound dialplan. FusionPBX's Lua xml_handler is disabled, so the
    // v_dialplans projection below is never loaded by FreeSWITCH; every PBX module
    // instead emits static XML that the ictcore dialplan context includes. This is
    // the live outbound path for registered extensions dialing out.
    try {
      $this->sync_fs_dialplan();
    } catch (\Throwable $e) {
      Corelog::log("Route sync_fs_dialplan failed: " . $e->getMessage(), Corelog::ERROR);
    }

    // Publish to FusionPBX v_dialplans (legacy projection; kept best-effort so a
    // future re-enable of the xml_handler still sees the row). Failures must not
    // block MariaDB save.
    try {
      $this->publish_to_fusionpbx();
    } catch (\Throwable $e) {
      Corelog::log("Route publish_to_fusionpbx failed: " . $e->getMessage(), Corelog::ERROR);
    }

    return $result;
  }

  /**
   * Write this voice route as a static FreeSWITCH outbound dialplan file.
   *
   * Mirrors the other PBX modules (RingGroup/IvrMenu/...): a file under the
   * provider dialplan dir that the ictcore context includes, applied via
   * touch + reloadxml. Bridges by gateway NAME (sofia/gateway/<provider_name>/...)
   * because the static sip_profiles/provider gateways are declared by name, not
   * by the FusionPBX fpbx_gateway_uuid the v_dialplans projection used.
   */
  private function sync_fs_dialplan()
  {
    // Only voice routes drive registered-extension outbound dialing. Fax/SMS/email
    // routes are consumed by ICTCore's service-specific spool logic, never by
    // extension dial-out — and must not leave a stale voice dialplan behind.
    if ((((int) $this->service_flag) & 1) === 0) {
      $this->remove_fs_dialplan();
      Corelog::log("Route sync skipped: non-voice service_flag={$this->service_flag}", Corelog::WARNING);
      return;
    }

    if (empty($this->destination_id) || empty($this->provider_id)) {
      Corelog::log("Route sync skipped: missing destination_id or provider_id", Corelog::WARNING);
      return;
    }

    // Resolve destination prefix.
    $dest_q = DB::query(self::$tbl_destination,
      "SELECT prefix, name FROM " . self::$tbl_destination . " WHERE destination_id='%destination_id%'",
      array('destination_id' => $this->destination_id));
    $dest = mysqli_fetch_assoc($dest_q);
    if (empty($dest) || empty($dest['prefix'])) {
      Corelog::log("Route sync skipped: destination prefix unresolved for destination_id={$this->destination_id}", Corelog::WARNING);
      return;
    }
    $prefix = $dest['prefix'];

    // Resolve provider gateway NAME (= FreeSWITCH gateway name in sip_profiles/provider)
    // and the trunk's authorized outbound caller-ID DID.
    $prov_q = DB::query(self::$tbl_provider,
      "SELECT name, outbound_caller_id FROM " . self::$tbl_provider . " WHERE provider_id='%provider_id%'",
      array('provider_id' => $this->provider_id));
    $prov = mysqli_fetch_assoc($prov_q);
    if (empty($prov) || empty($prov['name'])) {
      Corelog::log("Route sync skipped: provider_id={$this->provider_id} name unresolved", Corelog::WARNING);
      return;
    }
    $gateway_name = $prov['name'];

    // Carriers (e.g. Commio/SignalWire) reject outbound calls whose caller-ID is not
    // a DID owned on that trunk's account (403 Forbidden). Pin the trunk's authorized
    // DID on the outbound leg so the egress CLI is always one the carrier accepts,
    // regardless of the dialing extension's effective_caller_id_number. Digits/+ only.
    $cid = isset($prov['outbound_caller_id'])
      ? preg_replace('/[^0-9+]/', '', (string) $prov['outbound_caller_id'])
      : '';

    $e = function($s) { return htmlspecialchars((string)$s, ENT_XML1, 'UTF-8'); };
    $label = $e($this->name ?: ($dest['name'] ?: $gateway_name));
    $regex = $e('^\+?' . preg_quote($prefix, '/') . '([0-9]+)$');
    $gw    = $e($gateway_name);
    $pfx   = $e($prefix);

    $cid_actions = '';
    if ($cid !== '') {
      $cid_e = $e($cid);
      $cid_actions  = "      <action application=\"set\" data=\"effective_caller_id_number={$cid_e}\"/>\n";
      $cid_actions .= "      <action application=\"set\" data=\"effective_caller_id_name={$cid_e}\"/>\n";
    }

    $xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $xml .= '<include>' . "\n";
    $xml .= "  <extension name=\"{$label}\" continue=\"false\">\n";
    $xml .= "    <condition field=\"destination_number\" expression=\"{$regex}\">\n";
    $xml .= "      <action application=\"export\" data=\"call_direction=outbound\" inline=\"true\"/>\n";
    $xml .= $cid_actions;
    $xml .= "      <action application=\"unset\" data=\"call_timeout\"/>\n";
    $xml .= "      <action application=\"bridge\" data=\"sofia/gateway/{$gw}/{$pfx}\$1\"/>\n";
    $xml .= "      <action application=\"hangup\"/>\n";
    $xml .= "    </condition>\n";
    $xml .= "  </extension>\n";
    $xml .= '</include>' . "\n";

    $dir = self::$fs_dialplan_dir;
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
    $file = $this->fs_dialplan_file();
    if (file_put_contents($file, $xml) === false) {
      throw new CoreException(500, "Route dialplan write failed: $file");
    }
    Corelog::log("Route dialplan XML written: $file", Corelog::CRUD);

    $this->reload_fs();
  }

  private function remove_fs_dialplan()
  {
    if (empty($this->route_id)) {
      return;
    }
    $file = $this->fs_dialplan_file();
    if (is_file($file)) {
      @unlink($file);
      Corelog::log("Route dialplan XML removed: $file", Corelog::CRUD);
      $this->reload_fs();
    }
  }

  private function fs_dialplan_file()
  {
    return self::$fs_dialplan_dir . '/route_' . $this->route_id . '.xml';
  }

  private function reload_fs()
  {
    @touch('/etc/freeswitch/dialplan/ictcore.xml');
    try {
      if (class_exists('\\ICT\\Core\\Realtime')) {
        \ICT\Core\Realtime::run_cmd('reloadxml');
      }
    } catch (\Throwable $e) {
      Corelog::log("reloadxml failed: " . $e->getMessage(), Corelog::WARNING);
    }
  }

  /**
   * Render this Route as a FusionPBX outbound dialplan row.
   *
   * Resolves destination prefix + provider gateway UUID, builds the
   * <extension>...</extension> XML, then upserts a row in v_dialplans.
   * Domain is resolved from the provider's tenant; with a single domain
   * present this collapses to the only active FusionPBX domain.
   */
  private function publish_to_fusionpbx()
  {
    // Only voice routes feed the registered-extension outbound dialplan in FusionPBX.
    // Fax/SMS/email routes are consumed by ICTCore's service-specific spool logic
    // (Route::getRoutes filtered by service_flag), never by extension dial-out.
    if ((((int) $this->service_flag) & 1) === 0) {
      Corelog::log("Route publish skipped: non-voice service_flag={$this->service_flag}", Corelog::WARNING);
      return;
    }

    if (empty($this->destination_id) || empty($this->provider_id)) {
      Corelog::log("Route publish skipped: missing destination_id or provider_id", Corelog::WARNING);
      return;
    }

    // Resolve destination prefix.
    $dest_q = DB::query(self::$tbl_destination,
      "SELECT prefix, name FROM " . self::$tbl_destination . " WHERE destination_id='%destination_id%'",
      array('destination_id' => $this->destination_id));
    $dest = mysqli_fetch_assoc($dest_q);
    if (empty($dest) || empty($dest['prefix'])) {
      Corelog::log("Route publish skipped: destination prefix unresolved for destination_id={$this->destination_id}", Corelog::WARNING);
      return;
    }
    $prefix = $dest['prefix'];
    $dest_name = $dest['name'] ?? $prefix;

    // Resolve provider gateway UUID + tenant.
    $prov_q = DB::query(self::$tbl_provider,
      "SELECT name, tenant_id, fpbx_gateway_uuid FROM " . self::$tbl_provider . " WHERE provider_id='%provider_id%'",
      array('provider_id' => $this->provider_id));
    $prov = mysqli_fetch_assoc($prov_q);
    if (empty($prov) || empty($prov['fpbx_gateway_uuid'])) {
      Corelog::log("Route publish skipped: provider_id={$this->provider_id} has no fpbx_gateway_uuid (provider not yet published)", Corelog::WARNING);
      return;
    }
    $gateway_uuid = $prov['fpbx_gateway_uuid'];
    $provider_name = $prov['name'] ?? ('provider_' . $this->provider_id);

    $pdo = FpbxDomain::fpbx_db();
    $domain_uuid = FpbxDomain::get_domain_uuid($prov['tenant_id'] ?? null);
    if (empty($domain_uuid)) {
      Corelog::log("Route publish skipped: no FusionPBX domain resolved", Corelog::WARNING);
      return;
    }
    $domain_name = FpbxDomain::get_domain_name($domain_uuid);

    if (empty($this->fpbx_dialplan_uuid)) {
      $this->fpbx_dialplan_uuid = $this->generate_uuid();
    }

    $dialplan_name   = 'route_' . $this->route_id . '_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $this->name ?: $provider_name);
    $dialplan_number = $prefix;
    $dialplan_xml    = $this->build_dialplan_xml(
      $this->fpbx_dialplan_uuid,
      $this->name ?: $dest_name,
      $prefix,
      $gateway_uuid
    );

    $values = [
      'dialplan_uuid'        => $this->fpbx_dialplan_uuid,
      'domain_uuid'          => $domain_uuid,
      'dialplan_context'     => $domain_name,
      'dialplan_name'        => $dialplan_name,
      'dialplan_number'      => $dialplan_number,
      'dialplan_destination' => 'false',
      'dialplan_continue'    => 'false',
      'dialplan_xml'         => $dialplan_xml,
      'dialplan_order'       => 200,
      'dialplan_enabled'     => 'true',
      'dialplan_description' => 'ICTCore route ' . $this->route_id . ' (' . ($this->name ?: $dest_name) . ')',
    ];

    try {
      $check = $pdo->prepare("SELECT dialplan_uuid FROM v_dialplans WHERE dialplan_uuid = :uuid");
      $check->execute(['uuid' => $this->fpbx_dialplan_uuid]);
      if ($check->fetch()) {
        $sets = implode(', ', array_map(fn($f) => "$f = :$f", array_keys($values)));
        $pdo->prepare("UPDATE v_dialplans SET $sets WHERE dialplan_uuid = :dialplan_uuid")->execute($values);
      } else {
        $cols = implode(', ', array_keys($values));
        $vals = implode(', ', array_map(fn($f) => ':' . $f, array_keys($values)));
        $pdo->prepare("INSERT INTO v_dialplans ($cols) VALUES ($vals)")->execute($values);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Route v_dialplans publish failed: ' . $e->getMessage());
    }

    // Persist the projection UUID back so subsequent saves update in-place.
    DB::update(self::$table, [
      'route_id' => $this->route_id,
      'fpbx_dialplan_uuid' => $this->fpbx_dialplan_uuid,
    ], 'route_id');
  }

  private function unpublish_from_fusionpbx()
  {
    if (empty($this->fpbx_dialplan_uuid)) {
      return;
    }
    $pdo = FpbxDomain::fpbx_db();
    $pdo->prepare("DELETE FROM v_dialplans WHERE dialplan_uuid = :uuid")
        ->execute(['uuid' => $this->fpbx_dialplan_uuid]);
  }

  /**
   * Build FreeSWITCH outbound dialplan XML.
   *
   * Matches calls whose destination_number starts with the configured prefix
   * and bridges them through the projected gateway. Mirrors the FusionPBX
   * dialplan_outbound bridge pattern (sofia/gateway/<uuid>/<number>).
   */
  private function build_dialplan_xml($dialplan_uuid, $route_label, $prefix, $gateway_uuid)
  {
    $label_safe  = htmlspecialchars($route_label, ENT_XML1);
    $regex_safe  = htmlspecialchars('^' . preg_quote($prefix, '/') . '([0-9]+)$', ENT_XML1);
    $uuid_safe   = htmlspecialchars($dialplan_uuid, ENT_XML1);
    $gw_safe     = htmlspecialchars($gateway_uuid, ENT_XML1);
    $prefix_safe = htmlspecialchars($prefix, ENT_XML1);

    return "<extension name=\"{$label_safe}\" continue=\"false\" uuid=\"{$uuid_safe}\">\n"
         . "  <condition field=\"destination_number\" expression=\"{$regex_safe}\">\n"
         . "    <action application=\"export\" data=\"call_direction=outbound\" inline=\"true\"/>\n"
         . "    <action application=\"unset\" data=\"call_timeout\"/>\n"
         . "    <action application=\"bridge\" data=\"sofia/gateway/{$gw_safe}/{$prefix_safe}\$1\"/>\n"
         . "  </condition>\n"
         . "</extension>";
  }

  private function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
      mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
  }
}
