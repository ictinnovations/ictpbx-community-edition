-- DEF-006: widen document.type from VARCHAR(8) to VARCHAR(32).
-- 'coverpage' (9 chars) was silently truncated to 'coverpag' on existing installs.
ALTER TABLE document MODIFY COLUMN type VARCHAR(32) NOT NULL DEFAULT '';
