<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Website : http://www.ictinnovations.com/                        *
 * *************************************************************** */

use Exception;
use Swift_Mailer;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;

global $path_root;
require_once $path_root . '/vendor/swiftmailer/swiftmailer/lib/swift_required.php';

/**
 * Lightweight synchronous email sender.
 *
 * Reuses the [sendmail] section of ictcore.conf (same config that the
 * Gateway\Sendmail spool pipeline uses) but sends inline via SwiftMailer,
 * without creating a Program/Transmission/Spool. Intended for short
 * transactional alerts (e.g. low-credit notifications) where the async
 * spool pipeline would be overkill.
 *
 * Failures are non-fatal: they are logged and the method returns false,
 * so a mail outage never breaks the caller (billing, etc.).
 */
class Notification
{
  /**
   * @param string $to_email recipient address
   * @param string $subject
   * @param string $body_html HTML body
   * @param string|null $body_alt optional plain-text alternative
   * @return bool true on success, false on any failure
   */
  public static function send($to_email, $subject, $body_html, $body_alt = null)
  {
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
      Corelog::log("Notification::send skipped, invalid recipient: " . $to_email, Corelog::WARNING);
      return false;
    }

    try {
      $transport = self::build_transport();
      if ($transport === null) {
        Corelog::log("Notification::send skipped, no mail transport configured", Corelog::WARNING);
        return false;
      }

      $from = Conf::get('sendmail:user', '');
      if (empty($from)) {
        $from = 'noreply@' . Conf::get('sendmail:domain', 'localhost');
      }

      $message = Swift_Message::newInstance();
      $message->setTo($to_email);
      $message->setFrom($from);
      $message->setSubject($subject);
      $message->setBody($body_html, 'text/html');
      if (!empty($body_alt)) {
        $message->addPart($body_alt, 'text/plain');
      }

      $mailer = Swift_Mailer::newInstance($transport);
      $sent = $mailer->send($message);

      Corelog::log("Notification email to $to_email ('$subject') sent=$sent", Corelog::CRUD);
      return $sent > 0;
    } catch (Exception $e) {
      Corelog::log("Notification::send failed: " . $e->getMessage(), Corelog::ERROR);
      return false;
    }
  }

  /**
   * Builds a SwiftMailer transport from the [sendmail] config section.
   * Mirrors Gateway\Sendmail::default_route() + connect().
   */
  protected static function build_transport()
  {
    $type = Conf::get('sendmail:type', 'sendmail');
    switch ($type) {
      case 'smtp':
        $host = Conf::get('sendmail:host', '127.0.0.1');
        $port = Conf::get('sendmail:port', '25');
        $transport = Swift_SmtpTransport::newInstance($host, $port);
        $encryption = Conf::get('sendmail:encryption', null);
        if (!empty($encryption)) {
          $transport->setEncryption($encryption);
        }
        $user = Conf::get('sendmail:user', '');
        if (!empty($user)) {
          $transport->setUsername($user);
          $transport->setPassword(Conf::get('sendmail:pass', ''));
        }
        return $transport;
      case 'sendmail':
      default:
        $cli = Conf::get('sendmail:cli', '/usr/sbin/sendmail -bs');
        return Swift_SendmailTransport::newInstance($cli);
    }
  }
}
