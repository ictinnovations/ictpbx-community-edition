-- Outbound Routes -> FusionPBX dialplan projection link.
-- Idempotent ALTER. Safe to run on EE + CE.
ALTER TABLE route ADD COLUMN IF NOT EXISTS fpbx_dialplan_uuid VARCHAR(36) NULL AFTER service_flag;
