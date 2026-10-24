-- Tenant admins need 'program' to send a fax: Send Fax creates a sendfax program
-- (program_create) before the transmission. role_tenant.sql never granted it.
-- Idempotent; a no-op where no 'tenant' role exists (CE).
INSERT INTO role_permission (role_id, permission_id)
SELECT r.role_id, p.permission_id
  FROM role r JOIN permission p ON p.name = 'program'
 WHERE r.name = 'tenant'
   AND NOT EXISTS (SELECT 1 FROM role_permission rp
                    WHERE rp.role_id = r.role_id AND rp.permission_id = p.permission_id);
