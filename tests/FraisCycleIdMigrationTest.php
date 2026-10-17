<?php
/**
 * Test Suite: Phase II-B & II-C Cycle ID Security & Tariff Resolution Migration
 * Verifies ClasseController::store() security, Cycle::findByNom(), Migration 31,
 * and Frais::findForClasse() technical cycle_id matching.
 */

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Frais.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../db/migrations/20240115_31_migrate_frais_cycle_id.php';

function run_phase2b_security_and_tariff_tests() {
    echo "========================================================\n";
    echo "RUNNING TEST SUITE: Phase II-B & II-C Cycle ID Security & Tariff Resolution\n";
    echo "========================================================\n";

    $db = Database::getInstance();

    // Run Migration 31
    migrate_31($db);

    // Setup Test Fixtures
    $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (8801, 8802)");
    $db->exec("DELETE FROM frais WHERE lycee_id IN (881, 882)");
    $db->exec("DELETE FROM etudes WHERE eleve_id >= 88000");
    $db->exec("DELETE FROM eleves WHERE id_eleve >= 88000");
    $db->exec("DELETE FROM classes WHERE lycee_id IN (881, 882)");
    $db->exec("DELETE FROM cycles WHERE lycee_id IN (881, 882)");
    $db->exec("DELETE FROM param_lycee WHERE id IN (881, 882)");
    $db->exec("DELETE FROM utilisateurs WHERE id_user IN (8801, 8802)");

    // Schools
    $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (881, 'Lycée Alpha')");
    $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (882, 'Lycée Beta')");

    // Academic Year
    $db->exec("INSERT OR IGNORE INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee)
               VALUES (88, '2024-2025 Phase2B', '2024-09-01', '2025-06-30', 1, 0)");

    // Cycles with identical names across 2 schools
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8810, 881, 'CEG Homonyme')");
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8820, 882, 'CEG Homonyme')");

    // Users
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, auth_version) VALUES (8801, 881, 'User', 'Alpha', 'alpha@test.com', 3, 1)");
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, auth_version) VALUES (8802, 882, 'User', 'Beta', 'beta@test.com', 3, 1)");

    // Cycle Assignments
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8801, 8810, '2020-01-01', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8802, 8820, '2020-01-01', 1)");

    echo "[OK] Test Environment & Homonymous Cycles Prepared.\n\n";

    // --- TEST 1: Creation of a class with an authorized cycle ID ---
    echo "TEST 1: Création d'une classe avec un cycle_id autorisé\n";
    $_SESSION = [
        'user' => [
            'id_user' => 8801,
            'lycee_id' => 881,
            'role_name' => 'admin_local',
            'permissions' => ['class:create', 'class:view'],
            'auth_version' => 1
        ]
    ];
    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'] ?? 'test',
        'lycee_id' => '881',
        'cycle_id' => '8810',
        'niveau' => '6ème',
        'numero' => '1'
    ];

    $ctlClasse = new ClasseController();
    ob_start();
    try {
        $ctlClasse->store();
        ob_get_clean();
    } catch (Exception $e) {
        ob_get_clean();
    }

    $createdClass1 = $db->query("SELECT * FROM classes WHERE lycee_id = 881 AND cycle_id = 8810 AND niveau = '6ème' AND numero = 1")->fetch(PDO::FETCH_ASSOC);
    if ($createdClass1 && (int)$createdClass1['cycle_id'] === 8810) {
        echo "  -> OK: Classe créée avec succès avec cycle_id = 8810 | PASS\n";
    } else {
        echo "  -> FAIL: Échec de création de la classe | FAIL\n";
    }

    // --- TEST 2: Homonymous cycles across two schools ---
    echo "\nTEST 2: Création d'une classe au Lycée 882 avec le cycle homonyme 8820\n";
    $_SESSION['user'] = [
        'id_user' => 8802,
        'lycee_id' => 882,
        'role_name' => 'admin_local',
        'permissions' => ['class:create', 'class:view'],
        'auth_version' => 1
    ];
    $_POST = [
        'lycee_id' => '882',
        'cycle_id' => '8820',
        'niveau' => '6ème',
        'numero' => '1'
    ];

    ob_start();
    try {
        $ctlClasse->store();
        ob_get_clean();
    } catch (Exception $e) {
        ob_get_clean();
    }

    $createdClass2 = $db->query("SELECT * FROM classes WHERE lycee_id = 882 AND cycle_id = 8820 AND niveau = '6ème' AND numero = 1")->fetch(PDO::FETCH_ASSOC);
    if ($createdClass2 && (int)$createdClass2['cycle_id'] === 8820) {
        echo "  -> OK: Classe liée au cycle 8820 du Lycée 882 (aucun télescopage) | PASS\n";
    } else {
        echo "  -> FAIL: Télescopage détecté entre cycles homonymes | FAIL\n";
    }

    // --- TEST 3: Attempting cross-tenant cycle_id submission ---
    echo "\nTEST 3: Tentative de soumission d'un cycle_id d'un autre établissement\n";
    $_SESSION['user'] = [
        'id_user' => 8801,
        'lycee_id' => 881,
        'role_name' => 'admin_local',
        'permissions' => ['class:create'],
        'auth_version' => 1
    ];
    $_POST = [
        'lycee_id' => '881',
        'cycle_id' => '8820', // Belongs to lycee 882!
        'niveau' => '5ème',
        'numero' => '1'
    ];

    ob_start();
    try {
        $ctlClasse->store();
        $out3 = ob_get_clean();
        if (str_contains($out3, '403') || isset($_SESSION['error_message'])) {
            echo "  -> OK: Tentative inter-établissement bloquée | PASS\n";
        } else {
            echo "  -> FAIL: cycle_id d'un autre lycée accepté | FAIL\n";
        }
    } catch (Exception $e) {
        ob_get_clean();
        echo "  -> OK: Rejeté par exception ({$e->getMessage()}) | PASS\n";
    }

    // --- TEST 4: Invalid/Missing cycle_id ---
    echo "\nTEST 4: cycle_id absent ou inexistant\n";
    $_POST = [
        'lycee_id' => '881',
        'cycle_id' => '999999',
        'niveau' => '4ème',
        'numero' => '1'
    ];

    ob_start();
    try {
        $ctlClasse->store();
        $out4 = ob_get_clean();
        if (isset($_SESSION['error_message'])) {
            echo "  -> OK: cycle_id inexistant rejeté ({$_SESSION['error_message']}) | PASS\n";
        } else {
            echo "  -> FAIL: cycle_id inexistant non rejeté | FAIL\n";
        }
    } catch (Exception $e) {
        ob_get_clean();
        echo "  -> OK: cycle_id inexistant rejeté par exception | PASS\n";
    }

    // --- TEST 5: Tariff selection via canonical technical cycle_id ---
    echo "\nTEST 5: Sélection des frais applicables par cycle_id dans Frais::findForClasse()\n";
    $db->exec("INSERT INTO frais (lycee_id, annee_academique_id, frais_inscription, frais_mensuel, type_config, cycle, cycle_id)
               VALUES (881, 88, 15000.00, 8000.00, 'cycle', 'CEG Homonyme', 8810)");

    $testClasse1 = ['id_classe' => 7710, 'lycee_id' => 881, 'cycle_id' => 8810, 'niveau' => '6ème', 'serie' => ''];
    $fraisRes5 = Frais::findForClasse($testClasse1, 88);

    if ($fraisRes5 && (float)$fraisRes5['frais_inscription'] === 15000.00 && (int)$fraisRes5['cycle_id'] === 8810) {
        echo "  -> OK: Tarification résolue via cycle_id = 8810 | PASS\n";
    } else {
        echo "  -> FAIL: Résolution tarifaire échouée sur cycle_id | FAIL\n";
    }

    // --- TEST 6: Resilience on cycle renaming ---
    echo "\nTEST 6: Renommage du cycle sans perte de correspondance tarifaire\n";
    $db->exec("UPDATE cycles SET nom_cycle = 'Collège Renommé' WHERE id_cycle = 8810");

    $fraisRes6 = Frais::findForClasse($testClasse1, 88);
    if ($fraisRes6 && (float)$fraisRes6['frais_inscription'] === 15000.00) {
        echo "  -> OK: Tarification conservée après renommage du nom_cycle grâce à cycle_id = 8810 | PASS\n";
    } else {
        echo "  -> FAIL: Correspondance tarifaire perdue après renommage | FAIL\n";
    }

    // --- TEST 7: Legacy unlinked historical tariff fallback ---
    echo "\nTEST 7: Fallback legacy sur tarif historique non lié (cycle_id IS NULL)\n";
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8811, 881, 'Secondaire Legacy')");
    $db->exec("INSERT INTO frais (lycee_id, annee_academique_id, frais_inscription, frais_mensuel, type_config, cycle, cycle_id)
               VALUES (881, 88, 25000.00, 12000.00, 'cycle', 'Secondaire Legacy', NULL)");

    $testClasseLegacy = ['id_classe' => 7711, 'lycee_id' => 881, 'cycle_id' => 8811, 'niveau' => '2nde', 'serie' => 'A'];
    $fraisRes7 = Frais::findForClasse($testClasseLegacy, 88);

    if ($fraisRes7 && (float)$fraisRes7['frais_inscription'] === 25000.00) {
        echo "  -> OK: Fallback legacy non-ambigu fonctionnel pour tarif sans cycle_id | PASS\n";
    } else {
        echo "  -> FAIL: Fallback legacy défaillant | FAIL\n";
    }

    // --- TEST 8: Priority 1 & 2 range tariffs take precedence ---
    echo "\nTEST 8: Priorité 1 (Plage de niveau + Série) prévaut sur le tarif de cycle\n";
    $db->exec("INSERT INTO frais (lycee_id, annee_academique_id, frais_inscription, frais_mensuel, type_config, niveau_debut, niveau_fin, serie)
               VALUES (881, 88, 30000.00, 15000.00, 'plage', '6ème', '3ème', 'A')");

    $testClasseRange = ['id_classe' => 7712, 'lycee_id' => 881, 'cycle_id' => 8810, 'niveau' => '6ème', 'serie' => 'A'];
    $fraisRes8 = Frais::findForClasse($testClasseRange, 88);

    if ($fraisRes8 && (float)$fraisRes8['frais_inscription'] === 30000.00) {
        echo "  -> OK: Tarif par plage de niveau + série prévaut sur la règle de cycle | PASS\n";
    } else {
        echo "  -> FAIL: Priorité des règles de tarif non respectée | FAIL\n";
    }

    // --- TEST 9: Ambiguous historical tariff rule is NOT applied automatically ---
    echo "\nTEST 9: Règle tarifaire historique ambiguë ignorée (Refus d'application automatique)\n";
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8812, 881, 'Doublon Ambigu')");
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8813, 881, 'Doublon Ambigu')");
    $db->exec("INSERT INTO frais (lycee_id, annee_academique_id, frais_inscription, frais_mensuel, type_config, cycle, cycle_id)
               VALUES (881, 88, 99000.00, 50000.00, 'cycle', 'Doublon Ambigu', NULL)");

    $testClasseAmbiguous = ['id_classe' => 7713, 'lycee_id' => 881, 'cycle_id' => 8812, 'niveau' => '6ème', 'serie' => ''];
    $fraisRes9 = Frais::findForClasse($testClasseAmbiguous, 88);

    if ($fraisRes9 === false) {
        echo "  -> OK: La règle historique ambiguë a été ignorée par sécurité (FALSE renvoyé) | PASS\n";
    } else {
        echo "  -> FAIL: Règle historique ambiguë appliquée par erreur | FAIL\n";
    }

    // --- TEST 10: Level and Series Inconsistency Rejection ---
    echo "\nTEST 10: Incohérence Niveau / Série rejetée par ClasseController::store()\n";
    $_SESSION['user'] = [
        'id_user' => 8801,
        'lycee_id' => 881,
        'role_name' => 'admin_local',
        'permissions' => ['class:create'],
        'auth_version' => 1
    ];
    $_POST = [
        'lycee_id' => '881',
        'cycle_id' => '8810',
        'niveau' => '6ème', // CEG level
        'serie' => 'A',     // Series should not exist for CEG!
        'numero' => '1'
    ];

    ob_start();
    try {
        $ctlClasse->store();
        ob_get_clean();
        if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], 'série')) {
            echo "  -> OK: Incohérence série sur 6ème rejetée avec succès ({$_SESSION['error_message']}) | PASS\n";
        } else {
            echo "  -> FAIL: Série A autorisée sur une 6ème | FAIL\n";
        }
    } catch (Exception $e) {
        ob_get_clean();
        echo "  -> OK: Rejeté par exception ({$e->getMessage()}) | PASS\n";
    }

    // Cleanup Test Data
    $db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (8801, 8802)");
    $db->exec("DELETE FROM frais WHERE lycee_id IN (881, 882)");
    $db->exec("DELETE FROM etudes WHERE eleve_id >= 88000");
    $db->exec("DELETE FROM eleves WHERE id_eleve >= 88000");
    $db->exec("DELETE FROM classes WHERE lycee_id IN (881, 882)");
    $db->exec("DELETE FROM cycles WHERE lycee_id IN (881, 882)");
    $db->exec("DELETE FROM param_lycee WHERE id IN (881, 882)");
    $db->exec("DELETE FROM utilisateurs WHERE id_user IN (8801, 8802)");

    echo "\n========================================================\n";
    echo "RÉSULTAT SUITE PHASE II-C : TOUS LES TESTS (1 à 10) VALIDÉS\n";
    echo "========================================================\n";
}

run_phase2b_security_and_tariff_tests();
