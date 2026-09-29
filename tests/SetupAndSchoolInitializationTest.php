<?php

require_once __DIR__ . '/../src/config/database.php';

$testDb = new PDO("sqlite:" . __DIR__ . "/../database.sqlite", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
Database::setInstance($testDb);

require_once __DIR__ . '/../migrate.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/services/SchoolInitializationService.php';

echo "Running test suite: SetupAndSchoolInitializationTest...\n";

$db = Database::getInstance();

// Clear test rows
$db->exec("DELETE FROM sequences");
$db->exec("DELETE FROM comptes_financiers");
$db->exec("DELETE FROM exercices_financiers");
$db->exec("DELETE FROM param_type_evaluation");
$db->exec("DELETE FROM utilisateurs WHERE email LIKE '%@lte-test.com' OR email LIKE '%@saintgabriel-test.com' OR email LIKE '%admin.err@test.com'");
$db->exec("DELETE FROM param_general WHERE lycee_id IN (SELECT id FROM param_lycee WHERE nom_lycee LIKE '%Test%')");
$db->exec("DELETE FROM param_lycee WHERE nom_lycee LIKE '%Test%' OR nom_lycee LIKE '%Saint Gabriel%' OR nom_lycee LIKE '%Erreur%'");

// --- TEST CASE 1: Mono School Initialization with 3 Trimestres ---
echo "  Case 1: Mono School Initialization with 3 Trimestres...\n";

$monoData = [
    'nom_lycee' => 'Lycée Test Excellence',
    'type_lycee' => 'prive',
    'sigle' => 'LTE',
    'tel' => '+241 01 02 03 04',
    'email' => 'contact@lte-test.com',
    'ville' => 'Libreville',
    'quartier' => 'Batterie IV',

    'admin_nom' => 'Nguema',
    'admin_prenom' => 'Paul',
    'admin_email' => 'p.nguema@lte-test.com',
    'admin_pass' => 'password123',

    'annee_libelle' => '2025-2026',
    'annee_date_debut' => '2025-09-01',
    'annee_date_fin' => '2026-06-30',

    'sequence_annuelle' => 'Trimestrielle',
    'devise_pays' => 'FCFA',
    'mode_cycle' => 'separe_ceg_lycee',

    'periods' => [
        ['nom' => 'Trimestre 1', 'date_debut' => '2025-09-01', 'date_fin' => '2025-11-30'],
        ['nom' => 'Trimestre 2', 'date_debut' => '2025-12-01', 'date_fin' => '2026-02-28'],
        ['nom' => 'Trimestre 3', 'date_debut' => '2026-03-01', 'date_fin' => '2026-06-30']
    ]
];

$res1 = SchoolInitializationService::initializeSchool($monoData);

assert(!empty($res1['lycee_id']), "Lycee ID should be returned");
assert(!empty($res1['annee_id']), "Annee ID should be returned");
assert(!empty($res1['user_id']), "Admin User ID should be returned");
assert(count($res1['sequence_ids']) === 3, "Exactly 3 sequence IDs should be created for Trimestrielle");

// Verify created database rows
$stmtL = $db->prepare("SELECT * FROM param_lycee WHERE id = :id");
$stmtL->execute(['id' => $res1['lycee_id']]);
$lyceeRow = $stmtL->fetch(PDO::FETCH_ASSOC);
assert($lyceeRow['nom_lycee'] === 'Lycée Test Excellence');
assert($lyceeRow['type_lycee'] === 'prive');

$stmtU = $db->prepare("SELECT * FROM utilisateurs WHERE id_user = :id");
$stmtU->execute(['id' => $res1['user_id']]);
$userRow = $stmtU->fetch(PDO::FETCH_ASSOC);
assert((int)$userRow['role_id'] === 3, "Admin Local should have role_id = 3");
assert((int)$userRow['lycee_id'] === $res1['lycee_id']);

$stmtA = $db->prepare("SELECT * FROM annees_academiques WHERE id = :id");
$stmtA->execute(['id' => $res1['annee_id']]);
$anneeRow = $stmtA->fetch(PDO::FETCH_ASSOC);
assert((int)$anneeRow['est_active'] === 1, "Academic year should be active");

// Verify Evaluation Types
$stmtTE = $db->prepare("SELECT code, nombre_evaluation FROM param_type_evaluation WHERE lycee_id = :l ORDER BY ordre_affichage ASC");
$stmtTE->execute(['l' => $res1['lycee_id']]);
$tEvals = $stmtTE->fetchAll(PDO::FETCH_KEY_PAIR);
assert($tEvals['devoir'] == 2, "Devoir count should be 2");
assert($tEvals['interrogation'] == 3, "Interrogation count should be 3");
assert($tEvals['composition'] == 1, "Composition count should be 1");

// Verify Financials
$stmtEx = $db->prepare("SELECT * FROM exercices_financiers WHERE lycee_id = :l AND est_actif = 1");
$stmtEx->execute(['l' => $res1['lycee_id']]);
$exRow = $stmtEx->fetch(PDO::FETCH_ASSOC);
assert(!empty($exRow), "Active financial exercise should exist");

$stmtC = $db->prepare("SELECT * FROM comptes_financiers WHERE lycee_id = :l");
$stmtC->execute(['l' => $res1['lycee_id']]);
$comptes = $stmtC->fetchAll(PDO::FETCH_ASSOC);
assert(count($comptes) === 1, "Only 1 default account (Caisse Principale) should be created");
assert($comptes[0]['nom_compte'] === 'Caisse Principale');
assert((float)$comptes[0]['solde_courant'] === 0.00);

echo "  -> Case 1 PASSED.\n";

// --- TEST CASE 2: Semestrielle School Initialization with 2 Semestres ---
echo "  Case 2: Semestrielle School Initialization with 2 Semestres...\n";

$semData = [
    'nom_lycee' => 'Collège Saint Gabriel Test',
    'type_lycee' => 'parapublic',
    'sigle' => 'CSG',
    'tel' => '+241 01 99 88 77',
    'email' => 'saintgabriel@test.com',
    'ville' => 'Port-Gentil',

    'admin_nom' => 'Mba',
    'admin_prenom' => 'Jean',
    'admin_email' => 'j.mba@saintgabriel-test.com',
    'admin_pass' => 'password123',

    'annee_libelle' => '2025-2026',
    'annee_date_debut' => '2025-09-01',
    'annee_date_fin' => '2026-06-30',

    'sequence_annuelle' => 'Semestrielle',
    'devise_pays' => 'FCFA',

    'periods' => [
        ['nom' => 'Semestre 1', 'date_debut' => '2025-09-01', 'date_fin' => '2026-01-31'],
        ['nom' => 'Semestre 2', 'date_debut' => '2026-02-01', 'date_fin' => '2026-06-30']
    ]
];

$res2 = SchoolInitializationService::initializeSchool($semData);
assert(count($res2['sequence_ids']) === 2, "Exactly 2 sequence IDs should be created for Semestrielle");

echo "  -> Case 2 PASSED.\n";

// --- TEST CASE 3: Transaction Rollback on Exception ---
echo "  Case 3: Atomic Transaction Rollback on Invalid Input...\n";

$invalidData = [
    'nom_lycee' => 'Lycée Erreur Test',
    'type_lycee' => 'prive',
    'admin_email' => 'admin.err@test.com',
    'admin_pass' => '1234',
    'annee_libelle' => '2025-2026',
    'annee_date_debut' => '2025-09-01',
    'annee_date_fin' => '2026-06-30',
    'sequence_annuelle' => 'Trimestrielle',
    'periods' => [
        // Invalid overlapping periods
        ['nom' => 'T1', 'date_debut' => '2025-09-01', 'date_fin' => '2025-12-31'],
        ['nom' => 'T2', 'date_debut' => '2025-11-01', 'date_fin' => '2026-03-31'], // Overlap
        ['nom' => 'T3', 'date_debut' => '2026-04-01', 'date_fin' => '2026-06-30']
    ]
];

$caught = false;
try {
    SchoolInitializationService::initializeSchool($invalidData);
} catch (Exception $e) {
    $caught = true;
}
assert($caught, "Exception should be thrown on overlapping periods");

// Ensure no orphan lycee was created
$stmtErr = $db->prepare("SELECT COUNT(*) FROM param_lycee WHERE nom_lycee = 'Lycée Erreur Test'");
$stmtErr->execute();
assert((int)$stmtErr->fetchColumn() === 0, "No orphan lycee should remain after rollback");

echo "  -> Case 3 PASSED.\n";

echo "ALL TESTS PASSED SUCCESSFULLY IN SetupAndSchoolInitializationTest.\n";
