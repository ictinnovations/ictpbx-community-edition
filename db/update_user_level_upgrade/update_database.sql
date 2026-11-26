-- Alter Tenant table
ALTER TABLE tenant
ADD COLUMN `first_name` varchar(128) NOT NULL AFTER `tenant_id`,
ADD COLUMN `last_name` varchar(128) NOT NULL AFTER `first_name`,
ADD COLUMN `company` varchar(128) NULL AFTER `last_name`,
ADD COLUMN `phone` varchar(16) NULL AFTER `company`,
ADD COLUMN `email` varchar(128) NOT NULL AFTER `phone`,
ADD COLUMN `address` varchar(128) NULL AFTER `email`,
ADD COLUMN `country_id` int(11) NULL AFTER `address`,
ADD COLUMN `timezone_id` int(11) NULL AFTER `country_id`,
ADD COLUMN `active` int(1) NOT NULL AFTER `timezone_id`,
ADD COLUMN `credit` int(16) NULL default 0 AFTER `active`,
ADD COLUMN `daily_limit` int(16) NOT NULL default 0 AFTER `credit`,
ADD COLUMN `monthly_limit` int(16) NOT NULL default 0 AFTER `daily_limit`,
ADD COLUMN `daily_sent` int(16) NOT NULL default 0 AFTER `monthly_limit`,
ADD COLUMN `monthly_sent` int(16) NOT NULL default 0 AFTER `daily_sent`;
-- Add uniq key
ALTER TABLE tenant ADD CONSTRAINT email UNIQUE (email);

-- Alter Usr table
ALTER TABLE usr ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `role_id`;
-- Alter all tables for tenant_id
ALTER TABLE account ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `account_id`;
ALTER TABLE campaign ADD COLUMN `tenant_id` int(11)  default NULL AFTER `account_id`;
ALTER TABLE config ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `config_id`;
ALTER TABLE config_data ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `config_data_id`;
ALTER TABLE configuration_data ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `campaign_id`;
ALTER TABLE contact ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `contact_id`;
ALTER TABLE contact_group ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `group_id`;
ALTER TABLE document ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `document_id`;
ALTER TABLE ivr ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `ivr_id`;
ALTER TABLE payment ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `payment_id`;
ALTER TABLE program ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `program_id`;
ALTER TABLE provider ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `node_id`;
ALTER TABLE quota ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `usr_id`;
ALTER TABLE recording ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `recording_id`;
ALTER TABLE subscription ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `subscription_id`;
ALTER TABLE template ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `template_id`;
ALTER TABLE text ADD COLUMN `tenant_id` int(11) NOT NULL default 0 AFTER `text_id`;
ALTER TABLE transmission ADD COLUMN `tenant_id` int(11)  default NULL AFTER `service_flag`;

-- permissions for admin role
SELECT @roleId := role_id FROM role WHERE name='admin';
-- Add super admin permission
SELECT @permissionId := permission_id FROM permission WHERE name='super_admin';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* Super Admin */
SELECT @permissionId := permission_id FROM permission WHERE name='tenant';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* Super Admin */

-- permissions for tenant role
SELECT @roleId := role_id FROM role WHERE name='tenant';
-- Add user admin permission
SELECT @permissionId := permission_id FROM permission WHERE name='user_admin';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* User Admin */

DELIMITER |
CREATE TRIGGER usr_insert AFTER INSERT
  ON usr FOR EACH ROW BEGIN
    INSERT INTO account (account_id, type, tenant_id, username, passwd, passwd_pin, first_name, last_name, phone, email, address,
                         active, date_created, created_by, last_updated, updated_by)
    SELECT NULL, 'account', NEW.tenant_id, NEW.username, NEW.passwd, LEFT(RAND()*999999, 4), NEW.first_name, NEW.last_name, NEW.phone,
           NEW.email, NEW.address, NEW.active, NEW.date_created, NEW.usr_id, NULL, NULL;
  END;
|
DELIMITER ;

-- Add field dashboard_cards for Dashboard customization
ALTER TABLE usr ADD COLUMN `dashboard_cards` varchar(256) NULL default '' AFTER `monthly_sent`;
