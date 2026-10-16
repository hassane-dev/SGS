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
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';
require_once __DIR__ . '/../src/controllers/CarteController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Student RBAC & Classless Student Audit Test\n";
echo "========================================================\n";

// Track permissions created specifically during this test run for clean teardown
$createdPermissionIds = [];

function getPermId($db, $resource, $action, $description = '') {
    global $createdPermissionIds;
    $stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :res AND action = :act");
    $stmt->execute(['res' => $resource, 'act' => $action]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:res, :act, :desc)")
           ->execute(['res' => $resource, 'act' => $action, 'desc' => $description ?: "$action $resource"]);
        $id = $db->lastInsertId();
        $createdPermissionIds[] = (int)$id;
    }
    return (int)$id;
}

// Cleanup any leftover mock test records before run
$db->exec("DELETE FROM role_permissions WHERE role_id IN (9901, 9902, 9903)");
$db->exec("DELETE FROM roles WHERE id_role IN (9901, 9902, 9903)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (9981, 9982, 9983)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99901, 99902, 99903)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99901, 99902, 99903)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99910, 99920)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983)");

// Ensure active academic year exists
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}

// Find cycles
$cycleCeg = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%ceg%' OR LOWER(nom_cycle) LIKE '%collège%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$cycleLycee = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%lycée%' OR LOWER(nom_cycle) LIKE '%lycee%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cycleCeg || !$cycleLycee) {
    echo "ERROR: Missing required cycles in database.\n";
    exit(1);
}

$idCeg = (int)$cycleCeg['id_cycle'];
$idLycee = (int)$cycleLycee['id_cycle'];

$pViewAll = getPermId($db, 'eleve', 'view_all');
$pView = getPermId($db, 'eleve', 'view');
$pEdit = getPermId($db, 'eleve', 'edit');
$pDelete = getPermId($db, 'eleve', 'delete');
$pInscrire = getPermId($db, 'eleve', 'inscrire');
$pPrintCard = getPermId($db, 'eleve', 'print_card');

// Seed custom test roles
// Role 9901: Custom Restricted Role (Only view_all:eleve, NO delete:eleve, NO print_card:eleve)
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (9901, 'role_personnalise_restreint', 1)");
$db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (9901, $pViewAll)");

// Role 9902: Custom Full Role (view_all, delete, print_card, inscrire, edit)
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (9902, 'role_personnalise_complet', 1)");
foreach ([$pViewAll, $pEdit, $pDelete, $pInscrire, $pPrintCard] as $pid) {
    $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (9902, $pid)");
}

// Role 9903: Custom Role with ONLY eleve:view (individual consultation permission)
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (9903, 'role_personnalise_consultation_seule', 1)");
$db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (9903, $pView)");

// Create mock users
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif)
           VALUES (9981, 'Restreint', 'User', 'restreint@test.com', 'hash', 9901, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif)
           VALUES (9982, 'Complet', 'User', 'complet@test.com', 'hash', 9902, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif)
           VALUES (9983, 'Consultant', 'User', 'consultant@test.com', 'hash', 9903, 1, 1)");

// Assign Users to CEG cycle
foreach ([9981, 9982, 9983] as $uid) {
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif)
               VALUES ($uid, $idCeg, '2024-01-01', 1)");
}

// Seed mock card template for lycee_id = 1
$db->exec("INSERT OR IGNORE INTO carte_templates (id, lycee_id, nom_modele) VALUES (999, 1, 'Modèle Test')");

// Create mock classes
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (99910, 1, $idCeg, '6e', 'A', 1)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (99920, 2, $idLycee, '2nd', 'C', 1)"); // Foreign school (lycee_id = 2)

// Create mock students
// Student 99901: In school 1, assigned to CEG class 99910
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, date_naissance, sexe, statut) VALUES (99901, 1, 'Eleve', 'Assigne', '2010-01-01', 'M', 'actif')");
$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (99901, 99910, 1, {$activeYear['id']}, 1, 'active')");

// Student 99902: In school 1, CLASSLESS (no etudes record / classe_id NULL)
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, date_naissance, sexe, statut) VALUES (99902, 1, 'Eleve', 'SansClasse', '2010-05-05', 'F', 'en_attente')");

// Student 99903: Foreign establishment (lycee_id = 2)
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, date_naissance, sexe, statut) VALUES (99903, 2, 'Eleve', 'Etranger', '2008-08-08', 'M', 'actif')");
$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (99903, 99920, 2, {$activeYear['id']}, 1, 'active')");

echo "[OK] Mock test data successfully seeded.\n\n";

$passed = 0;
$failed = 0;

function assertTest($condition, $description) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] " . $description . "\n";
        $passed++;
    } else {
        echo "  [FAIL] " . $description . "\n";
        $failed++;
    }
}

// -------------------------------------------------------------
// TEST 1: Carte Printing Security (`CarteController::generer`)
// -------------------------------------------------------------
echo "TEST 1: Impression de la Carte Scolaire (CarteController::generer)\n";

// 1a. User 9981 (no print_card permission) attempts card generation
Auth::setSessionContext([
    'id' => 9981,
    'role_id' => 9901,
    'lycee_id' => 1,
    'role_name' => 'role_personnalise_restreint'
]);
AuthorizationScopeService::invalidateUserCache(9981);

$_GET = ['eleve_id' => 99901];
ob_start();
$carteCtrl = new CarteController();
$carteCtrl->generer();
$output1 = ob_get_clean();

assertTest(
    strpos($output1, "Accès Interdit") !== false,
    "1a. Utilisateur sans permission 'print_card' REJETÉ côté serveur (Accès Interdit)."
);

// 1b. User 9982 (with print_card permission) generates card for in-scope student
Auth::setSessionContext([
    'id' => 9982,
    'role_id' => 9902,
    'lycee_id' => 1,
    'role_name' => 'role_personnalise_complet'
]);
AuthorizationScopeService::invalidateUserCache(9982);

$_GET = ['eleve_id' => 99901];
ob_start();
$carteCtrl->generer();
$output2 = ob_get_clean();

assertTest(
    strpos($output2, "Accès Interdit") === false,
    "1b. Utilisateur avec permission 'print_card' AUTORISÉ à générer la carte."
);

// -------------------------------------------------------------
// TEST 2: Radiation Security (`EleveController::destroy`)
// -------------------------------------------------------------
echo "\nTEST 2: Radiation d'un Élève (EleveController::destroy)\n";

// 2a. User 9981 (without delete permission) attempts radiation
Auth::setSessionContext([
    'id' => 9981,
    'role_id' => 9901,
    'lycee_id' => 1
]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['id' => 99901];
$eleveCtrl = new EleveController();

ob_start();
try {
    $eleveCtrl->destroy();
} catch (Exception $e) {
}
ob_end_clean();
$status2a = $db->query("SELECT statut FROM eleves WHERE id_eleve = 99901")->fetchColumn();

assertTest(
    $status2a === 'actif',
    "2a. Radiation sans permission 'delete:eleve' REJETÉE (statut demeure 'actif')."
);

// 2b. User 9982 (with delete permission) radiés student 99901
Auth::setSessionContext([
    'id' => 9982,
    'role_id' => 9902,
    'lycee_id' => 1
]);

$_POST = ['id' => 99901];
ob_start();
$eleveCtrl->destroy();
ob_end_clean();
$status2b = $db->query("SELECT statut FROM eleves WHERE id_eleve = 99901")->fetchColumn();

assertTest(
    $status2b === 'radié',
    "2b. Radiation par un utilisateur autorisé réussit (statut mis à jour à 'radié')."
);

// -------------------------------------------------------------
// TEST 3: Isolation Cross-Tenant & Cross-Scope (IDOR)
// -------------------------------------------------------------
echo "\nTEST 3: Isolation Cross-Tenant & IDOR (Élève d'un autre lycée)\n";

// Attempt to details/edit/destroy student 99903 (lycee_id = 2) while logged into lycee_id = 1
Auth::setSessionContext([
    'id' => 9982,
    'role_id' => 9902,
    'lycee_id' => 1
]);

// 3a. Details on foreign student
$_GET = ['id' => 99903];
$idorBlockedDetails = false;
ob_start();
try {
    $eleveCtrl->details();
} catch (Exception $e) {
    $idorBlockedDetails = true;
}
ob_end_clean();
assertTest($idorBlockedDetails, "3a. Consultation dossier (details) d'un élève hors lycée REJETÉE (403/Scope Exception).");

// 3b. Destruction on foreign student
$_POST = ['id' => 99903];
$idorBlockedDestroy = false;
ob_start();
try {
    $eleveCtrl->destroy();
} catch (Exception $e) {
    $idorBlockedDestroy = true;
}
ob_end_clean();
$status3b = $db->query("SELECT statut FROM eleves WHERE id_eleve = 99903")->fetchColumn();

assertTest(
    $idorBlockedDestroy && $status3b === 'actif',
    "3b. Radiation (destroy) d'un élève d'un autre établissement REJETÉE."
);

// -------------------------------------------------------------
// TEST 4: Élèves Inscrits Sans Classe (Visibilité et Affectation)
// -------------------------------------------------------------
echo "\nTEST 4: Élèves Inscrits Sans Classe\n";

// 4a. Verify student 99902 (classless) is fetched by AcademicFilterService query
$_GET = [];
$baseSql = "
    SELECT DISTINCT e.*, c.id_classe, c.niveau, c.serie, c.numero, c.cycle_id, cy.nom_cycle
    FROM eleves e
    LEFT JOIN etudes et ON e.id_eleve = et.eleve_id AND (et.is_active = 1 OR et.status = 'en_attente_paiement')
    LEFT JOIN classes c ON et.classe_id = c.id_classe
    LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
";
require_once __DIR__ . '/../src/services/AcademicFilterService.php';
$applied = AcademicFilterService::applyToQuery($baseSql, [], $_GET, 'c');
$stmt = $db->prepare($applied['sql'] . " AND e.id_eleve = 99902");
$stmt->execute($applied['params']);
$classlessStudent = $stmt->fetch(PDO::FETCH_ASSOC);

assertTest(
    $classlessStudent && empty($classlessStudent['niveau']),
    "4a. Élève inscrit sans classe (99902) est visible dans la liste avec niveau NULL."
);

// 4b. Assign class to classless student with authorized user 9982
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'eleve_id' => 99902,
    'cycle_id' => $idCeg,
    'niveau' => '6e',
    'serie' => 'A',
    'numero' => 1
];
ob_start();
$eleveCtrl->processAssignment();
ob_end_clean();

$etudeRecord = $db->query("SELECT * FROM etudes WHERE eleve_id = 99902")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $etudeRecord && (int)$etudeRecord['classe_id'] === 99910,
    "4b. Affectation d'une classe à l'élève sans classe réussie (classe_id = 99910)."
);

// -------------------------------------------------------------
// TEST 5: Harmonisation Consultation Dossier (`EleveController::details`)
// -------------------------------------------------------------
echo "\nTEST 5: Harmonisation Permission Consultation Dossier Élève (EleveController::details)\n";

// 5a. User 9983 (role 9903, possessing ONLY 'eleve:view', NOT 'eleve:view_all')
Auth::setSessionContext([
    'id' => 9983,
    'role_id' => 9903,
    'lycee_id' => 1,
    'role_name' => 'role_personnalise_consultation_seule'
]);
AuthorizationScopeService::invalidateUserCache(9983);

$_GET = ['id' => 99901];
$detailsAccessedWithView = false;
ob_start();
try {
    $eleveCtrl->details();
    $detailsAccessedWithView = true;
} catch (Exception $e) {
    $detailsAccessedWithView = false;
}
ob_end_clean();

assertTest(
    $detailsAccessedWithView,
    "5a. Consultation dossier (details) autorisée pour un utilisateur disposant de la permission 'eleve:view'."
);

// -------------------------------------------------------------
// SUMMARY & CLEANUP
// -------------------------------------------------------------
echo "\n========================================================\n";
echo "SUMMARY: Passed $passed / " . ($passed + $failed) . " tests.\n";
echo "========================================================\n";

// Teardown mock test records
$db->exec("DELETE FROM role_permissions WHERE role_id IN (9901, 9902, 9903)");
$db->exec("DELETE FROM roles WHERE id_role IN (9901, 9902, 9903)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (9981, 9982, 9983)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99901, 99902, 99903)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99901, 99902, 99903)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99910, 99920)");
$db->exec("DELETE FROM carte_templates WHERE id = 999");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983)");

// Remove permissions created dynamically by this test suite
if (!empty($createdPermissionIds)) {
    $placeholders = implode(',', array_map('intval', $createdPermissionIds));
    $db->exec("DELETE FROM role_permissions WHERE permission_id IN ($placeholders)");
    $db->exec("DELETE FROM permissions WHERE id_permission IN ($placeholders)");
}

if ($failed > 0) {
    exit(1);
}
