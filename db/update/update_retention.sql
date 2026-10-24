ALTER TABLE tenant ADD `min_retention` int(11) NOT NULL default 0 AFTER `monthly_sent`;
ALTER TABLE tenant ADD `max_retention` int(11) NOT NULL default 0 AFTER `min_retention`;
ALTER TABLE tenant ADD `retention_period` int(11) NOT NULL default 0 AFTER `max_retention`;
ALTER TABLE transmission ADD `is_downloaded` int(1) NOT NULL default 1 AFTER `last_run`;
