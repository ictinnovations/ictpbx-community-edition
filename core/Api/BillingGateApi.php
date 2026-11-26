<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\Corelog;
use ICT\Core\DB;
use ICT\Core\FpbxDomain;
use ICT\Core\PbxQuota;
use ICT\Core\Realtime;

/**
 * Billing / call-admission gate.
 *
 * Called by the FreeSWITCH call-control hook (application.lua) before bridging
 * an outbound or inbound call. Enforces, in one place, all three gates:
 *   1. Credit      — tenant.credit must be > 0.
 *   2. Usage quota — PbxQuota::check() for voice (12) or fax (13).
 *   3. Concurrency — live channels for the tenant's domain must be below
 *                    tenant.max_concurrent_calls (0/NULL = unlimited).
 *
 * Fail-open by design: any internal error or unresolvable tenant/domain
 * returns allowed=true so a gate fault never blocks live calls. CE always
 * allows.
 */
#[\AllowDynamicProperties]
class BillingGateApi extends Api
{
  /**
   * @noAuth
   * @url GET /billing/gate
   */
  public function read($query = [])
  {
    $query     = (array)$query;
    $tenant_id = isset($query['tenant_id']) ? (int)$query['tenant_id'] : 0;
    $type      = isset($query['type']) ? strtolower(trim($query['type'])) : 'voice';

    if (\ICT\Core\is_community_edition()) {
      return ['allowed' => true, 'reason' => 'community-edition'];
    }
    if ($tenant_id <= 0) {
      return ['allowed' => true, 'reason' => 'no-tenant'];
    }

    try {
      // 1. Credit
      $result = DB::query('tenant',
        "SELECT credit, max_concurrent_calls FROM tenant WHERE tenant_id = $tenant_id LIMIT 1"
      );
      $row = mysqli_fetch_assoc($result);
      if (!$row) {
        return ['allowed' => true, 'reason' => 'tenant-not-found'];
      }
      $credit = (float)$row['credit'];
      if ($credit <= 0) {
        return ['allowed' => false, 'reason' => 'no-credit'];
      }

      // 2. Usage quota (voice = 12, fax = 13)
      $resource_id = ($type === 'fax') ? 13 : 12;
      if (!PbxQuota::check($tenant_id, $resource_id)) {
        return ['allowed' => false, 'reason' => 'quota-exceeded'];
      }

      // 3. Concurrency
      $max = (int)$row['max_concurrent_calls'];
      if ($max > 0) {
        $live = $this->_countLiveChannels($tenant_id);
        if ($live >= 0 && $live >= $max) {
          return ['allowed' => false, 'reason' => 'max-concurrent-calls'];
        }
      }

      return ['allowed' => true, 'reason' => 'ok'];
    } catch (\Throwable $e) {
      // Fail open — never block calls on a gate fault.
      Corelog::log("BillingGate fault (fail-open): " . $e->getMessage(), Corelog::ERROR);
      return ['allowed' => true, 'reason' => 'gate-error'];
    }
  }

  /**
   * Count active FreeSWITCH channels belonging to a tenant's domain.
   * Returns -1 when the domain cannot be resolved (caller treats as unlimited).
   */
  private function _countLiveChannels(int $tenant_id): int
  {
    $domain_uuid = FpbxDomain::get_domain_uuid($tenant_id);
    if (empty($domain_uuid)) {
      return -1; // no domain mapping — don't enforce concurrency (B7)
    }
    $domain_name = FpbxDomain::get_domain_name($domain_uuid);
    if (empty($domain_name) || $domain_name === 'default') {
      return -1;
    }

    $json = Realtime::run_cmd('show channels as json');
    $parsed = json_decode($json, true);
    if (empty($parsed['rows']) || !is_array($parsed['rows'])) {
      return 0;
    }

    $needle = '@' . strtolower($domain_name);
    $count  = 0;
    foreach ($parsed['rows'] as $r) {
      $presence = strtolower((string)($r['presence_id'] ?? ''));
      if ($presence !== '' && substr($presence, -strlen($needle)) === $needle) {
        $count++;
      }
    }
    return $count;
  }
}
