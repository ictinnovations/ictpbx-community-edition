<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Corelog;
use ICT\Core\DB;
use ICT\Core\FpbxDomain;
use SplFileInfo;

#[\AllowDynamicProperties]
class CdrApi extends Api
{
  /**
   * List all CDR rows (lega + legb joined on call_id).
   *
   * @url GET /cdr
   * @url GET /cdr/list
   */
  public function list_view($query = [])
  {
    $this->_authorize('cdr_list');

    $limit  = isset($query['limit'])  ? (int)$query['limit']  : 500;
    $offset = isset($query['offset']) ? (int)$query['offset'] : 0;
    if ($limit  < 1 || $limit  > 5000) $limit  = 500;
    if ($offset < 0)                   $offset = 0;

    $esc = function($v) { return mysqli_real_escape_string(\ICT\Core\DB::$link, $v); };
    $where = [];
    if (!empty($query['domain']))       $where[] = "a.domain       = '" . $esc($query['domain'])       . "'";
    if (!empty($query['hangup_cause'])) $where[] = "a.hangup_cause = '" . $esc($query['hangup_cause']) . "'";
    if (!empty($query['direction']))    $where[] = "a.direction    = '" . $esc($query['direction'])    . "'";
    if (!empty($query['call_type']))    $where[] = "a.call_type    = '" . $esc($query['call_type'])    . "'";
    if (!empty($query['start_date']))   $where[] = "a.start_time  >= '" . $esc($query['start_date'])   . "'";
    if (!empty($query['end_date']))     $where[] = "a.start_time   < '" . $esc($query['end_date'])     . "'";

    // Scope non-admins to their tenant's FusionPBX domain
    if (!\ICT\Core\can_access('super_admin')) {
      $domain_uuid = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
      $domain_name = $domain_uuid ? FpbxDomain::get_domain_name($domain_uuid) : null;
      if ($domain_name) {
        $where[] = "a.domain = '" . $esc($domain_name) . "'";
      } else {
        return []; // tenant has no PBX domain — no CDR to show
      }
    }

    $where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT a.id, a.call_id,
                   COALESCE(a.caller_number, b.caller_number) AS caller_number,
                   a.destination, a.context,
                   a.direction, a.call_type, a.remote_ip, a.last_app,
                   a.start_time, a.answer_time, a.end_time,
                   a.duration, a.billsec, a.hangup_cause, a.domain
            FROM cdr_lega a
            LEFT JOIN cdr_legb b ON a.call_id = b.call_id
            $where_sql
            ORDER BY a.start_time DESC
            LIMIT $limit OFFSET $offset";
    Corelog::log("cdr list query: $sql", Corelog::INFO);

    $result = DB::query('cdr_lega', $sql);
    $rows   = [];
    while ($row = mysqli_fetch_assoc($result)) {
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Export CDR list as CSV.
   *
   * @url GET /cdr/csv
   */
  public function export_csv($query = [])
  {
    $this->_authorize('cdr_list');
    $query['limit'] = 5000;
    $rows = $this->list_view($query);

    $file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export_cdr.csv';
    $handle = fopen($file_path, 'w');
    if (!$handle) throw new CoreException(500, 'Unable to open CSV file');
    fwrite($handle, '"Start","Direction","Type","Caller","Destination","Duration","Billsec","Hangup Cause","Remote IP","UUID"' . "\n");
    foreach ($rows as $r) {
      fwrite($handle, '"'
        . ($r['start_time']    ?? '') . '","'
        . ($r['direction']     ?? '') . '","'
        . ($r['call_type']     ?? '') . '","'
        . ($r['caller_number'] ?? '') . '","'
        . ($r['destination']   ?? '') . '","'
        . ($r['duration']      ?? '') . '","'
        . ($r['billsec']       ?? '') . '","'
        . ($r['hangup_cause']  ?? '') . '","'
        . ($r['remote_ip']     ?? '') . '","'
        . ($r['call_id']       ?? '') . '"' . "\n");
    }
    fclose($handle);
    return new SplFileInfo($file_path);
  }
}
