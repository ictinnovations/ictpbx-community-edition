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
    return true;
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
