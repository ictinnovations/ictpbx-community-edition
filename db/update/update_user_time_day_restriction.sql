ALTER TABLE usr ADD COLUMN `allowed_timeslot` varchar(256) NULL default NULL AFTER `monthly_sent`;
ALTER TABLE usr ADD COLUMN `allowed_days` varchar(256) NULL default NULL AFTER `allowed_timeslot`;