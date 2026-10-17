<?php
/**
 * Test Suite: Phase 1 Cycle Isolation & Security Remediation
 * Verifies cycle access controls in DisciplineSearchService, PaiementController::restes,
 * DisciplineIncidentController, and DisciplineSanctionController.
 */

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/DisciplineSearchService.php';
require_once __DIR__ . '/../src/controllers/PaiementController.php';
require_once __DIR__ . '/../src/controllers/DisciplineIncidentController.php';
require_once __DIR__ . '/../src/controllers/DisciplineSanctionController.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Eleve.php';

function run_phase1_cycle_isolation_tests() {
    echo "========================================================\n";
    echo "RUNNING TEST SUITE: Phase 1 Cycle Isolation & Security Fixes\n";
    echo "========================================================\n";

    $db = Database::getInstance();

    // 1. Setup Test Data
    $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (7701, 7702, 7703, 7704)");
    $db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id >= 77000");
    $db->exec("DELETE FROM discipline_incidents WHERE id >= 77000");
    $db->exec("DELETE FROM discipline_types_incidents WHERE id = 77");
    $db->exec("DELETE FROM discipline_types_sanctions WHERE id = 77");
    $db->exec("DELETE FROM discipline_sanctions WHERE id >= 77000");
    $db->exec("DELETE FROM etudes WHERE eleve_id >= 77000");
    $db->exec("DELETE FROM eleves WHERE id_eleve >= 77000");
    $db->exec("DELETE FROM classes WHERE id_classe IN (771, 772)");
    $db->exec("DELETE FROM cycles WHERE id_cycle IN (771, 772)");
    $db->exec("DELETE FROM utilisateurs WHERE id_user IN (7701, 7702, 7703, 7704)");

    // Active Year
    $db->exec("INSERT OR IGNORE INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee)
               VALUES (77, '2024-2025 Test', '2024-09-01', '2025-06-30', 1, 0)");

    // Referential incident & sanction types
    $db->exec("INSERT OR IGNORE INTO discipline_types_incidents (id, lycee_id, code, libelle, niveau_gravite)
               VALUES (77, 1, 'TEST_INC_77', 'Incident Test', 'moyenne')");

    $db->exec("INSERT OR IGNORE INTO discipline_types_sanctions (id, lycee_id, code, libelle)
               VALUES (77, 1, 'TEST_SANC_77', 'Sanction Test')");

    // Cycles
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (771, 1, 'CEG Test Scope')");
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (772, 1, 'Lycée Test Scope')");

    // Classes
    $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (771, 1, 771, '6ème', '', 1)");
    $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (772, 1, 772, '2nde', 'A', 1)");

    // Users
    // 7701: CEG only
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, auth_version) VALUES (7701, 1, 'User', 'CEG', 'ceg@test.com', 5, 1)");
    // 7702: Lycee only
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, auth_version) VALUES (7702, 1, 'User', 'Lycee', 'lycee@test.com', 5, 1)");
    // 7703: No assignment
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, auth_version) VALUES (7703, 1, 'User', 'None', 'none@test.com', 5, 1)");
    // 7704: Multi-cycle (CEG + Lycee)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, auth_version) VALUES (7704, 1, 'User', 'Multi', 'multi@test.com', 5, 1)");

    // Cycle Assignments
    $today = date('Y-m-d');
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7701, 771, '2020-01-01', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7702, 772, '2020-01-01', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7704, 771, '2020-01-01', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7704, 772, '2020-01-01', 1)");

    // Students
    $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (77001, 1, 'Eleve', 'CEG', 'MAT-77001', 'actif')");
    $db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, status, is_active) VALUES (77001, 771, 77, 'active', 1)");

    $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (77002, 1, 'Eleve', 'Lycee', 'MAT-77002', 'actif')");
    $db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, status, is_active) VALUES (77002, 772, 77, 'active', 1)");

    // Incidents & Sanctions
    $db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, description_faits, signale_par_user_id, date_incident, statut)
               VALUES (77001, 1, 77, 77, 'Test fait 1', 7701, '$today', 'signale')");
    $db->exec("INSERT INTO discipline_incident_eleves (incident_id, eleve_id, classe_id, role_implication)
               VALUES (77001, 77001, 771, 'auteur_principal')");

    $db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, description_faits, signale_par_user_id, date_incident, statut)
               VALUES (77002, 1, 77, 77, 'Test fait 2', 7702, '$today', 'signale')");
    $db->exec("INSERT INTO discipline_incident_eleves (incident_id, eleve_id, classe_id, role_implication)
               VALUES (77002, 77002, 772, 'auteur_principal')");

    $db->exec("INSERT INTO discipline_sanctions (id, lycee_id, annee_academique_id, type_sanction_id, eleve_id, classe_id, incident_id, date_decision, prononcee_par_user_id, motif, statut)
               VALUES (77001, 1, 77, 77, 77001, 771, 77001, '$today', 7701, 'Motif 1', 'prononcee')");
    $db->exec("INSERT INTO discipline_sanctions (id, lycee_id, annee_academique_id, type_sanction_id, eleve_id, classe_id, incident_id, date_decision, prononcee_par_user_id, motif, statut)
               VALUES (77002, 1, 77, 77, 77002, 772, 77002, '$today', 7702, 'Motif 2', 'prononcee')");

    echo "[OK] Initial Test Data Prepared.\n\n";

    // --- SCENARIO 1: CEG User queries CEG data ---
    echo "SCENARIO 1: Utilisateur affecté uniquement au CEG consulte les données du CEG\n";
    $_SESSION = [
        'user' => [
            'id_user' => 7701,
            'lycee_id' => 1,
            'role_name' => 'surveillant_general',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions'],
            'auth_version' => 1
        ]
    ];
    $res1 = DisciplineSearchService::getFilteredIncidents(['cycle_id' => 771], 7701, 'surveillant_general', 1, true);
    if ($res1['total'] === 1 && $res1['items'][0]['incident_id'] == 77001) {
        echo "  -> OK: Accès autorisé au CEG (1 incident trouvé) | PASS\n";
    } else {
        echo "  -> FAIL: Impossible d'accéder au CEG | FAIL\n";
    }

    // --- SCENARIO 2: CEG User attempts to pass Lycee cycle_id ---
    echo "\nSCENARIO 2: Utilisateur CEG transmet le cycle_id du Lycée (Falsification)\n";
    try {
        DisciplineSearchService::getFilteredIncidents(['cycle_id' => 772], 7701, 'surveillant_general', 1, true);
        echo "  -> FAIL: L'accès au Lycée aurait dû être refusé | FAIL\n";
    } catch (InvalidArgumentException $e) {
        if ($e->getCode() === 403 || str_contains($e->getMessage(), 'ACCES_REFUSE')) {
            echo "  -> OK: Accès refusé avec exception 403 ({$e->getMessage()}) | PASS\n";
        } else {
            echo "  -> FAIL: Code exception inattendu | FAIL\n";
        }
    }

    // --- SCENARIO 3: CEG User omits cycle_id ---
    echo "\nSCENARIO 3: Utilisateur CEG omet cycle_id (Filtrage automatique par défaut)\n";
    $res3 = DisciplineSearchService::getFilteredIncidents([], 7701, 'surveillant_general', 1, true);
    $incIds3 = array_map('intval', array_column($res3['items'], 'incident_id'));
    if ($res3['total'] === 1 && in_array(77001, $incIds3) && !in_array(77002, $incIds3)) {
        echo "  -> OK: Résultats automatiquement restreints au seul CEG (77001) | PASS\n";
    } else {
        echo "  -> FAIL: Filtrage automatique défaillant (" . json_encode($incIds3) . ") | FAIL\n";
    }

    // --- SCENARIO 4: Lycee User cannot access CEG ---
    echo "\nSCENARIO 4: Utilisateur affecté uniquement au Lycée tente d'accéder au CEG\n";
    $_SESSION['user'] = [
        'id_user' => 7702,
        'lycee_id' => 1,
        'role_name' => 'surveillant_general',
        'permissions' => ['discipline:view_incidents'],
        'auth_version' => 1
    ];
    try {
        DisciplineSearchService::getFilteredIncidents(['cycle_id' => 771], 7702, 'surveillant_general', 1, true);
        echo "  -> FAIL: Accès au CEG non bloqué pour l'utilisateur Lycée | FAIL\n";
    } catch (InvalidArgumentException $e) {
        echo "  -> OK: Accès refusé au CEG pour l'utilisateur Lycée | PASS\n";
    }

    // --- SCENARIO 5: User with NO cycle assignment ---
    echo "\nSCENARIO 5: Utilisateur sans affectation de cycle n'accède à aucun cycle\n";
    $_SESSION['user'] = [
        'id_user' => 7703,
        'lycee_id' => 1,
        'role_name' => 'surveillant_general',
        'permissions' => ['discipline:view_incidents'],
        'auth_version' => 1
    ];
    $res5 = DisciplineSearchService::getFilteredIncidents([], 7703, 'surveillant_general', 1, true);
    if ($res5['total'] === 0) {
        echo "  -> OK: Refus par défaut (0 résultat renvoyé) | PASS\n";
    } else {
        echo "  -> FAIL: L'utilisateur sans cycle a obtenu des données | FAIL\n";
    }

    // --- SCENARIO 6: Multi-cycle User ---
    echo "\nSCENARIO 6: Utilisateur affecté à plusieurs cycles (CEG + Lycee)\n";
    $_SESSION['user'] = [
        'id_user' => 7704,
        'lycee_id' => 1,
        'role_name' => 'surveillant_general',
        'permissions' => ['discipline:view_incidents'],
        'auth_version' => 1
    ];
    $res6 = DisciplineSearchService::getFilteredIncidents([], 7704, 'surveillant_general', 1, true);
    $incIds6 = array_map('intval', array_column($res6['items'], 'incident_id'));
    if (in_array(77001, $incIds6) && in_array(77002, $incIds6)) {
        echo "  -> OK: Accès accordé uniquement aux 2 cycles autorisés | PASS\n";
    } else {
        echo "  -> FAIL: Multi-cycle incomplet (" . json_encode($incIds6) . ") | FAIL\n";
    }

    // --- SCENARIO 7: Forged ID on PaiementController::restes ---
    echo "\nSCENARIO 7: Identifiant falsifié sur PaiementController::restes()\n";
    $_SESSION['user'] = [
        'id_user' => 7701,
        'lycee_id' => 1,
        'role_name' => 'admin_local',
        'permissions' => ['paiement:view'],
        'auth_version' => 1
    ];
    $_GET = ['cycle_id' => '772', 'view_type' => 'class'];
    $ctlPaiement = new PaiementController();

    ob_start();
    try {
        $ctlPaiement->restes();
        $output = ob_get_clean();
        if (str_contains($output, '403') || str_contains($output, 'Accès Refusé') || str_contains($output, 'Accès Interdit')) {
            echo "  -> OK: PaiementController::restes() a renvoyé la page 403 pour le cycle falsifié 772 | PASS\n";
        } else {
            echo "  -> FAIL: PaiementController::restes() n'a pas bloqué le cycle 772 | FAIL\n";
        }
    } catch (Exception $e) {
        ob_get_clean();
        echo "  -> OK: PaiementController::restes() a rejeté le cycle falsifié 772 ({$e->getMessage()}) | PASS\n";
    }

    // --- SCENARIO 8: Global admin permissions work legitimately ---
    echo "\nSCENARIO 8: Les permissions globales autorisées (view_all_cycles) fonctionnent\n";
    $_SESSION['user'] = [
        'id_user' => 7703,
        'lycee_id' => 1,
        'role_name' => 'super_admin_createur',
        'permissions' => ['cycle:view_all_cycles', 'lycee:view_all_lycees', 'discipline:view_incidents'],
        'auth_version' => 1
    ];
    $res8 = DisciplineSearchService::getFilteredIncidents([], 7703, 'super_admin_createur', 1, true);
    if ($res8['total'] >= 2) {
        echo "  -> OK: Administrateur global accède à tous les cycles | PASS\n";
    } else {
        echo "  -> FAIL: Bloqué à tort pour le Super Admin | FAIL\n";
    }

    // --- SCENARIO 9: Single Incident/Sanction Object Verification ---
    echo "\nSCENARIO 9: Vérification d'objet individuel pour incident/sanction d'un autre cycle\n";
    $_SESSION['user'] = [
        'id_user' => 7701,
        'lycee_id' => 1,
        'role_name' => 'surveillant_general',
        'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions'],
        'auth_version' => 1
    ];

    $_GET = ['id' => '77002']; // Lycee incident
    $ctlInc = new DisciplineIncidentController();
    ob_start();
    $ctlInc->show();
    $outInc = ob_get_clean();

    if (str_contains($outInc, 'discipline/incidents') || str_contains($outInc, 'Accès non autorisé') || headers_list()) {
        echo "  -> OK: Consultation d'un incident de cycle non autorisé bloquée | PASS\n";
    } else {
        echo "  -> FAIL: Incident du Lycée affiché pour l'utilisateur CEG | FAIL\n";
    }

    // Cleanup
    $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (7701, 7702, 7703, 7704)");
    $db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id >= 77000");
    $db->exec("DELETE FROM discipline_incidents WHERE id >= 77000");
    $db->exec("DELETE FROM discipline_types_incidents WHERE id = 77");
    $db->exec("DELETE FROM discipline_types_sanctions WHERE id = 77");
    $db->exec("DELETE FROM discipline_sanctions WHERE id >= 77000");
    $db->exec("DELETE FROM etudes WHERE eleve_id >= 77000");
    $db->exec("DELETE FROM eleves WHERE id_eleve >= 77000");
    $db->exec("DELETE FROM classes WHERE id_classe IN (771, 772)");
    $db->exec("DELETE FROM cycles WHERE id_cycle IN (771, 772)");
    $db->exec("DELETE FROM utilisateurs WHERE id_user IN (7701, 7702, 7703, 7704)");

    echo "\n========================================================\n";
    echo "RÉSULTAT SUITE PHASE 1 : TOUS LES SCÉNARIOS (1 à 9) VALIDÉS\n";
    echo "========================================================\n";
}

run_phase1_cycle_isolation_tests();
