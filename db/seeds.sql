-- =================================================================
-- Default Data Seeds for School Management Application
-- This script should be run AFTER schema.sql
-- =================================================================

-- --------------------------------------------------------
-- Default Roles
-- These are global roles available to all schools.
-- --------------------------------------------------------

INSERT INTO `roles` (`id_role`, `nom_role`, `lycee_id`) VALUES
(1, 'super_admin_createur', NULL),
(2, 'super_admin_national', NULL),
(3, 'admin_local', NULL), -- This is a template role for school-specific admins
(4, 'censeur', NULL),
(5, 'surveillant', NULL),
(6, 'enseignant', NULL),
(7, 'comptable', NULL),
(8, 'eleve', NULL);

-- --------------------------------------------------------
-- Default Permissions
-- A list of actions that can be performed in the system.
-- --------------------------------------------------------

INSERT INTO `permissions` (`id_permission`, `nom_permission`, `description`) VALUES
(1, 'manage_users', 'Can create, edit, and delete users.'),
(2, 'manage_roles', 'Can manage roles and their permissions.'),
(3, 'manage_lycees', 'Can manage schools (for national admins).'),
(4, 'manage_all_lycees', 'Global permission to see data from all schools.'),
(5, 'manage_classes', 'Can manage classes, cycles, and rooms.'),
(6, 'manage_matieres', 'Can manage subjects and coefficients.'),
(7, 'manage_eleves', 'Can manage student profiles.'),
(8, 'manage_inscriptions', 'Can enroll students in classes.'),
(9, 'manage_notes', 'Can enter and manage student grades.'),
(10, 'manage_cahier_texte', 'Can view and manage all digital logbook entries.'),
(11, 'fill_cahier_texte', 'Can fill their own digital logbook entries (for teachers).'),
(12, 'manage_paiements', 'Can manage student payments.'),
(13, 'manage_salaires', 'Can manage staff payroll.'),
(14, 'manage_settings', 'Can edit school-specific settings.'),
(15, 'view_dashboard', 'Can view the main dashboard.');

-- --------------------------------------------------------
-- Role-Permission Assignments
-- Linking roles to their allowed permissions.
-- --------------------------------------------------------

-- Super Admin (Creator and National) has all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15),
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15);

-- Local Admin (Template) has most permissions, but not global ones
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), -- manage_users
(3, 2), -- manage_roles
(3, 5), -- manage_classes
(3, 6), -- manage_matieres
(3, 7), -- manage_eleves
(3, 8), -- manage_inscriptions
(3, 9), -- manage_notes
(3, 10), -- manage_cahier_texte
(3, 12), -- manage_paiements
(3, 13), -- manage_salaires
(3, 14), -- manage_settings
(3, 15); -- view_dashboard

-- Censeur (Academic Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 5), -- manage_classes
(4, 6), -- manage_matieres
(4, 9), -- manage_notes
(4, 10), -- manage_cahier_texte
(4, 15); -- view_dashboard

-- Surveillant (Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(5, 7), -- manage_eleves (e.g., view profiles)
(5, 15); -- view_dashboard

-- Enseignant (Teacher)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(6, 9), -- manage_notes (for their classes)
(6, 11), -- fill_cahier_texte
(6, 15); -- view_dashboard

-- Comptable (Accountant)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(7, 12), -- manage_paiements
(7, 13), -- manage_salaires
(7, 15); -- view_dashboard

-- Eleve (Student) - can only view their own things, handled in code
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(8, 15); -- view_dashboard (e.g., a student dashboard)