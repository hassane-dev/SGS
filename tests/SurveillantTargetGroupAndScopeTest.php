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
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Surveillant Target Group & Scope Security Test\n";
echo "========================================================\n";

// Ensure Migration 33 is executed
require_once __DIR__ . '/../db/migrations/20240115_33_create_surveillant_affectations_and_groupes.php';
migrate_33($db);

// Clean up test records
$db->exec("DELETE FROM surveillant_affectations WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM surveillant_groupe_classes WHERE groupe_id IN (SELECT id FROM surveillant_groupes WHERE lycee_id IN (991, 992))");
$db->exec("DELETE FROM surveillant_groupes WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99101, 99102, 99103, 99201)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (9910, 9920)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9911, 9912, 9913, 9914, 9915, 9921)");
$db->exec("DELETE FROM param_lycee WHERE id IN (991, 992)");

// 1. Mock Schools
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee, surveillant_config_version, surveillant_migration_status) VALUES (991, 'Lycée Target Test 1', 'public', 1, 'migre_valide')");
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee, surveillant_config_version, surveillant_migration_status) VALUES (992, 'Lycée Target Test 2', 'public', 1, 'migre_valide')");

// 2. Mock Active Academic Year
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (99, '2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}
$annee_id = (int)$activeYear['id'];

// 3. Mock Cycles
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9910, 'Collège', 991)");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9920, 'Collège Lycée 2', 992)");

// 4. Mock Classes
// School 991: 6e 1 (99101), 6e 2 (99102), 5e 1 (99103)
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99101, '6e', 'A', 1, 9910, 991)");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99102, '6e', 'A', 2, 9910, 991)");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99103, '5e', 'A', 1, 9910, 991)");

// School 992: 6e 1 (99201)
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (99201, '6e', 'A', 1, 9920, 992)");

// 5. Mock Users
// 9911: Surveillant 1 (Lycee 991)
// 9912: Surveillant 2 (Lycee 991)
// 9913: Admin (Lycee 991)
// 9914: Unassigned Surveillant (Lycee 991)
// 9921: Foreign Surveillant (Lycee 992)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif, auth_version) VALUES (9911, 'Durand', 'Paul', 'p.durand@test.com', 5, 991, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif, auth_version) VALUES (9912, 'Martin', 'Sophie', 's.martin@test.com', 5, 991, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif, auth_version) VALUES (9913, 'Admin', 'Marc', 'm.admin@test.com', 1, 991, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif, auth_version) VALUES (9914, 'SansAff', 'Jean', 'j.sans@test.com', 5, 991, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif, auth_version) VALUES (9921, 'Etranger', 'Claire', 'c.etranger@test.com', 5, 992, 1, 1)");


// =========================================================================
// SCENARIO 1: Surveillant affecté uniquement à 6e 1 (99101)
// =========================================================================
echo "\n--- SCENARIO 1: Surveillant affecté uniquement à 6e 1 (99101) ---\n";

SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9911,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'classe',
    'classe_id' => 99101,
    'date_debut' => date('Y-m-d'),
    'statut' => 'actif'
]);

$_SESSION['user'] = [
    'id' => 9911,
    'id_user' => 9911,
    'nom' => 'Durand',
    'prenom' => 'Paul',
    'role_name' => 'surveillant',
    'id_role' => 5,
    'lycee_id' => 991,
    'auth_version' => 1
];

AuthorizationScopeService::invalidateUserCache(9911);
$authClasses = AuthorizationScopeService::getAuthorizedClassIds(991);

if ($authClasses === [99101]) {
    echo "[PASS] 1a. Supervisor 9911 receives strictly authorized class [99101].\n";
} else {
    echo "[FAIL] 1a. Expected [99101], got: " . json_encode($authClasses) . "\n";
    exit(1);
}

if (AuthorizationScopeService::canAccessClass(99101) && !AuthorizationScopeService::canAccessClass(99102) && !AuthorizationScopeService::canAccessClass(99103)) {
    echo "[PASS] 1b. canAccessClass(99101) TRUE, 99102 FALSE, 99103 FALSE.\n";
} else {
    echo "[FAIL] 1b. Class access check failed for supervisor 9911.\n";
    exit(1);
}


// =========================================================================
// SCENARIO 2: Vérification des niveaux et filtres (Ne propose pas 5e, 4e, 3e)
// =========================================================================
echo "\n--- SCENARIO 2: Les filtres ne proposent pas 5e si non autorisée ---\n";

$niveaux = Classe::findDistinctNiveauxByCycle(9910, 991, $authClasses);
if ($niveaux === ['6e']) {
    echo "[PASS] 2a. Level filter returns strictly ['6e'] for supervisor 9911 (5e is excluded).\n";
} else {
    echo "[FAIL] 2a. Expected ['6e'], got: " . json_encode($niveaux) . "\n";
    exit(1);
}

$numeros6e = Classe::findAvailableNumeros('6e', 'A', 991, 9910, $authClasses);
if ($numeros6e === [1]) {
    echo "[PASS] 2b. Class numbers filter returns strictly [1] for 6e (6e 2 is excluded).\n";
} else {
    echo "[FAIL] 2b. Expected [1], got: " . json_encode($numeros6e) . "\n";
    exit(1);
}

$numeros5e = Classe::findAvailableNumeros('5e', 'A', 991, 9910, $authClasses);
if (empty($numeros5e)) {
    echo "[PASS] 2c. Class numbers for unauthorized 5e returns empty array.\n";
} else {
    echo "[FAIL] 2c. Expected empty array, got: " . json_encode($numeros5e) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 3: Formulaire d'affectation d'un élève & traitement serveur
// =========================================================================
echo "\n--- SCENARIO 3: Résolution de classe et sécurité lors de l'affectation d'un élève ---\n";

$res6e1 = Classe::findIdByDetails(991, '6e', 'A', 1, 9910, $authClasses);
if ($res6e1 == 99101) {
    echo "[PASS] 3a. findIdByDetails resolves 6e 1 -> 99101 for authorized supervisor.\n";
} else {
    echo "[FAIL] 3a. Expected 99101, got: " . var_export($res6e1, true) . "\n";
    exit(1);
}

$res5e1 = Classe::findIdByDetails(991, '5e', 'A', 1, 9910, $authClasses);
if ($res5e1 === null) {
    echo "[PASS] 3b. findIdByDetails for unauthorized 5e 1 returns NULL.\n";
} else {
    echo "[FAIL] 3b. Expected NULL for unauthorized class, got: " . var_export($res5e1, true) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 4: Requête directe vers endpoint AJAX ne permet pas le contournement
// =========================================================================
echo "\n--- SCENARIO 4: Requête directe AJAX /classes/ajax-niveaux et find-class-id ---\n";

$_GET = ['cycle_id' => 9910, 'lycee_id' => 991];
ob_start();
$controller = new ClasseController();
$controller->getNiveauxForCycle();
$outputNiveaux = ob_get_clean();
$jsonNiveaux = json_decode($outputNiveaux, true);

if ($jsonNiveaux === ['6e']) {
    echo "[PASS] 4a. Direct AJAX getNiveauxForCycle returns strictly ['6e'] for supervisor.\n";
} else {
    echo "[FAIL] 4a. Expected ['6e'], got: " . $outputNiveaux . "\n";
    exit(1);
}

$_GET = ['lycee_id' => 991, 'niveau' => '5e', 'serie' => 'A', 'numero' => 1, 'cycle_id' => 9910];
ob_start();
$controller->findClassId();
$outputFind = ob_get_clean();
$jsonFind = json_decode($outputFind, true);

if (array_key_exists('id_classe', $jsonFind) && $jsonFind['id_classe'] === null) {
    echo "[PASS] 4b. Direct AJAX findClassId for unauthorized 5e 1 returns id_classe: null.\n";
} else {
    echo "[FAIL] 4b. Expected id_classe null, got: " . $outputFind . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 5: Surveillant affecté à un Groupe de Classes
// =========================================================================
echo "\n--- SCENARIO 5: Surveillant affecté à un Groupe de Classes ---\n";

$grpId = SurveillantGroupe::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'nom_groupe' => 'Groupe 6e Total',
    'description' => 'Test groupe 6e',
    'type_groupe' => 'personnalise'
]);
SurveillantGroupe::syncClasses($grpId, [99101, 99102]); // 6e 1 and 6e 2, but NOT 5e 1 (99103)

SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9912,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'groupe_classes',
    'groupe_id' => $grpId,
    'date_debut' => date('Y-m-d'),
    'statut' => 'actif'
]);

$_SESSION['user'] = [
    'id' => 9912,
    'id_user' => 9912,
    'nom' => 'Martin',
    'prenom' => 'Sophie',
    'role_name' => 'surveillant',
    'id_role' => 5,
    'lycee_id' => 991,
    'auth_version' => 1
];

AuthorizationScopeService::invalidateUserCache(9912);
$authGrpClasses = AuthorizationScopeService::getAuthorizedClassIds(991);
sort($authGrpClasses);

if ($authGrpClasses === [99101, 99102]) {
    echo "[PASS] 5a. Supervisor 9912 receives strictly member classes of the group [99101, 99102].\n";
} else {
    echo "[FAIL] 5a. Expected [99101, 99102], got: " . json_encode($authGrpClasses) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 6: Affectation de Niveau et de Cycle selon leur portée exacte
// =========================================================================
echo "\n--- SCENARIO 6: Affectation Niveau et Cycle selon portée exacte ---\n";

// Test 6a: Niveau 6e
$db->exec("DELETE FROM surveillant_affectations WHERE surveillant_id = 9912");
SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9912,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'niveau',
    'niveau_code' => '6e',
    'date_debut' => date('Y-m-d'),
    'statut' => 'actif'
]);

AuthorizationScopeService::invalidateUserCache(9912);
$authNivClasses = AuthorizationScopeService::getAuthorizedClassIds(991);
sort($authNivClasses);

if ($authNivClasses === [99101, 99102]) {
    echo "[PASS] 6a. Level scope '6e' grants strictly all 6e classes [99101, 99102] (excludes 5e 99103).\n";
} else {
    echo "[FAIL] 6a. Expected [99101, 99102], got: " . json_encode($authNivClasses) . "\n";
    exit(1);
}

// Test 6b: Cycle 9910
$db->exec("DELETE FROM surveillant_affectations WHERE surveillant_id = 9912");
SurveillantAffectation::save([
    'lycee_id' => 991,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9912,
    'type_responsabilite' => 'surveillant_general',
    'portee_type' => 'cycle',
    'cycle_id' => 9910,
    'date_debut' => date('Y-m-d'),
    'statut' => 'actif'
]);

AuthorizationScopeService::invalidateUserCache(9912);
$authCycClasses = AuthorizationScopeService::getAuthorizedClassIds(991);
sort($authCycClasses);

if ($authCycClasses === [99101, 99102, 99103]) {
    echo "[PASS] 6b. Cycle scope '9910' grants all classes in cycle [99101, 99102, 99103].\n";
} else {
    echo "[FAIL] 6b. Expected [99101, 99102, 99103], got: " . json_encode($authCycClasses) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 7: Administrateur conserve son accès global légitime
// =========================================================================
echo "\n--- SCENARIO 7: Administrateur conserve un accès global légitime ---\n";

$db->exec("INSERT OR IGNORE INTO permissions (id_permission, resource, action, description) VALUES (9991, 'lycee', 'view_all_lycees', 'Voir tous les lycées')");
$db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 9991)");

$_SESSION['user'] = [
    'id' => 9913,
    'id_user' => 9913,
    'nom' => 'Admin',
    'prenom' => 'Marc',
    'role_name' => 'admin_local',
    'id_role' => 1,
    'role_id' => 1,
    'lycee_id' => 991,
    'auth_version' => 1
];

AuthorizationScopeService::invalidateUserCache(9913);
$adminClasses = AuthorizationScopeService::getAuthorizedClassIds(991);

if ($adminClasses === null) {
    echo "[PASS] 7a. Admin user gets getAuthorizedClassIds() === NULL (unrestricted access).\n";
} else {
    echo "[FAIL] 7a. Expected NULL, got: " . json_encode($adminClasses) . "\n";
    exit(1);
}

$allClasses = Classe::findAll(991, $adminClasses);
if (count($allClasses) === 3) {
    echo "[PASS] 7b. Admin retrieves all 3 classes of the establishment via Classe::findAll.\n";
} else {
    echo "[FAIL] 7b. Expected 3 classes, got: " . count($allClasses) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 8: Surveillant sans affectation valide (Refus par défaut)
// =========================================================================
echo "\n--- SCENARIO 8: Surveillant sans affectation (Principe du refus par défaut) ---\n";

$_SESSION['user'] = [
    'id' => 9914,
    'id_user' => 9914,
    'nom' => 'SansAff',
    'prenom' => 'Jean',
    'role_name' => 'surveillant',
    'id_role' => 5,
    'lycee_id' => 991,
    'auth_version' => 1
];

AuthorizationScopeService::invalidateUserCache(9914);
$unassignedClasses = AuthorizationScopeService::getAuthorizedClassIds(991);

if ($unassignedClasses === []) {
    echo "[PASS] 8a. Unassigned supervisor gets empty array [] for getAuthorizedClassIds().\n";
} else {
    echo "[FAIL] 8a. Expected [], got: " . json_encode($unassignedClasses) . "\n";
    exit(1);
}

if (!AuthorizationScopeService::canAccessClass(99101) && !AuthorizationScopeService::canAccessClass(99102)) {
    echo "[PASS] 8b. canAccessClass returns FALSE for all classes for unassigned supervisor.\n";
} else {
    echo "[FAIL] 8b. Unassigned supervisor inappropriately granted class access.\n";
    exit(1);
}


// =========================================================================
// SCENARIO 9: Isolement multi-établissement et année académique
// =========================================================================
echo "\n--- SCENARIO 9: Isolation multi-établissement et clôture d'année ---\n";

$_SESSION['user'] = [
    'id' => 9921,
    'id_user' => 9921,
    'nom' => 'Etranger',
    'prenom' => 'Claire',
    'role_name' => 'surveillant',
    'id_role' => 5,
    'lycee_id' => 992,
    'auth_version' => 1
];

// Give 9921 an assignment in school 992
SurveillantAffectation::save([
    'lycee_id' => 992,
    'annee_academique_id' => $annee_id,
    'surveillant_id' => 9921,
    'type_responsabilite' => 'surveillant_classe',
    'portee_type' => 'classe',
    'classe_id' => 99201,
    'date_debut' => date('Y-m-d'),
    'statut' => 'actif'
]);

AuthorizationScopeService::invalidateUserCache(9921);
if (!AuthorizationScopeService::canAccessClass(99101) && AuthorizationScopeService::canAccessClass(99201)) {
    echo "[PASS] 9a. Foreign supervisor 9921 can access 99201 in school 992 but NOT 99101 in school 991.\n";
} else {
    echo "[FAIL] 9a. Multi-establishment boundary isolation failed.\n";
    exit(1);
}

// Test terminated assignment in past year
$db->exec("UPDATE surveillant_affectations SET statut = 'termine', date_fin = '2023-06-30' WHERE surveillant_id = 9921");
AuthorizationScopeService::invalidateUserCache(9921);
$pastClasses = AuthorizationScopeService::getAuthorizedClassIds(992);

if ($pastClasses === []) {
    echo "[PASS] 9b. Closed/Terminated assignment does NOT grant active class access.\n";
} else {
    echo "[FAIL] 9b. Terminated assignment improperly granted active access: " . json_encode($pastClasses) . "\n";
    exit(1);
}


// Clean up test data
$db->exec("DELETE FROM surveillant_affectations WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM surveillant_groupe_classes WHERE groupe_id IN (SELECT id FROM surveillant_groupes WHERE lycee_id IN (991, 992))");
$db->exec("DELETE FROM surveillant_groupes WHERE lycee_id IN (991, 992)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99101, 99102, 99103, 99201)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (9910, 9920)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9911, 9912, 9913, 9914, 9915, 9921)");
$db->exec("DELETE FROM param_lycee WHERE id IN (991, 992)");

echo "\n========================================================\n";
echo "SUCCESS: ALL SURVEILLANT TARGET GROUP & SCOPE TESTS PASSED!\n";
echo "========================================================\n";
