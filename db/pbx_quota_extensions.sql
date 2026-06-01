-- Link tenant → FusionPBX domain. FpbxDomain::get_domain_uuid() reads this
-- column to resolve a tenant_id to its FusionPBX v_domains.domain_uuid.
-- Defined here (idempotent migration) because tenant.sql ships without it.
--
-- IMPORTANT: This ALTER runs FIRST so it succeeds on Community Edition,
-- where the resource/quota tables below do not exist (those are EE-only
-- billing tables). MySQL stops processing on the first error when reading
-- a script via `<`, so any CE-incompatible statements must come AFTER this.
ALTER TABLE tenant
  ADD COLUMN IF NOT EXISTS fpbx_domain_uuid VARCHAR(36) DEFAULT NULL
  COMMENT 'FusionPBX domain UUID for this tenant';

-- Add PBX quota columns to usr table (CE-safe).
-- All 8 columns added via separate IF NOT EXISTS so the migration is idempotent
-- on EE (where some columns already ship with the EE mariadb-schema dump) and
-- complete on CE (where the dump strips them). No AFTER clauses — column
-- ordering is irrelevant and AFTER referencing a non-existent column would
-- abort the entire ALTER statement, leaving later columns unadded.
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_ring_groups   INT DEFAULT NULL;
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_call_queues   INT DEFAULT NULL;
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_voicemail     INT DEFAULT NULL;
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_conference    INT DEFAULT NULL;
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_music_on_hold INT DEFAULT NULL;
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_extensions    INT DEFAULT NULL;
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_devices       INT DEFAULT NULL;
ALTER TABLE usr ADD COLUMN IF NOT EXISTS quota_ivr_menus     INT DEFAULT NULL;

-- ============================================================================
-- EE-only below: billing resource + quota seeding.
-- These statements fail harmlessly on CE (tables don't exist) — kept in this
-- file so EE installs get the seed data via the same migration.
-- ============================================================================

-- Add 3 new PBX resource types
INSERT IGNORE INTO resource (resource_id, type, name, description, service_flag) VALUES
  (15, '', 'extensions', '', 32),
  (16, '', 'devices',    '', 32),
  (17, '', 'ivr_menus',  '', 32);

-- Add quota rows for new resources for all existing tenants
INSERT IGNORE INTO quota (usr_id, tenant_id, subscription_id, resource_id, quota_limit, quota_offset, quota_locked, quota_used)
  SELECT 1, q.tenant_id, q.subscription_id, 15, 25, 0, 0, 0 FROM quota q WHERE q.resource_id = 7 GROUP BY q.tenant_id;
INSERT IGNORE INTO quota (usr_id, tenant_id, subscription_id, resource_id, quota_limit, quota_offset, quota_locked, quota_used)
  SELECT 1, q.tenant_id, q.subscription_id, 16, 25, 0, 0, 0 FROM quota q WHERE q.resource_id = 7 GROUP BY q.tenant_id;
INSERT IGNORE INTO quota (usr_id, tenant_id, subscription_id, resource_id, quota_limit, quota_offset, quota_locked, quota_used)
  SELECT 1, q.tenant_id, q.subscription_id, 17, 10, 0, 0, 0 FROM quota q WHERE q.resource_id = 7 GROUP BY q.tenant_id;
