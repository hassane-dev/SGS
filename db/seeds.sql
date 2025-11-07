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

-- Lycees (Schools) & System
(30, 'lycee', 'create', 'Can create new schools (super admin)'),
(31, 'lycee', 'view_all', 'Can view all schools (super admin)'),
(32, 'lycee', 'edit', 'Can edit school information (super admin)'),
(33, 'lycee', 'delete', 'Can delete schools (super admin)'),
(34, 'lycee', 'view_all_lycees', 'Special permission to bypass lycee_id scope checks'),

-- Academic Structure
(40, 'class', 'view', 'Can view the list of classes'),
(41, 'class', 'create', 'Can create new classes'),
(42, 'class', 'edit', 'Can edit existing classes'),
(43, 'class', 'delete', 'Can delete classes'),
(44, 'matiere', 'view', 'Can view the list of subjects'),
(45, 'matiere', 'create', 'Can create new subjects'),
(46, 'matiere', 'edit', 'Can edit existing subjects'),
(47, 'matiere', 'delete', 'Can delete subjects'),
(48, 'annee_academique', 'manage', 'Can manage academic years'),
(49, 'sequence', 'manage', 'Can manage academic sequences (trimesters, semesters)'),


-- Students
(50, 'eleve', 'create', 'Can create new student profiles'),
(51, 'eleve', 'view_all', 'Can view all student profiles'),
(52, 'eleve', 'edit', 'Can edit student profiles'),
(53, 'eleve', 'delete', 'Can delete student profiles'),
(54, 'inscription', 'manage', 'Can enroll students in classes'),
(55, 'eleve', 'inscrire', 'Can enroll a new student'),
(56, 'eleve', 'reinscrire', 'Can re-enroll an existing student for a new academic year'),

-- Academics
(60, 'note', 'create_own', 'Can enter their own student grades'),
(61, 'note', 'view_all', 'Can view all student grades'),
(62, 'cahier_texte', 'view_all', 'Can view all digital logbook entries'),
(63, 'cahier_texte', 'edit_all', 'Can edit any digital logbook entry'),
(64, 'cahier_texte', 'create_own', 'Can fill their own digital logbook entries'),
(65, 'cahier_texte', 'edit_own', 'Can edit their own digital logbook entries'),
(66, 'evaluation', 'manage_settings', 'Can configure evaluation (grading) periods'),

-- Finance
(70, 'paiement', 'manage', 'Can manage and validate student payments'),
(71, 'salaire', 'manage', 'Can manage staff payroll records'),
(72, 'frais', 'manage', 'Can manage the fee structure (frais)'),
(75, 'bulletin', 'generate', 'Can generate and view report cards'),
(76, 'bulletin', 'validate', 'Can validate report cards and add final appreciations'),
(77, 'bulletin_template', 'manage', 'Can edit the report card template layout'),

-- Settings
(80, 'setting', 'edit', 'Can edit school-specific settings'),
(81, 'param_lycee', 'edit', 'Can edit the school identity settings'),
(82, 'param_general', 'edit', 'Can edit the general system settings for the school'),
(83, 'param_devoir', 'edit', 'Can edit the homework parameters'),
(84, 'param_composition', 'edit', 'Can edit the exam parameters');

-- --------------------------------------------------------
-- Role-Permission Assignments
-- --------------------------------------------------------

-- Super Admins (Creator & National) get all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id_role, p.id_permission
FROM roles r, permissions p
WHERE r.nom_role IN ('super_admin_createur', 'super_admin_national');

-- Admin Local
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
-- Dashboard & Users & Roles
(3, 1), (3, 10), (3, 11), (3, 12), (3, 13), (3, 14), (3, 15), (3, 20), (3, 21), (3, 22), (3, 23),
-- Academic Structure
(3, 40), (3, 41), (3, 42), (3, 43), -- Classes
(3, 44), (3, 45), (3, 46), (3, 47), -- Matieres
(3, 48), -- Annees Academiques
(3, 49), -- Sequences
-- Students & Academics
(3, 50), (3, 51), (3, 52), (3, 53), (3, 54), (3, 55), (3, 56), (3, 61), (3, 62), (3, 63), (3, 66),
-- Finance & Settings
(3, 70), (3, 71), (3, 72), (3, 75), (3, 76), (3, 77),
(3, 81), (3, 82), (3, 83), (3, 84);

-- Censeur (Academic Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 1), (4, 40), (4, 51), (4, 61), (4, 62), (4, 75), (4, 76);

-- Surveillant (Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(5, 1), (5, 11), (5, 12);

-- Enseignant (Teacher)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(6, 1), (6, 60), (6, 64), (6, 65);

-- Comptable (Accountant)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(7, 1), (7, 70), (7, 71);

-- Eleve (Student)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(8, 1); -- Can view dashboard

-- --------------------------------------------------------
-- Default Contract Types
-- These are global contract types available to all schools.
-- --------------------------------------------------------
INSERT INTO `type_contrat` (`libelle`, `description`, `type_paiement`, `prise_en_charge`, `lycee_id`) VALUES
('Fonctionnaire', 'Employé de la fonction publique', 'fixe', 'Etat', NULL),
('Contractuel', 'Employé sous contrat à durée déterminée ou indéterminée avec l''école', 'fixe', 'Ecole', NULL),
('Vacataire', 'Payé à l''heure pour des missions ponctuelles', 'a_l_heure', 'Ecole', NULL),
('Stagiaire', 'En stage au sein de l''établissement', 'aucun', 'Ecole', NULL);