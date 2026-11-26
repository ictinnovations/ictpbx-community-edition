/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account` (
  `account_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `type` varchar(32) DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `passwd` varchar(128) DEFAULT NULL,
  `passwd_pin` varchar(32) DEFAULT NULL,
  `first_name` varchar(128) DEFAULT NULL,
  `last_name` varchar(128) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `linkdid_id` int(11) DEFAULT NULL,
  `address` varchar(128) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 0,
  `settings` text DEFAULT NULL,
  `caller_id_name` varchar(128) DEFAULT NULL,
  `domain` varchar(128) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `username` (`type`,`username`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `action` (
  `action_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(128) NOT NULL DEFAULT '',
  `action` int(11) NOT NULL DEFAULT 0,
  `data` varchar(64) NOT NULL DEFAULT '',
  `weight` int(4) NOT NULL DEFAULT 0,
  `is_default` int(1) DEFAULT 0,
  `application_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`action_id`),
  KEY `action_application` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activitylog` (
  `activitylog_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(6) DEFAULT NULL,
  `tenant_id` int(5) DEFAULT NULL,
  `username` varchar(250) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `date` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`activitylog_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1279 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcement` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(5) NOT NULL,
  `title` varchar(250) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`announcement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `application` (
  `application_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `type` varchar(64) NOT NULL DEFAULT '',
  `data` text DEFAULT NULL,
  `weight` int(4) NOT NULL DEFAULT 0,
  `program_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  KEY `application_programe_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auto_number` (
  `num0` int(11) unsigned NOT NULL,
  `num1` int(11) unsigned NOT NULL,
  `num2` int(11) unsigned NOT NULL,
  `num3` int(11) unsigned NOT NULL,
  `num4` int(11) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branding` (
  `branding_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) unsigned NOT NULL,
  `domain` varchar(128) NOT NULL,
  `title` varchar(128) NOT NULL,
  `logo` varchar(128) DEFAULT NULL,
  `footer` text NOT NULL,
  `def` int(1) DEFAULT 0,
  `favicon_url` varchar(255) DEFAULT NULL,
  `login_subtitle` varchar(255) DEFAULT NULL,
  `support_email` varchar(128) DEFAULT NULL,
  `login_bg_url` varchar(255) DEFAULT NULL,
  `theme_lock` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`branding_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign` (
  `campaign_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `program_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `cpm` int(11) NOT NULL DEFAULT 2,
  `try_allowed` int(11) NOT NULL DEFAULT 1,
  `contact_total` int(11) NOT NULL DEFAULT 0,
  `contact_done` int(11) NOT NULL DEFAULT 0,
  `status` varchar(128) NOT NULL DEFAULT '',
  `reason` varchar(128) NOT NULL DEFAULT '',
  `pid` varchar(128) NOT NULL DEFAULT '',
  `last_run` int(11) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`campaign_id`),
  KEY `campaign_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carriertype` (
  `carriertype_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `service_flag` int(11) DEFAULT NULL,
  PRIMARY KEY (`carriertype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cdr_lega` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` varchar(64) DEFAULT NULL,
  `destination` varchar(64) DEFAULT NULL,
  `context` varchar(64) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `answer_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `billsec` int(11) DEFAULT NULL,
  `hangup_cause` varchar(64) DEFAULT NULL,
  `domain` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cdr_legb` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` varchar(64) DEFAULT NULL,
  `caller_number` varchar(64) DEFAULT NULL,
  `context` varchar(64) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `answer_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `billsec` int(11) DEFAULT NULL,
  `hangup_cause` varchar(64) DEFAULT NULL,
  `domain` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `codec` (
  `codec_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `codec_name` varchar(128) NOT NULL DEFAULT '',
  `codec_value` varchar(128) NOT NULL DEFAULT '',
  `codec_flag` int(11) NOT NULL DEFAULT 0,
  `active` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`codec_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `config_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file_name` varchar(64) NOT NULL DEFAULT '',
  `file_path` varchar(255) NOT NULL DEFAULT '',
  `source` varchar(255) DEFAULT NULL,
  `version` int(11) DEFAULT 0,
  `gateway_flag` int(11) unsigned DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_data` (
  `config_data_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `data` text DEFAULT NULL,
  `description` varchar(128) NOT NULL DEFAULT '',
  `group_name` varchar(64) NOT NULL DEFAULT '',
  `group_child` varchar(64) NOT NULL DEFAULT '',
  `file_name` varchar(64) NOT NULL DEFAULT '',
  `node_id` int(11) unsigned DEFAULT 0,
  `gateway_flag` int(11) unsigned DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`config_data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_node` (
  `config_id` int(11) unsigned NOT NULL,
  `node_id` int(11) unsigned NOT NULL,
  `version` int(11) NOT NULL DEFAULT 0,
  `date_created` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuration` (
  `configuration_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `type` varchar(32) DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `data` varchar(255) DEFAULT NULL,
  `permission_flag` int(11) unsigned DEFAULT 186,
  PRIMARY KEY (`configuration_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuration_data` (
  `configuration_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `class` int(11) NOT NULL DEFAULT 1,
  `node_id` int(11) unsigned NOT NULL DEFAULT 0,
  `campaign_id` int(11) unsigned NOT NULL DEFAULT 0,
  `data` varchar(255) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`configuration_id`,`class`,`node_id`,`created_by`,`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact` (
  `contact_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `first_name` varchar(64) DEFAULT NULL,
  `last_name` varchar(64) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `address` varchar(128) DEFAULT NULL,
  `is_deleted` int(1) DEFAULT 0,
  `custom1` varchar(128) DEFAULT NULL,
  `custom2` varchar(128) DEFAULT NULL,
  `custom3` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`contact_id`),
  KEY `contact_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_dnc` (
  `contact_dnc_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) unsigned NOT NULL,
  `first_name` varchar(64) DEFAULT NULL,
  `last_name` varchar(64) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `address` varchar(128) DEFAULT NULL,
  `custom1` varchar(128) DEFAULT NULL,
  `custom2` varchar(128) DEFAULT NULL,
  `custom3` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`contact_dnc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_group` (
  `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `contact_total` int(11) NOT NULL DEFAULT 0,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  KEY `contact_group_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_link` (
  `group_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`contact_id`),
  KEY `contact_link_contact_id` (`contact_id`),
  KEY `contact_link_group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER contact_link_insert AFTER INSERT
  ON contact_link FOR EACH ROW BEGIN
    UPDATE contact_group SET contact_total = (contact_total + 1) WHERE group_id = NEW.group_id;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER contact_link_delete AFTER DELETE
  ON contact_link FOR EACH ROW BEGIN
    UPDATE contact_group SET contact_total = (contact_total - 1) WHERE group_id = OLD.group_id;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `country` (
  `country_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `iso_code_2` varchar(2) DEFAULT NULL,
  `iso_code_3` varchar(3) DEFAULT NULL,
  `dialing_code` text DEFAULT NULL,
  `ndd` varchar(6) DEFAULT NULL,
  `idd` varchar(6) DEFAULT NULL,
  `locallenght` int(2) DEFAULT NULL,
  `timezone_id` int(11) DEFAULT NULL,
  `timezone_dst` int(1) DEFAULT 0,
  `language_id` varchar(2) DEFAULT NULL,
  `currency_id` varchar(3) DEFAULT NULL,
  `region_id` varchar(5) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`country_id`),
  KEY `country_region_id` (`region_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1021 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coverpage` (
  `coverpage_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `tenant_id` int(5) DEFAULT NULL,
  `created_by` int(5) DEFAULT NULL,
  PRIMARY KEY (`coverpage_id`),
  KEY `coverpage_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency` (
  `currency_id` varchar(3) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination` (
  `destination_id` varchar(11) NOT NULL,
  `prefix` varchar(64) NOT NULL DEFAULT '',
  `ocn` varchar(16) DEFAULT NULL,
  `carrier_id` int(11) DEFAULT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `carriertype_id` int(11) DEFAULT NULL,
  `timezone_id` int(11) DEFAULT NULL,
  `timezone_dst` int(1) DEFAULT 0,
  `country_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`destination_id`),
  KEY `destination_country_id` (`country_id`),
  KEY `destination_prefix` (`prefix`),
  KEY `destination_ocn` (`ocn`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialplan` (
  `dialplan_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gateway_flag` int(11) DEFAULT 15,
  `source` varchar(128) DEFAULT '*',
  `destination` varchar(128) DEFAULT '*',
  `context` varchar(16) DEFAULT '*',
  `weight` int(11) DEFAULT 0,
  `program_id` varchar(128) DEFAULT NULL,
  `application_id` varchar(128) DEFAULT NULL,
  `filter_flag` int(11) DEFAULT 31,
  PRIMARY KEY (`dialplan_id`),
  UNIQUE KEY `dialplan_check` (`gateway_flag`,`source`,`destination`,`context`),
  KEY `dialplan_source` (`source`),
  KEY `dialplan_destination` (`destination`),
  KEY `dialplan_context` (`context`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document` (
  `document_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT '',
  `file_name` varchar(128) NOT NULL DEFAULT '',
  `file_source` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `ocr` blob DEFAULT NULL,
  `has_token` varchar(250) DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT 0,
  `size_x` int(11) NOT NULL DEFAULT 0,
  `size_y` int(11) NOT NULL DEFAULT 0,
  `quality` enum('basic','standard','fine','super','superior','ultra') DEFAULT 'standard',
  `resolution_x` int(11) NOT NULL DEFAULT 0,
  `resolution_y` int(11) NOT NULL DEFAULT 0,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`document_id`),
  KEY `document_created_by` (`created_by`),
  KEY `document_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faxactivity` (
  `faxactivity_id` int(8) NOT NULL AUTO_INCREMENT,
  `faxid` int(5) DEFAULT NULL,
  `faxactivity` varchar(250) DEFAULT NULL,
  `date` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`faxactivity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faxlog` (
  `faxlog_id` int(7) NOT NULL AUTO_INCREMENT,
  `faxid` int(7) DEFAULT NULL,
  `sourceid` int(9) DEFAULT NULL,
  `sourcename` varchar(250) DEFAULT NULL,
  `tenant` varchar(50) DEFAULT NULL,
  `sourcephone` varchar(250) DEFAULT NULL,
  `callerid` varchar(50) DEFAULT NULL,
  `destination` varchar(250) DEFAULT NULL,
  `destinationname` varchar(250) DEFAULT NULL,
  `faxstatus` longtext DEFAULT NULL,
  `coverpage` varchar(5) DEFAULT NULL,
  `duration` varchar(250) DEFAULT NULL,
  `pending` varchar(255) DEFAULT NULL,
  `processing` varchar(255) DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL,
  `response` varchar(255) DEFAULT NULL,
  `origin` varchar(200) DEFAULT NULL,
  `date` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`faxlog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forgot_password` (
  `forgot_password_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `token` longtext DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`forgot_password_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gateway` (
  `gateway_flag` int(11) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `service_flag` int(11) unsigned DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`gateway_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ivr` (
  `ivr_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `data` text DEFAULT NULL,
  `created` int(11) unsigned DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`ivr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `language` (
  `language_id` varchar(2) NOT NULL DEFAULT '',
  `active` int(1) NOT NULL DEFAULT 0,
  `name` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempt` (
  `login_attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(6) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `ip_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`login_attempt_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `node` (
  `node_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `api_host` varchar(32) NOT NULL DEFAULT '',
  `api_port` varchar(16) NOT NULL DEFAULT '',
  `api_user` varchar(32) NOT NULL DEFAULT '',
  `api_pass` varchar(32) NOT NULL DEFAULT '',
  `ssh_host` varchar(32) NOT NULL DEFAULT '',
  `ssh_port` varchar(16) NOT NULL DEFAULT '',
  `ssh_user` varchar(32) NOT NULL DEFAULT '',
  `ssh_pass` varchar(32) NOT NULL DEFAULT '',
  `gateway_flag` varchar(16) DEFAULT NULL,
  `channel` int(11) NOT NULL DEFAULT 0,
  `cps` int(11) NOT NULL DEFAULT 1,
  `server_flag` int(11) unsigned DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`node_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `package` (
  `package_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `package_interval` int(11) NOT NULL DEFAULT -1,
  `plan_id` int(11) unsigned DEFAULT NULL,
  `role_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `package_resource` (
  `package_id` int(11) unsigned NOT NULL,
  `resource_id` int(11) unsigned NOT NULL,
  `quota_limit` int(11) NOT NULL DEFAULT 0,
  `quota_rollover` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`package_id`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_history` (
  `password_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `passwd` varchar(255) NOT NULL,
  `usr_id` int(5) DEFAULT NULL,
  PRIMARY KEY (`password_history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_policy` (
  `password_policy_id` int(11) NOT NULL AUTO_INCREMENT,
  `min_length` int(11) DEFAULT NULL,
  `special_character` int(11) DEFAULT NULL,
  `min_numbers` int(11) DEFAULT NULL,
  `min_uppercase` int(11) DEFAULT NULL,
  `min_lowercase` int(11) DEFAULT NULL,
  `passwd_exp_limit` int(11) DEFAULT NULL,
  `passwd_email_notify` int(11) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT NULL,
  `passwd_history` int(11) DEFAULT NULL,
  `sessiontime` int(11) DEFAULT NULL,
  PRIMARY KEY (`password_policy_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment` (
  `payment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `description` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `paid_amount` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `paid_date` varchar(20) DEFAULT NULL,
  `usr_id` int(11) DEFAULT NULL,
  `paid_for` varchar(20) NOT NULL DEFAULT 'credit',
  `subscription_id` int(11) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `payment_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER payment_insert AFTER INSERT
  ON payment FOR EACH ROW BEGIN
    UPDATE usr SET credit = credit + NEW.paid_amount WHERE usr_id = NEW.usr_id;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission` (
  `permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan` (
  `plan_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `program` (
  `program_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `cover_id` int(11) DEFAULT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `type` varchar(64) NOT NULL DEFAULT '',
  `data` text DEFAULT NULL,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_resource` (
  `program_id` int(11) unsigned DEFAULT NULL,
  `resource_type` varchar(64) NOT NULL DEFAULT 'message',
  `resource_id` int(11) unsigned DEFAULT NULL,
  KEY `program_resource_resource_type` (`program_id`,`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider` (
  `provider_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL DEFAULT '',
  `service_flag` int(11) unsigned DEFAULT NULL,
  `node_id` int(11) unsigned DEFAULT NULL,
  `host` varchar(128) NOT NULL DEFAULT '',
  `port` int(6) NOT NULL DEFAULT 5060,
  `username` varchar(128) NOT NULL DEFAULT '',
  `password` varchar(128) NOT NULL DEFAULT '',
  `dialstring` varchar(255) NOT NULL DEFAULT '',
  `prefix` varchar(255) NOT NULL DEFAULT '',
  `settings` text DEFAULT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `register` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT 0,
  `type` varchar(32) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `httpkey` varchar(128) DEFAULT NULL,
  `encryption` varchar(250) DEFAULT NULL,
  `from_email` varchar(250) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`provider_id`),
  KEY `provider_created_by` (`created_by`),
  KEY `provider_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quota` (
  `quota_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `subscription_id` int(11) unsigned DEFAULT NULL,
  `resource_id` int(11) unsigned NOT NULL,
  `quota_limit` int(11) NOT NULL DEFAULT 0,
  `quota_offset` int(11) NOT NULL DEFAULT 0,
  `quota_locked` int(11) NOT NULL DEFAULT 0,
  `quota_used` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`quota_id`),
  UNIQUE KEY `quota_tenant_resource` (`tenant_id`,`resource_id`),
  KEY `quota_usr_id` (`usr_id`),
  KEY `quota_subscription_id` (`subscription_id`),
  KEY `quota_resource_id` (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=517 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate` (
  `rate_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `unit_billing_block` int(5) NOT NULL DEFAULT 1,
  `unit_first_block` int(5) NOT NULL DEFAULT 1,
  `unit_increment` int(5) NOT NULL DEFAULT 1,
  `unit_free` int(5) NOT NULL DEFAULT 0,
  `unit_block_rate` decimal(16,6) DEFAULT 0.000000,
  `cost_connection` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `cost_minimum` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `service_flag` int(11) DEFAULT NULL,
  `weight` int(5) NOT NULL DEFAULT 0,
  `active` int(1) NOT NULL DEFAULT 1,
  `plan_id` int(11) DEFAULT NULL,
  `destination_id` varchar(11) NOT NULL,
  PRIMARY KEY (`rate_id`),
  KEY `rate_destination_id` (`destination_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER rate_insert AFTER INSERT
  ON rate FOR EACH ROW BEGIN
    INSERT IGNORE INTO destination (destination_id, prefix, name) VALUES (NEW.destination_id, NEW.destination_id, NEW.name);
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recording` (
  `recording_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL,
  `type` varchar(8) NOT NULL,
  `file_name` varchar(128) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `length` int(11) NOT NULL DEFAULT 0,
  `codec` varchar(16) NOT NULL DEFAULT 'pcm',
  `channel` int(2) NOT NULL DEFAULT 1,
  `sample` int(11) NOT NULL DEFAULT 8000,
  `bitrate` int(11) NOT NULL DEFAULT 16,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`recording_id`),
  KEY `recording_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `region` (
  `region_id` varchar(5) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource` (
  `resource_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `service_flag` int(11) DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  KEY `resource_type` (`type`),
  KEY `resource_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retry_interval` (
  `retry_interval` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role` (
  `role_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permission` (
  `role_permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL DEFAULT 0,
  `permission_id` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`role_permission_id`),
  KEY `permission_id` (`permission_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=230 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `route` (
  `route_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `destination_id` varchar(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_flag` int(11) DEFAULT 0,
  PRIMARY KEY (`route_id`),
  KEY `route_destination_id` (`destination_id`),
  KEY `route_provider_id` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER route_insert AFTER INSERT
  ON route FOR EACH ROW BEGIN
    INSERT IGNORE INTO destination (destination_id, prefix, name) VALUES (NEW.destination_id, NEW.destination_id, NEW.name);
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule` (
  `year` varchar(50) NOT NULL DEFAULT '*',
  `weekday` varchar(50) NOT NULL DEFAULT '*',
  `month` varchar(50) NOT NULL DEFAULT '*',
  `day` varchar(50) NOT NULL DEFAULT '*',
  `hour` varchar(50) NOT NULL DEFAULT '*',
  `minute` varchar(50) NOT NULL DEFAULT '*',
  `task_id` int(11) unsigned NOT NULL,
  KEY `schedule_task_id` (`task_id`),
  KEY `schedule_time` (`hour`,`minute`),
  CONSTRAINT `schedule_task_delete` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sequence` (
  `sequence_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(128) NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY (`sequence_id`),
  KEY `table_name` (`table_name`),
  KEY `sequence_table_name` (`table_name`(3))
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service` (
  `service_flag` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `unit_id` varchar(16) NOT NULL,
  PRIMARY KEY (`service_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `session` (
  `session_id` varchar(80) NOT NULL,
  `time_start` int(11) DEFAULT 0,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `spool` (
  `spool_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `time_spool` int(11) DEFAULT NULL,
  `time_start` int(11) DEFAULT NULL,
  `time_connect` int(11) DEFAULT NULL,
  `time_end` int(11) DEFAULT NULL,
  `call_id` varchar(80) NOT NULL DEFAULT '',
  `status` varchar(80) NOT NULL DEFAULT '',
  `response` varchar(80) NOT NULL DEFAULT '',
  `is_deleted` int(1) NOT NULL DEFAULT 0,
  `amount` int(11) NOT NULL DEFAULT 0,
  `amount_net` int(11) NOT NULL DEFAULT 0,
  `rate` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `cost` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `service_flag` int(11) unsigned DEFAULT NULL,
  `transmission_id` int(11) unsigned DEFAULT NULL,
  `destination_id` int(11) unsigned DEFAULT NULL,
  `provider_id` int(11) unsigned DEFAULT NULL,
  `rate_id` int(11) unsigned DEFAULT NULL,
  `quota_id` int(11) unsigned DEFAULT NULL,
  `node_id` int(11) unsigned DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`spool_id`),
  KEY `spool_transmission_id` (`transmission_id`),
  KEY `spool_account_id` (`account_id`),
  KEY `spool_time_end` (`time_end`),
  KEY `spool_provider_id` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER spool_update AFTER UPDATE
  ON spool FOR EACH ROW BEGIN
    DECLARE UserID INT(11);
    SELECT created_by INTO UserID FROM transmission WHERE transmission_id=NEW.transmission_id;
    UPDATE usr SET credit = credit - (NEW.cost - OLD.cost) WHERE usr_id=UserID;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `spool_result` (
  `spool_result_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `type` varchar(80) NOT NULL DEFAULT '',
  `name` varchar(80) NOT NULL DEFAULT '',
  `data` varchar(80) NOT NULL DEFAULT '',
  `date_created` int(11) DEFAULT NULL,
  `spool_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`spool_result_id`),
  KEY `spool_result_spool_id` (`spool_id`),
  KEY `spool_result_application_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription` (
  `subscription_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `package_id` int(11) unsigned NOT NULL,
  `time_start` int(11) NOT NULL DEFAULT 0,
  `time_offset` int(11) NOT NULL DEFAULT 0,
  `active` int(1) NOT NULL DEFAULT 0,
  `auto_renew` int(1) NOT NULL DEFAULT 0,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `subscription_usr_id` (`usr_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task` (
  `task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL DEFAULT '',
  `action` varchar(64) NOT NULL DEFAULT '0',
  `data` text DEFAULT NULL,
  `weight` int(4) NOT NULL DEFAULT 0,
  `status` int(4) NOT NULL DEFAULT 0,
  `is_recurring` int(4) NOT NULL DEFAULT 0,
  `due_at` int(11) DEFAULT NULL,
  `expiry` int(11) DEFAULT NULL,
  `last_run` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `template` (
  `template_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(8) NOT NULL DEFAULT '1',
  `description` varchar(255) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text DEFAULT NULL,
  `body_alt` text DEFAULT NULL,
  `attachment` text DEFAULT NULL,
  `length` int(11) NOT NULL DEFAULT 0,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`template_id`),
  KEY `template_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant` (
  `tenant_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(128) NOT NULL,
  `last_name` varchar(128) NOT NULL,
  `company` varchar(128) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `email` varchar(128) NOT NULL,
  `address` varchar(128) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `timezone_id` int(11) DEFAULT NULL,
  `active` int(1) NOT NULL,
  `credit` int(16) DEFAULT 0,
  `daily_limit` int(16) NOT NULL DEFAULT 0,
  `monthly_limit` int(16) NOT NULL DEFAULT 0,
  `daily_sent` int(16) NOT NULL DEFAULT 0,
  `monthly_sent` int(16) NOT NULL DEFAULT 0,
  `min_retention` int(11) NOT NULL DEFAULT 0,
  `max_retention` int(11) NOT NULL DEFAULT 0,
  `retention_period` int(11) NOT NULL DEFAULT 0,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  `fpbx_domain_uuid` varchar(36) DEFAULT NULL COMMENT 'FusionPBX domain UUID for this tenant',
  PRIMARY KEY (`tenant_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_permission` (
  `tenant_permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) unsigned NOT NULL DEFAULT 0,
  `permission_id` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`tenant_permission_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `text` (
  `text_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL,
  `data` text DEFAULT NULL,
  `type` varchar(8) NOT NULL DEFAULT 'UTF-8',
  `description` varchar(255) DEFAULT NULL,
  `length` int(11) unsigned DEFAULT NULL,
  `class` varchar(8) NOT NULL DEFAULT '1',
  `encoding` varchar(8) NOT NULL DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`text_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `timezone` (
  `timezone_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`timezone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transmission` (
  `transmission_id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `service_flag` int(11) unsigned DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `program_id` int(11) unsigned DEFAULT NULL,
  `sendcover` int(1) DEFAULT 0,
  `origin` varchar(128) DEFAULT NULL,
  `direction` varchar(128) DEFAULT NULL,
  `status` varchar(128) DEFAULT NULL,
  `response` varchar(255) NOT NULL DEFAULT '',
  `try_allowed` int(2) NOT NULL DEFAULT 1,
  `try_done` int(2) NOT NULL DEFAULT 0,
  `last_run` int(11) DEFAULT NULL,
  `is_downloaded` int(1) NOT NULL DEFAULT 1,
  `personalize_fax` int(1) NOT NULL DEFAULT 0,
  `is_deleted` int(1) NOT NULL DEFAULT 0,
  `is_print` int(1) NOT NULL DEFAULT 0,
  `campaign_id` int(11) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  `is_read` int(11) NOT NULL DEFAULT 0,
  `pages` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`transmission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER transmission_insert AFTER INSERT
  ON transmission FOR EACH ROW BEGIN
    IF (NEW.campaign_id IS NOT NULL) THEN
     UPDATE campaign SET contact_total = (contact_total + 1) WHERE campaign_id = NEW.campaign_id;
    END IF;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER transmission_update AFTER UPDATE
  ON transmission FOR EACH ROW BEGIN
    IF (OLD.status = 'pending' AND NEW.status != 'pending') THEN
      UPDATE campaign SET contact_done = (contact_done + 1) WHERE campaign_id = OLD.campaign_id;
    END IF;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER transmission_delete AFTER DELETE
  ON transmission FOR EACH ROW BEGIN
    IF (OLD.status = 'pending') THEN
      UPDATE campaign SET contact_total = (contact_total - 1) WHERE campaign_id = OLD.campaign_id;
    ELSE
      UPDATE campaign SET contact_total = (contact_total - 1), contact_done = (contact_done - 1) WHERE campaign_id = OLD.campaign_id;
    END IF;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transmission_buffer` (
  `transmission_id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `service_flag` int(11) unsigned DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `program_id` int(11) unsigned DEFAULT NULL,
  `origin` varchar(128) DEFAULT NULL,
  `direction` varchar(128) DEFAULT NULL,
  `status` varchar(128) DEFAULT NULL,
  `response` varchar(255) NOT NULL DEFAULT '',
  `is_print` int(1) NOT NULL DEFAULT 0,
  `is_coverpage` int(1) NOT NULL DEFAULT 0,
  `cover_id` int(11) DEFAULT NULL,
  `coverpage_name` varchar(250) NOT NULL DEFAULT '',
  `try_allowed` int(2) NOT NULL DEFAULT 1,
  `try_done` int(2) NOT NULL DEFAULT 0,
  `last_run` int(11) DEFAULT NULL,
  `is_deleted` int(1) NOT NULL DEFAULT 0,
  `campaign_id` int(11) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  `is_read` int(11) NOT NULL DEFAULT 0,
  `read_codec` varchar(50) DEFAULT NULL,
  `write_codec` varchar(50) DEFAULT NULL,
  `company` varchar(128) DEFAULT NULL,
  `tenant_unique_id` varchar(16) DEFAULT NULL,
  `contact_phone` varchar(32) DEFAULT NULL,
  `account_tags` varchar(255) DEFAULT NULL,
  `account_phone` varchar(16) DEFAULT NULL,
  `created_by_username` varchar(255) DEFAULT NULL,
  `created_by_unique_id` varchar(255) DEFAULT NULL,
  `updated_by_username` varchar(255) DEFAULT NULL,
  `updated_by_unique_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`transmission_id`),
  KEY `tb_tenant_status_date` (`tenant_id`,`status`,`date_created`),
  KEY `tb_account_date` (`account_id`,`date_created`),
  KEY `tb_contact_date` (`contact_id`,`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit` (
  `unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `measurement` varchar(16) NOT NULL,
  PRIMARY KEY (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_block` (
  `unit_id` int(11) NOT NULL,
  `block` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permission` (
  `user_permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL DEFAULT 0,
  `permission_id` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_permission_id`),
  KEY `usr_id` (`usr_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_role` (
  `role_id` int(11) unsigned DEFAULT NULL,
  `usr_id` int(11) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usersession` (
  `usersession_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `activities` text DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`usersession_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usr` (
  `usr_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `role_id` int(11) unsigned NOT NULL DEFAULT 0,
  `username` varchar(64) DEFAULT NULL,
  `passwd` varchar(128) DEFAULT NULL,
  `first_name` varchar(128) DEFAULT NULL,
  `last_name` varchar(128) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `mobile` varchar(16) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `address` varchar(128) DEFAULT NULL,
  `company` varchar(128) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `language_id` varchar(2) DEFAULT NULL,
  `timezone_id` int(11) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 0,
  `credit` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `daily_limit` int(16) NOT NULL DEFAULT 0,
  `monthly_limit` int(16) NOT NULL DEFAULT 0,
  `auth_type` varchar(16) DEFAULT NULL,
  `verify` int(1) DEFAULT 0,
  `appsecret` varchar(128) DEFAULT NULL,
  `daily_sent` int(16) NOT NULL DEFAULT 0,
  `monthly_sent` int(16) NOT NULL DEFAULT 0,
  `allowed_timeslot` varchar(256) DEFAULT NULL,
  `allowed_days` varchar(256) DEFAULT NULL,
  `dashboard_cards` varchar(256) DEFAULT NULL,
  `user_permission` varchar(256) DEFAULT NULL,
  `active_desc` varchar(256) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  `cover` int(1) DEFAULT NULL,
  `pass_exp_in` int(11) NOT NULL DEFAULT 0,
  `email_send` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'logout',
  PRIMARY KEY (`usr_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER usr_insert AFTER INSERT
  ON usr FOR EACH ROW BEGIN
    INSERT INTO account (account_id, tenant_id, type, username, passwd, passwd_pin, first_name, last_name, phone, email, address,
                         active, date_created, created_by, last_updated, updated_by)
    SELECT NULL, NEW.tenant_id, 'account', NEW.username, NEW.passwd, LEFT(RAND()*999999, 4), NEW.first_name, NEW.last_name, NEW.phone,
           NEW.email, NEW.address, NEW.active, NEW.date_created, NEW.usr_id, NULL, NULL;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER usr_update AFTER UPDATE
  ON usr FOR EACH ROW BEGIN
    UPDATE account SET tenant_id = NEW.tenant_id, username = NEW.username, passwd = NEW.passwd, first_name = NEW.first_name, last_name = NEW.last_name, email = NEW.email, phone = NEW.phone WHERE type = 'account' AND created_by = NEW.usr_id limit 1;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER usr_delete AFTER DELETE 
 ON usr FOR EACH ROW BEGIN
   DELETE FROM account WHERE type = 'account' AND created_by = OLD.usr_id limit 1;
 END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

