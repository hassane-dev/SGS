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
(8, 'eleve', NULL),
(9, 'chef_comptable', NULL),
(10, 'caissier', NULL),
(11, 'drh', NULL)
ON DUPLICATE KEY UPDATE nom_role=VALUES(nom_role);

-- --------------------------------------------------------
-- Clear Old Permissions before seeding new ones
-- --------------------------------------------------------
DELETE FROM `role_permissions`;
DELETE FROM `permissions`;

-- --------------------------------------------------------
-- Modern RBAC Permission Catalogue
-- Synchronized with migrate.php
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
(24, 'role', 'manage', 'Global permission for role management section'),

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
(35, 'class', 'manage', 'Global permission for class management section'),
(44, 'matiere', 'view', 'Can view the list of subjects'),
(45, 'matiere', 'create', 'Can create new subjects'),
(46, 'matiere', 'edit', 'Can edit existing subjects'),
(47, 'matiere', 'delete', 'Can delete subjects'),
(48, 'annee_academique', 'manage', 'Can manage academic years'),
(49, 'sequence', 'manage', 'Can manage academic sequences (trimesters, semesters)'),
(100, 'cycle', 'manage', 'Can manage academic cycles'),

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
(69, 'cahier_texte', 'manage', 'Global permission for cahier de texte section'),
(66, 'evaluation', 'manage_settings', 'Can configure evaluation (grading) periods'),
(67, 'presence', 'manage', 'Can manage student attendance'),
(68, 'presence', 'view', 'Can view student attendance'),

-- Finance & Report Cards
(70, 'paiement', 'manage', 'Can manage and validate student payments'),
(71, 'salaire', 'manage', 'Can manage staff payroll records'),
(72, 'frais', 'manage', 'Can manage the fee structure (frais)'),
(73, 'paiement', 'view', 'Can view student payment information and history'),
(75, 'bulletin', 'generate', 'Can generate and view report cards'),
(76, 'bulletin', 'validate', 'Can validate report cards and add final appreciations'),
(77, 'bulletin_template', 'manage', 'Can edit the report card template layout'),

-- Settings
(80, 'setting', 'edit', 'Can edit school-specific settings'),
(81, 'param_lycee', 'edit', 'Can edit the school identity settings'),
(82, 'param_general', 'edit', 'Can edit the general system settings for the school'),
(83, 'param_devoir', 'edit', 'Can edit the homework parameters'),
(84, 'param_composition', 'edit', 'Can edit the exam parameters'),

-- Timetable
(90, 'timetable', 'manage', 'Can manage timetables'),

-- Series
(91, 'series', 'manage', 'Gérer les structures des classes (séries, etc.)'),

-- Boutique
(95, 'boutique', 'manage', 'Gérer la boutique (articles et achats)'),

-- Test Entree
(96, 'tests_entree', 'manage', 'Gérer les tests d''entrée'),

-- Finance Permissions
(110, 'finance', 'view_policy', 'Consulter la politique financière du lycée'),
(111, 'finance', 'edit_policy', 'Modifier la politique financière du lycée'),
(112, 'finance', 'view_advantages', 'Consulter les avantages financiers des élèves'),
(113, 'finance', 'edit_advantages', 'Modifier les avantages financiers des élèves'),
(114, 'finance', 'view_history', 'Consulter l''historique des modifications de l''élève'),
(115, 'finance', 'view_control', 'Consulter le panneau de contrôle financier'),
(120, 'paiement', 'cancel', 'Annuler un reçu de paiement'),
(121, 'paiement', 'refund', 'Effectuer un remboursement élève'),
(122, 'annee_academique', 'cloturer', 'Clôturer ou réouvrir une année académique'),
(130, 'comptes_financiers', 'view', 'Consulter la liste des comptes financiers et leurs soldes'),
(131, 'comptes_financiers', 'create', 'Créer de nouveaux comptes financiers'),
(132, 'comptes_financiers', 'edit', 'Modifier les propriétés d''un compte financier'),
(133, 'comptes_financiers', 'manage', 'Suspendre ou réactiver un compte financier'),
(144, 'comptes_comptables', 'view', 'Consulter le plan de comptes comptables general'),
(145, 'comptes_comptables', 'create', 'Créer un compte comptable'),
(146, 'comptes_comptables', 'edit', 'Modifier un compte comptable'),
(149, 'comptes_comptables', 'delete', 'Supprimer ou désactiver un compte comptable'),
(134, 'sessions_caisse', 'create', 'Ouvrir une session de caisse physique journalière'),
(135, 'sessions_caisse', 'edit', 'Fermer sa propre session de caisse'),
(136, 'sessions_caisse', 'validate', 'Valider la clôture d''une session de caisse et régulariser les écarts'),
(137, 'sessions_caisse', 'reopen', 'Réouvrir exceptionnellement une caisse fermée (Permission sensible d''audit)'),
(138, 'mouvements_tresorerie', 'view', 'Consulter le grand livre de trésorerie'),
(139, 'mouvements_tresorerie', 'create', 'Enregistrer un mouvement de trésorerie manuel'),
(140, 'transferts', 'create', 'Initier une demande de virement inter-comptes'),
(141, 'transferts', 'validate', 'Approuver et exécuter un transfert de fonds'),
(142, 'sessions_caisse', 'view', 'Consulter la liste et le détail des sessions de caisse'),
(143, 'sessions_caisse', 'modify', 'Modifier une session de caisse'),
(147, 'finance', 'view_reports', 'Consulter les rapports financiers'),
(148, 'journal', 'view', 'Consulter le journal comptable unique'),

-- Phase 3 (Dépenses)
(150, 'depense', 'create', 'Créer une demande d''engagement de dépense (brouillon)'),
(151, 'depense', 'update', 'Modifier une demande de dépense non approuvée'),
(152, 'depense', 'validate', 'Voter pour ou approuver une demande de dépense'),
(153, 'depense', 'reject', 'Rejeter une demande de dépense'),
(154, 'depense', 'pay', 'Exécuter le règlement financier d''une dépense approuvée'),
(155, 'depense', 'cancel', 'Annuler une dépense payée avec contre-passation'),
(156, 'depense', 'view', 'Consulter la liste et l''historique d''audit des dépenses'),
(157, 'depense', 'export', 'Exporter le registre des dépenses'),
(158, 'depense', 'manage', 'Gérer les catégories, centres de coûts et bénéficiaires de dépenses'),

-- Phase 4 (Budgets)
(160, 'budget', 'view', 'Consulter la liste des budgets et lignes budgétaires'),
(161, 'budget', 'create', 'Configurer un nouveau budget annuel pour l''établissement'),
(162, 'budget', 'update', 'Modifier ou ajouter des lignes de budget'),
(163, 'budget', 'delete', 'Supprimer un budget non approuvé'),
(164, 'budget', 'activate', 'Valider et activer officiellement un budget annuel'),
(165, 'budget', 'close', 'Clôturer un exercice budgétaire'),
(166, 'budget', 'adjust', 'Ajouter une allocation supplémentaire d''urgence sur une ligne'),
(167, 'budget', 'transfer', 'Effectuer un virement de crédits entre deux lignes budgétaires'),
(168, 'budget', 'report', 'Consulter la synthèse visuelle et graphiques d''exécution'),
(169, 'budget', 'override', 'Autoriser le dépassement budgétaire exceptionnel'),

-- Module DRH (Lot 1)
(170, 'drh', 'view_all', 'Consulter le registre général du personnel RH'),
(171, 'drh', 'view_one', 'Consulter la fiche 360° d''un membre du personnel'),
(172, 'drh', 'create', 'Créer un nouveau membre du personnel'),
(173, 'drh', 'edit', 'Modifier les informations du personnel'),
(174, 'drh', 'delete', 'Archiver ou réactiver un compte personnel'),
(175, 'drh', 'manage_affectations', 'Gérer les affectations par lycée et par cycle'),
(176, 'drh', 'manage_contrats', 'Gérer les contrats et éléments contractuels de rémunération'),
(177, 'drh', 'manage_statut', 'Gérer les statuts RH (congés, suspensions, départs)'),
(178, 'drh', 'manage_documents', 'Téléverser et gérer les pièces jointes du personnel'),
(179, 'drh', 'view_sensitive', 'Consulter les données RH confidentielles (CNSS, contrats, salaires)'),
(180, 'drh', 'export', 'Exporter le registre et les rapports du personnel'),
(181, 'drh', 'view_history', 'Consulter l''historique d''audit des mouvements RH'),
(182, 'drh', 'view_contracts', 'Consulter les contrats du personnel'),
(183, 'drh', 'create_contracts', 'Créer un contrat pour un membre du personnel'),
(184, 'drh', 'create_amendments', 'Créer un avenant à un contrat existant'),
(185, 'drh', 'view_contract_history', 'Consulter l''historique des versions et avenants de contrat'),
(186, 'drh', 'view_contract_documents', 'Consulter les pièces jointes contractuelles'),

-- Exercices Financiers et Périodes Comptables
(190, 'comptabilite', 'view', 'Consulter les exercices financiers et périodes comptables'),
(191, 'comptabilite', 'create', 'Créer des exercices financiers et générer des périodes comptables'),
(192, 'comptabilite', 'edit', 'Modifier ou activer des exercices financiers et périodes comptables'),
(193, 'comptabilite', 'close', 'Clôturer un exercice financier ou une période comptable'),
(194, 'comptabilite', 'reopen', 'Réouvrir une période comptable clôturée'),

-- Module Paie (Lot 2.1)
(200, 'paie', 'view', 'Consulter les périodes, bulletins et registres de paie'),
(201, 'paie', 'create', 'Créer une nouvelle période de paie ou importer les salaires'),
(211, 'paie', 'edit', 'Modifier une période de paie non engagée'),
(202, 'paie', 'calculate', 'Lancer le calcul automatisé des bulletins de paie'),
(203, 'paie', 'validate', 'Valider les bulletins de paie et heures pédagogiques'),
(204, 'paie', 'redraw', 'Exécuter un re-tirage atomique de bulletin (V1 vers V2)'),
(205, 'paie', 'accounting', 'Comptabiliser les bulletins de paie au Grand Livre'),
(206, 'paie', 'settle', 'Payer et régler les bulletins de paie'),
(207, 'paie', 'regularize', 'Créer une régularisation de paie sur la période N+1'),
(208, 'paie', 'close', 'Clôturer définitivement une période de paie'),
(209, 'paie', 'audit', 'Consulter le journal d''audit complet de la paie'),
(210, 'paie', '*', 'Wildcard paie'),
(212, 'paie', 'config', 'Gérer les règles de paie, barèmes d\'impôts et cotisations'),

-- Phase 9 (Reporting Décisionnel)
(220, 'reporting', 'view', 'Consulter le tableau de bord décisionnel général'),
(221, 'reporting', 'dashboard', 'Accéder au cockpit principal de décision'),
(222, 'reporting', 'kpis', 'Consulter le catalogue détaillé des KPI et drill-down'),
(223, 'reporting', 'analyse', 'Accéder aux analyses d''évolution temporelle'),
(224, 'reporting', 'comparaison', 'Accéder aux analyses comparatives multi-établissements'),
(225, 'reporting', 'previsions', 'Consulter les projections de flux et prévisions financières'),
(226, 'reporting', 'export', 'Exporter les données de reporting au format CSV/PDF'),
(227, 'reporting', 'threshold_manage', 'Paramétrer les seuils d''alertes des KPI'),
(228, 'reporting', 'forecast_manage', 'Gérer les configurations et hypothèses de prévisions'),
(229, 'reporting', 'snapshot_manage', 'Gérer manuellement la génération de snapshots analytiques'),
(230, 'reporting', 'view_all_lycees', 'Permission spéciale d''audit et d\'analyse transversale multi-établissements'),

-- Pedagogy (Affectations Pédagogiques)
(240, 'pedagogy', 'manage_affectations', 'Créer, modifier, suspendre et clôturer les affectations pédagogiques'),
(241, 'pedagogy', 'view_affectations', 'Consulter le registre général des affectations pédagogiques'),
(242, 'pedagogy', 'view_my_affectations', 'Consulter ses propres affectations pédagogiques (enseignant)');

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
(3, 1), (3, 10), (3, 11), (3, 12), (3, 13), (3, 14), (3, 15), (3, 20), (3, 21), (3, 22), (3, 23), (3, 24),
-- Academic Structure
(3, 40), (3, 41), (3, 42), (3, 43), (3, 35), -- Classes & manage:class
(3, 44), (3, 45), (3, 46), (3, 47), -- Matieres
(3, 48), -- Annees Academiques
(3, 49), -- Sequences
(3, 100), -- Cycles
-- Students & Academics
(3, 50), (3, 51), (3, 52), (3, 53), (3, 54), (3, 55), (3, 56), (3, 61), (3, 62), (3, 63), (3, 69), (3, 66), (3, 67), (3, 68),
-- Finance & Settings
(3, 70), (3, 71), (3, 72), (3, 73), (3, 75), (3, 76), (3, 77),
(3, 81), (3, 82), (3, 83), (3, 84),
-- Timetable
(3, 90),
-- Series
(3, 91),
-- Boutique
(3, 95),
-- Test Entree
(3, 96),
-- Phase 1 & 2 integration
(3, 110), (3, 111), (3, 115), (3, 142), (3, 143), (3, 147), (3, 148),
-- Phase 3 (Dépenses)
(3, 150), (3, 151), (3, 152), (3, 153), (3, 154), (3, 155), (3, 156), (3, 157), (3, 158),
-- Phase 4 (Budgets)
(3, 160), (3, 161), (3, 162), (3, 163), (3, 164), (3, 165), (3, 166), (3, 167), (3, 168), (3, 169),
-- Exercices & Périodes Comptables
(3, 190), (3, 191), (3, 192), (3, 193), (3, 194),
-- Module DRH
(3, 170), (3, 171), (3, 172), (3, 173), (3, 175), (3, 180), (3, 181),
-- Module Paie
(3, 200), (3, 201), (3, 211), (3, 202), (3, 203), (3, 204), (3, 205), (3, 206), (3, 207), (3, 208), (3, 209), (3, 210),
-- Reporting
(3, 220), (3, 221), (3, 222), (3, 223), (3, 224), (3, 225), (3, 226), (3, 227), (3, 228), (3, 229), (3, 240), (3, 241);

-- Censeur (Academic Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 1), (4, 40), (4, 51), (4, 61), (4, 62), (4, 73), (4, 75), (4, 76), (4, 67), (4, 68), (4, 170), (4, 171), (4, 181), (4, 240), (4, 241);

-- Surveillant (Supervisor)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(5, 1), (5, 11), (5, 12), (5, 73), (5, 67), (5, 68);

-- Enseignant (Teacher)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(6, 1), (6, 60), (6, 61), (6, 64), (6, 65), (6, 67), (6, 68), (6, 242);

-- Comptable (Accountant)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(7, 1), (7, 70), (7, 71), (7, 73), (7, 110), (7, 112), (7, 114), (7, 115),
-- Trésorerie
(7, 130), (7, 134), (7, 135), (7, 138), (7, 140),
-- Phase 1 & 2 integration
(7, 142), (7, 143), (7, 147), (7, 148),
-- Phase 3 (Dépenses)
(7, 154), (7, 156), (7, 157),
-- Phase 4 (Budgets)
(7, 160), (7, 168),
-- Exercices & Périodes Comptables
(7, 190), (7, 191), (7, 192),
-- Module Paie (Consulter & Régler)
(7, 200), (7, 206);

-- Chef comptable (Chief Accountant)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(9, 1), (9, 70), (9, 71), (9, 73), (9, 110), (9, 111), (9, 112), (9, 113), (9, 114), (9, 115), (9, 120), (9, 121), (9, 122),
-- Trésorerie
(9, 130), (9, 131), (9, 132), (9, 133), (9, 134), (9, 135), (9, 136), (9, 137), (9, 138), (9, 139), (9, 140), (9, 141),
-- Phase 1 & 2 integration
(9, 142), (9, 143), (9, 147), (9, 148),
-- Phase 3 (Dépenses)
(9, 150), (9, 151), (9, 152), (9, 153), (9, 154), (9, 155), (9, 156), (9, 157), (9, 158),
-- Phase 4 (Budgets)
(9, 160), (9, 161), (9, 162), (9, 163), (9, 164), (9, 165), (9, 166), (9, 167), (9, 168), (9, 169),
-- Exercices & Périodes Comptables
(9, 190), (9, 191), (9, 192), (9, 193), (9, 194),
-- Module DRH
(9, 170), (9, 171), (9, 176), (9, 179), (9, 181),
-- Module Paie (Complet)
(9, 200), (9, 201), (9, 211), (9, 202), (9, 203), (9, 204), (9, 205), (9, 206), (9, 207), (9, 208), (9, 209), (9, 210);

-- Caissier (Cashier)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(10, 1), (10, 70), (10, 73),
-- Trésorerie
(10, 130), (10, 134), (10, 135),
-- Phase 1 & 2 integration
(10, 110), (10, 115), (10, 142), (10, 143), (10, 147), (10, 148);

-- Eleve (Student)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(8, 1); -- Can view dashboard

-- DRH (HR Manager)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 11, p.id_permission
FROM permissions p
WHERE p.resource = 'drh'
   OR p.resource = 'paie'
   OR p.resource = 'pedagogy'
   OR (p.resource = 'role' AND p.action = 'view_all')
   OR (p.resource = 'dashboard' AND p.action = 'view');

-- --------------------------------------------------------
-- Default Academic Cycles
-- --------------------------------------------------------
INSERT INTO `cycles` (`id_cycle`, `nom_cycle`, `niveau_debut`, `niveau_fin`) VALUES
(1, 'CEG', '6e', '3e'),
(2, 'Lycée', '2nd', 'Terminale')
ON DUPLICATE KEY UPDATE nom_cycle=VALUES(nom_cycle), niveau_debut=VALUES(niveau_debut), niveau_fin=VALUES(niveau_fin);

-- --------------------------------------------------------
-- Default Contract Types
-- These are global contract types available to all schools.
-- --------------------------------------------------------
INSERT INTO `type_contrat` (`libelle`, `description`, `type_paiement`, `prise_en_charge`, `lycee_id`) VALUES
('Fonctionnaire', 'Employé de la fonction publique', 'fixe', 'Etat', NULL),
('Contractuel', 'Employé sous contrat à durée déterminée ou indéterminée avec l''école', 'fixe', 'Ecole', NULL),
('Vacataire', 'Payé à l''heure pour des missions ponctuelles', 'a_l_heure', 'Ecole', NULL),
('Stagiaire', 'En stage au sein de l''établissement', 'aucun', 'Ecole', NULL);
