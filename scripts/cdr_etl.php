<?php
/**
 * cdr_etl.php — ingest FreeSWITCH mod_cdr_csv Master.csv into ictfax.cdr_lega + cdr_legb.
 * Idempotent via byte-offset watermark in /usr/ictcore/cache/cdr_etl.state.
 *
 * Custom "ictcore" CSV template (16 fields, see /etc/freeswitch/autoload_configs/cdr_csv.conf.xml):
 *   1 caller_id_name      2 caller_id_number   3 destination_number   4 context
 *   5 start_stamp         6 answer_stamp       7 end_stamp            8 duration
 *   9 billsec            10 hangup_cause      11 uuid                12 bleg_uuid
 *  13 accountcode        14 last_app          15 call_direction      16 sip_network_ip
 *
 * Field 15 is the dialplan-exported ${call_direction} (outbound on outbound-route
 * legs, inbound on public DID legs). When empty/invalid (e.g. ext-to-ext bridge),
 * classify_direction() derives it from the caller/destination number pattern.
 *
 * Backward-compat: rows with fewer fields (legacy 15-field "example" template) parse OK;
 * the missing tail columns end up NULL.
 */

define('CONF_PATH',    '/usr/ictcore/etc/ictcore.conf');
define('MASTER_CSV',   '/var/log/freeswitch/cdr-csv/Master.csv');
define('STATE_FILE',   '/usr/ictcore/cache/cdr_etl.state');
define('STATUS_FILE',  '/usr/ictcore/cache/cdr_etl_status.json');
define('BACKFILL_DIR', '/tmp');

function read_section(string $section): array {
  if (!is_readable(CONF_PATH)) { fwrite(STDERR, "cdr_etl: ictcore.conf not readable\n"); exit(1); }
  $out = []; $in = false;
  foreach (file(CONF_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === ';' || $line[0] === '#') continue;
    if (preg_match('/^\[([^\]]+)\]$/', $line, $m)) { $in = (strtolower($m[1]) === $section); continue; }
    if ($in && preg_match('/^([a-z_]+)\s*=\s*(.*)$/i', $line, $m)) {
      $out[strtolower($m[1])] = trim($m[2], " \t\"'");
    }
  }
  return $out;
}

function load_state(): array {
  if (!is_file(STATE_FILE)) return [];
  $j = json_decode(@file_get_contents(STATE_FILE) ?: '', true);
  return is_array($j) ? $j : [];
}
function save_state(array $state): void {
  @mkdir(dirname(STATE_FILE), 0775, true);
  file_put_contents(STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));
}

function write_status(array $data): void {
  file_put_contents(STATUS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function read_status(): array {
  if (!is_file(STATUS_FILE)) return ['running' => false, 'last_run' => null, 'inserted' => 0, 'status' => 'never_run'];
  $j = json_decode(@file_get_contents(STATUS_FILE) ?: '', true);
  return is_array($j) ? $j : ['running' => false, 'status' => 'unknown'];
}

function classify_type(string $last_app, string $destination, string $context): string {
  $app = strtolower($last_app);
  if ($app === 'txfax' || $app === 'rxfax' || strpos($app, 'fax') !== false) return 'fax';
  if ($app === 'conference') return 'conf';
  if ($app === 'voicemail') return 'voicemail';
  if ($destination === '*99' || strpos($destination, '*99') === 0) return 'fax';
  if ($destination === '*98' || strpos($destination, '*98') === 0) return 'voice';
  if (preg_match('/^3\d{3}$/', $destination)) return 'conf';
  return 'voice';
}

/**
 * Fallback call-direction classifier for rows where the dialplan-exported
 * ${call_direction} is empty/invalid (e.g. local ext-to-ext bridges, which
 * don't export it). Local extension = 4-6 bare digits; anything else (E.164,
 * 7+ digits, leading +) is external.
 *   local  -> external = outbound
 *   external -> local  = inbound
 *   local  -> local    = internal
 * Returns null when both ends are external/unknown (leave as logged).
 */
function classify_direction(string $caller, string $destination): ?string {
  $is_local = function (string $n): bool {
    return preg_match('/^\d{4,6}$/', trim($n)) === 1;
  };
  $caller_local = $is_local($caller);
  $dest_local   = $is_local($destination);
  if ($caller_local && !$dest_local) return 'outbound';
  if (!$caller_local && $dest_local) return 'inbound';
  if ($caller_local && $dest_local)  return 'internal';
  return null;
}

function ts_or_null(?string $v): ?string {
  if ($v === null) return null;
  $v = trim($v);
  if ($v === '' || $v === '0' || strpos($v, '0000-00-00') === 0) return null;
  return $v;
}

function utf8s(?string $v): string {
  if ($v === null) return '';
  return mb_convert_encoding($v, 'UTF-8', 'UTF-8');
}

function parse_row(string $line): ?array {
  $f = str_getcsv($line, ',', '"');
  if (count($f) < 11) return null;
  $direction = strtolower(trim($f[14] ?? ''));
  if (!in_array($direction, ['inbound', 'outbound', 'internal'], true)) {
    $direction = classify_direction($f[1] ?? '', $f[2] ?? '');
  }
  return [
    'caller_id_name'   => utf8s($f[0]  ?? ''),
    'caller_number'    => utf8s($f[1]  ?? ''),
    'destination'      => utf8s($f[2]  ?? ''),
    'context'          => utf8s($f[3]  ?? ''),
    'start_time'       => ts_or_null($f[4]  ?? null),
    'answer_time'      => ts_or_null($f[5]  ?? null),
    'end_time'         => ts_or_null($f[6]  ?? null),
    'duration'         => (int)($f[7]  ?? 0),
    'billsec'          => (int)($f[8]  ?? 0),
    'hangup_cause'     => utf8s($f[9]  ?? ''),
    'uuid'             => utf8s($f[10] ?? ''),
    'accountcode'      => utf8s($f[12] ?? ''),
    'last_app'         => utf8s($f[13] ?? ''),
    'direction'        => $direction,
    'remote_ip'        => utf8s($f[15] ?? ''),
  ];
}

function ingest_file(\mysqli $db, string $path, int $start_offset, string $local_domain): array {
  if (!is_file($path) || !is_readable($path)) return ['inserted' => 0, 'offset' => $start_offset];
  $fh = fopen($path, 'r');
  if (!$fh) return ['inserted' => 0, 'offset' => $start_offset];
  if ($start_offset > 0 && filesize($path) >= $start_offset) fseek($fh, $start_offset);
  else { $start_offset = 0; fseek($fh, 0); }

  $stmt_a = $db->prepare(
    "INSERT INTO cdr_lega
       (call_id, caller_number, destination, context, direction, call_type, remote_ip, last_app,
        start_time, answer_time, end_time, duration, billsec, hangup_cause, domain)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE call_id = call_id"
  );
  $stmt_b = $db->prepare(
    "INSERT INTO cdr_legb (call_id, caller_number, context, start_time, answer_time, end_time, duration, billsec, hangup_cause, domain)
     VALUES (?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE call_id = call_id"
  );
  $check = $db->prepare("SELECT 1 FROM cdr_lega WHERE call_id = ? LIMIT 1");

  $inserted = 0;
  while (($line = fgets($fh)) !== false) {
    $r = parse_row($line);
    if (!$r || empty($r['uuid'])) continue;

    $check->bind_param('s', $r['uuid']);
    $check->execute(); $check->store_result();
    if ($check->num_rows > 0) { $check->free_result(); continue; }
    $check->free_result();

    $call_type = classify_type($r['last_app'], $r['destination'], $r['context']);

    $a_types = 'sssssssssssiiss';
    $a_vals  = [
      $r['uuid'], $r['caller_number'], $r['destination'], $r['context'],
      $r['direction'], $call_type, $r['remote_ip'], $r['last_app'],
      $r['start_time'], $r['answer_time'], $r['end_time'],
      $r['duration'], $r['billsec'], $r['hangup_cause'], $local_domain,
    ];
    $stmt_a->bind_param($a_types, ...$a_vals);
    $stmt_a->execute();

    $b_types = 'ssssssiiss';
    $b_vals  = [
      $r['uuid'], $r['caller_number'], $r['context'],
      $r['start_time'], $r['answer_time'], $r['end_time'],
      $r['duration'], $r['billsec'], $r['hangup_cause'], $local_domain,
    ];
    $stmt_b->bind_param($b_types, ...$b_vals);
    $stmt_b->execute();

    $inserted++;
  }
  $end_offset = ftell($fh);
  fclose($fh);
  return ['inserted' => $inserted, 'offset' => $end_offset];
}

// -------- main --------
write_status(['running' => true, 'started_at' => date('Y-m-d H:i:s'), 'status' => 'running']);

$db_conf = read_section('db');
$db = new \mysqli(
  $db_conf['host'] ?? 'localhost',
  $db_conf['user'] ?? 'ictfax',
  $db_conf['pass'] ?? '',
  $db_conf['name'] ?? 'ictfax',
  (int)($db_conf['port'] ?? 3306)
);
if ($db->connect_error) {
  write_status(['running' => false, 'status' => 'error', 'error' => $db->connect_error, 'last_run' => date('Y-m-d H:i:s')]);
  fwrite(STDERR, "cdr_etl: mysqli connect: " . $db->connect_error . "\n");
  exit(1);
}
$db->set_charset('utf8mb4');

$local_domain = gethostname() ?: '127.0.0.1';
$state = load_state();
$total = 0;

$offset = (int)($state[MASTER_CSV] ?? 0);
$res = ingest_file($db, MASTER_CSV, $offset, $local_domain);
$state[MASTER_CSV] = $res['offset'];
$total += $res['inserted'];
echo "Master.csv: +" . $res['inserted'] . " (offset " . $res['offset'] . ")\n";

foreach (glob(BACKFILL_DIR . '/Master.pre-*.csv') as $path) {
  if (!empty($state['done:' . $path])) continue;
  $res = ingest_file($db, $path, 0, $local_domain);
  $state['done:' . $path] = ['inserted' => $res['inserted'], 'at' => date('c')];
  $total += $res['inserted'];
  echo "backfill $path: +" . $res['inserted'] . "\n";
}

save_state($state);
write_status(['running' => false, 'status' => 'ok', 'last_run' => date('Y-m-d H:i:s'), 'inserted' => $total]);
echo "cdr_etl: inserted $total new rows\n";
$db->close();
