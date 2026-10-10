<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Corelog;
use ICT\Core\FpbxDomain;
use ICT\Core\Realtime;

/**
 * Click-to-call: originate a call from a local extension to a destination
 * number. Rings the agent's extension first, then bridges the dialed number
 * through the ictcore dialplan context.
 */
#[\AllowDynamicProperties]
class ClickToCallApi extends Api
{
  /**
   * @url POST /call/originate
   */
  public function originate($data = [])
  {
    // Write-protected: only admins / tenant-admins may place calls.
    $this->_authorize_pbx('realtime', true);

    $from_ext  = trim((string)($data['from_ext'] ?? ''));
    $to_number = trim((string)($data['to_number'] ?? ''));

    // Validate strictly to avoid dialplan / shell injection. run_cmd already
    // escapeshellarg()s the whole command, but reject anything non-numeric.
    if (!preg_match('/^\d{2,6}$/', $from_ext)) {
      throw new CoreException(400, 'Invalid from_ext (digits only)');
    }
    if (!preg_match('/^\+?[0-9*#]{2,20}$/', $to_number)) {
      throw new CoreException(400, 'Invalid to_number');
    }

    // Resolve the calling extension's FusionPBX domain via the caller's tenant.
    $domain_uuid = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
    if (empty($domain_uuid)) {
      throw new CoreException(409, 'No PBX domain configured for this tenant');
    }
    $domain_name = FpbxDomain::get_domain_name($domain_uuid);
    if (empty($domain_name) || $domain_name === 'default') {
      throw new CoreException(409, 'Unable to resolve PBX domain name');
    }

    // Endpoints are addressed at the domain the FreeSWITCH directory declares, which is
    // one domain for every tenant -- the FusionPBX domain name (tenant.local, ...) is not
    // a directory FreeSWITCH knows, so it would never resolve for a sub-tenant.
    $dial_domain = $domain_name;

    // bgapi hands back a Job-UUID immediately and the originate result arrives
    // asynchronously, so the command output can never reveal a failed call. Check the
    // leg we are about to ring is actually registered -- the failure that really happens
    // -- so the caller gets an error instead of a cheerful 200 for a call that never was.
    $contact = trim((string)Realtime::run_cmd(sprintf('sofia_contact */%s@%s', $from_ext, $dial_domain)));
    if ($contact === '' || stripos($contact, 'error/') === 0 || stripos($contact, 'user_not_registered') !== false) {
      throw new CoreException(409, "Extension $from_ext is not registered, so it cannot be called.");
    }

    $cmd = sprintf(
      'bgapi originate {origination_caller_id_number=%s,ignore_early_media=true}user/%s@%s %s XML ictcore',
      $to_number, $from_ext, $dial_domain, $to_number
    );

    $out = Realtime::run_cmd($cmd);
    Corelog::log("Click-to-call $from_ext -> $to_number on $dial_domain: " . trim((string)$out), Corelog::CRUD);

    // Still worth catching, for the errors bgapi *can* report synchronously.
    if (stripos((string)$out, '-ERR') !== false) {
      throw new CoreException(417, 'Originate failed: ' . trim((string)$out));
    }

    // The originate is queued, not completed: report the job so the outcome is traceable
    // in the FreeSWITCH log rather than implying the call connected.
    $job_uuid = null;
    if (preg_match('/Job-UUID:\s*([0-9a-f-]+)/i', (string)$out, $m)) {
      $job_uuid = $m[1];
    }

    return [
      'status'    => 'queued',
      'from_ext'  => $from_ext,
      'to_number' => $to_number,
      'job_uuid'  => $job_uuid,
    ];
  }
}
