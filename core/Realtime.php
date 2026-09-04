<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;

#[\AllowDynamicProperties]
class Realtime
{
  private static $fs_cli = '/usr/bin/fs_cli';
  private static $esl    = null;

  public static function get_snapshot()
  {
    $channels      = self::run_cmd('show channels as json');
    $registrations = self::run_cmd('show registrations as json');
    $gateways_raw  = self::run_cmd('sofia status gateway');

    $ch_data  = json_decode($channels, true);
    $reg_data = json_decode($registrations, true);

    $channel_rows = [];
    if (!empty($ch_data['rows']) && is_array($ch_data['rows'])) {
      foreach ($ch_data['rows'] as $row) {
        $channel_rows[] = [
          'uuid'        => $row['uuid'] ?? '',
          'direction'   => $row['direction'] ?? '',
          'created'     => $row['created'] ?? '',
          'name'        => $row['name'] ?? '',
          'state'       => $row['state'] ?? '',
          'cid_name'    => $row['cid_name'] ?? '',
          'cid_num'     => $row['cid_num'] ?? '',
          'dest'        => $row['dest'] ?? '',
          'application' => $row['application'] ?? '',
          'read_codec'  => $row['read_codec'] ?? '',
          'write_codec' => $row['write_codec'] ?? '',
          'callstate'   => $row['callstate'] ?? '',
          'secure'      => $row['secure'] ?? '',
          'ip_addr'     => $row['ip_addr'] ?? '',
          'hostname'    => $row['hostname'] ?? '',
        ];
      }
    }

    $reg_rows = [];
    if (!empty($reg_data['rows']) && is_array($reg_data['rows'])) {
      foreach ($reg_data['rows'] as $row) {
        $reg_rows[] = [
          'reg_user'   => $row['reg_user'] ?? '',
          'realm'      => $row['realm'] ?? '',
          'token'      => $row['token'] ?? '',
          'url'        => $row['url'] ?? '',
          'expires'    => $row['expires'] ?? '',
          'network_ip' => $row['network_ip'] ?? '',
          'user_agent' => $row['user_agent'] ?? '',
          'status'     => $row['status'] ?? '',
          'hostname'   => $row['hostname'] ?? '',
        ];
      }
    }

    return [
      'channel_count'      => $ch_data['row_count'] ?? 0,
      'registration_count' => $reg_data['row_count'] ?? 0,
      'channels'           => $channel_rows,
      'registrations'      => $reg_rows,
      'gateways_raw'       => $gateways_raw ?: '',
    ];
  }

  public static function hangup(string $uuid): void
  {
    self::run_cmd('uuid_kill ' . escapeshellarg($uuid));
  }

  public static function hold(string $uuid): void
  {
    self::run_cmd('uuid_hold ' . escapeshellarg($uuid));
  }

  public static function transfer(string $uuid, string $dest): void
  {
    $safe_dest = preg_replace('/[^0-9a-zA-Z_\-+#*]/', '', $dest);
    self::run_cmd('uuid_transfer ' . escapeshellarg($uuid) . ' ' . $safe_dest . ' XML default');
  }

  /**
   * Parse [freeswitch] section of /usr/ictcore/etc/ictcore.conf once and
   * cache the ESL host/port/password so fs_cli works regardless of
   * which user runs it (apache/php-fpm runs as `ictcore`, which has no
   * /home/ictcore/.fs_cli_conf — bare fs_cli fails with "Error Connecting").
   */
  private static function esl_args(): string
  {
    if (self::$esl !== null) return self::$esl;
    $conf = '/usr/ictcore/etc/ictcore.conf';
    $host = '127.0.0.1';
    $port = '8021';
    $pass = '';
    if (is_readable($conf)) {
      $in_section = false;
      foreach (file($conf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === ';' || $line[0] === '#') continue;
        if (preg_match('/^\[([^\]]+)\]$/', $line, $m)) {
          $in_section = (strtolower($m[1]) === 'freeswitch');
          continue;
        }
        if (!$in_section) continue;
        if (preg_match('/^([a-z_]+)\s*=\s*(.*)$/i', $line, $m)) {
          $k = strtolower(trim($m[1]));
          $v = trim($m[2], " \t\"'");
          if ($k === 'host')     $host = $v;
          if ($k === 'port')     $port = $v;
          if ($k === 'password') $pass = $v;
        }
      }
    }
    self::$esl = '-H ' . escapeshellarg($host)
               . ' -P ' . escapeshellarg($port)
               . ($pass !== '' ? ' -p ' . escapeshellarg($pass) : '');
    return self::$esl;
  }

  public static function run_cmd($cmd)
  {
    if (trim($cmd) === 'reloadxml') {
      // Touch parent wrapper files so FS re-expands their X-PRE-PROCESS glob includes.
      // Without this, reloadxml flushes the cache but skips re-expansion of unchanged parent files.
      @touch('/etc/freeswitch/dialplan/ictcore.xml');
      @touch('/etc/freeswitch/directory/fpbx_webrtc.xml');
    }
    $args    = self::esl_args();
    $escaped = escapeshellarg($cmd);
    $output  = shell_exec(self::$fs_cli . ' ' . $args . ' -x ' . $escaped . ' 2>/dev/null');
    return $output ?: '{"row_count":0}';
  }
}
