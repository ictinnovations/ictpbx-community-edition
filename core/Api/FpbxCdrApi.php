<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\FpbxDomain;

#[\AllowDynamicProperties]
class FpbxCdrApi extends Api
{
  /**
   * List FusionPBX CDR records (admin: all domains; tenant: own domain)
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

    $pdo = FpbxDomain::fpbx_db();

    $where  = [];
    $params = [];

    if (!$is_admin) {
      $domain_uuid = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
      if (!$domain_uuid) return ['rows' => [], 'total' => 0];
      $where[]  = 'domain_uuid = ?';
      $params[] = $domain_uuid;
    }

    if ($start_date) {
      $where[]  = 'start_stamp >= ?';
      $params[] = $start_date . ' 00:00:00';
    }
    if ($end_date) {
      $where[]  = 'start_stamp <= ?';
      $params[] = $end_date . ' 23:59:59';
    }
    if ($direction && in_array($direction, ['inbound', 'outbound', 'local'])) {
      $where[]  = 'direction = ?';
      $params[] = $direction;
    }

    $sql_where = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM v_xml_cdr $sql_where");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();

    // data
    $data_params   = array_merge($params, [$limit, $offset]);
    $data_stmt     = $pdo->prepare(
      "SELECT xml_cdr_uuid, domain_name, direction,
              caller_id_name, caller_id_number, destination_number,
              start_stamp, duration, billsec, hangup_cause, missed_call
       FROM v_xml_cdr
       $sql_where
       ORDER BY start_stamp DESC
       LIMIT ? OFFSET ?"
    );
    $data_stmt->execute($data_params);
    $rows = $data_stmt->fetchAll(\PDO::FETCH_ASSOC);

    return ['rows' => $rows, 'total' => $total];
  }
}
