/*******************************************************************/
/* Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   */
/* Developed By: Nasir Iqbal                                       */
/* Website : http://www.ictinnovations.com/                        */
/* Mail : nasir@ictinnovations.com                                 */
/*******************************************************************/

/*==============================================================*/
/* Table: login_attempts                                        */
/* Desc: this table provide store information related to each   */
/*       failed attempts perform by user.                       */
/* TODO: handle / keep data of previous / multi attempts.       */
/*==============================================================*/


CREATE TABLE login_attempts 
(   
   login_attempts_id                             INT(11)                NOT NULL AUTO_INCREMENT,
   user_id                        INT(6)                 DEFAULT NULL,
   attempts                       int(11)                NOT NULL default 0,
   ip_address                     VARCHAR(255)           DEFAULT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB;