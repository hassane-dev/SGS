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
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "SUITE DE TEST : SURVEILLANT CLASS ASSIGNMENT & SCOPE INTEGRATION\n";
echo "========================================================\n";

function assert_test($condition, $message) {
    if ($condition) {
        echo "  [PASS] " . $message . "\n";
    } else {
        echo "  [FAIL] " . $message . "\n";
        throw new Exception("Test failed: " . $message);
    }
}

// Cleanup previous test data
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99801, 99802, 99803)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99801, 99802, 99803)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820, 99830)");
$db->exec("DELETE FROM surveillant_classes WHERE surveillant_id IN (9981, 9982)");
$db->exec("DELETE FROM surveillant_niveaux WHERE surveillant_id IN (9981, 9982)");
$db->exec("DELETE FROM surveillant_general WHERE surveillant_id IN (9981, 9982)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983, 9984)");

// Ensure active academic year exists
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}

// Retrieve or create cycles for Lycée 1
$cycleCollege = $db->query("SELECT * FROM cycles WHERE lycee_id = 1 AND (LOWER(nom_cycle) LIKE '%ceg%' OR LOWER(nom_cycle) LIKE '%collège%' OR LOWER(nom_cycle) LIKE '%college%') LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$cycleLycee = $db->query("SELECT * FROM cycles WHERE lycee_id = 1 AND (LOWER(nom_cycle) LIKE '%lycée%' OR LOWER(nom_cycle) LIKE '%lycee%') LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cycleCollege) {
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9910, 'Collège Test', 1)");
    $cycleCollege = ['id_cycle' => 9910, 'nom_cycle' => 'Collège Test', 'lycee_id' => 1];
}
if (!$cycleLycee) {
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9920, 'Lycée Test', 1)");
    $cycleLycee = ['id_cycle' => 9920, 'nom_cycle' => 'Lycée Test', 'lycee_id' => 1];
}

$idCollege = (int)$cycleCollege['id_cycle'];
$idLycee = (int)$cycleLycee['id_cycle'];

// Create test classes
// Class 99810: Collège 6e 1 in Lycée 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (99810, 1, {$idCollege}, '6e', 1)");
// Class 99820: Lycée 2nd C 1 in Lycée 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (99820, 1, {$idLycee}, '2nd', 'C', 1)");
// Class 99830: Collège 6e 1 in Lycée 2 (Cross-tenant)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (99830, 2, {$idCollege}, '6e', 1)");

// Create test students
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99801, 1, 'Inscrit', 'Surveillant', 'en_attente')");
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99802, 1, 'Inscrit', 'Comptable', 'en_attente')");
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (99803, 2, 'Eleve', 'LyceeDeux', 'en_attente')");

// Create test users:
// User 9981: Surveillant (General/Unrestricted in Lycée 1) - role_id 5
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9981, 1, 'Surveillant', 'General', 5, 1, 1)");
// User 9982: Surveillant (Restricted to Collège 6e 1 in Lycée 1) - role_id 5
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9982, 1, 'Surveillant', 'Restreint', 5, 1, 1)");
$db->exec("INSERT INTO surveillant_classes (surveillant_id, classe_id) VALUES (9982, 99810)");

// User 9983: Comptable (Lycée 1) - role_id 7
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9983, 1, 'Comptable', 'Standard', 7, 1, 1)");

// User 9984: Eleve / User without enrollment permission - role_id 8
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9984, 1, 'User', 'SansPerms', 8, 1, 1)");

function mockUserSession($userId, $roleId, $roleName, $lyceeId) {
    unset($_SESSION['user']['authorized_cycles']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => $roleId,
        'role_name' => $roleName,
        'auth_version' => 1
    ];
    AuthorizationScopeService::invalidateUserCache($userId);
}

$classeCtrl = new ClasseController();
$eleveCtrl = new EleveController();

// =============================================================
// TEST 1: Surveillant autorisé peut charger les cycles autorisés
// =============================================================
echo "\nTEST 1: Surveillant avec permissions peut charger les cycles autorisés\n";
mockUserSession(9981, 5, 'surveillant', 1);

$permittedCyclesSurv = AuthorizationScopeService::getPermittedCycles(1);
$permittedCycleIdsSurv = array_column($permittedCyclesSurv, 'id_cycle');

assert_test(!empty($permittedCycleIdsSurv), "Surveillant Général obtient au moins un cycle autorisé.");
assert_test(in_array($idCollege, $permittedCycleIdsSurv) && in_array($idLycee, $permittedCycleIdsSurv), "Surveillant Général du Lycée 1 voit tous les cycles de son établissement.");

// Restricted Surveillant (User 9982) assigned to Collège class 99810
mockUserSession(9982, 5, 'surveillant', 1);
$permittedCyclesRest = AuthorizationScopeService::getPermittedCycles(1);
$permittedCycleIdsRest = array_column($permittedCyclesRest, 'id_cycle');

assert_test(count($permittedCycleIdsRest) === 1 && in_array($idCollege, $permittedCycleIdsRest), "Surveillant restreint voit strictly le cycle [{$idCollege}] de sa classe affectée.");

// =============================================================
// TEST 2: La sélection d'un cycle déclenche le chargement des niveaux et des classes
// =============================================================
echo "\nTEST 2: La sélection d'un cycle déclenche le chargement AJAX des niveaux et des classes\n";
mockUserSession(9981, 5, 'surveillant', 1);

// Query niveaux for Collège cycle
$_GET = ['cycle_id' => $idCollege, 'lycee_id' => 1];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveauxRes = json_decode(ob_get_clean(), true);
assert_test(in_array('6e', $niveauxRes), "getNiveauxForCycle renvoie '6e' pour le cycle Collège.");

// Query numeros for Collège 6e
$_GET = ['cycle_id' => $idCollege, 'niveau' => '6e', 'lycee_id' => 1];
ob_start(); $classeCtrl->getNumerosForClasse(); $numerosRes = json_decode(ob_get_clean(), true);
assert_test(in_array(1, $numerosRes) || in_array('1', $numerosRes), "getNumerosForClasse renvoie '1' pour Collège 6e.");

// Query series for Lycée 2nd
$_GET = ['cycle_id' => $idLycee, 'niveau' => '2nd', 'lycee_id' => 1];
ob_start(); $classeCtrl->getSeriesForNiveau(); $seriesRes = json_decode(ob_get_clean(), true);
assert_test(in_array('C', $seriesRes), "getSeriesForNiveau renvoie 'C' pour Lycée 2nd.");

// =============================================================
// TEST 3: Le Surveillant peut terminer l'affectation d'un élève à une classe autorisée
// =============================================================
echo "\nTEST 3: Le Surveillant affecte un nouvel élève à une classe autorisée avec succès\n";
mockUserSession(9981, 5, 'surveillant', 1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'eleve_id' => 99801,
    'cycle_id' => $idCollege,
    'niveau' => '6e',
    'numero' => 1
];

ob_start();
try {
    $eleveCtrl->processAssignment();
} catch (Throwable $e) {}
ob_end_clean();

$checkEtude = $db->query("SELECT * FROM etudes WHERE eleve_id = 99801 AND classe_id = 99810")->fetch(PDO::FETCH_ASSOC);
assert_test(!empty($checkEtude), "L'inscription de l'élève 99801 dans la classe 99810 a bien été enregistrée par le Surveillant.");

// =============================================================
// TEST 4: Le Surveillant ne peut pas affecter hors périmètre via manipulation HTTP
// =============================================================
echo "\nTEST 4: Tentative d'affectation hors périmètre par le Surveillant (rejet serveur)\n";
// User 9982 is strictly restricted to Collège cycle ($idCollege)
mockUserSession(9982, 5, 'surveillant', 1);

// Attempt to assign student to Lycée cycle class ($idLycee / 2nd C 1)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'eleve_id' => 99801,
    'cycle_id' => $idLycee,
    'niveau' => '2nd',
    'serie' => 'C',
    'numero' => 1
];

ob_start();
try {
    $eleveCtrl->processAssignment();
} catch (Throwable $e) {}
ob_end_clean();

assert_test(isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "Accès refusé"), "Tentative d'affectation dans un cycle hors périmètre bloquée par le serveur avec message d'erreur.");
unset($_SESSION['error_message']);

// =============================================================
// TEST 5: Le Comptable conserve son fonctionnement actuel
// =============================================================
echo "\nTEST 5: Le Comptable conserve son fonctionnement et son accès académique\n";
mockUserSession(9983, 7, 'comptable', 1);

$permittedCyclesComp = AuthorizationScopeService::getPermittedCycles(1);
$permittedCycleIdsComp = array_column($permittedCyclesComp, 'id_cycle');
assert_test(!empty($permittedCycleIdsComp), "Le Comptable accède aux cycles de son établissement.");

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'eleve_id' => 99802,
    'cycle_id' => $idCollege,
    'niveau' => '6e',
    'numero' => 1
];

ob_start();
try {
    $eleveCtrl->processAssignment();
} catch (Throwable $e) {}
ob_end_clean();

$checkEtudeComp = $db->query("SELECT * FROM etudes WHERE eleve_id = 99802 AND classe_id = 99810")->fetch(PDO::FETCH_ASSOC);
assert_test(!empty($checkEtudeComp), "L'inscription par le Comptable fonctionne parfaitement.");

// =============================================================
// TEST 6: Utilisateur sans permissions requises reste bloqué côté serveur
// =============================================================
echo "\nTEST 6: Utilisateur sans permission 'inscrire' reste bloqué côté serveur\n";
mockUserSession(9984, 8, 'eleve', 1);

$blocked = false;
try {
    ob_start();
    $eleveCtrl->assignClass();
    ob_end_clean();
} catch (Throwable $e) {
    $blocked = true;
}

assert_test($blocked || http_response_code() === 403, "Accès à assignClass() refusé (403) pour un rôle sans permission 'inscrire'.");

// =============================================================
// TEST 7: Multi-lycée et filtres académiques ne régressent pas
// =============================================================
echo "\nTEST 7: Contrôle d'isolation multi-lycée et non-régression des filtres académiques\n";
mockUserSession(9981, 5, 'surveillant', 1); // Belongs to Lycée 1

// Try to assign student belonging to Lycée 2 (eleve_id 99803)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'eleve_id' => 99803,
    'cycle_id' => $idCollege,
    'niveau' => '6e',
    'numero' => 1
];

$crossTenantBlocked = false;
try {
    ob_start();
    $eleveCtrl->processAssignment();
    ob_end_clean();
} catch (Throwable $e) {
    $crossTenantBlocked = true;
}

assert_test($crossTenantBlocked, "Affectation cross-tenant (élève d'un autre établissement) rejetée côté serveur.");

// Cleanup test data
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99801, 99802, 99803)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99801, 99802, 99803)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820, 99830)");
$db->exec("DELETE FROM surveillant_classes WHERE surveillant_id IN (9981, 9982)");
$db->exec("DELETE FROM surveillant_niveaux WHERE surveillant_id IN (9981, 9982)");
$db->exec("DELETE FROM surveillant_general WHERE surveillant_id IN (9981, 9982)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983, 9984)");

echo "\n========================================================\n";
echo "TOUS LES TESTS D'INTEGRATION POUR LE SURVEILLANT ONT REUSSI !\n";
echo "========================================================\n";
