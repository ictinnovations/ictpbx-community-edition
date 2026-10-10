/*******************************************************************/
/* Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   */
/* Developed By: Nasir Iqbal                                       */
/* Website : http://www.ictinnovations.com/                        */
/* Mail : nasir@ictinnovations.com                                 */
/*******************************************************************/

/*==============================================================*/
/* Table: passwd_history                                        */
/* Desc: Enforce Password History                               */                                           
/*==============================================================*/
CREATE TABLE passwd_history (
   passwd_history_id                          int(11)             NOT NULL auto_increment,
   passwd                      varchar(255)        NOT NULL,
   usr_id                      int(5)              Default NULL,
   PRIMARY KEY (id)
) ENGINE = InnoDB;