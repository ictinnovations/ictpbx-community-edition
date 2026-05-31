-- Adds FusionPBX gateway-equivalent columns to ictcore provider table so the
-- Add Provider form can mirror FPBX Add Gateway and project to v_gateways on save.
-- Idempotent; safe to re-run.
ALTER TABLE provider ADD COLUMN IF NOT EXISTS realm              VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS from_user          VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS from_domain        VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS proxy              VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS register_proxy     VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS outbound_proxy     VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS expire_seconds     INT(11)      NULL DEFAULT 800;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS retry_seconds      INT(11)      NULL DEFAULT 30;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS register_transport VARCHAR(16)  NULL DEFAULT 'udp';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS auth_username      VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS contact_params     VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS extension          VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS distinct_to        VARCHAR(8)   NULL DEFAULT 'false';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS caller_id_in_from  VARCHAR(8)   NULL DEFAULT 'false';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS supress_cng        VARCHAR(8)   NULL DEFAULT 'false';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS sip_cid_type       VARCHAR(16)  NULL DEFAULT 'none';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS codec_prefs        VARCHAR(255) NULL DEFAULT 'PCMU,PCMA';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS extension_in_contact VARCHAR(8) NULL DEFAULT 'false';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS ping               VARCHAR(16)  NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS ping_min           VARCHAR(16)  NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS ping_max           VARCHAR(16)  NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS contact_in_ping    VARCHAR(8)   NULL DEFAULT 'false';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS channels           INT(11)      NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS hostname           VARCHAR(255) NULL;
ALTER TABLE provider ADD COLUMN IF NOT EXISTS context            VARCHAR(128) NULL DEFAULT 'public';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS profile            VARCHAR(64)  NULL DEFAULT 'external';
ALTER TABLE provider ADD COLUMN IF NOT EXISTS enabled            VARCHAR(8)   NULL DEFAULT 'true';
