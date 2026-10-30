<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/models/PaieRegularisation.php';
require_once __DIR__ . '/../src/services/PaieDashboardService.php';
require_once __DIR__ . '/../src/controllers/PaieDashboardController.php';
require_once __DIR__ . '/../src/controllers/PaiePeriodesController.php';
require_once __DIR__ . '/../src/controllers/PaieBulletinsController.php';
require_once __DIR__ . '/../src/controllers/PaieCahierTexteController.php';
require_once __DIR__ . '/../src/controllers/PaieRegularisationsController.php';
require_once __DIR__ . '/../src/controllers/PaieLegacyController.php';
require_once __DIR__ . '/../db/migrations/20240115_35_fix_paie_periodes_schema.php';

function assert_p35($condition, $message) {
    if ($condition) {
        echo "  [PASS] $message\n";
    } else {
        echo "  [FAIL] $message\n";
        exit(1);
    }
}

echo "=== DÉBUT DU TEST DE LA MIGRATION 35 ET DES 9 ROUTES PAIE ===\n\n";

$db = Database::getInstance();

// Seed super_admin role and user 1 to pass Auth::synchronizeSessionIfNeeded
$db->exec("
    INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id)
    VALUES (1, 'super_admin_createur', NULL);

    INSERT OR IGNORE INTO utilisateurs (id_user, nom, prenom, email, identifiant_public, lycee_id, role_id, actif, auth_version)
    VALUES (1, 'Admin', 'Super', 'admin@school.com', 'ADM-001', 1, 1, 1, 1);

    UPDATE utilisateurs SET auth_version = 1, actif = 1 WHERE id_user = 1;
");

// Fetch permissions for role 1
$perms = Role::getPermissions(1);

$_SESSION['user'] = [
    'id' => 1,
    'id_user' => 1,
    'nom' => 'Admin',
    'prenom' => 'Super',
    'email' => 'admin@school.com',
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'lycee_id' => 1,
    'auth_version' => 1,
    'permissions' => $perms
];

// 1. Test Migration 35 idempotency
echo "1. Test idempotence de la Migration 35...\n";
migrate_35($db);
migrate_35($db);

$stmt = $db->query("PRAGMA table_info(paie_periodes)");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($cols, 'name');

assert_p35(in_array('annee', $colNames), "La colonne 'annee' existe sur paie_periodes");
assert_p35(in_array('mois', $colNames), "La colonne 'mois' existe sur paie_periodes");
assert_p35(in_array('periode_comptable_id', $colNames), "La colonne 'periode_comptable_id' existe sur paie_periodes");
assert_p35(in_array('cree_par', $colNames), "La colonne 'cree_par' existe sur paie_periodes");
assert_p35(in_array('updated_at', $colNames), "La colonne 'updated_at' existe sur paie_periodes");

// 2. Test model query execution
echo "\n2. Test de requêtes modèle PaiePeriode et PaieDashboardService...\n";
$periodes = PaiePeriode::findAllForLycee(1);
assert_p35(is_array($periodes), "PaiePeriode::findAllForLycee s'exécute sans erreur SQL");

$dashData = PaieDashboardService::getDashboardData(1, null);
assert_p35(is_array($dashData) && isset($dashData['g1_trend_6m']), "PaieDashboardService::getDashboardData s'exécute sans erreur SQL");

// 3. Test Controller routes
echo "\n3. Test des routes et contrôleurs de la Paie...\n";

// Route 1 & 2: /drh/dashboard & /paie/dashboard
ob_start();
$cDash = new PaieDashboardController();
$cDash->index();
$outDash = ob_get_clean();
assert_p35(!empty($outDash) && !str_contains($outDash, 'SQLSTATE[42S22]'), "Route /drh/dashboard & /paie/dashboard s'exécute avec succès");

// Route 3: /paie/periodes
ob_start();
$cPer = new PaiePeriodesController();
$cPer->index();
$outPer = ob_get_clean();
assert_p35(!empty($outPer) && !str_contains($outPer, 'SQLSTATE[42S22]'), "Route /paie/periodes s'exécute avec succès");

// Route 4: /paie/bulletins/prepare
ob_start();
$cBulPrep = new PaieBulletinsController();
$cBulPrep->prepare();
$outBulPrep = ob_get_clean();
assert_p35(!empty($outBulPrep) && !str_contains($outBulPrep, 'SQLSTATE[42S22]'), "Route /paie/bulletins/prepare s'exécute avec succès");

// Route 5: /paie/bulletins
ob_start();
$cBulIdx = new PaieBulletinsController();
$cBulIdx->index();
$outBulIdx = ob_get_clean();
assert_p35(!empty($outBulIdx) && !str_contains($outBulIdx, 'SQLSTATE[42S22]'), "Route /paie/bulletins s'exécute avec succès");

// Route 6: /paie/cahier-texte
$_GET['search_mode'] = 'pedagogique';
ob_start();
$cCt = new PaieCahierTexteController();
$cCt->index();
$outCt = ob_get_clean();
assert_p35(!empty($outCt) && !str_contains($outCt, 'SQLSTATE[42S22]'), "Route /paie/cahier-texte s'exécute avec succès");

// Route 7: /paie/regularisations
ob_start();
$cReg = new PaieRegularisationsController();
$cReg->index();
$outReg = ob_get_clean();
assert_p35(!empty($outReg) && !str_contains($outReg, 'SQLSTATE[42S22]'), "Route /paie/regularisations s'exécute avec succès");

// Route 8: /paie/legacy/conflits
ob_start();
$cLeg = new PaieLegacyController();
$cLeg->conflits();
$outLeg = ob_get_clean();
assert_p35(!empty($outLeg) && !str_contains($outLeg, 'SQLSTATE[42S22]'), "Route /paie/legacy/conflits s'exécute avec succès");

// Route 9: /paie/historique
ob_start();
$cHist = new PaieBulletinsController();
$cHist->historique();
$outHist = ob_get_clean();
assert_p35(!empty($outHist) && !str_contains($outHist, 'SQLSTATE[42S22]'), "Route /paie/historique s'exécute avec succès");

echo "\n=== TOUS LES TESTS DE LA MIGRATION 35 ET DES 9 ROUTES ONT RÉUSSI AVEC SUCCÈS ! ===\n";
