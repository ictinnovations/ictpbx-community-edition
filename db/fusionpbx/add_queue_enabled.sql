-- Migration: add queue_enabled to v_call_center_queues
-- Applied via PG; FusionPBX schema is upstream-pinned, this column is ICTCore-managed
ALTER TABLE v_call_center_queues ADD COLUMN IF NOT EXISTS queue_enabled TEXT DEFAULT 'true';
