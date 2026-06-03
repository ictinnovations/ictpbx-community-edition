<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\DB;
use ICT\Core\FpbxDomain;

#[\AllowDynamicProperties]
class FpbxCdrApi extends Api
{
  /**
   * List PBX CDR records (admin: all; tenant: own extensions).
   *
   * Sourced from ICTCore cdr_lega, fed by cdr_etl.php from FreeSWITCH
   * mod_cdr_csv. The legacy FusionPBX v_xml_cdr source is never populated on
   * this deployment: every call runs in the ictcore context, so FusionPBX's
   * domain-keyed xml_cdr import drops the records.
   *
   * @url GET /fpbx_cdr
   */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('realtime');

    $is_admin   = \ICT\Core\can_access('super_admin', $this->oUser->user_id);
    $limit      = max(1, min(100, (int)($_GET['limit']  ?? 25)));
    $offset     = max(0, (int)($_GET['offset'] ?? 0));
    $start_date = $_GET['start_date'] ?? '';
    $end_date   = $_GET['end_date']   ?? '';
    $direction  = $_GET['direction']  ?? '';

    $where  = ['1=1'];
    $values = array();

    // Tenant isolation: cdr_lega carries no tenant column (domain = server
    // hostname for every row), so scope non-admins by the extensions on their
    // FusionPBX domain — a call is theirs if their extension is caller or callee.
    if (!$is_admin) {
      $exts = $this->_tenant_extensions();
      if (empty($exts)) return ['rows' => [], 'total' => 0];
      $placeholders = array();
      foreach ($exts as $i => $ext) {
        $key = 'ext' . $i;
        $placeholders[] = "'%$key%'";
        $values[$key]   = $ext;
      }
      $in = implode(',', $placeholders);
      $where[] = "(caller_number IN ($in) OR destination IN ($in))";
    }

    if ($start_date) {
      $where[] = "start_time >= '%start_date%'";
      $values['start_date'] = $start_date . ' 00:00:00';
    }
    if ($end_date) {
      $where[] = "start_time <= '%end_date%'";
      $values['end_date'] = $end_date . ' 23:59:59';
    }
    if ($direction && in_array($direction, ['inbound', 'outbound', 'local'])) {
      $where[] = "direction = '%direction%'";
      $values['direction'] = $direction;
    }

    $sql_where = 'WHERE ' . implode(' AND ', $where);

    // total count
    $count_res = DB::query('cdr_lega', "SELECT COUNT(*) AS c FROM cdr_lega $sql_where", $values);
    $total = $count_res ? (int)(mysqli_fetch_assoc($count_res)['c'] ?? 0) : 0;

    // data — alias cdr_lega columns to the field names the frontend renders
    $values['limit']  = $limit;
    $values['offset'] = $offset;
    $data_res = DB::query('cdr_lega',
      "SELECT call_id AS xml_cdr_uuid, domain AS domain_name, direction,
              '' AS caller_id_name, caller_number AS caller_id_number,
              destination AS destination_number, start_time AS start_stamp,
              duration, billsec, hangup_cause,
              (answer_time IS NULL) AS missed_call
       FROM cdr_lega
       $sql_where
       ORDER BY id DESC
       LIMIT %limit% OFFSET %offset%",
      $values
    );

    $rows = array();
    if ($data_res) {
      while ($r = mysqli_fetch_assoc($data_res)) {
        $r['missed_call'] = (bool)$r['missed_call'];
        $r['duration']    = (int)$r['duration'];
        $r['billsec']     = (int)$r['billsec'];
        $rows[] = $r;
      }
    }

    return ['rows' => $rows, 'total' => $total];
  }

  /**
   * Extension numbers registered to the current user's FusionPBX domain.
   */
  private function _tenant_extensions()
  {
    $domain_uuid = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
    if (!$domain_uuid) return array();
    try {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT extension FROM v_extensions WHERE domain_uuid = ?");
      $stmt->execute([$domain_uuid]);
      return array_values(array_filter(array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN))));
    } catch (\Throwable $e) {
      return array();
    }
  }
}
