/*******************************************************************/
/* Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   */
/* Developed By: Nasir Iqbal                                       */
/* Website : http://www.ictinnovations.com/                        */
/* Mail : nasir@ictinnovations.com                                 */
/*******************************************************************/

/*==============================================================*/
/* Table: text                                                  */
/*==============================================================*/
CREATE TABLE IF NOT EXISTS text
(
   text_id                  int(11) unsigned       NOT NULL auto_increment,
   tenant_id                int(11)                NOT NULL default 0,
   name                     varchar(128)           NOT NULL,
   data                     text,
   type                     varchar(8)             NOT NULL default 'UTF-8',
   description              varchar(255)           default NULL,
   length                   int(11) unsigned       default NULL,
   class                    varchar(8)             NOT NULL default 1,
   encoding                 varchar(8)             NOT NULL default 0,
   date_created             int(11)                default NULL,
   created_by               int(11) unsigned       default NULL,
   last_updated             int(11)                default NULL,
   updated_by               int(11) unsigned       default NULL,
   PRIMARY KEY (text_id)
) ENGINE = InnoDB;

/*==============================================================*/
/* Desc: Dumping Default System configurations                  */
/*==============================================================*/
-- service
-- ready. No unique key on configuration, so guard on absence rather than
-- relying on INSERT IGNORE, which would happily add a second row.
INSERT INTO configuration (tenant_id, type, name, data, permission_flag)
SELECT 0, 'service', 'sms_status', '0', 254 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM configuration WHERE type='service' AND name='sms_status');

/*==============================================================*/
/* Table: insert sms module permissions                         */
/*==============================================================*/
-- Text permissions
INSERT IGNORE INTO permission VALUES (NULL, 'text', '');
INSERT IGNORE INTO permission VALUES (NULL, 'text_create', '');
INSERT IGNORE INTO permission VALUES (NULL, 'text_list', '');
INSERT IGNORE INTO permission VALUES (NULL, 'text_read', '');
INSERT IGNORE INTO permission VALUES (NULL, 'text_update', '');
INSERT IGNORE INTO permission VALUES (NULL, 'text_delete', '');
