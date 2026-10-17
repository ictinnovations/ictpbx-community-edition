<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class MusicOnHold
{
  // FreeSWITCH serves local_stream.conf.xml statically; it includes one
  // <directory> file per category from here (see installer).
  const STREAM_DIR  = '/usr/ictcore/etc/freeswitch/local_stream';
  // Uploaded audio, one directory per category. setgid freeswitch:daemon tree,
  // writable by php-fpm (ictcore is in group daemon) and readable by FreeSWITCH.
  const FILES_ROOT  = '/var/lib/freeswitch/storage/music_on_hold';
  const STREAM_CONF = '/etc/freeswitch/autoload_configs/local_stream.conf.xml';
  const MAX_FILE_BYTES = 20971520;

  public $music_on_hold_uuid       = null;
  public $domain_uuid              = null;
  public $tenant_id                = null;
  public $music_on_hold_name       = null;
  public $music_on_hold_path       = null;
  public $music_on_hold_rate       = 8000;
  public $music_on_hold_shuffle    = 'true';
  public $music_on_hold_channels   = 1;
  public $music_on_hold_interval   = 20;
  public $music_on_hold_timer_name = 'soft';
  public $music_on_hold_chime_list = null;
  public $music_on_hold_chime_freq = 0;
  public $music_on_hold_chime_max  = 0;
  public $music_on_hold_stream     = null;

  private $loaded_stream = null;

  public function __construct($music_on_hold_uuid = null)
  {
    if ($music_on_hold_uuid !== null) {
      $pdo  = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_music_on_hold WHERE music_on_hold_uuid = ?");
      $stmt->execute([$music_on_hold_uuid]);
      $row  = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) { throw new CoreException(404, 'Music on Hold not found'); }
      foreach ($row as $k => $v) { $this->$k = $v; }
      $this->music_on_hold_stream = self::stream_name($this->music_on_hold_name, self::domain_name($this->domain_uuid));
      $this->loaded_stream = $this->music_on_hold_stream;
    }
  }

  public static function search($aFilter = [])
  {
    $tenant_id   = isset($aFilter['tenant_id']) ? $aFilter['tenant_id'] : null;
    $domain_uuid = FpbxDomain::get_domain_uuid($tenant_id);
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT m.domain_uuid, m.music_on_hold_uuid, m.music_on_hold_name, m.music_on_hold_path,
              m.music_on_hold_shuffle, d.domain_name
       FROM v_music_on_hold m LEFT JOIN v_domains d ON d.domain_uuid = m.domain_uuid
       WHERE m.domain_uuid = ? ORDER BY m.music_on_hold_name"
    );
    $stmt->execute([$domain_uuid]);
    $rows = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    foreach ($rows as $row) {
      $row->music_on_hold_stream = self::stream_name($row->music_on_hold_name, $row->domain_name);
      unset($row->domain_name);
    }
    return $rows;
  }

  /**
   * The local_stream name FreeSWITCH knows this category by, e.g. "jazz@acme.local".
   * Scoped by domain so two tenants can both have a "jazz" category.
   */
  public static function stream_name($name, $domain_name)
  {
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$name)), '-');
    if ($slug === '' || empty($domain_name)) return null;
    return $slug . '@' . $domain_name;
  }

  public function files_dir()
  {
    return self::FILES_ROOT . '/' . self::domain_name($this->domain_uuid) . '/' . $this->music_on_hold_uuid;
  }

  /** Uploads go only into the managed directory, never into a custom server path. */
  public function uses_managed_dir()
  {
    return $this->music_on_hold_path === $this->files_dir();
  }

  public function save()
  {
    $domain_uuid       = FpbxDomain::get_domain_uuid($this->tenant_id);
    // An existing category stays in its own domain when an admin edits it.
    $this->domain_uuid = $this->domain_uuid ?: $domain_uuid;
    $domain_uuid       = $this->domain_uuid;
    $pdo               = FpbxDomain::fpbx_db();

    if (self::domain_name($domain_uuid) === null) {
      throw new CoreException(412, 'Music on Hold domain not found');
    }
    $stream = self::stream_name($this->music_on_hold_name, self::domain_name($domain_uuid));
    if ($stream === null) {
      throw new CoreException(412, 'Music on Hold name must contain letters or digits');
    }
    $others = $pdo->prepare("SELECT music_on_hold_name FROM v_music_on_hold WHERE domain_uuid = ? AND music_on_hold_uuid IS DISTINCT FROM ?");
    $others->execute([$domain_uuid, $this->music_on_hold_uuid]);
    foreach ($others->fetchAll(PDO::FETCH_COLUMN) as $other) {
      if (self::stream_name($other, self::domain_name($domain_uuid)) === $stream) {
        throw new CoreException(409, "A Music on Hold category named '{$other}' already exists in this domain.");
      }
    }

    $is_new = empty($this->music_on_hold_uuid);
    if ($is_new) {
      $this->music_on_hold_uuid = self::generate_uuid();
    }
    if (empty($this->music_on_hold_path)) {
      $this->music_on_hold_path = $this->files_dir();
    }

    $fields = [
      'domain_uuid'              => $domain_uuid,
      'music_on_hold_name'       => $this->music_on_hold_name,
      'music_on_hold_path'       => $this->music_on_hold_path,
      'music_on_hold_rate'       => $this->music_on_hold_rate ?: 8000,
      'music_on_hold_shuffle'    => $this->music_on_hold_shuffle === 'false' ? 'false' : 'true',
      'music_on_hold_channels'   => $this->music_on_hold_channels ?: 1,
      'music_on_hold_interval'   => $this->music_on_hold_interval ?: 20,
      'music_on_hold_timer_name' => $this->music_on_hold_timer_name ?: 'soft',
      'music_on_hold_chime_list' => $this->music_on_hold_chime_list ?: null,
      'music_on_hold_chime_freq' => $this->music_on_hold_chime_freq ?: 0,
      'music_on_hold_chime_max'  => $this->music_on_hold_chime_max ?: 0,
    ];

    try {
      if ($is_new) {
        $fields['music_on_hold_uuid'] = $this->music_on_hold_uuid;
        $cols = implode(', ', array_keys($fields));
        $phs  = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO v_music_on_hold ($cols) VALUES ($phs)")
            ->execute(array_values($fields));
      } else {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
        $pdo->prepare("UPDATE v_music_on_hold SET $set WHERE music_on_hold_uuid = ?")
            ->execute([...array_values($fields), $this->music_on_hold_uuid]);
      }
    } catch (\PDOException $e) {
      if ($is_new) $this->music_on_hold_uuid = null;
      throw new CoreException(500, 'Music on Hold save failed: ' . $e->getMessage());
    }

    if ($this->uses_managed_dir() && !is_dir($this->files_dir())) {
      @mkdir($this->files_dir(), 02775, true);
    }
    $this->music_on_hold_stream = $stream;
    $this->sync_fs_stream();
    $this->loaded_stream = $stream;
    return $this->music_on_hold_uuid;
  }

  public function delete()
  {
    if (empty($this->music_on_hold_uuid)) { throw new CoreException(400, 'Missing uuid'); }
    // Guard: block deletion if any call queue uses this MoH, by name or by stream reference
    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT queue_name FROM v_call_center_queues
       WHERE domain_uuid = ? AND queue_moh_sound IN (?, ?) LIMIT 5"
    );
    $stmt->execute([$this->domain_uuid, $this->music_on_hold_name, 'local_stream://' . $this->music_on_hold_stream]);
    $queues = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    if (!empty($queues)) {
      throw new CoreException(409,
        "Cannot delete: used by Call Queue(s): " . implode(', ', $queues) . ". Update those queues first.");
    }
    $pdo->prepare("DELETE FROM v_music_on_hold WHERE music_on_hold_uuid = ?")
        ->execute([$this->music_on_hold_uuid]);

    if ($this->uses_managed_dir()) {
      foreach ($this->list_files() as $f) { @unlink($this->files_dir() . '/' . $f['name']); }
      @rmdir($this->files_dir());
    }
    @unlink(self::STREAM_DIR . '/' . $this->music_on_hold_uuid . '.xml');
    self::reload_fs();
    if ($this->loaded_stream) Realtime::run_cmd('local_stream stop ' . $this->loaded_stream);
    return true;
  }

  /** Audio files in the managed directory, each with a stable id for delete. */
  public function list_files()
  {
    $dir = $this->files_dir();
    if (!$this->uses_managed_dir() || !is_dir($dir)) return [];
    $files = [];
    foreach (scandir($dir) as $name) {
      if (!preg_match('/\.(wav|mp3)$/i', $name) || !is_file("$dir/$name")) continue;
      $files[] = [
        'file_id'  => substr(sha1($name), 0, 16),
        'name'     => $name,
        'size'     => filesize("$dir/$name"),
        'modified' => date('Y-m-d H:i:s', filemtime("$dir/$name")),
      ];
    }
    return $files;
  }

  public function add_file($tmp_path, $original_name)
  {
    if (!$this->uses_managed_dir()) {
      throw new CoreException(409, 'This category plays from a custom server path. Clear the path to use uploaded files.');
    }
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['wav', 'mp3'])) {
      throw new CoreException(400, 'Only WAV or MP3 files are allowed');
    }
    if (filesize($tmp_path) > self::MAX_FILE_BYTES) {
      throw new CoreException(413, 'File is larger than 20 MB');
    }
    $head = (string)file_get_contents($tmp_path, false, null, 0, 12);
    $is_wav = substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WAVE';
    $is_mp3 = substr($head, 0, 3) === 'ID3' || (strlen($head) > 1 && ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0);
    if (($ext === 'wav' && !$is_wav) || ($ext === 'mp3' && !$is_mp3)) {
      throw new CoreException(400, 'The file content is not a valid ' . strtoupper($ext) . ' file');
    }

    $dir = $this->files_dir();
    if (!is_dir($dir) && !@mkdir($dir, 02775, true)) {
      throw new CoreException(500, 'Could not create the music directory');
    }
    $base = trim(preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($original_name, PATHINFO_FILENAME)), '._') ?: 'music';
    $name = "$base.$ext";
    for ($i = 2; file_exists("$dir/$name"); $i++) { $name = "{$base}_{$i}.$ext"; }
    if (!move_uploaded_file($tmp_path, "$dir/$name")) {
      throw new CoreException(500, 'Failed to save the music file');
    }
    @chmod("$dir/$name", 0664);
    $this->restart_fs_stream();
    foreach ($this->list_files() as $f) { if ($f['name'] === $name) return $f; }
    return ['name' => $name];
  }

  public function delete_file($file_id)
  {
    foreach ($this->list_files() as $f) {
      if ($f['file_id'] === $file_id) {
        @unlink($this->files_dir() . '/' . $f['name']);
        $this->restart_fs_stream();
        return true;
      }
    }
    throw new CoreException(404, 'File not found');
  }

  /** Write this category's <directory> entry and (re)start its stream. */
  private function sync_fs_stream()
  {
    if (!is_dir(self::STREAM_DIR)) @mkdir(self::STREAM_DIR, 0755, true);
    $e = fn($v) => htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES);
    $xml  = "<directory name=\"{$e($this->music_on_hold_stream)}\" path=\"{$e($this->music_on_hold_path)}\">\n";
    $xml .= "  <param name=\"rate\" value=\"" . (int)($this->music_on_hold_rate ?: 8000) . "\"/>\n";
    $xml .= "  <param name=\"shuffle\" value=\"" . ($this->music_on_hold_shuffle === 'false' ? 'false' : 'true') . "\"/>\n";
    $xml .= "  <param name=\"channels\" value=\"" . (int)($this->music_on_hold_channels ?: 1) . "\"/>\n";
    $xml .= "  <param name=\"interval\" value=\"" . (int)($this->music_on_hold_interval ?: 20) . "\"/>\n";
    $xml .= "  <param name=\"timer-name\" value=\"{$e($this->music_on_hold_timer_name ?: 'soft')}\"/>\n";
    if (!empty($this->music_on_hold_chime_list)) {
      $xml .= "  <param name=\"chime-list\" value=\"{$e($this->music_on_hold_chime_list)}\"/>\n";
      $xml .= "  <param name=\"chime-freq\" value=\"" . (int)$this->music_on_hold_chime_freq . "\"/>\n";
      $xml .= "  <param name=\"chime-max\" value=\"" . (int)$this->music_on_hold_chime_max . "\"/>\n";
    }
    $xml .= "</directory>\n";
    if (file_put_contents(self::STREAM_DIR . '/' . $this->music_on_hold_uuid . '.xml', $xml) === false) {
      throw new CoreException(500, 'Could not write the FreeSWITCH music stream config');
    }
    self::reload_fs();
    if ($this->loaded_stream && $this->loaded_stream !== $this->music_on_hold_stream) {
      Realtime::run_cmd('local_stream stop ' . $this->loaded_stream);
    }
    $this->restart_fs_stream();
  }

  /**
   * Make the stream pick up new settings and files. A running stream is reloaded in
   * place; stop+start would race, since stop only signals the stream thread.
   */
  private function restart_fs_stream()
  {
    $show = (string)Realtime::run_cmd('local_stream show ' . $this->music_on_hold_stream);
    $running = strpos($show, '-ERR') === false && strpos($show, $this->music_on_hold_stream) !== false;
    Realtime::run_cmd('local_stream ' . ($running ? 'reload ' : 'start ') . $this->music_on_hold_stream);
  }

  private static function reload_fs()
  {
    @touch(self::STREAM_CONF);   // force re-expansion of the include glob
    Realtime::run_cmd('reloadxml');
  }

  private static function domain_name($domain_uuid)
  {
    static $cache = [];
    if (empty($domain_uuid)) return null;
    if (!array_key_exists($domain_uuid, $cache)) {
      $stmt = FpbxDomain::fpbx_db()->prepare("SELECT domain_name FROM v_domains WHERE domain_uuid = ?");
      $stmt->execute([$domain_uuid]);
      $cache[$domain_uuid] = $stmt->fetchColumn() ?: null;
    }
    return $cache[$domain_uuid];
  }

  private static function generate_uuid()
  {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
      mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
      mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
  }
}
