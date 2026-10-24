-- CE demo seed: two accounts, two roles (admin + end_user). No billing, no multi-tenant.
-- CE roles: admin (role_id=2) and end_user (role_id=4) only.
-- role_id=1 (user/base) and role_id=3 (tenant) are stripped from CE.
-- INSERT IGNORE makes re-runs idempotent.
INSERT IGNORE INTO tenant(tenant_id, first_name, last_name, company, phone, email, active, daily_limit, monthly_limit, daily_sent, monthly_sent)
  VALUES (1, 'Super', 'Admin', 'Super Admin', '111111', 'admin@ictcore.org', 1, 100000, 2000000, 0, 0);

-- Admin account (role_id=2 only — no base role_id=1 in CE)
INSERT IGNORE INTO usr(usr_id, tenant_id, username, passwd, first_name, last_name, email, phone, active, created_by, pass_exp_in, daily_limit, monthly_limit, allowed_days, allowed_timeslot)
  VALUES (1, 1, 'admin@ictcore.org', MD5('helloAdmin'), 'System', 'Administrator', 'admin@ictcore.org', '111111', 1, 1, 0, 10000, 2000000, '1,2,3,4,5,6,7', '00:00,23:59');
INSERT IGNORE INTO user_role (role_id, usr_id) SELECT role_id, 1 FROM role WHERE name='admin';

-- End-user demo account (role_id=4 only)
INSERT IGNORE INTO usr(usr_id, tenant_id, username, passwd, first_name, last_name, email, phone, active, created_by, pass_exp_in, daily_limit, monthly_limit, allowed_days, allowed_timeslot)
  VALUES (2, 1, 'user@ictcore.org', MD5('helloUser'), 'End', 'User', 'user@ictcore.org', '111111', 1, 1, 0, 100, 1000, '1,2,3,4,5,6,7', '00:00,23:59');
INSERT IGNORE INTO user_role (role_id, usr_id) SELECT role_id, 2 FROM role WHERE name='end_user';
UPDATE usr SET user_permission='voicemails,follow_me' WHERE usr_id=2 AND user_permission='';
