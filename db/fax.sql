/*******************************************************************/
/* Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   */
/* Developed By: Nasir Iqbal                                       */
/* Website : http://www.ictinnovations.com/                        */
/* Mail : nasir@ictinnovations.com                                 */
/*******************************************************************/

/*==============================================================*/
/* Table: document                                              */
/* Desc: this table will hold documents for fax broadcasting    */
/*==============================================================*/
CREATE TABLE IF NOT EXISTS document
(
   document_id              int(11) unsigned       NOT NULL auto_increment,
   tenant_id                int(11)                NOT NULL default 0,
   name                     varchar(128)           NOT NULL,
   type                     varchar(8)             NOT NULL default '',
   file_name                varchar(128)           NOT NULL default '',
   file_source              varchar(255)           NOT NULL default '',
   description              varchar(255)           NOT NULL default '',
   ocr                      blob                   default NULL,
   has_token                varchar(250)           default NULL,
   pages                    int(11)                NOT NULL default 0,
   size_x                   int(11)                NOT NULL default 0,
   size_y                   int(11)                NOT NULL default 0,
   quality                  ENUM('basic', 'standard', 'fine', 'super', 'superior', 'ultra') default 'standard',
   resolution_x             int(11)                NOT NULL default 0,
   resolution_y             int(11)                NOT NULL default 0,
   date_created             int(11)                default NULL,
   created_by               int(11) unsigned       default NULL,
   last_updated             int(11)                default NULL,
   updated_by               int(11) unsigned       default NULL,
   PRIMARY KEY (document_id)
) ENGINE = InnoDB;
CREATE INDEX IF NOT EXISTS document_created_by ON document (created_by);
CREATE INDEX IF NOT EXISTS document_tenant_id ON document (tenant_id);

/*==============================================================*/
/* Desc: Dumping Default System configurations                  */
/*==============================================================*/
-- service
-- ready. No unique key on configuration, so guard on absence rather than
-- relying on INSERT IGNORE, which would happily add a second row.
INSERT INTO configuration (tenant_id, type, name, data, permission_flag)
SELECT 0, 'service', 'fax_status', '0', 254 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM configuration WHERE type='service' AND name='fax_status');

/*==============================================================*/
/* Table: insert fax module permissions                         */
/*==============================================================*/
-- Document permissions
INSERT IGNORE INTO permission VALUES (NULL, 'document', '');
INSERT IGNORE INTO permission VALUES (NULL, 'document_create', '');
INSERT IGNORE INTO permission VALUES (NULL, 'document_list', '');
INSERT IGNORE INTO permission VALUES (NULL, 'document_read', '');
INSERT IGNORE INTO permission VALUES (NULL, 'document_update', '');
INSERT IGNORE INTO permission VALUES (NULL, 'document_delete', '');
