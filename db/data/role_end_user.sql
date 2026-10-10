-- Create end_user role (role_id=4) with correct base permissions copied from user role (role_id=1)
-- Does NOT include user_admin / usr_admin (those are tenant-admin-only)
INSERT IGNORE INTO role (role_id, name, description) VALUES (4, 'end_user', 'authorized end user');

-- Remove any accidentally seeded admin-level permissions from end_user
DELETE FROM role_permission
WHERE role_id = 4
  AND permission_id IN (
    SELECT permission_id FROM permission WHERE name IN ('user_admin', 'usr_admin', 'super_admin')
  );

-- Copy base user (role_id=1) permissions to end_user; ignore duplicates
INSERT IGNORE INTO role_permission (role_id, permission_id)
SELECT 4, permission_id FROM role_permission WHERE role_id = 1;
