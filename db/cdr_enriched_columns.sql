-- cdr_enriched_columns.sql
-- Add direction / call_type / remote_ip / last_app / caller_number to cdr_lega
-- so the unified CDR reports can show inbound/outbound, call type icon,
-- and remote SIP peer IP instead of just FusionPBX domain name.
--
-- Populated by /usr/ictcore/scripts/cdr_etl.php from the FreeSWITCH
-- mod_cdr_csv "ictcore" template (see /etc/freeswitch/autoload_configs/cdr_csv.conf.xml).
--
-- Idempotent: uses ADD COLUMN IF NOT EXISTS + ADD INDEX IF NOT EXISTS (MariaDB 10.3+).

ALTER TABLE cdr_lega
  ADD COLUMN IF NOT EXISTS caller_number VARCHAR(64) NULL AFTER call_id,
  ADD COLUMN IF NOT EXISTS direction     VARCHAR(16) NULL AFTER context,
  ADD COLUMN IF NOT EXISTS call_type     VARCHAR(16) NULL AFTER direction,
  ADD COLUMN IF NOT EXISTS remote_ip     VARCHAR(64) NULL AFTER call_type,
  ADD COLUMN IF NOT EXISTS last_app      VARCHAR(64) NULL AFTER remote_ip;

-- Indexes required for CDR API query performance (LEFT JOIN on call_id, ORDER BY start_time).
ALTER TABLE cdr_lega
  ADD INDEX IF NOT EXISTS idx_call_id    (call_id),
  ADD INDEX IF NOT EXISTS idx_start_time (start_time),
  ADD INDEX IF NOT EXISTS idx_domain_start (domain, start_time);

ALTER TABLE cdr_legb
  ADD INDEX IF NOT EXISTS idx_call_id (call_id);
