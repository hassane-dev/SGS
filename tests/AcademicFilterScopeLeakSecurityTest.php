<?php

/**
 * Test Suite: Academic Filter Scope Leak Security Test (FILTER-SCOPE-FIX-1)
 *
 * Covers the 10 mandatory test scenarios:
 * 1. User assigned to 2 classes of 2 different levels in the same cycle: only these 2 levels appear.
 * 2. User assigned to a single class: only 1 level appears.
 * 3. User assigned to a class of a level using series: only series matching authorized classes appear.
 * 4. User assigned to a class of a cycle without series: series filter is not proposed / returns empty.
 * 5. User with cycle access but NO class assignment: does not automatically get all levels and classes (returns empty).
 * 6. User with view_all_cycles: global cycle access preserved.
 * 7. User with view_all_lycees: global lycee access preserved.
 * 8. User assigned to multiple cycles: each cycle strictly presents levels/classes matching its scope.
 * 9. Successive cascade changes (cycle/niveau/serie): no lingering out-of-scope selections.
 * 10. Direct AJAX endpoint tampering with unauthorized level/series/class parameters: strictly rejected.
 * 11. Cache invalidation on assignment change and class:create permission scope guard.
 */

define('TEST_MODE', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Serie.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/controllers/AffectationPedagogiqueController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Academic Filter Scope Leak Security Test\n";
echo "========================================================\n\n";

// Setup Test Data
$db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM personnel_assignments WHERE personnel_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM surveillant_affectations WHERE surveillant_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");

$db->exec("DELETE FROM classes WHERE id_classe IN (77101, 77102, 77103, 77104, 77105, 77106, 77107, 77201)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (7710, 7720, 7730)");
$db->exec("DELETE FROM param_lycee WHERE id IN (771, 772)");

// Ensure permissions and roles for testing
$db->exec("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES ('cycle', 'view_all_cycles', 'Voir tous les cycles')");
$pCycleAll = (int)$db->query("SELECT id_permission FROM permissions WHERE resource = 'cycle' AND action = 'view_all_cycles'")->fetchColumn();

$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id) VALUES (97, 'cycle_all_role', 771)");
$db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (97, $pCycleAll)");

$db->exec("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES ('class', 'create', 'Créer une classe')");
$pClassCreate = (int)$db->query("SELECT id_permission FROM permissions WHERE resource = 'class' AND action = 'create'")->fetchColumn();

$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id) VALUES (98, 'createur_classes', 771)");
$db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (98, $pClassCreate)");

// 1. Establishments
$db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee, type_lycee) VALUES (771, 'Lycée Pilote Scope 1', 'prive')");
$db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee, type_lycee) VALUES (772, 'Lycée Pilote Scope 2', 'prive')");

// 2. Active Academic Year
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}

// 3. Cycles:
// Cycle 7710: Collège (utilise_series = 0, bounds: 6e -> 3e)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (7710, 'Collège Scope', 771, 0, '6e', '3e')");
// Cycle 7720: Lycée (utilise_series = 1, bounds: 2nde -> Terminale)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (7720, 'Lycée Scope', 771, 1, '2nde', 'Terminale')");
// Cycle 7730: Formation Pro (utilise_series = NULL, bounds: 1ère -> Terminale)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (7730, 'Formation Pro', 771, NULL, '1ère', 'Terminale')");

// 4. Classes in Cycle 7720 (Lycée Scope, Lycee 771):
// 77101: 2nde A 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77101, 771, 7720, '2nde', 'A', 1)");
// 77102: 2nde C 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77102, 771, 7720, '2nde', 'C', 1)");
// 77103: 1ère C 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77103, 771, 7720, '1ère', 'C', 1)");
// 77104: 1ère D 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77104, 771, 7720, '1ère', 'D', 1)");
// 77105: Terminale C 1 (Unassigned class to test exclusion)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77105, 771, 7720, 'Terminale', 'C', 1)");

// Classes in Cycle 7710 (Collège Scope, Lycee 771, without series):
// 77106: 6e 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77106, 771, 7710, '6e', NULL, 1)");
// 77107: 5e 1
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77107, 771, 7710, '5e', NULL, 1)");

// Class in School 772:
// 77201: 2nde A 1 in school 772
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (77201, 772, 7720, '2nde', 'A', 1)");

// 5. Users
// User 7701: Teacher assigned to 2nde A (77101) and 1ère C (77103) in Lycée (7720)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, role_id, lycee_id, actif, auth_version) VALUES (7701, 'Prof', 'DeuxClasses', 6, 771, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7701, 7720, '2020-01-01', 1)");
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, date_debut, statut) VALUES (7701, 77101, 1, '2020-01-01', 'actif')");
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, date_debut, statut) VALUES (7701, 77103, 1, '2020-01-01', 'actif')");

// User 7702: Assigned to a SINGLE class: 6e 1 (77106) in Collège (7710)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, role_id, lycee_id, actif, auth_version) VALUES (7702, 'Prof', 'UneClasse', 6, 771, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7702, 7710, '2020-01-01', 1)");
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, date_debut, statut) VALUES (7702, 77106, 1, '2020-01-01', 'actif')");

// User 7703: Assigned to Cycle Lycée (7720) but ZERO classes assigned
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, role_id, lycee_id, actif, auth_version) VALUES (7703, 'User', 'CycleSansClasse', 6, 771, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7703, 7720, '2020-01-01', 1)");

// User 7704: User with view_all_cycles permission (global cycle access)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, role_id, lycee_id, actif, auth_version) VALUES (7704, 'Admin', 'ViewAllCycles', 97, 771, 1, 1)");

// User 7705: User with view_all_lycees permission (global lycee access)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, role_id, lycee_id, actif, auth_version) VALUES (7705, 'SuperAdmin', 'ViewAllLycees', 2, 771, 1, 1)");

// User 7706: Multi-cycle assigned: 6e 1 in Collège (7710) + 2nde A in Lycée (7720)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, role_id, lycee_id, actif, auth_version) VALUES (7706, 'Prof', 'MultiCycle', 6, 771, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7706, 7710, '2020-01-01', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7706, 7720, '2020-01-01', 1)");
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, date_debut, statut) VALUES (7706, 77106, 1, '2020-01-01', 'actif')");
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, date_debut, statut) VALUES (7706, 77101, 1, '2020-01-01', 'actif')");

// User 7707: User with class:create but restricted to Seconde A only for viewing
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, role_id, lycee_id, actif, auth_version) VALUES (7707, 'User', 'CreatorNoViewAll', 98, 771, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7707, 7720, '2020-01-01', 1)");
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, date_debut, statut) VALUES (7707, 77101, 1, '2020-01-01', 'actif')");

$classeCtrl = new ClasseController();
$affCtrl = new AffectationPedagogiqueController();

function setSessionUser($userId, $lyceeId, $roleId = null) {
    unset($_SESSION['user']['authorized_cycles']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => $roleId,
        'auth_version' => 1
    ];
}


// =========================================================================
// SCENARIO 1: Utilisateur affecté à deux classes de deux niveaux différents
// =========================================================================
echo "--- SCENARIO 1: Affecté à Seconde A et Première C (Cycle Lycée) ---\n";
setSessionUser(7701, 771, 6);

$_GET = ['cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveaux1 = json_decode(ob_get_clean(), true);
sort($niveaux1);

if ($niveaux1 === ['1ère', '2nde']) {
    echo "  [PASS] 1a. Only assigned levels ['1ère', '2nde'] appear. Terminale is strictly EXCLUDED.\n";
} else {
    echo "  [FAIL] 1a. Expected ['1ère', '2nde'], got: " . json_encode($niveaux1) . "\n";
    exit(1);
}

ob_start(); $affCtrl->getNiveaux(); $niveauxAff1 = json_decode(ob_get_clean(), true);
sort($niveauxAff1);
if ($niveauxAff1 === ['1ère', '2nde']) {
    echo "  [PASS] 1b. AffectationPedagogiqueController::getNiveaux strictly returns ['1ère', '2nde'].\n";
} else {
    echo "  [FAIL] 1b. Expected ['1ère', '2nde'], got: " . json_encode($niveauxAff1) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 2: Utilisateur affecté à une seule classe
// =========================================================================
echo "\n--- SCENARIO 2: Utilisateur affecté à une seule classe (6e 1) ---\n";
setSessionUser(7702, 771, 6);

$_GET = ['cycle_id' => 7710, 'lycee_id' => 771];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveaux2 = json_decode(ob_get_clean(), true);

if ($niveaux2 === ['6e']) {
    echo "  [PASS] 2a. Exactly single level ['6e'] appears. 5e, 4e, 3e are strictly excluded.\n";
} else {
    echo "  [FAIL] 2a. Expected ['6e'], got: " . json_encode($niveaux2) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 3: Utilisateur affecté à une classe d'un niveau utilisant les séries
// =========================================================================
echo "\n--- SCENARIO 3: Filtrage des Séries selon les classes autorisées ---\n";
// User 7701 is assigned to 2nde A (77101) and 1ère C (77103).
// In 2nde, classes exist for A and C, but user 7701 is ONLY assigned to A.
setSessionUser(7701, 771, 6);

$_GET = ['niveau' => '2nde', 'cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getSeriesForNiveau(); $series2nde = json_decode(ob_get_clean(), true);

if ($series2nde === ['A']) {
    echo "  [PASS] 3a. ClasseController::getSeriesForNiveau returns strictly ['A'] (Series C is excluded).\n";
} else {
    echo "  [FAIL] 3a. Expected ['A'], got: " . json_encode($series2nde) . "\n";
    exit(1);
}

ob_start(); $affCtrl->getSeries(); $seriesAff2nde = json_decode(ob_get_clean(), true);
if ($seriesAff2nde === ['A']) {
    echo "  [PASS] 3b. AffectationPedagogiqueController::getSeries returns strictly ['A'].\n";
} else {
    echo "  [FAIL] 3b. Expected ['A'], got: " . json_encode($seriesAff2nde) . "\n";
    exit(1);
}

// In 1ère, classes exist for C and D, but user 7701 is ONLY assigned to C.
$_GET = ['niveau' => '1ère', 'cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getSeriesForNiveau(); $series1ere = json_decode(ob_get_clean(), true);
if ($series1ere === ['C']) {
    echo "  [PASS] 3c. In 1ère, series returns strictly ['C'] (Series D is excluded).\n";
} else {
    echo "  [FAIL] 3c. Expected ['C'], got: " . json_encode($series1ere) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 4: Cycle sans séries (utilise_series = 0 ou NULL)
// =========================================================================
echo "\n--- SCENARIO 4: Cycle sans séries (utilise_series = 0 ou NULL) ---\n";
setSessionUser(7702, 771, 6);

// Cycle 7710 has utilise_series = 0
$_GET = ['niveau' => '6e', 'cycle_id' => 7710, 'lycee_id' => 771];
ob_start(); $classeCtrl->getSeriesForNiveau(); $seriesCeg = json_decode(ob_get_clean(), true);

if ($seriesCeg === []) {
    echo "  [PASS] 4a. Series filter returns empty array [] for cycle with utilise_series = 0.\n";
} else {
    echo "  [FAIL] 4a. Expected [], got: " . json_encode($seriesCeg) . "\n";
    exit(1);
}

ob_start(); $affCtrl->getSeries(); $seriesAffCeg = json_decode(ob_get_clean(), true);
if ($seriesAffCeg === []) {
    echo "  [PASS] 4b. AffectationPedagogiqueController::getSeries returns [] for utilise_series = 0.\n";
} else {
    echo "  [FAIL] 4b. Expected [], got: " . json_encode($seriesAffCeg) . "\n";
    exit(1);
}

// Cycle 7730 has utilise_series = NULL
$_GET = ['niveau' => '1ère', 'cycle_id' => 7730, 'lycee_id' => 771];
ob_start(); $classeCtrl->getSeriesForNiveau(); $seriesNull = json_decode(ob_get_clean(), true);
if ($seriesNull === []) {
    echo "  [PASS] 4c. Series filter returns empty array [] for cycle with utilise_series = NULL.\n";
} else {
    echo "  [FAIL] 4c. Expected [], got: " . json_encode($seriesNull) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 5: Utilisateur disposant d'un accès au cycle mais SANS affectation de classe
// =========================================================================
echo "\n--- SCENARIO 5: Cycle sans affectation de classe (Pas d'attribution automatique) ---\n";
setSessionUser(7703, 771, 6);

$authClasses7703 = AuthorizationScopeService::getAuthorizedClassIds(771);
if ($authClasses7703 === []) {
    echo "  [PASS] 5a. getAuthorizedClassIds() returns strictly empty array [] for cycle-only user.\n";
} else {
    echo "  [FAIL] 5a. Expected [], got: " . json_encode($authClasses7703) . "\n";
    exit(1);
}

$_GET = ['cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveaux5 = json_decode(ob_get_clean(), true);
if ($niveaux5 === []) {
    echo "  [PASS] 5b. getNiveauxForCycle returns empty array [] for cycle-only user.\n";
} else {
    echo "  [FAIL] 5b. Expected [], got: " . json_encode($niveaux5) . "\n";
    exit(1);
}

ob_start(); $affCtrl->getNiveaux(); $niveauxAff5 = json_decode(ob_get_clean(), true);
if ($niveauxAff5 === []) {
    echo "  [PASS] 5c. AffectationPedagogiqueController::getNiveaux returns [] for cycle-only user.\n";
} else {
    echo "  [FAIL] 5c. Expected [], got: " . json_encode($niveauxAff5) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 6: Utilisateur disposant de view_all_cycles
// =========================================================================
echo "\n--- SCENARIO 6: Permission globale view_all_cycles ---\n";
setSessionUser(7704, 771, 97);

if (AuthorizationScopeService::canAccessCycle(7710) && AuthorizationScopeService::canAccessCycle(7720)) {
    echo "  [PASS] 6a. User with view_all_cycles can access all cycles of the establishment.\n";
} else {
    echo "  [FAIL] 6a. Failed cycle access for view_all_cycles user.\n";
    exit(1);
}

$permittedCycles6 = AuthorizationScopeService::getPermittedCycles(771);
$permittedIds6 = array_column($permittedCycles6, 'id_cycle');
if (in_array(7710, $permittedIds6) && in_array(7720, $permittedIds6) && in_array(7730, $permittedIds6)) {
    echo "  [PASS] 6b. getPermittedCycles returns all establishment cycles for view_all_cycles user.\n";
} else {
    echo "  [FAIL] 6b. Expected all cycles [7710, 7720, 7730], got: " . json_encode($permittedIds6) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 7: Utilisateur disposant de view_all_lycees
// =========================================================================
echo "\n--- SCENARIO 7: Permission globale view_all_lycees ---\n";
setSessionUser(7705, 771, 2);

if (AuthorizationScopeService::canAccessLycee(771) && AuthorizationScopeService::canAccessLycee(772)) {
    echo "  [PASS] 7a. User with view_all_lycees can access both Lycee 771 and Lycee 772.\n";
} else {
    echo "  [FAIL] 7a. Failed multi-lycee access for view_all_lycees user.\n";
    exit(1);
}

$authClasses7705 = AuthorizationScopeService::getAuthorizedClassIds(771);
if ($authClasses7705 === null) {
    echo "  [PASS] 7b. User with view_all_lycees gets unrestricted getAuthorizedClassIds() === null.\n";
} else {
    echo "  [FAIL] 7b. Expected null, got: " . json_encode($authClasses7705) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 8: Utilisateur affecté à plusieurs cycles
// =========================================================================
echo "\n--- SCENARIO 8: Utilisateur multi-cycles (Collège 6e 1 + Lycée 2nde A) ---\n";
setSessionUser(7706, 771, 6);

// When querying Collège (7710): only 6e must appear
$_GET = ['cycle_id' => 7710, 'lycee_id' => 771];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveauxMultiCeg = json_decode(ob_get_clean(), true);
if ($niveauxMultiCeg === ['6e']) {
    echo "  [PASS] 8a. Under Collège cycle, strictly ['6e'] appears.\n";
} else {
    echo "  [FAIL] 8a. Expected ['6e'], got: " . json_encode($niveauxMultiCeg) . "\n";
    exit(1);
}

// When querying Lycée (7720): only 2nde must appear
$_GET = ['cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveauxMultiLycee = json_decode(ob_get_clean(), true);
if ($niveauxMultiLycee === ['2nde']) {
    echo "  [PASS] 8b. Under Lycée cycle, strictly ['2nde'] appears (no cross-cycle contamination).\n";
} else {
    echo "  [FAIL] 8b. Expected ['2nde'], got: " . json_encode($niveauxMultiLycee) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 9: Changements successifs de cycle, niveau et série (Cascade Reset)
// =========================================================================
echo "\n--- SCENARIO 9: Cohérence des changements de cycle, niveau et série ---\n";
setSessionUser(7701, 771, 6);

// Step 9a: Query in-scope 2nde series -> ['A']
$_GET = ['niveau' => '2nde', 'cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getSeriesForNiveau(); $sA = json_decode(ob_get_clean(), true);

// Step 9b: Switch cycle to Collège 7710, where 2nde does not exist -> must return []
$_GET = ['niveau' => '2nde', 'cycle_id' => 7710, 'lycee_id' => 771];
ob_start(); $classeCtrl->getSeriesForNiveau(); $sInvalidCycle = json_decode(ob_get_clean(), true);
if ($sInvalidCycle === []) {
    echo "  [PASS] 9a. Switching to cycle without this level strictly returns empty series [].\n";
} else {
    echo "  [FAIL] 9a. Expected [], got: " . json_encode($sInvalidCycle) . "\n";
    exit(1);
}

// Step 9c: Querying class numbers for previous serie 'A' on newly selected 1ère (where only 'C' is authorized) -> []
$_GET = ['niveau' => '1ère', 'serie' => 'A', 'cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getNumerosForClasse(); $numTamper = json_decode(ob_get_clean(), true);
if ($numTamper === []) {
    echo "  [PASS] 9b. Requesting numbers for non-authorized combination (1ère + Serie A) returns [].\n";
} else {
    echo "  [FAIL] 9b. Expected [], got: " . json_encode($numTamper) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 10: Appels directs aux endpoints avec paramètres non autorisés (Tampering)
// =========================================================================
echo "\n--- SCENARIO 10: Protection contre le frelatage de requêtes directes (Tampering) ---\n";
setSessionUser(7701, 771, 6);

// 10a. Requesting series for unauthorized Terminale
$_GET = ['niveau' => 'Terminale', 'cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getSeriesForNiveau(); $tamperSeries = json_decode(ob_get_clean(), true);
if ($tamperSeries === []) {
    echo "  [PASS] 10a. Tampering with unauthorized level (Terminale) returns empty series [].\n";
} else {
    echo "  [FAIL] 10a. Expected [], got: " . json_encode($tamperSeries) . "\n";
    exit(1);
}

// 10b. Requesting class numbers for unauthorized Terminale C
$_GET = ['niveau' => 'Terminale', 'serie' => 'C', 'cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->getNumerosForClasse(); $tamperNum = json_decode(ob_get_clean(), true);
if ($tamperNum === []) {
    echo "  [PASS] 10b. Tampering with unauthorized class numbers (Terminale C) returns [].\n";
} else {
    echo "  [FAIL] 10b. Expected [], got: " . json_encode($tamperNum) . "\n";
    exit(1);
}

// 10c. Direct findClassId for unauthorized class 77105 (Terminale C 1)
$_GET = ['niveau' => 'Terminale', 'serie' => 'C', 'numero' => 1, 'cycle_id' => 7720, 'lycee_id' => 771];
ob_start(); $classeCtrl->findClassId(); $tamperId = json_decode(ob_get_clean(), true);
if (is_array($tamperId) && array_key_exists('id_classe', $tamperId) && $tamperId['id_classe'] === null) {
    echo "  [PASS] 10c. Direct findClassId for unauthorized class strictly returns id_classe: null.\n";
} else {
    echo "  [FAIL] 10c. Expected null, got: " . json_encode($tamperId) . "\n";
    exit(1);
}

// 10d. AffectationPedagogiqueController direct getClasseId for unauthorized Terminale C 1
ob_start(); $affCtrl->getClasseId(); $tamperAffId = json_decode(ob_get_clean(), true);
if (is_array($tamperAffId) && array_key_exists('id_classe', $tamperAffId) && $tamperAffId['id_classe'] === null) {
    echo "  [PASS] 10d. AffectationPedagogiqueController::getClasseId returns id_classe: null.\n";
} else {
    echo "  [FAIL] 10d. Expected null, got: " . json_encode($tamperAffId) . "\n";
    exit(1);
}


// =========================================================================
// SCENARIO 11: Scope de class:create et Invalidation de Cache
// =========================================================================
echo "\n--- SCENARIO 11: Permissions class:create et Invalidation de Cache ---\n";
// User 7707 has class:create (role 98), but is only assigned to 2nde A.
// In consultation mode (context=filter), class:create must NOT bypass class restrictions!
setSessionUser(7707, 771, 98);

$_GET = ['cycle_id' => 7720, 'lycee_id' => 771, 'context' => 'filter'];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveauxFilter = json_decode(ob_get_clean(), true);
if ($niveauxFilter === ['2nde']) {
    echo "  [PASS] 11a. User with class:create in consultation/filter mode strictly sees only assigned level ['2nde'].\n";
} else {
    echo "  [FAIL] 11a. Expected ['2nde'], got: " . json_encode($niveauxFilter) . "\n";
    exit(1);
}

// In creation mode (context=creation), user with class:create is allowed theoretical bounds
$_GET = ['cycle_id' => 7720, 'lycee_id' => 771, 'context' => 'creation'];
ob_start(); $classeCtrl->getNiveauxForCycle(); $niveauxCreation = json_decode(ob_get_clean(), true);
if ($niveauxCreation === ['2nde', '1ère', 'Terminale']) {
    echo "  [PASS] 11b. User with class:create in creation mode receives full theoretical level range.\n";
} else {
    echo "  [FAIL] 11b. Expected ['2nde', '1ère', 'Terminale'], got: " . json_encode($niveauxCreation) . "\n";
    exit(1);
}

// Invalidation of cache when assignment changes
AuthorizationScopeService::invalidateUserCache(7707);
$newVersion = (int)$db->query("SELECT auth_version FROM utilisateurs WHERE id_user = 7707")->fetchColumn();
if ($newVersion === 2) {
    echo "  [PASS] 11c. invalidateUserCache increments auth_version in DB to invalidate cached scopes.\n";
} else {
    echo "  [FAIL] 11c. Expected auth_version 2, got: " . $newVersion . "\n";
    exit(1);
}

// Cleanup Test Data
$db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM personnel_assignments WHERE personnel_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM surveillant_affectations WHERE surveillant_id IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (7701, 7702, 7703, 7704, 7705, 7706, 7707, 7708)");
$db->exec("DELETE FROM classes WHERE id_classe IN (77101, 77102, 77103, 77104, 77105, 77106, 77107, 77201)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (7710, 7720, 7730)");
$db->exec("DELETE FROM param_lycee WHERE id IN (771, 772)");
$db->exec("DELETE FROM role_permissions WHERE role_id IN (97, 98)");
$db->exec("DELETE FROM roles WHERE id_role IN (97, 98)");

echo "\n========================================================\n";
echo "SUCCESS: ALL 11 ACADEMIC FILTER SCOPE SECURITY TESTS PASSED!\n";
echo "========================================================\n";
