<?php
// tests/PersonnelContractCycleIdFixTest.php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';

function assert_test($condition, $message) {
    if (!$condition) {
        echo " [FAIL] {$message}\n";
        exit(1);
    }
    echo " [PASS] {$message}\n";
}

echo "=== DÉMARRAGE DES TESTS CIBLÉS : FIX pah.cycle_id DANS PersonnelContractService ===\n\n";

$db = Database::getInstance();

// Mock Auth Session for testing
$_SESSION['user'] = [
    'id_user' => 1,
    'nom' => 'Admin',
    'prenom' => 'Super',
    'role_id' => null,
    'lycee_id' => 1,
    'permissions' => ['drh' => ['view_all', 'view_one', 'view_sensitive', 'manage_salaires']]
];
$_SESSION['user_id'] = 1;
$_SESSION['id_user'] = 1;
$_SESSION['lycee_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['role'] = 'super_admin_createur';
$_SESSION['permissions'] = ['*'];

// Clean test IDs
$testUserIds = [88901, 88902, 88903, 88904, 88905];
$inUsers = implode(',', $testUserIds);

$db->exec("DELETE FROM paie_bulletins WHERE personnel_id IN ($inUsers)");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN ($inUsers)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN ($inUsers)");
$db->exec("DELETE FROM personnel_details WHERE personnel_id IN ($inUsers)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN ($inUsers)");

// Seed 5 test personnel records
foreach ($testUserIds as $uid) {
    $db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif)
               VALUES ($uid, 'TestNom_$uid', 'TestPrenom_$uid', 'user_$uid@test.com', 1, 1, 1)");
    $db->exec("INSERT INTO personnel_details (personnel_id, statut_rh) VALUES ($uid, 'en_activite')");
}

// Ensure at least 2 cycles exist (e.g., 901 = Collège, 902 = Lycée)
$db->exec("INSERT OR IGNORE INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series) VALUES (901, 'Collège Test', 1, 0)");
$db->exec("INSERT OR IGNORE INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series) VALUES (902, 'Lycée Test', 1, 1)");

// Ensure a test paie_periode exists (e.g. ID 9991, 2025-01-01 to 2025-01-31)
$db->exec("DELETE FROM paie_periodes WHERE id = 9991");
$db->exec("INSERT INTO paie_periodes (id, lycee_id, periode_comptable_id, code_periode, mois, annee, date_debut, date_fin, statut, cree_par, created_at, updated_at)
           VALUES (9991, 1, 1, 'P-2025-01', 1, 2025, '2025-01-01', '2025-01-31', 'brouillon', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

// --- SCÉNARIO 1: Consultation dossier 360° sans erreur SQL (/drh/show?id=X) ---
echo "--- SCÉNARIO 1: Consultation dossier 360° DRH ---\n";
try {
    $details = PersonnelService::get360Details(88901, true);
    assert_test(is_array($details), "PersonnelService::get360Details() s'exécute sans erreur SQL 1054");
} catch (Exception $e) {
    assert_test(false, "S'exécute sans erreur SQL: " . $e->getMessage());
}

// --- SCÉNARIO 2: Personnel ayant un seul cycle actif ---
echo "--- SCÉNARIO 2: Personnel avec un seul cycle actif ---\n";
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (88901, 901, '2024-01-01', 1)");
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat)
           VALUES (88901, 1, '2024-01-01', 250000.00, 'actif')");

$contracts1 = PersonnelContractService::getContractsForPersonnel(88901);
assert_test(count($contracts1) === 1, "getContractsForPersonnel retourne exactement 1 contrat");
assert_test((int)$contracts1[0]['cycle_id'] === 901, "cycle_id retourné est bien 901");

// --- SCÉNARIO 3 & 9: Personnel avec plusieurs cycles actifs -> Aucun doublon de contrat ---
echo "--- SCÉNARIO 3 & 9: Plus de 1 cycle actif & non-duplication ---\n";
// Add second active cycle assignment for user 88901
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (88901, 902, '2024-09-01', 1)");

$contractsMulti = PersonnelContractService::getContractsForPersonnel(88901);
assert_test(count($contractsMulti) === 1, "Non-duplication: count(getContractsForPersonnel) reste égal à 1 malgré 2 cycles actifs");
assert_test(in_array((int)$contractsMulti[0]['cycle_id'], [901, 902]), "cycle_id est un identifiant scalaire valide");

$eligibleMulti = PersonnelContractService::getEligibleContractsForPeriod(9991, 1);
$user88901Rows = array_filter($eligibleMulti, fn($c) => (int)$c['personnel_id'] === 88901);
assert_test(count($user88901Rows) === 1, "Non-duplication: count(getEligibleContractsForPeriod) retourne 1 seule ligne pour user 88901");

// --- SCÉNARIO 4: Affectations historiques vs affectation actuelle ---
echo "--- SCÉNARIO 4: Affectations historiques vs actuelles ---\n";
// User 88902 has an inactive historical cycle (901) and active current cycle (902)
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (88902, 901, '2020-01-01', '2023-12-31', 0)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (88902, 902, '2024-01-01', 1)");
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat)
           VALUES (88902, 1, '2024-01-01', 300000.00, 'actif')");

$contractsHist = PersonnelContractService::getContractsForPersonnel(88902);
assert_test((int)$contractsHist[0]['cycle_id'] === 902, "L'affectation actuelle active (902) est sélectionnée à la place de l'historique inactive (901)");

// --- SCÉNARIO 5: Contrat sans affectation de cycle ---
echo "--- SCÉNARIO 5: Contrat sans affectation de cycle ---\n";
// User 88903 has contract but no cycle assignment
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat)
           VALUES (88903, 1, '2024-01-01', 180000.00, 'actif')");

$contractsNoCycle = PersonnelContractService::getContractsForPersonnel(88903);
assert_test(count($contractsNoCycle) === 1, "Contrat sans cycle récupéré sans erreur");
assert_test($contractsNoCycle[0]['cycle_id'] === null, "cycle_id est NULL pour personnel sans affectation de cycle");

// --- SCÉNARIO 6: Affectation couvrant exactement la période de paie ---
echo "--- SCÉNARIO 6: Affectation couvrant exactement la période de paie ---\n";
// User 88904 assigned to cycle 901 during 2025-01-01 to 2025-01-31
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (88904, 901, '2025-01-01', '2025-01-31', 1)");
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat)
           VALUES (88904, 1, '2025-01-01', 200000.00, 'actif')");

$eligibleExact = PersonnelContractService::getEligibleContractsForPeriod(9991, 1);
$user88904Match = array_values(array_filter($eligibleExact, fn($c) => (int)$c['personnel_id'] === 88904));
assert_test(count($user88904Match) === 1, "User 88904 est éligible pour la période");
assert_test((int)$user88904Match[0]['cycle_id'] === 901, "cycle_id (901) est associé car couvrant la période de paie");

// --- SCÉNARIO 7: Affectation commencée APRÈS la période de paie ---
echo "--- SCÉNARIO 7: Affectation commencée après la période de paie ---\n";
// User 88905 assigned to cycle 902 starting 2025-02-01 (after Jan 2025 period)
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (88905, 902, '2025-02-01', 1)");
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat)
           VALUES (88905, 1, '2025-01-01', 220000.00, 'actif')");

$eligibleFuture = PersonnelContractService::getEligibleContractsForPeriod(9991, 1);
$user88905Match = array_values(array_filter($eligibleFuture, fn($c) => (int)$c['personnel_id'] === 88905));
assert_test(count($user88905Match) === 1, "User 88905 est éligible (contrat actif)");
assert_test($user88905Match[0]['cycle_id'] === null, "cycle_id est NULL pour la période Janvier 2025 car l'affectation commence en Février 2025");

// --- SCÉNARIO 8: Affectation terminée AVANT la période de paie ---
echo "--- SCÉNARIO 8: Affectation terminée avant la période de paie ---\n";
// Create user 88906 with assignment ending 2024-12-31 (before Jan 2025 period)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif) VALUES (88906, 'Old', 'User', 'old@test.com', 1, 1, 1)");
$db->exec("INSERT INTO personnel_details (personnel_id, statut_rh) VALUES (88906, 'en_activite')");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (88906, 901, '2024-01-01', '2024-12-31', 1)");
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat) VALUES (88906, 1, '2024-01-01', 210000.00, 'actif')");

$eligiblePast = PersonnelContractService::getEligibleContractsForPeriod(9991, 1);
$user88906Match = array_values(array_filter($eligiblePast, fn($c) => (int)$c['personnel_id'] === 88906));
assert_test(count($user88906Match) === 1, "User 88906 est éligible (contrat actif)");
assert_test($user88906Match[0]['cycle_id'] === null, "cycle_id est NULL pour la période Janvier 2025 car l'affectation s'est terminée le 31 Décembre 2024");

// --- SCÉNARIO 10: Préservation du cycle correctement associé ---
echo "--- SCÉNARIO 10: Préservation du cycle correctement associé ---\n";
assert_test((int)$user88904Match[0]['cycle_id'] === 901, "Cycle 901 préservé pour l'affectation active de la période");

// Cleanup test records
$cleanUsers = [88901, 88902, 88903, 88904, 88905, 88906];
$inClean = implode(',', $cleanUsers);
$db->exec("DELETE FROM paie_bulletins WHERE personnel_id IN ($inClean)");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN ($inClean)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN ($inClean)");
$db->exec("DELETE FROM personnel_details WHERE personnel_id IN ($inClean)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN ($inClean)");
$db->exec("DELETE FROM paie_periodes WHERE id = 9991");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES 10 SCÉNARIOS DE VÉRIFICATION SUR pah.cycle_id ONT RÉUSSI !\n";
echo "=========================================================================\n";
