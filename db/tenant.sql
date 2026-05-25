-- Tenant Table
CREATE TABLE tenant
(
    tenant_id         int(11) unsigned       NOT NULL auto_increment, 
    first_name        varchar(128)           NOT NULL,
    last_name         varchar(128)           NOT NULL,
    company           varchar(128)           NULL,
    phone             varchar(16)            NULL,
    email             varchar(128)           NOT NULL,
    address           varchar(128)           NULL,
    country_id        int(11)                NULL,
    timezone_id       int(11)                NULL,
    active            int(1)                 NOT NULL,
    credit            DECIMAL(10,4)          NULL default 0.0000,
    daily_limit       int(16)                NOT NULL default 0,
    monthly_limit     int(16)                NOT NULL default 0,
    daily_sent        int(16)                NOT NULL default 0,
    monthly_sent      int(16)                NOT NULL default 0,
    date_created      int(11)                default NULL,
    created_by        int(11) unsigned       default NULL,
    last_updated      int(11)                default NULL,
    updated_by        int(11) unsigned       default NULL,
    PRIMARY KEY (tenant_id)
) ENGINE = InnoDB;

/*==============================================================*/
/* Table: tenant_permission                                       */
/*==============================================================*/
CREATE TABLE tenant_permission
(
   tenant_permission_id           int(11) unsigned       NOT NULL auto_increment,
   tenant_id                      int(11) unsigned       NOT NULL default '0',
   permission_id                  int(11) unsigned       NOT NULL default '0',
   PRIMARY KEY  (tenant_permission_id),
   KEY tenant_id (tenant_id),
   KEY permission_id (permission_id)
) ENGINE = InnoDB;

-- Tenant permissions

ALTER TABLE tenant ADD CONSTRAINT email UNIQUE (email);
