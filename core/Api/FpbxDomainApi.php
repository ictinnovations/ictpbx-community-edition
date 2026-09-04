<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\FpbxDomain;

#[\AllowDynamicProperties]
class FpbxDomainApi extends Api
{
  /**
   * List all FusionPBX domains
   *
   * @url GET /fpbx_domains
   */
  public function list_view($query = array())
  {
    $this->_authorize('super_admin');
    return FpbxDomain::list_domains();
  }

  /**
   * Create a new FusionPBX domain and link it to a tenant
   * POST body: { tenant_id, domain_name, domain_description }
   *
   * @url POST /fpbx_domains
   */
  public function create($data = array())
  {
    $this->_authorize('super_admin');
    if (empty($data['tenant_id']) || empty($data['domain_name'])) {
      throw new CoreException(412, 'tenant_id and domain_name are required');
    }
    $uuid = FpbxDomain::create_domain(
      $data['tenant_id'],
      $data['domain_name'],
      $data['domain_description'] ?? ''
    );
    return ['domain_uuid' => $uuid, 'domain_name' => $data['domain_name']];
  }

  /**
   * Link an existing FusionPBX domain to a tenant
   * PUT body: { domain_uuid }
   *
   * @url PUT /fpbx_domains/$tenant_id
   */
  public function update($tenant_id, $data = array())
  {
    $this->_authorize('super_admin');
    if (empty($data['domain_uuid'])) {
      throw new CoreException(412, 'domain_uuid is required');
    }
    FpbxDomain::link_domain($tenant_id, $data['domain_uuid']);
    return ['tenant_id' => $tenant_id, 'domain_uuid' => $data['domain_uuid']];
  }

  /**
   * Get FusionPBX domain linked to a tenant
   *
   * @url GET /fpbx_domains/$tenant_id
   */
  public function read($tenant_id)
  {
    $this->_authorize('super_admin');
    $domain_uuid = FpbxDomain::get_domain_uuid($tenant_id);
    $domain_name = FpbxDomain::get_domain_name($domain_uuid);
    return ['tenant_id' => $tenant_id, 'domain_uuid' => $domain_uuid, 'domain_name' => $domain_name];
  }
}
