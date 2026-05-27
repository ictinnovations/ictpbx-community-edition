<?php

namespace ICT\Core;

use ICT\Core\FpbxDomain;
use PDO;

#[\AllowDynamicProperties]
class Voicemail
{
  public $voicemail_uuid                   = null;
  public $domain_uuid                      = null;
  public $tenant_id                        = null;
  public $voicemail_id                     = null;
  public $voicemail_password               = null;
  public $voicemail_mail_to                = null;
  public $voicemail_sms_to                 = null;
  public $voicemail_transcription_enabled  = false;
  public $voicemail_file                   = 'attach';
  public $voicemail_local_after_email      = true;
  public $voicemail_tutorial               = false;
  public $voicemail_recording_instructions = false;
  public $voicemail_recording_options      = false;
  public $voicemail_alternate_greet_id     = null;
  public $voicemail_enabled                = true;
  public $voicemail_description            = null;
  public $greetings                        = [];

  public function __construct($voicemail_uuid = null)
  {
    if ($voicemail_uuid !== null) {
      $pdo = FpbxDomain::fpbx_db();
      $stmt = $pdo->prepare("SELECT * FROM v_voicemails WHERE voicemail_uuid = ?");
      $stmt->execute([$voicemail_uuid]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) {
        throw new CoreException(404, 'Voicemail not found');
      }
      foreach ($row as $k => $v) {
        $this->$k = $v;
      }
      $this->greetings = self::fetch_greetings($voicemail_uuid);
    }
  }

  private static function fetch_greetings($voicemail_uuid)
  {
    $pdo = FpbxDomain::fpbx_db();
    // greetings are linked by voicemail_id + domain_uuid, not voicemail_uuid
    $stmt = $pdo->prepare(
      "SELECT vg.voicemail_greeting_uuid, vg.greeting_id, vg.greeting_name,
              vg.greeting_filename, vg.greeting_description
       FROM v_voicemail_greetings vg
       JOIN v_voicemails vm ON vm.voicemail_id = vg.voicemail_id AND vm.domain_uuid = vg.domain_uuid
       WHERE vm.voicemail_uuid = ?
       ORDER BY vg.greeting_id"
    );
    $stmt->execute([$voicemail_uuid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  public static function search($aFilter = [])
  {
    $tenant_id   = isset($aFilter['tenant_id']) ? $aFilter['tenant_id'] : null;
    $domain_filter = FpbxDomain::get_domain_filter($tenant_id); /* domain-filter-v2 */
    if ($domain_filter === false) return [];
    $params = $domain_filter ? ['domain_uuid' => $domain_filter] : []; /* params-fix-v3 */
    $where  = $domain_filter ? 'WHERE domain_uuid = :domain_uuid' : '';

    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT v.domain_uuid, voicemail_uuid, voicemail_id, voicemail_mail_to, voicemail_enabled, voicemail_description,
              (SELECT COUNT(*) FROM v_voicemail_greetings g
               WHERE g.voicemail_id = v.voicemail_id AND g.domain_uuid = v.domain_uuid) AS greeting_count
       FROM v_voicemails v
       " . $where . "
       ORDER BY voicemail_id"
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
  }

  public function save()
  {
    $domain_uuid = FpbxDomain::get_domain_uuid($this->tenant_id);
    if ($domain_uuid === null) { /* null-domain-guard */
      throw new \ICT\Core\CoreException(409, 'No FusionPBX domain assigned to this tenant. Contact an administrator.');
    }
    $this->domain_uuid = $domain_uuid;
    $pdo = FpbxDomain::fpbx_db();

    $conflict = FpbxDomain::extension_in_use($domain_uuid, $this->voicemail_id, $this->voicemail_uuid);
    if ($conflict !== null) {
      throw new CoreException(409, "Extension number {$this->voicemail_id} is already in use by a $conflict in this domain.");
    }

    $bool_fields = [
      'voicemail_transcription_enabled', 'voicemail_local_after_email',
      'voicemail_tutorial', 'voicemail_recording_instructions',
      'voicemail_recording_options', 'voicemail_enabled',
    ];

    $fields = [
      'domain_uuid', 'voicemail_id', 'voicemail_password',
      'voicemail_mail_to', 'voicemail_sms_to',
      'voicemail_transcription_enabled', 'voicemail_file',
      'voicemail_local_after_email', 'voicemail_tutorial',
      'voicemail_recording_instructions', 'voicemail_recording_options',
      'voicemail_alternate_greet_id', 'voicemail_enabled', 'voicemail_description',
    ];

    $values = [];
    foreach ($fields as $f) {
      $val = $this->$f;
      if (in_array($f, $bool_fields)) {
        $val = ($val === true || $val === 'true' || $val === 1 || $val === '1') ? 'true' : 'false';
      }
      $values[$f] = ($val === '') ? null : $val;
    }

    try {
      if (empty($this->voicemail_uuid)) {
        $this->voicemail_uuid = self::generate_uuid();
        $values['voicemail_uuid'] = $this->voicemail_uuid;
        $cols = implode(', ', array_keys($values));
        $phs  = implode(', ', array_fill(0, count($values), '?'));
        $pdo->prepare("INSERT INTO v_voicemails ($cols) VALUES ($phs)")
            ->execute(array_values($values));
      } else {
        $set  = implode(', ', array_map(fn($c) => "$c = ?", array_keys($values)));
        $pdo->prepare("UPDATE v_voicemails SET $set WHERE voicemail_uuid = ?")
            ->execute([...array_values($values), $this->voicemail_uuid]);
      }
    } catch (\PDOException $e) {
      throw new CoreException(500, 'Voicemail save failed: ' . $e->getMessage());
    }

    $this->sync_fs_dialplan();
    return $this->voicemail_uuid;
  }

  public function delete()
  {
    if (empty($this->voicemail_uuid)) {
      throw new CoreException(400, 'Missing voicemail_uuid');
    }
    $pdo = FpbxDomain::fpbx_db();
    // Guard: block deletion if any extension shares this voicemail number
    $stmt = $pdo->prepare(
      "SELECT extension FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension = :ext LIMIT 5"
    );
    $stmt->execute(['domain_uuid' => $this->domain_uuid, 'ext' => $this->voicemail_id]);
    $exts = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    if (!empty($exts)) {
      throw new CoreException(409,
        "Cannot delete: Extension(s) " . implode(', ', $exts) . " use this voicemail. Update those extensions first.");
    }
    // delete greetings first (by voicemail_id + domain_uuid)
    $pdo->prepare(
      "DELETE FROM v_voicemail_greetings WHERE voicemail_id = ? AND domain_uuid = ?"
    )->execute([$this->voicemail_id, $this->domain_uuid]);
    $pdo->prepare("DELETE FROM v_voicemails WHERE voicemail_uuid = ?")
        ->execute([$this->voicemail_uuid]);
    $this->sync_fs_dialplan(true);
    return true;
  }

  private function sync_fs_dialplan($delete = false)
  {
    $dp_dir   = '/usr/ictcore/etc/freeswitch/dialplan/voicemails';
    $dp_file  = $dp_dir . '/' . $this->voicemail_uuid . '.xml';
    $dir_dir  = '/usr/ictcore/etc/freeswitch/directory/voicemails';
    $dir_file = $dir_dir . '/' . $this->voicemail_uuid . '.xml';

    if ($delete) {
      if (file_exists($dp_file))  @unlink($dp_file);
      if (file_exists($dir_file)) @unlink($dir_file);
    } else {
      $domain   = FpbxDomain::get_domain_name($this->domain_uuid) ?: 'localhost';
      $vm_id    = htmlspecialchars((string)$this->voicemail_id, ENT_XML1, 'UTF-8');
      $e_dom    = htmlspecialchars($domain, ENT_XML1, 'UTF-8');
      $vm_pass  = htmlspecialchars((string)($this->voicemail_password ?: $this->voicemail_id), ENT_XML1, 'UTF-8');
      $vm_email = htmlspecialchars((string)($this->voicemail_mail_to ?: ''), ENT_XML1, 'UTF-8');
      $attach   = ($this->voicemail_file === 'attach') ? 'true' : 'false';
      $keep     = ($this->voicemail_local_after_email) ? 'true' : 'false';

      // Dialplan entry: route *99<id> to voicemail app
      if (!is_dir($dp_dir)) @mkdir($dp_dir, 0755, true);
      $dp_xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n<include>\n";
      $dp_xml .= "  <extension name=\"voicemail_{$vm_id}\" continue=\"false\">\n";
      $dp_xml .= "    <condition field=\"destination_number\" expression=\"^\\*99{$vm_id}\$\">\n";
      $dp_xml .= "      <action application=\"answer\"/>\n";
      $dp_xml .= "      <action application=\"voicemail\" data=\"default {$e_dom} {$vm_id}\"/>\n";
      $dp_xml .= "    </condition>\n  </extension>\n</include>\n";
      file_put_contents($dp_file, $dp_xml);

      // Directory entry: plain <user> element, included by ictcore_voicemails.xml inside <users> block
      if (!is_dir($dir_dir)) @mkdir($dir_dir, 0755, true);
      $dir_xml  = "<user id=\"{$vm_id}\">\n";
      $dir_xml .= "  <params>\n";
      $dir_xml .= "    <param name=\"vm-password\" value=\"{$vm_pass}\"/>\n";
      $dir_xml .= "    <param name=\"vm-mailto\" value=\"{$vm_email}\"/>\n";
      $dir_xml .= "    <param name=\"vm-attach-file\" value=\"{$attach}\"/>\n";
      $dir_xml .= "    <param name=\"vm-keep-local-after-email\" value=\"{$keep}\"/>\n";
      $dir_xml .= "  </params>\n";
      $dir_xml .= "  <variables>\n";
      $dir_xml .= "    <variable name=\"domain_name\" value=\"{$e_dom}\"/>\n";
      $dir_xml .= "    <variable name=\"user_context\" value=\"ictcore\"/>\n";
      $dir_xml .= "  </variables>\n";
      $dir_xml .= "</user>\n";
      file_put_contents($dir_file, $dir_xml);
    }

    @touch('/etc/freeswitch/dialplan/ictcore.xml');
    @touch('/etc/freeswitch/directory/fpbx_webrtc.xml');

    try {
      if (class_exists('\\ICT\\Core\\Realtime')) {
        \ICT\Core\Realtime::run_cmd('reloadxml');
      }
    } catch (\Throwable $ex) {
      Corelog::log("Voicemail reloadxml failed: " . $ex->getMessage(), Corelog::WARNING);
    }
  }

  private static function generate_uuid()
  {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }
}
