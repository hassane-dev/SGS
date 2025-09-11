-- Migration to add permissions for the timetable feature

-- 1. Add the new permission
INSERT INTO `permissions` (`nom_permission`, `description`)
VALUES ('manage_timetable', 'Allow user to manage timetables');

-- 2. Associate the permission with admin roles
-- Assuming 'admin_local' has id_role = 4 and 'super_admin_national' has id_role = 6
-- A more robust migration would look up the IDs first.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT
    (SELECT id_role FROM roles WHERE nom_role = 'admin_local'),
    (SELECT id_permission FROM permissions WHERE nom_permission = 'manage_timetable');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT
    (SELECT id_role FROM roles WHERE nom_role = 'super_admin_national'),
    (SELECT id_permission FROM permissions WHERE nom_permission = 'manage_timetable');
