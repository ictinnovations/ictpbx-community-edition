-- api_key — programmatic REST access tokens for tenants and users.
-- A key authenticates AS the owning `usr` row, so all existing RBAC
-- (_authorize_pbx / _tenant_filter) applies unchanged.
-- Only the sha256 hash is stored; the plaintext key is shown once at create time.
-- CE-safe: plain table create, no dependency on EE-only billing tables.
CREATE TABLE IF NOT EXISTS `api_key` (
  `api_key_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `tenant_id`    INT(11) DEFAULT NULL,
  `usr_id`       INT(11) NOT NULL,
  `name`         VARCHAR(64) NOT NULL,
  `key_prefix`   VARCHAR(16) NOT NULL,
  `key_hash`     CHAR(64) NOT NULL,
  `rate_limit`   INT(11) NOT NULL DEFAULT 60,
  `active`       TINYINT(1) NOT NULL DEFAULT 1,
  `expires`      DATETIME DEFAULT NULL,
  `last_used`    DATETIME DEFAULT NULL,
  `window_start` INT(11) NOT NULL DEFAULT 0,
  `window_count` INT(11) NOT NULL DEFAULT 0,
  `created`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`api_key_id`),
  UNIQUE KEY `uniq_api_key_hash` (`key_hash`),
  KEY `idx_api_key_tenant` (`tenant_id`),
  KEY `idx_api_key_usr` (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
