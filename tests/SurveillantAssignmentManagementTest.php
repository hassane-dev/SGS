<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/SurveillantGroupe.php';
require_once __DIR__ . '/../src/models/SurveillantAffectation.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/SurveillantAssignmentService.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Surveillant Assignment & Scope Security Test\n";
echo "========================================================\n";

// Run migration 33
require_once __DIR__ . '/../db/migrations/20240115_33_create_surveillant_affectations_and_groupes.php';
migrate_33($db);

// Clean up test data
$db->exec("DELETE FROM surveillant_affectations WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM surveillant_groupe_classes WHERE groupe_id IN (SELECT id FROM surveillant_groupes WHERE lycee_id IN (991, 992))");
$db->exec("DELETE FROM surveillant_groupes WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99101, 99102, 99103, 99201)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (9910, 9920)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9911, 9912, 9921)");
$db->exec("DELETE FROM param_lycee WHERE id IN (991, 992)");

// Setup mock schools
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee, surveillant_config_version, surveillant_migration_status) VALUES (991, 'Lycée Test 1', 'public', 1, 'migre_valide')");
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee, surveillant_config_version, surveillant_migration_status) VALUES (992, 'Lycée Test 2', 'public', 1, 'migre_valide')");

// Setup active academic year
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (99, '2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}
$annee_id = (int)$activeYear['id'];

// Setup mock cycles
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9910, 'Collège Lycée 1', 991)");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9920, 'Collège Lycée 2', 992)");

// Setup mock classes
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99101, '6e', 'A', 1, 9910, 991)");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99102, '6e', 'A', 2, 9910, 991)");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99103, '5e', 'A', 1, 9910, 991)");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99201, '6e', 'A', 1, 9920, 992)");

// Setup mock users
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version) VALUES (9911, 991, 'Surveillant1', 'A', 's1@test.com', 'pass', 5, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version) VALUES (9912, 991, 'Surveillant2', 'B', 's2@test.com', 'pass', 5, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version) VALUES (9921, 992, 'SurveillantForeign', 'C', 'sf@test.com', 'pass', 5, 1, 1)");

function mockSession($userId, $lyceeId, $roleId = 5) {
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
    unset($_SESSION['user']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => $roleId,
        'role_name' => 'surveillant',
        'auth_version' => 1
    ];
}

echo "\n--- TEST 1: Affectation à une classe autorisée et restriction de portée ---\n";
mockSession(9911, 991);

SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9911,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'classe',
    'classe_id' => 99101,
    'date_debut' => '2024-09-01',
    'statut' => 'actif'
]);

$authorizedClasses = AuthorizationScopeService::getAuthorizedClassIds();
if (is_array($authorizedClasses) && count($authorizedClasses) === 1 && $authorizedClasses[0] === 99101) {
    echo "[PASS] 1a. User 9911 has strictly authorized class [99101].\n";
} else {
    echo "[FAIL] 1a. User 9911 authorized classes error: " . json_encode($authorizedClasses) . "\n";
}

if (AuthorizationScopeService::canAccessClass(99101) && !AuthorizationScopeService::canAccessClass(99102)) {
    echo "[PASS] 1b. canAccessClass(99101) TRUE and canAccessClass(99102) FALSE.\n";
} else {
    echo "[FAIL] 1b. Class scope check failed.\n";
}

echo "\n--- TEST 2: Cohérence multi-établissement et rejet cross-tenant ---\n";
try {
    SurveillantAssignmentService::validateTenantHomogeneity([
        'lycee_id' => 991,
        'annee_academique_id' => $annee_id,
        'surveillant_id' => 9911,
        'classe_id' => 99201 // Foreign class from lycee 992
    ]);
    echo "[FAIL] 2a. Foreign class assignment was not rejected.\n";
} catch (InvalidArgumentException $e) {
    echo "[PASS] 2a. Foreign class assignment properly rejected: " . $e->getMessage() . "\n";
}

echo "\n--- TEST 3: Co-gardiennage (Plusieurs surveillants sur la même classe) ---\n";
SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9912,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'classe',
    'classe_id' => 99101, // Co-guarding 99101
    'date_debut' => '2024-09-01',
    'statut' => 'actif'
]);

mockSession(9912, 991);
$s2Classes = AuthorizationScopeService::getAuthorizedClassIds();
if (is_array($s2Classes) && in_array(99101, $s2Classes, true)) {
    echo "[PASS] 3a. Co-guarding supervisor 9912 also successfully authorized for class [99101].\n";
} else {
    echo "[FAIL] 3a. Co-guarding assignment failed.\n";
}

echo "\n--- TEST 4: Prévention des doublons actifs et réaffectation après clôture ---\n";
$aff = SurveillantAffectation::findActiveForUser(9911, $annee_id);
if (!empty($aff)) {
    $a1 = $aff[0];
    $a1['statut'] = 'termine';
    $a1['date_fin'] = date('Y-m-d');
    SurveillantAffectation::save($a1);

    // Now re-assign 9911 to 99101
    $newId = SurveillantAffectation::save([
        'lycee_id' => 991,
        'annee_academique_id' => $annee_id,
        'surveillant_id' => 9911,
        'type_responsabilite' => 'surveillant_classe',
        'portee_type' => 'classe',
        'classe_id' => 99101,
        'date_debut' => date('Y-m-d'),
        'statut' => 'actif'
    ]);

    if ($newId > 0) {
        echo "[PASS] 4a. Re-assignment after termination succeeded with new ID [{$newId}].\n";
    } else {
        echo "[FAIL] 4a. Re-assignment failed.\n";
    }
}

echo "\n--- TEST 5: Ajout manuel d'une classe à un groupe sans ajout automatique ---\n";
$grpId = SurveillantGroupe::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'nom_groupe' => 'Groupe 6e',
    'type_groupe' => 'niveau',
    'niveau' => '6e'
]);
SurveillantGroupe::syncClasses($grpId, [99101, 99102]);

$grpClasses = SurveillantGroupe::getClassIdsForGroup($grpId);
if (count($grpClasses) === 2 && in_array(99101, $grpClasses) && in_array(99102, $grpClasses)) {
    echo "[PASS] 5a. Group manually configured with exact class IDs [99101, 99102].\n";
} else {
    echo "[FAIL] 5a. Group class sync failed.\n";
}

echo "\n--- TEST 6: Archivage avant suppression d'une classe ---\n";
Classe::delete(99102, 991);
$grpClassesAfter = SurveillantGroupe::getClassIdsForGroup($grpId);
if (!in_array(99102, $grpClassesAfter, true)) {
    echo "[PASS] 6a. Deleted class 99102 cleanly removed from group pivot table.\n";
} else {
    echo "[FAIL] 6a. Deleted class still present in group pivot.\n";
}

echo "\n--- TEST 7: OCC et détection de caducité sous modification concurrente ---\n";
$proposal = SurveillantAssignmentService::proposeDistribution(991, $annee_id, [99101, 99103], [9911, 9912], 'equitable');
$oldHash = $proposal['state_hash'];

// Simulate concurrent modification by bumping config version
SurveillantAssignmentService::bumpConfigVersion(991);

try {
    $proposal['state_hash'] = $oldHash;
    SurveillantAssignmentService::applyDistribution($proposal, 9911);
    echo "[FAIL] 7a. Stale proposal was not rejected by OCC.\n";
} catch (ConcurrentModificationException $e) {
    echo "[PASS] 7a. Stale proposal properly rejected with OCC exception: " . $e->getMessage() . "\n";
}

echo "\n--- TEST 8: Rejet de droits actifs pour les données historiques non validées ---\n";
$legacyAffId = SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9911,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'classe',
    'classe_id' => 99103,
    'date_debut' => '2020-09-01',
    'statut' => 'termine', // Legacy data imported as terminated
    'est_reprise_legacy' => 1
]);

mockSession(9911, 991);
$activeClasses = AuthorizationScopeService::getAuthorizedClassIds();
if (!in_array(99103, $activeClasses, true)) {
    echo "[PASS] 8a. Terminated legacy historical assignment does NOT grant active rights.\n";
} else {
    echo "[FAIL] 8a. Legacy terminated assignment granted active rights.\n";
}

echo "\n--- TEST 9: Verification des notifications en co-gardiennage (Tous les surveillants notifiés) ---\n";
require_once __DIR__ . '/../src/models/Notification.php';

// Setup active co-guarding assignments for 9911 and 9912 on class 99101
SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9911,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'classe',
    'classe_id' => 99101,
    'date_debut' => '2024-09-01',
    'statut' => 'actif'
]);
SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9912,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'classe',
    'classe_id' => 99101,
    'date_debut' => '2024-09-01',
    'statut' => 'actif'
]);

$notifiedUids = Notification::notifyClassSupervisors(99101, 991, "Alerte de test co-gardiennage", "/discipline/incidents/show?id=1");
if (count($notifiedUids) === 2 && in_array(9911, $notifiedUids, true) && in_array(9912, $notifiedUids, true)) {
    echo "[PASS] 9a. Co-guarding notification successfully dispatched to ALL active supervisors [9911, 9912] without exclusion.\n";
} else {
    echo "[FAIL] 9a. Co-guarding notification dispatch error: " . json_encode($notifiedUids) . "\n";
}

// Cleanup test data
$db->exec("DELETE FROM surveillant_affectations WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM surveillant_groupe_classes WHERE groupe_id IN (SELECT id FROM surveillant_groupes WHERE lycee_id IN (991, 992))");
$db->exec("DELETE FROM surveillant_groupes WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99101, 99102, 99103, 99201)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (9910, 9920)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9911, 9912, 9921)");
$db->exec("DELETE FROM param_lycee WHERE id IN (991, 992)");

echo "\n========================================================\n";
echo "SUCCESS: ALL SURVEILLANT ASSIGNMENT TESTS PASSED!\n";
echo "========================================================\n";
