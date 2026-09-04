<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\DB;
use ICT\Core\User;
use ICT\Core\FpbxDomain;

#[\AllowDynamicProperties]
class VoiceIdentityApi extends Api
{

  /**
   * Resolve a SIP extension to a tenant-scoped JWT for the AI voice gateway (*99).
   *
   * The caller is already SIP-registered to the extension, so we authenticate by the
   * calling extension rather than by a typed password: extension -> FusionPBX domain ->
   * tenant -> that tenant's admin user (role_id=3) -> scoped JWT via generate_token().
   * Tenants with no role_id=3 user (e.g. the Super Admin domain) are refused, so a *99
   * call can never escalate to super-admin scope. Guarded by a shared secret in the
   * X-Voice-Gateway-Key header (NOT a user JWT); the endpoint is reached only from the
   * loopback gateway sidecar.
   *
   * @noAuth
   * @url POST /voice_identity
   */
  public function create($data = array())
  {
    $key_file = '/usr/ictcore/etc/voice_gateway.key';
    $expected = is_readable($key_file) ? trim(file_get_contents($key_file)) : '';
    $provided = isset($_SERVER['HTTP_X_VOICE_GATEWAY_KEY'])
      ? trim($_SERVER['HTTP_X_VOICE_GATEWAY_KEY']) : '';
    if (empty($expected) || !hash_equals($expected, $provided)) {
      throw new CoreException(403, 'Invalid gateway key');
    }

    $extension = isset($data['extension'])
      ? preg_replace('/[^0-9A-Za-z]/', '', (string) $data['extension']) : '';
    if (empty($extension)) {
      throw new CoreException(400, 'extension required');
    }

    // extension -> FusionPBX domain_uuid (PostgreSQL)
    $pdo = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare('SELECT domain_uuid FROM v_extensions WHERE extension = ? LIMIT 1');
    $stmt->execute(array($extension));
    $domain_uuid = $stmt->fetchColumn();
    if (empty($domain_uuid) || !preg_match('/^[0-9a-fA-F-]{36}$/', $domain_uuid)) {
      throw new CoreException(404, 'Extension not found');
    }

    // domain_uuid -> tenant_id (MariaDB)
    $result = DB::query('tenant',
      "SELECT tenant_id FROM tenant WHERE fpbx_domain_uuid = '$domain_uuid' LIMIT 1");
    $trow = $result ? mysqli_fetch_assoc($result) : null;
    if (empty($trow['tenant_id'])) {
      throw new CoreException(404, 'No tenant is mapped to this extension');
    }
    $tenant_id = (int) $trow['tenant_id'];

    // tenant admin (role_id=3); refuse if none -> caps super-admin-only domains
    $result = DB::query('usr',
      "SELECT usr_id FROM usr WHERE tenant_id = $tenant_id AND role_id = 3 AND active = 1 ORDER BY usr_id ASC LIMIT 1");
    $urow = $result ? mysqli_fetch_assoc($result) : null;
    if (empty($urow['usr_id'])) {
      throw new CoreException(403, 'This extension is not enabled for the assistant');
    }
    $usr_id = (int) $urow['usr_id'];

    $oUser = new User($usr_id);
    if (empty($oUser->user_id)) {
      throw new CoreException(404, 'Resolved user not found');
    }
    $token = $oUser->generate_token();

    return array(
      'token' => $token,
      'user_id' => $oUser->user_id,
      'tenant_id' => $oUser->tenant_id,
      'username' => $oUser->username,
      'extension' => $extension,
    );
  }
}
