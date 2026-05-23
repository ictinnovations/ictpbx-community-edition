<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\Voicemail;
use ICT\Core\FpbxDomain;

#[\AllowDynamicProperties]
class VoicemailGreetingApi extends Api
{
  /**
   * Upload a greeting file for a voicemail box
   * Accepts multipart/form-data: file (wav/mp3) + greeting_name + voicemail_uuid
   *
   * @url POST /voicemail_greetings
   */
  public function upload($data = [])
  {
    $this->_authorize_pbx('voicemails', true);

    $voicemail_uuid = trim($_POST['voicemail_uuid'] ?? '');
    if (empty($voicemail_uuid)) throw new CoreException(400, 'voicemail_uuid required');

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      throw new CoreException(400, 'No valid file uploaded');
    }
    $file = $_FILES['file'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['wav', 'mp3', 'ogg'])) {
      throw new CoreException(400, 'Only WAV, MP3, or OGG files are allowed');
    }

    $oVoicemail = new Voicemail($voicemail_uuid);
    if (empty($oVoicemail->voicemail_uuid)) throw new CoreException(404, 'Voicemail not found');
    $this->_assert_pbx_domain($oVoicemail);

    $pdo = FpbxDomain::fpbx_db();

    // Resolve domain name for file path
    $stmt = $pdo->prepare("SELECT domain_name FROM v_domains WHERE domain_uuid = ? LIMIT 1");
    $stmt->execute([$oVoicemail->domain_uuid]);
    $domain = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$domain) throw new CoreException(500, 'Domain not found');

    // Next greeting_id for this voicemail
    $stmt = $pdo->prepare(
      "SELECT COALESCE(MAX(greeting_id::int), 0) + 1 AS next_id
       FROM v_voicemail_greetings
       WHERE voicemail_id = ? AND domain_uuid = ?"
    );
    $stmt->execute([$oVoicemail->voicemail_id, $oVoicemail->domain_uuid]);
    $row        = $stmt->fetch(\PDO::FETCH_ASSOC);
    $greeting_id = (int)($row['next_id'] ?? 1);

    $greeting_name = trim($_POST['greeting_name'] ?? '') ?: "Greeting $greeting_id";
    $filename      = "greeting_{$greeting_id}.wav";
    $dir           = "/var/lib/freeswitch/storage/voicemail/{$domain['domain_name']}/{$oVoicemail->voicemail_id}";

    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
      throw new CoreException(500, 'Could not create storage directory');
    }

    $filepath = "$dir/$filename";
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
      throw new CoreException(500, 'Failed to save greeting file');
    }

    $base64 = base64_encode(file_get_contents($filepath));
    $uuid   = sprintf('%08x-%04x-%04x-%04x-%012x',
      mt_rand(0, 0xffffffff), mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffffffffffff));

    $stmt = $pdo->prepare(
      "INSERT INTO v_voicemail_greetings
         (voicemail_greeting_uuid, domain_uuid, voicemail_id, greeting_id,
          greeting_name, greeting_filename, greeting_base64, insert_date)
       VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
      $uuid, $oVoicemail->domain_uuid, $oVoicemail->voicemail_id,
      $greeting_id, $greeting_name, $filename, $base64,
    ]);

    return [
      'voicemail_greeting_uuid' => $uuid,
      'greeting_id'             => $greeting_id,
      'greeting_name'           => $greeting_name,
      'greeting_filename'       => $filename,
      'greeting_description'    => '',
    ];
  }

  /**
   * Delete a greeting
   *
   * @url DELETE /voicemail_greetings/$greeting_uuid
   */
  public function remove($greeting_uuid)
  {
    $this->_authorize_pbx('voicemails', true);

    $pdo  = FpbxDomain::fpbx_db();
    $stmt = $pdo->prepare(
      "SELECT vg.greeting_filename, vg.voicemail_id, vg.domain_uuid, d.domain_name
       FROM v_voicemail_greetings vg
       JOIN v_domains d ON d.domain_uuid = vg.domain_uuid
       WHERE vg.voicemail_greeting_uuid = ?"
    );
    $stmt->execute([$greeting_uuid]);
    $g = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$g) throw new CoreException(404, 'Greeting not found');

    // Tenant scope check
    $is_admin = \ICT\Core\can_access('super_admin', $this->oUser->user_id);
    if (!$is_admin) {
      $my_domain = FpbxDomain::get_domain_uuid($this->oUser->tenant_id);
      if ($g['domain_uuid'] !== $my_domain) throw new CoreException(403, 'Access denied');
    }

    // Delete file from disk
    $filepath = "/var/lib/freeswitch/storage/voicemail/{$g['domain_name']}/{$g['voicemail_id']}/{$g['greeting_filename']}";
    if (is_file($filepath)) @unlink($filepath);

    $del = $pdo->prepare("DELETE FROM v_voicemail_greetings WHERE voicemail_greeting_uuid = ?");
    $del->execute([$greeting_uuid]);

    return ['status' => 'deleted'];
  }
}
