INSERT INTO role(name, description) VALUES ('tenant', 'authorized tenant');

-- permissions for tenant role
SELECT @roleId := role_id FROM role WHERE name='tenant';
-- provider permissions
SELECT @permissionId := permission_id FROM permission WHERE name='transmission_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* transmission_create */
SELECT @permissionId := permission_id FROM permission WHERE name='transmission_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* transmission_list */
SELECT @permissionId := permission_id FROM permission WHERE name='transmission_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* transmission_read */
SELECT @permissionId := permission_id FROM permission WHERE name='transmission_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* transmission_update */
SELECT @permissionId := permission_id FROM permission WHERE name='transmission_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* transmission_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='transmission_send';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* transmission_send */
SELECT @permissionId := permission_id FROM permission WHERE name='task';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* task */
SELECT @permissionId := permission_id FROM permission WHERE name='task_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* task_create */
SELECT @permissionId := permission_id FROM permission WHERE name='task_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* task_read */
SELECT @permissionId := permission_id FROM permission WHERE name='task_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* task_list */
SELECT @permissionId := permission_id FROM permission WHERE name='task_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* task_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='schedule';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* schedule */
SELECT @permissionId := permission_id FROM permission WHERE name='schedule_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* schedule_create */
SELECT @permissionId := permission_id FROM permission WHERE name='schedule_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* schedule_read */
SELECT @permissionId := permission_id FROM permission WHERE name='schedule_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* schedule_list */
SELECT @permissionId := permission_id FROM permission WHERE name='schedule_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* schedule_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='spool';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* spool */
SELECT @permissionId := permission_id FROM permission WHERE name='spool_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* spool_read */
SELECT @permissionId := permission_id FROM permission WHERE name='result';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* result */
SELECT @permissionId := permission_id FROM permission WHERE name='result_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* result_read */
SELECT @permissionId := permission_id FROM permission WHERE name='result_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* result_list */
SELECT @permissionId := permission_id FROM permission WHERE name='contact';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* contact */
SELECT @permissionId := permission_id FROM permission WHERE name='contact_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* contact_create */
SELECT @permissionId := permission_id FROM permission WHERE name='contact_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* contact_list */
SELECT @permissionId := permission_id FROM permission WHERE name='contact_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* contact_read */
SELECT @permissionId := permission_id FROM permission WHERE name='contact_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* contact_update */
SELECT @permissionId := permission_id FROM permission WHERE name='contact_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* contact_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='group';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* group */
SELECT @permissionId := permission_id FROM permission WHERE name='group_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* group_create */
SELECT @permissionId := permission_id FROM permission WHERE name='group_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* group_list */
SELECT @permissionId := permission_id FROM permission WHERE name='group_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* group_read */
SELECT @permissionId := permission_id FROM permission WHERE name='group_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* group_update */
SELECT @permissionId := permission_id FROM permission WHERE name='group_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* group_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='account';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* account */
SELECT @permissionId := permission_id FROM permission WHERE name='account_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* account_create */
SELECT @permissionId := permission_id FROM permission WHERE name='account_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* account_list */
SELECT @permissionId := permission_id FROM permission WHERE name='account_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* account_read */
SELECT @permissionId := permission_id FROM permission WHERE name='account_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* account_update */
SELECT @permissionId := permission_id FROM permission WHERE name='account_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* account_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='user';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user */
SELECT @permissionId := permission_id FROM permission WHERE name='user_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user_create */
SELECT @permissionId := permission_id FROM permission WHERE name='user_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user_list */
SELECT @permissionId := permission_id FROM permission WHERE name='user_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user_read */
SELECT @permissionId := permission_id FROM permission WHERE name='user_password';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user_password */
SELECT @permissionId := permission_id FROM permission WHERE name='user_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user_update */
SELECT @permissionId := permission_id FROM permission WHERE name='user_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='user_admin';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* user_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='usr';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr */
SELECT @permissionId := permission_id FROM permission WHERE name='usr_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr_create */
SELECT @permissionId := permission_id FROM permission WHERE name='usr_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr_list */
SELECT @permissionId := permission_id FROM permission WHERE name='usr_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr_read */
SELECT @permissionId := permission_id FROM permission WHERE name='usr_password';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr_password */
SELECT @permissionId := permission_id FROM permission WHERE name='usr_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr_update */
SELECT @permissionId := permission_id FROM permission WHERE name='usr_admin';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr_update */
SELECT @permissionId := permission_id FROM permission WHERE name='usr_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* usr_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign_create */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign_list */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign_read */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign_update */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign_start';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign_start */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign_stop';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* campaign_stop */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* cdr */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* cdr_create */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* cdr_list */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* cdr_read */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* cdr_update */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* cdr_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='document';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* document */
SELECT @permissionId := permission_id FROM permission WHERE name='document_create';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* document_create */
SELECT @permissionId := permission_id FROM permission WHERE name='document_list';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* document_list */
SELECT @permissionId := permission_id FROM permission WHERE name='document_read';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* document_read */
SELECT @permissionId := permission_id FROM permission WHERE name='document_update';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* document_update */
SELECT @permissionId := permission_id FROM permission WHERE name='document_delete';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* document_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='role';
INSERT INTO role_permission VALUES (NULL, @roleId, @permissionId);   /* role */

