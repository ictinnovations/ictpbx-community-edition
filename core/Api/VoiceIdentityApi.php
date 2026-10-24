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
   * The caller already authenticated as the extension over SIP, so we trust it instead of
   * a typed password: extension@domain -> tenant -> the user the extension is assigned to
   * (account.created_by, set by "Assign to User"), or the tenant admin (role_id=3) when
   * it is unassigned -> scoped JWT via generate_token(). The domain is required because
   * extension numbers are only unique within a tenant. A resolved user outside the
   * tenant, or a tenant with no role_id=3 user (e.g. the Super Admin domain), is refused,
   * so a *99 call can never escalate to super-admin scope. Guarded by a shared secret in the
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

    $domain = isset($data['domain'])
      ? preg_replace('/[^0-9A-Za-z._-]/', '', (string) $data['domain']) : '';

    // extension@domain -> FusionPBX domain_uuid (PostgreSQL). Without a domain the number
    // is only accepted when exactly one tenant has it; otherwise we would be guessing.
    $pdo = FpbxDomain::fpbx_db();
    if ($domain !== '') {
      $stmt = $pdo->prepare('SELECT DISTINCT e.domain_uuid FROM v_extensions e
                               JOIN v_domains d ON d.domain_uuid = e.domain_uuid
                              WHERE e.extension = ? AND d.domain_name = ?');
      $stmt->execute(array($extension, $domain));
    } else {
      $stmt = $pdo->prepare('SELECT DISTINCT domain_uuid FROM v_extensions WHERE extension = ?');
      $stmt->execute(array($extension));
    }
    $matches = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    if (count($matches) > 1) {
      throw new CoreException(409, 'Extension exists in several tenants; domain required');
    }
    $domain_uuid = $matches ? $matches[0] : '';
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

    // The extension's own user, so the assistant acts with that person's permissions.
    // Admins (role_id=2) are excluded: a *99 call must never carry super-admin scope.
    $ext_esc = mysqli_real_escape_string(DB::$link, $extension);
    $result = DB::query('usr',
      "SELECT u.usr_id FROM account a JOIN usr u ON u.usr_id = a.created_by
        WHERE a.phone = '$ext_esc' AND a.tenant_id = $tenant_id
          AND a.type IN ('account','child_account')
          AND u.tenant_id = $tenant_id AND u.active = 1 AND u.role_id <> 2
        LIMIT 1");
    $urow = $result ? mysqli_fetch_assoc($result) : null;

    // Unassigned extension: fall back to the tenant admin; refuse if none.
    if (empty($urow['usr_id'])) {
      $result = DB::query('usr',
        "SELECT usr_id FROM usr WHERE tenant_id = $tenant_id AND role_id = 3 AND active = 1 ORDER BY usr_id ASC LIMIT 1");
      $urow = $result ? mysqli_fetch_assoc($result) : null;
    }
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
