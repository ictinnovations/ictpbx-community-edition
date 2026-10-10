<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;

#[\AllowDynamicProperties]
class CdrEtlApi extends Api
{
  private static $etl_script  = '/usr/ictcore/scripts/cdr_etl.php';
  private static $status_file = '/usr/ictcore/cache/cdr_etl_status.json';
  private static $log_file    = '/var/log/ictcore/cdr_etl.log';

  /**
   * Get ETL last-run status
   *
   * @url GET /cdr_etl/status
   */
  public function status()
  {
    $this->_authorize('super_admin');
    if (!is_file(self::$status_file)) {
      return ['running' => false, 'status' => 'never_run', 'last_run' => null, 'inserted' => 0];
    }
    $data = json_decode(@file_get_contents(self::$status_file) ?: '', true);
    return is_array($data) ? $data : ['running' => false, 'status' => 'unknown'];
  }

  /**
   * Trigger ETL run (async background)
   *
   * @url POST /cdr_etl/run
   */
  public function run($data = [])
  {
    $this->_authorize('super_admin');

    // Guard: don't double-launch if already running
    if (is_file(self::$status_file)) {
      $current = json_decode(@file_get_contents(self::$status_file) ?: '', true);
      if (!empty($current['running'])) {
        return ['status' => 'already_running', 'started_at' => $current['started_at'] ?? null];
      }
    }

    if (!is_file(self::$etl_script)) {
      throw new CoreException(500, 'ETL script not found');
    }

    @mkdir(dirname(self::$log_file), 0775, true);
    $cmd = 'php ' . escapeshellarg(self::$etl_script)
         . ' >> ' . escapeshellarg(self::$log_file)
         . ' 2>&1 &';
    shell_exec($cmd);

    return ['status' => 'started'];
  }
}
