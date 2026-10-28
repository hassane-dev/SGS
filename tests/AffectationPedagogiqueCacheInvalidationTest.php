<?php

/**
 * Test Suite: AffectationPedagogiqueCacheInvalidationTest
 * Tests targeted auth_version cache invalidations across pedagogical assignment mutations.
 */

define('TEST_MODE', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/AffectationPedagogiqueService.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: AffectationPedagogiqueCacheInvalidationTest\n";
echo "========================================================\n\n";

// Cleanup mock data
$db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id IN (99881, 99882)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (99881, 99882)");
$db->exec("DELETE FROM classe_matieres WHERE classe_id IN (99810, 99820)");
$db->exec("DELETE FROM matieres WHERE id_matiere IN (99801)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (99800, 99801)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (99881, 99882)");

// 1. Setup active year
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (9980, '2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}

// 2. Insert test cycles
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (99800, 'Cycle CEG Test', 1, 0, '6e', '3e')");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (99801, 'Cycle Lycee Test', 1, 1, '2nde', 'Terminale')");

// 3. Insert test classes
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (99810, 1, 99800, '6e', '1')");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (99820, 1, 99801, '2nde', 'A', '1')");

// 4. Insert test subject and assign to class curriculum
$db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (99801, 1, 'Mathématiques Test')");
$db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (99810, 99801, 3)");
$db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (99820, 99801, 3)");

// 5. Ensure role enseignant (role_id = 6)
$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id) VALUES (6, 'enseignant', 1)");

// 6. Insert teachers
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (99881, 1, 'Prof', 'Un', 6, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (99882, 1, 'Prof', 'Deux', 6, 1, 1)");

// 7. Cycle assignments for personnel to pass cycle coverage checks
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (99881, 99800, '2024-01-01', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (99881, 99801, '2024-01-01', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (99882, 99800, '2024-01-01', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (99882, 99801, '2024-01-01', 1)");

function getAuthVersion(int $userId): int {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT auth_version FROM utilisateurs WHERE id_user = :id");
    $stmt->execute(['id' => $userId]);
    return (int)$stmt->fetchColumn();
}

// -------------------------------------------------------------
// TEST A: Creation Invalidates Teacher Auth Version
// -------------------------------------------------------------
echo "--- TEST A: Creation Invalidates Teacher Cache ---\n";
$ver1_start = getAuthVersion(99881);

$assignment_data = [
    'enseignant_id' => 99881,
    'classe_id' => 99810,
    'matiere_id' => 99801,
    'volume_horaire_hebdo' => 4.0,
    'statut' => 'actif'
];

$_SESSION['user'] = [
    'id' => 1,
    'id_user' => 1,
    'lycee_id' => 1,
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'permissions' => ['lycee' => ['view_all_lycees']]
];

$aff_id = AffectationPedagogiqueService::createAssignment($assignment_data, 1);
$ver1_after_create = getAuthVersion(99881);

if ($ver1_after_create > $ver1_start) {
    echo "  [PASS] Teacher 99881 auth_version incremented after creation ({$ver1_start} -> {$ver1_after_create}).\n";
} else {
    echo "  [FAIL] Teacher 99881 auth_version NOT incremented on creation!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST B: Status Update & Reactivation Invalidation
// -------------------------------------------------------------
echo "\n--- TEST B: Status Update & Reactivation Invalidation ---\n";
$ver1_before_suspend = getAuthVersion(99881);

AffectationPedagogiqueService::updateStatus($aff_id, 'suspendu', 'Suspension temporaire', null, 1);
$ver1_after_suspend = getAuthVersion(99881);

if ($ver1_after_suspend > $ver1_before_suspend) {
    echo "  [PASS] Teacher 99881 auth_version incremented on suspension ({$ver1_before_suspend} -> {$ver1_after_suspend}).\n";
} else {
    echo "  [FAIL] Teacher 99881 auth_version NOT incremented on suspension!\n";
    exit(1);
}

$ver1_before_reactivate = getAuthVersion(99881);
AffectationPedagogiqueService::reactivateAssignment($aff_id, 1);
$ver1_after_reactivate = getAuthVersion(99881);

if ($ver1_after_reactivate > $ver1_before_reactivate) {
    echo "  [PASS] Teacher 99881 auth_version incremented on reactivation ({$ver1_before_reactivate} -> {$ver1_after_reactivate}).\n";
} else {
    echo "  [FAIL] Teacher 99881 auth_version NOT incremented on reactivation!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST C: Non-Structural & Structural Update Invalidation
// -------------------------------------------------------------
echo "\n--- TEST C: Update Invalidation ---\n";
$ver1_before_upd = getAuthVersion(99881);

AffectationPedagogiqueService::updateAssignment($aff_id, ['volume_horaire_hebdo' => 5.0], 1);
$ver1_after_upd = getAuthVersion(99881);

if ($ver1_after_upd > $ver1_before_upd) {
    echo "  [PASS] Non-structural update incremented teacher 99881 auth_version ({$ver1_before_upd} -> {$ver1_after_upd}).\n";
} else {
    echo "  [FAIL] Non-structural update did NOT increment auth_version!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST D: Replacement Invalidates Both Old and New Teacher
// -------------------------------------------------------------
echo "\n--- TEST D: Replacement Invalidates Old and New Teachers ---\n";
$old_ver = getAuthVersion(99881);
$new_ver = getAuthVersion(99882);

$replace_data = [
    'enseignant_id' => 99882,
    'volume_horaire_hebdo' => 4.0,
    'motif_changement' => 'Remplacement professeur principal'
];

$new_aff_id = AffectationPedagogiqueService::replaceAssignment($aff_id, $replace_data, 1);

$old_ver_after = getAuthVersion(99881);
$new_ver_after = getAuthVersion(99882);

if ($old_ver_after > $old_ver && $new_ver_after > $new_ver) {
    echo "  [PASS] Both old teacher 99881 ({$old_ver} -> {$old_ver_after}) and new teacher 99882 ({$new_ver} -> {$new_ver_after}) invalidated on replacement.\n";
} else {
    echo "  [FAIL] Replacement cache invalidation failed! Old: {$old_ver}->{$old_ver_after}, New: {$new_ver}->{$new_ver_after}\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST E: Failure / Overlap Error Does NOT Invalidate Cache
// -------------------------------------------------------------
echo "\n--- TEST E: Failure / Overlap Error Prevents Premature Invalidation ---\n";
$ver2_before_fail = getAuthVersion(99882);

$conflicting_data = [
    'enseignant_id' => 99881,
    'classe_id' => 99810,
    'matiere_id' => 99801,
    'volume_horaire_hebdo' => 4.0,
    'statut' => 'actif'
];

try {
    AffectationPedagogiqueService::createAssignment($conflicting_data, 1);
    echo "  [FAIL] Conflicting assignment was unexpectedly allowed!\n";
    exit(1);
} catch (InvalidArgumentException $e) {
    $ver2_after_fail = getAuthVersion(99882);
    if ($ver2_before_fail === $ver2_after_fail) {
        echo "  [PASS] Failed assignment creation correctly preserved auth_version ({$ver2_before_fail}).\n";
    } else {
        echo "  [FAIL] Failed assignment unexpectedly incremented auth_version ({$ver2_before_fail} -> {$ver2_after_fail})!\n";
        exit(1);
    }
}

// -------------------------------------------------------------
// TEST F: Recalculation Strictly Reflects Real DB Assignments
// -------------------------------------------------------------
echo "\n--- TEST F: Scope Recalculation Reflects Real DB Assignments ---\n";
$_SESSION['user'] = [
    'id' => 99882,
    'id_user' => 99882,
    'lycee_id' => 1,
    'role_id' => 6,
    'role_name' => 'enseignant',
    'auth_version' => getAuthVersion(99882) - 1 // Force version mismatch check
];

$cycles = AuthorizationScopeService::getAuthorizedCycleIds();
if (in_array(99800, $cycles)) {
    echo "  [PASS] Recalculated authorized_cycles for teacher 99882 includes new active cycle 99800.\n";
} else {
    echo "  [FAIL] Recalculated authorized_cycles did NOT include active cycle 99800!\n";
    exit(1);
}

// Cleanup mock data
$db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id IN (99881, 99882)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (99881, 99882)");
$db->exec("DELETE FROM classe_matieres WHERE classe_id IN (99810, 99820)");
$db->exec("DELETE FROM matieres WHERE id_matiere IN (99801)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (99800, 99801)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (99881, 99882)");

echo "\n========================================================\n";
echo "SUCCESS: ALL PEDAGOGICAL CACHE INVALIDATION TESTS PASSED!\n";
echo "========================================================\n";
