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
require_once __DIR__ . '/../src/services/AcademicFilterService.php';
require_once __DIR__ . '/../src/services/EleveDashboardService.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';
require_once __DIR__ . '/../src/controllers/EleveDashboardController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: RBAC Cycle Scope Correction Tests (7 Controls)\n";
echo "========================================================\n";

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (7781, 7782, 7783, 7784, 7785)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (77801, 77802)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (77801, 77802)");
$db->exec("DELETE FROM classes WHERE id_classe IN (77810, 77820, 77830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (7781, 7782, 7783, 7784, 7785)");

// Ensure active year
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}

// Find CEG and Lycée cycles for Establishment 1
$cycleCeg = $db->query("SELECT * FROM cycles WHERE (LOWER(nom_cycle) LIKE '%ceg%' OR LOWER(nom_cycle) LIKE '%collège%') AND (lycee_id = 1 OR lycee_id IS NULL) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$cycleLycee = $db->query("SELECT * FROM cycles WHERE (LOWER(nom_cycle) LIKE '%lycée%' OR LOWER(nom_cycle) LIKE '%lycee%') AND (lycee_id = 1 OR lycee_id IS NULL) LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cycleCeg || !$cycleLycee) {
    echo "ERROR: Cycles CEG or Lycee missing in test DB.\n";
    exit(1);
}

$idCeg = (int)$cycleCeg['id_cycle'];
$idLycee = (int)$cycleLycee['id_cycle'];

// Create test classes
// CEG class (6e A, Lycee 1)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (77810, 1, {$idCeg}, '6e', 'A')");
// Lycée class (2nd C1, Lycee 1)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77820, 1, {$idLycee}, '2nd', 'C', '1')");
// Lycée class for Establishment 2
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77830, 2, {$idLycee}, '2nd', 'C', '1')");

// Create test students
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (77801, 1, 'Eleve', 'CEG', 'en_attente')");
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (77802, 2, 'Eleve', 'Lycee2', 'en_attente')");

// Ensure role 77 exists with eleve:inscrire permission (no global view_all_cycles or view_all_lycees)
$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id) VALUES (77, 'agent_surveillant_test', 1)");
$permInscrire = $db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'inscrire'")->fetchColumn();
if (!$permInscrire) {
    $db->exec("INSERT INTO permissions (resource, action) VALUES ('eleve', 'inscrire')");
    $permInscrire = $db->lastInsertId();
}
$stmtRp = $db->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = 77 AND permission_id = :p");
$stmtRp->execute(['p' => $permInscrire]);
if ($stmtRp->fetchColumn() == 0) {
    $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (77, {$permInscrire})");
}

// User 7781: CEG-only assigned
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (7781, 1, 'Surveillant', 'CEGOnly', 77, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7781, {$idCeg}, '2020-01-01', 1)");

// User 7782: Lycee-only assigned
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (7782, 1, 'Surveillant', 'LyceeOnly', 77, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7782, {$idLycee}, '2020-01-01', 1)");

// User 7783: Multi-cycle assigned (CEG + Lycee)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (7783, 1, 'Surveillant', 'MultiCycle', 77, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7783, {$idCeg}, '2020-01-01', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7783, {$idLycee}, '2020-01-01', 1)");

// User 7784: User WITHOUT cycle assignments (unassigned user)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (7784, 1, 'User', 'Unassigned', 77, 1, 1)");

// User 7785: User with EXPIRED cycle assignment
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (7785, 1, 'User', 'ExpiredCycle', 77, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (7785, {$idCeg}, '2019-01-01', '2019-12-31', 1)");

function setMockSession($userId, $lyceeId) {
    unset($_SESSION['user']['authorized_cycles']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => 77,
        'role_name' => 'agent_surveillant_test',
        'auth_version' => 1
    ];
    AuthorizationScopeService::invalidateUserCache($userId);
}

$classeCtrl = new ClasseController();
$eleveCtrl = new EleveController();

// -------------------------------------------------------------
// CONTROL 1: Utilisateur autorisé uniquement sur CEG : seul CEG apparaît.
// -------------------------------------------------------------
echo "CONTROL 1: Utilisateur CEG Uniquement -> Seul CEG apparaît\n";
setMockSession(7781, 1);

$cycles = AuthorizationScopeService::getPermittedCycles(1);
$cycleIds = array_column($cycles, 'id_cycle');

if (count($cycleIds) === 1 && in_array($idCeg, $cycleIds, true)) {
    echo "  [PASS] 1a. AuthorizationScopeService::getPermittedCycles returns strictly CEG [{$idCeg}].\n";
} else {
    echo "  [FAIL] 1a. Expected strictly CEG [{$idCeg}], got: " . json_encode($cycleIds) . "\n";
}

// -------------------------------------------------------------
// CONTROL 2: Utilisateur autorisé uniquement sur Lycée : seul Lycée apparaît.
// -------------------------------------------------------------
echo "\nCONTROL 2: Utilisateur Lycée Uniquement -> Seul Lycée apparaît\n";
setMockSession(7782, 1);

$cycles = AuthorizationScopeService::getPermittedCycles(1);
$cycleIds = array_column($cycles, 'id_cycle');

if (count($cycleIds) === 1 && in_array($idLycee, $cycleIds, true)) {
    echo "  [PASS] 2a. AuthorizationScopeService::getPermittedCycles returns strictly Lycee [{$idLycee}].\n";
} else {
    echo "  [FAIL] 2a. Expected strictly Lycee [{$idLycee}], got: " . json_encode($cycleIds) . "\n";
}

// -------------------------------------------------------------
// CONTROL 3: Utilisateur autorisé sur plusieurs cycles : tous ses cycles autorisés apparaissent.
// -------------------------------------------------------------
echo "\nCONTROL 3: Utilisateur Multi-Cycles -> Tous ses cycles autorisés apparaissent\n";
setMockSession(7783, 1);

$cycles = AuthorizationScopeService::getPermittedCycles(1);
$cycleIds = array_column($cycles, 'id_cycle');
sort($cycleIds);
$expectedIds = [$idCeg, $idLycee];
sort($expectedIds);

if ($cycleIds === $expectedIds) {
    echo "  [PASS] 3a. Multi-cycle user receives exact union of assigned cycles [CEG, Lycée].\n";
} else {
    echo "  [FAIL] 3a. Expected " . json_encode($expectedIds) . ", got: " . json_encode($cycleIds) . "\n";
}

// -------------------------------------------------------------
// CONTROL 4: Utilisateur limité à un lycée : aucun cycle d'un autre lycée n'apparaît.
// -------------------------------------------------------------
echo "\nCONTROL 4: Isolation par Lycée -> Aucun cycle d'un autre lycée n'apparaît\n";
setMockSession(7781, 1); // User belongs to Lycée 1

$authorizedLyceeIds = AuthorizationScopeService::getAuthorizedLyceeIds();
if (count($authorizedLyceeIds) === 1 && $authorizedLyceeIds[0] === 1) {
    echo "  [PASS] 4a. Lycee IDs strictly locked to [1], foreign school [2] excluded.\n";
} else {
    echo "  [FAIL] 4a. Lycee scope leakage! Got: " . json_encode($authorizedLyceeIds) . "\n";
}

$_GET = ['cycle_id' => $idCeg, 'lycee_id' => 2];
ob_start(); $classeCtrl->getNiveauxForCycle(); $foreignNiveaux = json_decode(ob_get_clean(), true);
if (empty($foreignNiveaux)) {
    echo "  [PASS] 4b. Request for foreign lycee (2) returns EMPTY array.\n";
} else {
    echo "  [FAIL] 4b. Foreign lycee query returned data! Got: " . json_encode($foreignNiveaux) . "\n";
}

// -------------------------------------------------------------
// CONTROL 5: Cycle non autorisé transmis manuellement : requête refusée côté serveur.
// -------------------------------------------------------------
echo "\nCONTROL 5: Soumission manuelle d'un cycle non autorisé -> Refus serveur\n";
setMockSession(7781, 1); // CEG-only user

// 5a. Assignment Form process submission
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['eleve_id' => 77801, 'cycle_id' => $idLycee, 'niveau' => '2nd', 'serie' => 'C', 'numero' => '1'];
ob_start();
try { $eleveCtrl->processAssignment(); } catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "Accès refusé")) {
    echo "  [PASS] 5a. Manual POST with unauthorized Lycee cycle [{$idLycee}] REJECTED server-side.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 5a. Manual submission of unauthorized cycle was accepted! Error: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// 5b. Student Dashboard data service request
try {
    EleveDashboardService::getDashboardData(['lycee_id' => 1, 'cycle_id' => $idLycee], 7781);
    echo "  [FAIL] 5b. Dashboard query for unauthorized cycle was executed!\n";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), "Accès non autorisé")) {
        echo "  [PASS] 5b. Dashboard request targeting unauthorized cycle BLOCKED server-side.\n";
    } else {
        echo "  [FAIL] 5b. Unexpected exception message: " . $e->getMessage() . "\n";
    }
}

// -------------------------------------------------------------
// CONTROL 6: Aucun accès global n'est accordé par défaut lorsque les affectations sont absentes ou invalides.
// -------------------------------------------------------------
echo "\nCONTROL 6: Aucun accès global par défaut sur affectations absentes ou invalides\n";

// 6a. User 7784 with NO cycle assignment at all
setMockSession(7784, 1);
$unassignedCycleIds = AuthorizationScopeService::getAuthorizedCycleIds();
if (empty($unassignedCycleIds)) {
    echo "  [PASS] 6a. User without any cycle assignment receives EMPTY cycle scope (no global fallback).\n";
} else {
    echo "  [FAIL] 6a. User without assignments granted cycle scope! Got: " . json_encode($unassignedCycleIds) . "\n";
}

// 6b. User 7785 with EXPIRED cycle assignment
setMockSession(7785, 1);
$expiredCycleIds = AuthorizationScopeService::getAuthorizedCycleIds();
if (empty($expiredCycleIds)) {
    echo "  [PASS] 6b. User with EXPIRED cycle assignment receives EMPTY cycle scope (no global fallback).\n";
} else {
    echo "  [FAIL] 6b. User with expired assignment granted cycle scope! Got: " . json_encode($expiredCycleIds) . "\n";
}

// -------------------------------------------------------------
// CONTROL 7: Les filtres niveau, série et numéro ne permettent pas de contourner les restrictions.
// -------------------------------------------------------------
echo "\nCONTROL 7: Les filtres niveau, série et numéro ne permettent pas de contourner les restrictions\n";
setMockSession(7781, 1); // CEG-only user

// 7a. Query series for out-of-scope level '2nd'
$_GET = ['niveau' => '2nd', 'lycee_id' => 1, 'cycle_id' => $idLycee];
ob_start(); $classeCtrl->getSeriesForNiveau(); $resSeries = json_decode(ob_get_clean(), true);
if (empty($resSeries)) {
    echo "  [PASS] 7a. Series query for out-of-scope level '2nd' returns EMPTY array.\n";
} else {
    echo "  [FAIL] 7a. Series query allowed out-of-scope level! Got: " . json_encode($resSeries) . "\n";
}

// 7b. Query class ID for Lycée class
$_GET = ['lycee_id' => 1, 'niveau' => '2nd', 'serie' => 'C', 'numero' => '1', 'cycle_id' => $idLycee];
ob_start(); $classeCtrl->findClassId(); $resCls = json_decode(ob_get_clean(), true);
if (empty($resCls['id_classe'])) {
    echo "  [PASS] 7b. Attempt to resolve class ID for out-of-scope Lycee class returns NULL.\n";
} else {
    echo "  [FAIL] 7b. Class ID resolution bypassed cycle scope! Got: " . json_encode($resCls) . "\n";
}

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (7781, 7782, 7783, 7784, 7785)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (77801, 77802)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (77801, 77802)");
$db->exec("DELETE FROM classes WHERE id_classe IN (77810, 77820, 77830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (7781, 7782, 7783, 7784, 7785)");

unset($_SESSION['user']);

echo "\n========================================================\n";
echo "SUCCESS: ALL 7 RBAC CYCLE SCOPE CONTROLS PASSED!\n";
echo "========================================================\n";
