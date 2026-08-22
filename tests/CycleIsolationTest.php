<?php
// tests/CycleIsolationTest.php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../migrate.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/JournalComptable.php';

function run_cycle_isolation_tests() {
    echo "========================================================\n";
    echo "RUNNING SUITE: Isolation par Cycle & Sécurisation Globale (Phase 10)\n";
    echo "========================================================\n";

    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // --- 1. SETUP FIXTURES STRICTES ET COHÉRENTES ---

        // Setup Test Lycee
        $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (99, 'Lycée de Test Isolation') ON CONFLICT DO NOTHING");

        // Setup Cycles
        $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (991, 99, 'Cycle CEG Test')");
        $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (992, 99, 'Cycle Lycée Test')");

        // Setup Classes for both Cycles
        $db->exec("INSERT INTO classes (id_classe, niveau, cycle_id, lycee_id) VALUES (9910, '6eme', 991, 99)");
        $db->exec("INSERT INTO classes (id_classe, niveau, cycle_id, lycee_id) VALUES (9920, '2nde', 992, 99)");

        // Setup Students (Eleves)
        $db->exec("INSERT INTO eleves (id_eleve, nom, prenom, lycee_id, statut) VALUES (99100, 'Koffi', 'Jean', 99, 'actif')");
        $db->exec("INSERT INTO eleves (id_eleve, nom, prenom, lycee_id, statut) VALUES (99200, 'Soro', 'Ali', 99, 'actif')");

        // Associate Students to their classes for active academic year
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            $db->exec("INSERT OR IGNORE INTO annees_academiques (id, libelle, est_active) VALUES (99, '2024-2025', 1)");
            $annee_id = 99;
        } else {
            $annee_id = $active_year['id'];
        }
        $db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (99100, 9910, 99, $annee_id, 'active', 1)");
        $db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (99200, 9920, 99, $annee_id, 'active', 1)");

        // Setup Role
        $db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (99, 'Test Comptable Spécifique', 99)");

        // Grant permissions for class, eleve & journal view/edit to this role
        $required_perms = [
            ['eleve', 'view_all', 'Can view all students'],
            ['eleve', 'edit', 'Can edit students'],
            ['class', 'edit', 'Can edit classes'],
            ['journal', 'view', 'Can view journal']
        ];
        $stmt_ins = $db->prepare("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES (:res, :act, :desc)");
        foreach ($required_perms as $rp) {
            $stmt_ins->execute(['res' => $rp[0], 'act' => $rp[1], 'desc' => $rp[2]]);
        }

        $stmt_p = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :res AND action = :act");
        foreach ($required_perms as $p_g) {
            $stmt_p->execute(['res' => $p_g[0], 'act' => $p_g[1]]);
            $p_id = $stmt_p->fetchColumn();
            if ($p_id) {
                $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (99, $p_id)");
            }
        }

        // Setup Personnel (Utilisateurs)
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $db->exec("
            INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version)
            VALUES (9901, 'Mohamed', 'Comptable', 'mohamed@test.com', '$password', 99, 99, 1, 1)
        ");

        echo "[OK] Initial Setup Completed.\n\n";

        // --- TEST A: AFFECTATION UNIQUE (CEG uniquement) ---
        echo "TEST A: Affectation unique (CEG uniquement)\n";
        $today = date('Y-m-d');
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 991, '$today', NULL, 1)
        ");

        if (!Auth::login('mohamed@test.com', 'password123')) {
            throw new Exception("Échec de connexion simulée.");
        }

        $authorized_cycles = AuthorizationScopeService::getAuthorizedCycleIds();
        if (in_array(991, $authorized_cycles) && !in_array(992, $authorized_cycles)) {
            echo "  -> Attendu: CEG [991], Non-Lycée [992] | Obtenu: " . json_encode($authorized_cycles) . " | PASS\n";
        } else {
            echo "  -> Attendu: CEG [991] uniquement | Obtenu: " . json_encode($authorized_cycles) . " | FAIL\n";
            exit(1);
        }

        // --- TEST B: AFFECTATION MULTIPLE (CEG + Lycée) ---
        echo "TEST B: Affectation multiple (CEG + Lycée)\n";
        $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id = 9901");
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 991, '$today', NULL, 1)
        ");
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 992, '$today', NULL, 1)
        ");

        AuthorizationScopeService::invalidateUserCache(9901);
        $authorized_cycles = AuthorizationScopeService::getAuthorizedCycleIds();
        if (in_array(991, $authorized_cycles) && in_array(992, $authorized_cycles)) {
            echo "  -> Attendu: [991, 992] | Obtenu: " . json_encode($authorized_cycles) . " | PASS\n";
        } else {
            echo "  -> Attendu: [991, 992] | Obtenu: " . json_encode($authorized_cycles) . " | FAIL\n";
            exit(1);
        }

        // --- TEST C: AFFECTATION EXPIRÉE ---
        echo "TEST C: Affectation expirée\n";
        $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id = 9901");
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 991, '2020-01-01', '$yesterday', 1)
        ");

        AuthorizationScopeService::invalidateUserCache(9901);
        $authorized_cycles = AuthorizationScopeService::getAuthorizedCycleIds();
        if (!in_array(991, $authorized_cycles)) {
            echo "  -> Attendu: [] (Expiré) | Obtenu: " . json_encode($authorized_cycles) . " | PASS\n";
        } else {
            echo "  -> Attendu: [] (Expiré) | Obtenu: " . json_encode($authorized_cycles) . " | FAIL\n";
            exit(1);
        }

        // --- TEST D: AFFECTATION FUTURE ---
        echo "TEST D: Affectation future\n";
        $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id = 9901");
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 991, '$tomorrow', NULL, 1)
        ");

        AuthorizationScopeService::invalidateUserCache(9901);
        $authorized_cycles = AuthorizationScopeService::getAuthorizedCycleIds();
        if (!in_array(991, $authorized_cycles)) {
            echo "  -> Attendu: [] (Futur) | Obtenu: " . json_encode($authorized_cycles) . " | PASS\n";
        } else {
            echo "  -> Attendu: [] (Futur) | Obtenu: " . json_encode($authorized_cycles) . " | FAIL\n";
            exit(1);
        }

        // --- TEST E: DÉSACTIVATION ---
        echo "TEST E: Désactivation\n";
        $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id = 9901");
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 991, '2020-01-01', NULL, 0)
        ");

        AuthorizationScopeService::invalidateUserCache(9901);
        $authorized_cycles = AuthorizationScopeService::getAuthorizedCycleIds();
        if (!in_array(991, $authorized_cycles)) {
            echo "  -> Attendu: [] (Actif=0) | Obtenu: " . json_encode($authorized_cycles) . " | PASS\n";
        } else {
            echo "  -> Attendu: [] (Actif=0) | Obtenu: " . json_encode($authorized_cycles) . " | FAIL\n";
            exit(1);
        }

        // --- TEST F: IDOR PROTECTION ---
        echo "TEST F: IDOR Protection\n";
        $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id = 9901");
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 991, '2020-01-01', NULL, 1)
        ");
        AuthorizationScopeService::invalidateUserCache(9901);

        $ceg_student = Eleve::findById(99100);
        $lycee_student = Eleve::findById(99200);

        try {
            AuthorizationScopeService::assertAccessToObject($ceg_student['lycee_id'], $ceg_student['cycle_id']);
            echo "  -> Élève CEG (99100) : Attendu: ALLOWED | Obtenu: ALLOWED | PASS\n";
        } catch (Exception $e) {
            echo "  -> Élève CEG (99100) : Attendu: ALLOWED | Obtenu: DENIED (" . $e->getMessage() . ") | FAIL\n";
            exit(1);
        }

        $lycee_blocked = false;
        try {
            AuthorizationScopeService::assertAccessToObject($lycee_student['lycee_id'], $lycee_student['cycle_id']);
        } catch (Exception $e) {
            $lycee_blocked = true;
        }

        if ($lycee_blocked) {
            echo "  -> Élève Lycée (99200) : Attendu: DENIED (403) | Obtenu: DENIED (403) | PASS\n";
        } else {
            echo "  -> Élève Lycée (99200) : Attendu: DENIED (403) | Obtenu: ALLOWED | FAIL\n";
            exit(1);
        }

        // --- TEST G: LISTE SANS CYCLE_ID (DENY-BY-DEFAULT) ---
        echo "TEST G: Liste sans cycle_id (Deny-By-Default)\n";
        $students = Eleve::findAll(['lycee_id' => 99]);
        $student_ids = array_column($students, 'id_eleve');

        // Assert that the returned list contains EXACTLY the CEG student (99100) and NOT the Lycée student (99200)
        if (count($student_ids) === 1 && in_array(99100, $student_ids) && !in_array(99200, $student_ids)) {
            echo "  -> Attendu: [99100] (CEG uniquement) | Obtenu: " . json_encode($student_ids) . " | PASS\n";
        } else {
            echo "  -> Attendu: [99100] | Obtenu: " . json_encode($student_ids) . " | FAIL\n";
            exit(1);
        }

        // --- TEST H: PARAMÈTRE FALSIFIÉ ---
        echo "TEST H: Paramètre falsifié\n";
        $can_access_forged_param = AuthorizationScopeService::canAccessCycle(992);
        if (!$can_access_forged_param) {
            echo "  -> Attendu: FALSE | Obtenu: FALSE | PASS\n";
        } else {
            echo "  -> Attendu: FALSE | Obtenu: TRUE | FAIL\n";
            exit(1);
        }

        // --- TEST I: AJAX PROTECTION ---
        echo "TEST I: AJAX Protection\n";
        $ajax_blocked = false;
        try {
            AuthorizationScopeService::assertAccessToObject(99, 992);
        } catch (Exception $e) {
            $ajax_blocked = true;
        }

        if ($ajax_blocked) {
            echo "  -> Attendu: DENIED (403) | Obtenu: DENIED (403) | PASS\n";
        } else {
            echo "  -> Attendu: DENIED (403) | Obtenu: ALLOWED | FAIL\n";
            exit(1);
        }

        // --- TEST J: EXPORT PROTECTION ---
        echo "TEST J: Export Protection\n";
        $db->exec("INSERT INTO journal_comptable (id, lycee_id, eleve_id, user_id, annee_academique_id, operation, montant) VALUES (991, 99, 99100, 9901, $annee_id, 'Inscription CEG', 50000)");
        $db->exec("INSERT INTO journal_comptable (id, lycee_id, eleve_id, user_id, annee_academique_id, operation, montant) VALUES (992, 99, 99200, 9901, $annee_id, 'Inscription Lycée', 75000)");

        $journal_entries = JournalComptable::findAll(['lycee_id' => 99]);
        $entry_ids = array_column($journal_entries, 'id');
        if (count($entry_ids) === 1 && in_array(991, $entry_ids) && !in_array(992, $entry_ids)) {
            echo "  -> Attendu: [991] (CEG uniquement) | Obtenu: " . json_encode($entry_ids) . " | PASS\n";
        } else {
            echo "  -> Attendu: [991] | Obtenu: " . json_encode($entry_ids) . " | FAIL\n";
            exit(1);
        }

        // --- TEST K: RESSOURCE GLOBALE PROTECTION ---
        echo "TEST K: Ressource globale protection\n";
        $db->exec("INSERT INTO journal_comptable (id, lycee_id, eleve_id, user_id, annee_academique_id, operation, montant) VALUES (993, 99, NULL, 9901, $annee_id, 'Facture électricité', 200000)");

        $journal_entries_with_global = JournalComptable::findAll(['lycee_id' => 99]);
        $entry_ids_2 = array_column($journal_entries_with_global, 'id');
        if (!in_array(993, $entry_ids_2)) {
            echo "  -> Attendu: Exclusion des ressources globales [993] | Obtenu: " . json_encode($entry_ids_2) . " | PASS\n";
        } else {
            echo "  -> Attendu: Exclusion de [993] | Obtenu: " . json_encode($entry_ids_2) . " | FAIL\n";
            exit(1);
        }

        // --- TEST L: MULTI-LYCÉE VS CYCLE INDEPENDENCE ---
        echo "TEST L: Multi-lycée vs Cycle Independence\n";
        $has_lycee_all = Auth::can('view_all_lycees', 'lycee');
        $has_cycle_all = Auth::can('view_all_cycles', 'cycle');
        if ($has_lycee_all === false && $has_cycle_all === false) {
            echo "  -> Attendu: Les deux permissions sont FALSE | Obtenu: Lycee=" . var_export($has_lycee_all, true) . ", Cycle=" . var_export($has_cycle_all, true) . " | PASS\n";
        } else {
            echo "  -> Attendu: Les deux permissions sont FALSE | Obtenu: FAIL\n";
            exit(1);
        }

        // --- TEST M: CHEVAUCHEMENT TEMPOREL ---
        echo "TEST M: Non-chevauchement d'affectations temporelles\n";
        try {
            AuthorizationScopeService::validateNonOverlapping(9901, 991, '2021-01-01', null);
            echo "  -> Attendu: EXCEPTION (Chevauchement) | Obtenu: PASSED | FAIL\n";
            exit(1);
        } catch (Exception $e) {
            echo "  -> Attendu: EXCEPTION (Chevauchement) | Obtenu: EXCEPTION (\"" . $e->getMessage() . "\") | PASS\n";
        }

        // --- TEST N: CACHE INVALIDATION / SESSION ANCIENNE ---
        echo "TEST N: Invalidation de cache instantanée via versioning auth_version\n";
        $db->exec("
            INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
            VALUES (9901, 992, '2020-01-01', NULL, 1)
        ");
        AuthorizationScopeService::invalidateUserCache(9901);

        $cached_cycles_after = AuthorizationScopeService::getAuthorizedCycleIds();
        if (in_array(992, $cached_cycles_after)) {
            echo "  -> Attendu: Recalcul incluant [992] | Obtenu: " . json_encode($cached_cycles_after) . " | PASS\n";
        } else {
            echo "  -> Attendu: Recalcul incluant [992] | Obtenu: " . json_encode($cached_cycles_after) . " | FAIL\n";
            exit(1);
        }

        echo "\n========================================================\n";
        echo "RÉSULTAT FINAL: 14/14 TESTS (A à N) RÉUSSIS AVEC SUCCÈS !\n";
        echo "========================================================\n";

    } catch (Exception $e) {
        echo "  -> [CRITICAL FAIL] Test Suite Exception: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
        Auth::logout();
    }
}

run_cycle_isolation_tests();
?>
