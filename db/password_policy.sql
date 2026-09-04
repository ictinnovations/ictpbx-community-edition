/*******************************************************************/
/* Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   */
/* Developed By: Nasir Iqbal                                       */
/* Website : http://www.ictinnovations.com/                        */
/* Mail : nasir@ictinnovations.com                                 */
/*******************************************************************/

/*==============================================================*/
/* Table: password_policy                                       */
/* Desc: Password Policy for User                               */
/*==============================================================*/
CREATE TABLE IF NOT EXISTS password_policy (
   password_policy_id          int(11)                  NOT NULL auto_increment,
   min_length                  int(11)                  default NULL,
   special_character           int(11)                  default NULL,
   min_numbers                 int(11)                  default NULL,
   min_uppercase               int(11)                  default NULL,
   min_lowercase               int(11)                  default NULL,
   passwd_exp_limit            int(11)                  default NULL,
   passwd_email_notify         int(11)                  default NULL,
   failed_attempts             int(11)                  default NULL,
   passwd_history              int(11)                  default NULL,
   sessiontime                 int(11)                  default NULL,

   PRIMARY KEY (password_policy_id)
) ENGINE = InnoDB;

/*==============================================================*/
/* Permission seed: grant 'password_policy' to admin role (id=2)*/
/*==============================================================*/
INSERT IGNORE INTO permission (name, description)
VALUES ('password_policy', 'Manage password policy');

INSERT INTO role_permission (role_id, permission_id)
SELECT 2, p.permission_id
FROM permission p
WHERE p.name = 'password_policy'
  AND NOT EXISTS (
    SELECT 1 FROM role_permission rp
    WHERE rp.role_id = 2 AND rp.permission_id = p.permission_id
  );
