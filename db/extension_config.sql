-- extension_config: MariaDB companion table to v_extensions (PostgreSQL).
-- Stores extension_type (voice/fax) and related CE-friendly config.
-- Keyed by extension_uuid (UUID string from v_extensions).
-- Existing extensions without a row default to voice (backward compatible).
CREATE TABLE IF NOT EXISTS extension_config (
  extension_uuid  VARCHAR(36)  NOT NULL,
  tenant_id       INT          NOT NULL,
  extension_type  ENUM('voice','fax') NOT NULL DEFAULT 'voice',
  fax_email       VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (extension_uuid),
  INDEX idx_ec_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
