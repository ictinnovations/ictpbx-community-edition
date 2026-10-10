INSERT INTO role(name, description) VALUES ('admin', 'system administrator');

-- permissions for admin role
SELECT @roleId := role_id FROM role WHERE name='admin';
-- provider permissions
SELECT @permissionId := permission_id FROM permission WHERE name='super_admin';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* Super Admin */
SELECT @permissionId := permission_id FROM permission WHERE name='api';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* api */
SELECT @permissionId := permission_id FROM permission WHERE name='statistic';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* statistic */
SELECT @permissionId := permission_id FROM permission WHERE name='configuration';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* configuration */
SELECT @permissionId := permission_id FROM permission WHERE name='user';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* user */
SELECT @permissionId := permission_id FROM permission WHERE name='usr';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* usr */
SELECT @permissionId := permission_id FROM permission WHERE name='role';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* role */
SELECT @permissionId := permission_id FROM permission WHERE name='permission';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* permission */
SELECT @permissionId := permission_id FROM permission WHERE name='account';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* account */
SELECT @permissionId := permission_id FROM permission WHERE name='provider';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* provider */
SELECT @permissionId := permission_id FROM permission WHERE name='tenant';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* tenant */

/* Additional Permissions */
SELECT @permissionId := permission_id FROM permission WHERE name='transmission';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* transmission */
SELECT @permissionId := permission_id FROM permission WHERE name='document';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* document */
SELECT @permissionId := permission_id FROM permission WHERE name='contact';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* contact */
SELECT @permissionId := permission_id FROM permission WHERE name='group';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* group */
SELECT @permissionId := permission_id FROM permission WHERE name='campaign';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* campaign */

SELECT @permissionId := permission_id FROM permission WHERE name='payment';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* payment */
SELECT @permissionId := permission_id FROM permission WHERE name='rate';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* rate */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* cdr */
SELECT @permissionId := permission_id FROM permission WHERE name='plan';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* plan */

SELECT @permissionId := permission_id FROM permission WHERE name='branding';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* branding */


SELECT @permissionId := permission_id FROM permission WHERE name='announcement';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* announcement */

SELECT @permissionId := permission_id FROM permission WHERE name='password_policy';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* password_policy */

SELECT @permissionId := permission_id FROM permission WHERE name='enduser_password';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* enduser_password */

SELECT @permissionId := permission_id FROM permission WHERE name='document';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* document */

SELECT @permissionId := permission_id FROM permission WHERE name='route';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* route */


-- Session 2026-05-14: missing admin grants for granular permission trees
SELECT @roleId := role_id FROM role WHERE name='admin';
SELECT @permissionId := permission_id FROM permission WHERE name='cdr_list';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* cdr_list */
SELECT @permissionId := permission_id FROM permission WHERE name='cdr_read';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* cdr_read */
SELECT @permissionId := permission_id FROM permission WHERE name='country_create';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* country_create */
SELECT @permissionId := permission_id FROM permission WHERE name='country_delete';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* country_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='country_list';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* country_list */
SELECT @permissionId := permission_id FROM permission WHERE name='country_read';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* country_read */
SELECT @permissionId := permission_id FROM permission WHERE name='country_update';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* country_update */
SELECT @permissionId := permission_id FROM permission WHERE name='destination_create';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* destination_create */
SELECT @permissionId := permission_id FROM permission WHERE name='destination_delete';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* destination_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='destination_list';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* destination_list */
SELECT @permissionId := permission_id FROM permission WHERE name='destination_read';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* destination_read */
SELECT @permissionId := permission_id FROM permission WHERE name='destination_update';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* destination_update */
SELECT @permissionId := permission_id FROM permission WHERE name='region_create';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* region_create */
SELECT @permissionId := permission_id FROM permission WHERE name='region_delete';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* region_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='region_list';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* region_list */
SELECT @permissionId := permission_id FROM permission WHERE name='region_read';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* region_read */
SELECT @permissionId := permission_id FROM permission WHERE name='region_update';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* region_update */
SELECT @permissionId := permission_id FROM permission WHERE name='route_create';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* route_create */
SELECT @permissionId := permission_id FROM permission WHERE name='route_delete';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* route_delete */
SELECT @permissionId := permission_id FROM permission WHERE name='route_list';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* route_list */
SELECT @permissionId := permission_id FROM permission WHERE name='template';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* template */
SELECT @permissionId := permission_id FROM permission WHERE name='payment_list';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* payment_list */
SELECT @permissionId := permission_id FROM permission WHERE name='payment_read';
INSERT INTO role_permission (role_id, permission_id) SELECT @roleId, @permissionId FROM dual WHERE @permissionId IS NOT NULL;   /* payment_read */
