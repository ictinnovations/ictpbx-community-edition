<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Website : http://www.ictinnovations.com/                        *
 * *************************************************************** */

use Jacwright\RestServer\RestException;

/**
 * Programmatic REST credentials.
 *
 * A key authenticates AS its owning usr row, so every endpoint's existing
 * _authorize_pbx / _tenant_filter checks apply with no extra plumbing.
 * Only the sha256 hash is persisted; the plaintext is returned once at create.
 */
class ApiKey
{

  const PREFIX = 'ictp_';
  const TABLE  = 'api_key';

  /** @var bool set once the current request authenticated via an API key */
  private static $key_auth = false;

  public static function is_key_auth()
  {
    return self::$key_auth;
  }

  /**
   * Mint a new key. Returns plaintext (shown once), display prefix and hash.
   */
  public static function generate()
  {
    $secret = bin2hex(random_bytes(24));
    $plain  = self::PREFIX . $secret;
    return array(
      'plain'  => $plain,
      'prefix' => substr($plain, 0, 13),
      'hash'   => hash('sha256', $plain)
    );
  }

  /**
   * Resolve a presented key to its owning usr_id.
   * Returns null when the key is unknown, disabled or expired.
   *
   * @throws RestException 429 when the per-minute rate limit is exceeded.
   *   RestException (not CoreException) so it survives Api::authenticate's
   *   catch block and reaches the client as a real 429 instead of a 401.
   */
  public static function resolve($plain)
  {
    $plain = trim((string) $plain);
    if ($plain === '') {
      return null;
    }

    $hash = hash('sha256', $plain);
    $sql  = "SELECT api_key_id, usr_id, rate_limit, active, expires
             FROM " . self::TABLE . "
             WHERE key_hash = '%key_hash%'";
    $res  = DB::query(self::TABLE, $sql, array('key_hash' => $hash));
    $row  = ($res instanceof \mysqli_result) ? mysqli_fetch_assoc($res) : null;

    if (empty($row)) {
      Corelog::log('API key authentication failed: unknown key', Corelog::ERROR);
      return null;
    }
    if ((int) $row['active'] !== 1) {
      Corelog::log('API key authentication failed: key disabled', Corelog::ERROR);
      return null;
    }
    if (!empty($row['expires']) && strtotime($row['expires'] . ' UTC') < time()) {
      Corelog::log('API key authentication failed: key expired', Corelog::ERROR);
      return null;
    }

    self::_touch((int) $row['api_key_id'], (int) $row['rate_limit']);
    self::$key_auth = true;

    return (int) $row['usr_id'];
  }

  /**
   * Record usage and enforce the per-minute rate limit.
   * rate_limit of 0 means unlimited.
   */
  private static function _touch($api_key_id, $rate_limit)
  {
    $bucket = (int) floor(time() / 60);

    // window_count must be assigned BEFORE window_start: MySQL evaluates SET
    // left to right, so reading the old window_start requires it to still hold
    // the previous bucket at that point.
    $sql = "UPDATE " . self::TABLE . "
            SET window_count = IF(window_start = %bucket%, window_count + 1, 1),
                window_start = %bucket%,
                last_used    = UTC_TIMESTAMP()
            WHERE api_key_id = %api_key_id%";
    DB::query(self::TABLE, $sql, array('bucket' => $bucket, 'api_key_id' => $api_key_id));

    if ($rate_limit <= 0) {
      return;
    }

    $used = DB::query_result(
      self::TABLE,
      "SELECT window_count FROM " . self::TABLE . " WHERE api_key_id = %api_key_id%",
      'window_count',
      array('api_key_id' => $api_key_id)
    );

    if ((int) $used > $rate_limit) {
      throw new RestException(429, 'API key rate limit exceeded');
    }
  }

}
