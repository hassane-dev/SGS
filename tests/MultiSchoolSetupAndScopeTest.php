<?php

require_once __DIR__ . '/../src/config/database.php';

$testDb = new PDO("sqlite:" . __DIR__ . "/../database.sqlite", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
Database::setInstance($testDb);

// Execute migrate.php cleanly without outputting migration messages
ob_start();
require_once __DIR__ . '/../migrate.php';
ob_end_clean();

require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/services/SchoolInitializationService.php';
require_once __DIR__ . '/../src/controllers/SetupController.php';

echo "Running MultiSchoolSetupAndScopeTest...\n";

$db = Database::getInstance();

// Cleanup test records
$db->exec("DELETE FROM utilisateurs WHERE email IN ('superadmin.multi@test.com', 'admin.local@test.com')");
$db->exec("DELETE FROM param_lycee WHERE nom_lycee = 'Lycée Test Multi'");

// --- 1. Test Setup Multi Creation with 0 Lycées ---
echo "Case 1: Creating Global Super Admin with lycee_id = NULL and 0 lycées...\n";

$postData = [
    'install_mode' => 'multi',
    'nom' => 'National',
    'prenom' => 'SuperAdmin',
    'email' => 'superadmin.multi@test.com',
    'mot_de_passe' => 'Password123!'
];

// Invoke private method setupMultiSchool via Reflection
$ref = new ReflectionClass('SetupController');
$method = $ref->getMethod('setupMultiSchool');
$method->setAccessible(true);

$setupController = new SetupController();
$method->invoke($setupController, $postData);

// Verify user was created in DB
$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email");
$stmt->execute(['email' => 'superadmin.multi@test.com']);
$superAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$superAdmin) {
    echo "ERROR: Super Admin was not created.\n";
    exit(1);
}

if ($superAdmin['lycee_id'] !== null) {
    echo "ERROR: Expected utilisateurs.lycee_id IS NULL, got " . var_export($superAdmin['lycee_id'], true) . "\n";
    exit(1);
}

// Verify role is super_admin_national (role_id 2) with global role scope (roles.lycee_id IS NULL)
$stmtRole = $db->prepare("SELECT * FROM roles WHERE id_role = :id");
$stmtRole->execute(['id' => $superAdmin['role_id']]);
$role = $stmtRole->fetch(PDO::FETCH_ASSOC);

if (!$role || $role['nom_role'] !== 'super_admin_national') {
    echo "ERROR: Role should be super_admin_national.\n";
    exit(1);
}

if ($role['lycee_id'] !== null) {
    echo "ERROR: Role super_admin_national should be global (lycee_id IS NULL).\n";
    exit(1);
}

echo "-> Case 1 PASSED: Super Admin global created with lycee_id = NULL.\n";

// --- 2. Test School Creation and Local Admin Creation ---
echo "Case 2: Creating a school via SchoolInitializationService and verifying distinct scopes...\n";

$initData = [
    'nom_lycee' => 'Lycée Test Multi',
    'type_lycee' => 'prive',
    'sigle' => 'LTM',
    'tel' => '0102030405',
    'email' => 'contact@ltm.test',
    'ville' => 'Libreville',
    'quartier' => 'Centre',

    'admin_nom' => 'LocalAdmin',
    'admin_prenom' => 'Jean',
    'admin_email' => 'admin.local@test.com',
    'admin_pass' => 'Password123!',

    'annee_libelle' => '2025-2026',
    'annee_date_debut' => '2025-09-01',
    'annee_date_fin' => '2026-06-30',

    'sequence_annuelle' => 'Trimestrielle',
    'devise_pays' => 'FCFA',
    'monnaie' => 'FCFA',
    'mode_cycle' => 'separe_ceg_lycee',
    'nb_langue' => 1,
    'langue_1' => 'Francais',

    'periods' => [
        ['nom' => 'Trimestre 1', 'date_debut' => '2025-09-01', 'date_fin' => '2025-12-15'],
        ['nom' => 'Trimestre 2', 'date_debut' => '2025-12-16', 'date_fin' => '2026-03-31'],
        ['nom' => 'Trimestre 3', 'date_debut' => '2026-04-01', 'date_fin' => '2026-06-30']
    ]
];

$res = SchoolInitializationService::initializeSchool($initData);
$newLyceeId = $res['lycee_id'];

// Verify local admin
$stmtLocal = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email");
$stmtLocal->execute(['email' => 'admin.local@test.com']);
$localAdmin = $stmtLocal->fetch(PDO::FETCH_ASSOC);

if (!$localAdmin) {
    echo "ERROR: Local admin was not created.\n";
    exit(1);
}

if ((int)$localAdmin['lycee_id'] !== (int)$newLyceeId) {
    echo "ERROR: Local admin lycee_id " . var_export($localAdmin['lycee_id'], true) . " does not match new lycee ID {$newLyceeId}\n";
    exit(1);
}

// Re-verify Super Admin remains NULL
$stmtSuperCheck = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email");
$stmtSuperCheck->execute(['email' => 'superadmin.multi@test.com']);
$superAdminRecheck = $stmtSuperCheck->fetch(PDO::FETCH_ASSOC);

if ($superAdminRecheck['lycee_id'] !== null) {
    echo "ERROR: Super admin lycee_id was altered and is no longer NULL!\n";
    exit(1);
}

echo "-> Case 2 PASSED: Local admin has lycee_id={$newLyceeId} while Super Admin retains lycee_id = NULL.\n";

// Cleanup
$db->exec("DELETE FROM utilisateurs WHERE email IN ('superadmin.multi@test.com', 'admin.local@test.com')");
$db->exec("DELETE FROM param_lycee WHERE id = " . (int)$newLyceeId);

echo "ALL TESTS PASSED IN MultiSchoolSetupAndScopeTest.\n";
