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
(8, 'eleve', NULL)
ON DUPLICATE KEY UPDATE nom_role=VALUES(nom_role);

-- --------------------------------------------------------
-- Clear Old Permissions before seeding new ones
-- --------------------------------------------------------
DELETE FROM `role_permissions`;
DELETE FROM `permissions`;

-- --------------------------------------------------------
-- New Granular Permissions
-- --------------------------------------------------------
INSERT INTO `permissions` (`id_permission`, `resource`, `action`, `description`) VALUES
-- Dashboard
(1, 'dashboard', 'view', 'Can view the main dashboard'),

-- Users (Personnel)
(10, 'user', 'create', 'Can create new users'),
(11, 'user', 'view_all', 'Can view a list of all users within their scope'),
(12, 'user', 'view_one', 'Can view the detailed profile of a single user'),
(13, 'user', 'edit', 'Can edit user information'),
(14, 'user', 'delete', 'Can delete users'),
(15, 'user', 'manage', 'Global permission for user management section'),

-- Roles
(20, 'role', 'create', 'Can create new roles'),
(21, 'role', 'view_all', 'Can view all roles'),
(22, 'role', 'edit', 'Can edit roles and assign permissions'),
(23, 'role', 'delete', 'Can delete roles'),
(24, 'permission', 'manage', 'Can manage permissions (add, edit, delete)'),

-- Lycees (Schools) & System
(30, 'lycee', 'create', 'Can create new schools (super admin)'),
(31, 'lycee', 'view_all', 'Can view all schools (super admin)'),
(32, 'lycee', 'edit', 'Can edit school information (super admin)'),
(33, 'lycee', 'delete', 'Can delete schools (super admin)'),
(34, 'system', 'view_all_lycees', 'Special permission to bypass lycee_id scope checks'),

-- Academic Structure
(40, 'class', 'view', 'Can view the list of classes'),
(41, 'class', 'create', 'Can create new classes'),
(42, 'class', 'edit', 'Can edit existing classes'),
(43, 'class', 'delete', 'Can delete classes'),
(44, 'matiere', 'manage', 'Can manage subjects and their coefficients'),
(45, 'annee_academique', 'manage', 'Can manage academic years'),


-- Students
(50, 'eleve', 'create', 'Can create new student profiles'),
(51, 'eleve', 'view_all', 'Can view all student profiles'),
(52, 'eleve', 'edit', 'Can edit student profiles'),
(53, 'eleve', 'delete', 'Can delete student profiles'),
(54, 'inscription', 'manage', 'Can enroll students in classes'),

-- Academics
(60, 'note', 'manage', 'Can enter and manage student grades'),
(61, 'cahier_texte', 'view_all', 'Can view all digital logbook entries'),
(62, 'cahier_texte', 'edit_all', 'Can edit any digital logbook entry'),
(63, 'cahier_texte', 'create_own', 'Can fill their own digital logbook entries'),
(64, 'cahier_texte', 'edit_own', 'Can edit their own digital logbook entries'),

-- Finance
(70, 'paiement', 'manage', 'Can manage and validate student payments'),
(71, 'salaire', 'manage', 'Can manage staff payroll records'),
(72, 'frais', 'manage', 'Can manage the fee structure (frais)'),

-- Settings
(80, 'setting', 'edit', 'Can edit school-specific settings');

-- --------------------------------------------------------
-- Role-Permission Assignments
-- --------------------------------------------------------

-- Super Admins (Creator & National) get all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id_role, p.id_permission
FROM roles r, permissions p
WHERE r.nom_role IN ('super_admin_createur', 'super_admin_national');

-- Admin Local
-- Note: Permission 40 for 'class.manage' is replaced by 40, 41, 42, 43.
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), (3, 10), (3, 11), (3, 12), (3, 13), (3, 14), (3, 15), (3, 20), (3, 21), (3, 22), (3, 23), (3, 24),
(3, 40), (3, 41), (3, 42), (3, 43), (3, 44), (3, 45),
(3, 50), (3, 51), (3, 52), (3, 53), (3, 54), (3, 60), (3, 61), (3, 62),
(3, 70), (3, 71), (3, 72), (3, 80);

-- Censeur (Academic Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 1), (4, 40), (4, 51), (4, 60), (4, 61);

-- Surveillant (Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(5, 1), (5, 11), (5, 12);

-- Enseignant (Teacher)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(6, 1), (6, 60), (6, 63), (6, 64);

-- Comptable (Accountant)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(7, 1), (7, 70), (7, 71);

-- Eleve (Student)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(8, 1); -- Can view dashboard