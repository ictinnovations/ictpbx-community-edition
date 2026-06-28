-- Adds linkage column so an ICTCore Provider row owns its FusionPBX v_gateways projection.
-- Idempotent; safe to re-run.
ALTER TABLE provider ADD COLUMN IF NOT EXISTS fpbx_gateway_uuid VARCHAR(36) NULL AFTER tenant_id;
