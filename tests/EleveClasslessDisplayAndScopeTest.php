<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Classless Student Display & Scope Security Test\n";
echo "========================================================\n";

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (9981, 9982, 9983, 9984)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99801, 99802, 99803, 99804, 99805, 99806)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99801, 99802, 99803, 99804, 99805, 99806)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820, 99830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983, 9984)");

// Active academic year check
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}
$anneeId = (int)$activeYear['id'];

// Resolve cycles in test DB
$cycleColl = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%ceg%' OR LOWER(nom_cycle) LIKE '%collège%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$cycleLycee = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%lycée%' OR LOWER(nom_cycle) LIKE '%lycee%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cycleColl || !$cycleLycee) {
    echo "ERROR: Cycles missing in test DB.\n";
    exit(1);
}

$idColl = (int)$cycleColl['id_cycle'];
$idLycee = (int)$cycleLycee['id_cycle'];

// Create test classes
// Class 99810 in Collège (Lycee 1)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (99810, 1, {$idColl}, '6e', 'A')");
// Class 99820 in Lycée (Lycee 1)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (99820, 1, {$idLycee}, '2nd', 'C', '1')");
// Class 99830 in Lycée (Lycee 2)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (99830, 2, {$idLycee}, '2nd', 'C', '1')");

// Create test students
// 99801: Lycee 1, NO CLASS (Classless student)
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99801, 1, 'SansClasse', 'Eleve1', 'en_attente')");

// 99802: Lycee 1, Collège class
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99802, 1, 'College', 'Eleve2', 'actif')");
$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (99802, 99810, 1, {$anneeId}, 1, 'active')");

// 99803: Lycee 1, Lycée class
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99803, 1, 'Lycee', 'Eleve3', 'actif')");
$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (99803, 99820, 1, {$anneeId}, 1, 'active')");

// 99804: Lycee 2, NO CLASS (Classless student in foreign school)
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99804, 2, 'ForeignClassless', 'Eleve4', 'en_attente')");

// 99805: Lycee 1, Radié/Archived student
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99805, 1, 'Radie', 'Eleve5', 'radié')");

// Create test users
// Role 99 (restricted user without view_all_cycles / view_all_lycees)
$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id) VALUES (99, 'agent_scolarite', 1)");

// User 9981: Collège-only assigned
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version) VALUES (9981, 1, 'User', 'CollOnly', 99, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9981, {$idColl}, '2020-01-01', 1)");

// User 9982: User with NO cycle assignment
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version) VALUES (9982, 1, 'User', 'NoCycle', 99, 1)");

function mockSession($userId, $lyceeId) {
    unset($_SESSION['user']['authorized_cycles']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => 99,
        'role_name' => 'agent_scolarite',
        'auth_version' => 1
    ];
    AuthorizationScopeService::invalidateUserCache($userId);
}

$passCount = 0;
$failCount = 0;

// -------------------------------------------------------------
// SCENARIO 1 & 2: User with Collège scope views general list
// -------------------------------------------------------------
echo "SCENARIO 1 & 2: Authorized user with Collège scope views general student list\n";
mockSession(9981, 1);

$list = Eleve::findAll(['lycee_id' => 1]);
$ids = array_column($list, 'id_eleve');

if (in_array(99801, $ids)) {
    echo "  [PASS] Scenario 1: Classless student (99801) is VISIBLE in general list for authorized school user.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 1: Classless student (99801) is missing from general list!\n";
    $failCount++;
}

if (in_array(99802, $ids)) {
    echo "  [PASS] Scenario 2: Student in in-scope Collège cycle (99802) is VISIBLE.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 2: Collège student (99802) missing!\n";
    $failCount++;
}

// -------------------------------------------------------------
// SCENARIO 3: Student in unassigned/unpermitted cycle remains inaccessible
// -------------------------------------------------------------
echo "\nSCENARIO 3: Student in unpermitted cycle (Lycée) remains inaccessible\n";
if (!in_array(99803, $ids)) {
    echo "  [PASS] Scenario 3: Lycée student (99803) is INACCESSIBLE for Collège-only user.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 3: Lycée student (99803) was exposed to Collège-only user!\n";
    $failCount++;
}

// -------------------------------------------------------------
// SCENARIO 4: Foreign school classless student is inaccessible
// -------------------------------------------------------------
echo "\nSCENARIO 4: Foreign school classless student remains inaccessible\n";
if (!in_array(99804, $ids)) {
    echo "  [PASS] Scenario 4: Classless student from foreign Lycee 2 (99804) is INACCESSIBLE.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 4: Foreign classless student (99804) leaked into Lycee 1 list!\n";
    $failCount++;
}

// -------------------------------------------------------------
// SCENARIO 5: Explicit cycle filter behavior
// -------------------------------------------------------------
echo "\nSCENARIO 5: Explicit cycle filter behavior\n";
$collFiltered = Eleve::findAll(['lycee_id' => 1, 'cycle_id' => $idColl]);
$collIds = array_column($collFiltered, 'id_eleve');

if (in_array(99802, $collIds) && !in_array(99801, $collIds) && !in_array(99803, $collIds)) {
    echo "  [PASS] Scenario 5: Explicit filter for Collège returns strictly Collège students, excluding classless students.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 5: Explicit Collège filter failed! Got IDs: " . json_encode($collIds) . "\n";
    $failCount++;
}

// -------------------------------------------------------------
// SCENARIO 6: User with empty authorized cycle list gets 0 students
// -------------------------------------------------------------
echo "\nSCENARIO 6: User with empty authorized cycles list gets no unearned access\n";
mockSession(9982, 1); // User 9982 has no cycle assignments

$noCycleList = Eleve::findAll(['lycee_id' => 1]);
if (empty($noCycleList)) {
    echo "  [PASS] Scenario 6: User with empty cycle assignments gets 0 students (no unearned expansion of rights).\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 6: User with 0 cycle assignments was granted student access! Count: " . count($noCycleList) . "\n";
    $failCount++;
}

// -------------------------------------------------------------
// SCENARIO 7: Radié/Archived student does NOT appear in active list
// -------------------------------------------------------------
echo "\nSCENARIO 7: Radié student does NOT appear in active list\n";
mockSession(9981, 1);
$activeList = Eleve::findAll(['lycee_id' => 1]);
$activeIds = array_column($activeList, 'id_eleve');

if (!in_array(99805, $activeIds)) {
    echo "  [PASS] Scenario 7: Radié student (99805) is absent from active student list.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 7: Radié student (99805) appeared in active list!\n";
    $failCount++;
}

// Check archives method
$archivedList = Eleve::findAllArchived(1);
$archivedIds = array_column($archivedList, 'id_eleve');
if (in_array(99805, $archivedIds)) {
    echo "  [PASS] Scenario 7b: Radié student (99805) is present in archived list.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 7b: Radié student missing from archived list!\n";
    $failCount++;
}

// -------------------------------------------------------------
// SCENARIO 8: Niveau/série/numéro filters continue to work
// -------------------------------------------------------------
echo "\nSCENARIO 8: Niveau/série/numéro explicit filters\n";
$niveauFiltered = Eleve::findAll(['lycee_id' => 1, 'niveau' => '6e']);
$nivIds = array_column($niveauFiltered, 'id_eleve');

if (in_array(99802, $nivIds) && !in_array(99801, $nivIds)) {
    echo "  [PASS] Scenario 8: Niveau 6e filter strictly returns 6e student, excluding classless student.\n";
    $passCount++;
} else {
    echo "  [FAIL] Scenario 8: Niveau filter failed! Got IDs: " . json_encode($nivIds) . "\n";
    $failCount++;
}

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (9981, 9982, 9983, 9984)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99801, 99802, 99803, 99804, 99805, 99806)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99801, 99802, 99803, 99804, 99805, 99806)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820, 99830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983, 9984)");

echo "\n========================================================\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
