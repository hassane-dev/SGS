-- =================================================================
-- Default Data for Roles and Permissions
-- =================================================================

-- 1. Create Roles
-- Global roles have lycee_id = NULL
INSERT INTO `roles` (`id_role`, `nom_role`, `lycee_id`) VALUES
(1, 'super_admin_createur', NULL),
(2, 'super_admin_national', NULL),
(3, 'admin_local', NULL), -- This is a template role for local admins
(4, 'enseignant', NULL), -- Template role for teachers
(5, 'surveillant', NULL), -- Template role
(6, 'censeur', NULL); -- Template role


-- 2. Create Permissions
INSERT INTO `permissions` (`nom_permission`, `description`) VALUES
('manage_roles', 'Can create, edit, and delete roles and assign permissions'),
('manage_licences', 'Can manage application licenses for schools'),
('manage_all_lycees', 'Can manage all high schools in the system'),
('manage_own_lycee_settings', 'Can manage settings for their own high school'),
('manage_users', 'Can manage users (create, edit, delete)'),
('manage_cycles', 'Can manage academic cycles'),
('manage_classes', 'Can manage classes'),
('manage_matieres', 'Can manage subjects and assign them to classes'),
('manage_eleves', 'Can manage student profiles'),
('manage_inscriptions', 'Can enroll students in classes'),
('manage_notes', 'Can enter student grades'),
('manage_paiements', 'Can manage student payments'),
('manage_boutique', 'Can manage the school shop'),
('manage_tests_entree', 'Can manage entrance tests'),
('view_bulletins', 'Can view student report cards');


-- 3. Assign Permissions to Roles

-- super_admin_createur (has all permissions)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, p.id_permission FROM permissions p;

-- super_admin_national (has all permissions EXCEPT license management)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, p.id_permission FROM permissions p WHERE p.nom_permission != 'manage_licences';

-- admin_local (template)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, p.id_permission FROM permissions p WHERE p.nom_permission IN (
    'manage_own_lycee_settings',
    'manage_users',
    'manage_cycles',
    'manage_classes',
    'manage_matieres',
    'manage_eleves',
    'manage_inscriptions',
    'manage_notes',
    'manage_paiements',
    'manage_boutique',
    'manage_tests_entree',
    'view_bulletins'
);

-- enseignant (template)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, p.id_permission FROM permissions p WHERE p.nom_permission IN (
    'manage_notes',
    'view_bulletins'
);

-- Note: The super_admin_createur user needs to be created separately
-- and assigned role_id = 1. The old createSuperUser method needs to be updated.
-- We also need a way to assign users to their specific lycee-scoped roles.
-- This seed file sets up the global templates.
