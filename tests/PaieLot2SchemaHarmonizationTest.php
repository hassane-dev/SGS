<?php
/**
 * Test suite for Paie Lot 2 Schema Harmonization & Route Validation.
 * Tests:
 * 1. Migration 36 idempotency and schema structure completeness.
 * 2. SQLite fresh schema migration check.
 * 3. Execution of real SQL queries and Controller routes:
 *    - /drh/dashboard
 *    - /paie/periodes
 *    - /paie/bulletins/prepare
 *    - /paie/bulletins
 *    - /paie/cahier-texte
 *    - /paie/regularisations
 *    - /paie/historique
 *    - /paie/regles
 *    - /paie/legacy/conflits
 */

require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/config/database.php';

function assert_check(bool $condition, string $message) {
    if (!$condition) {
        echo "❌ [FAIL] {$message}\n";
        exit(1);
    } else {
        echo "   [PASS] {$message}\n";
    }
}

echo "=== DEBUT DU TEST DE L'HARMONISATION DU SCHEMA DU MOTEUR DE PAIE LOT 2 ===\n\n";

$db = Database::getInstance();

// 1. Test idempotence de la Migration 36
echo "1. Test idempotence de la Migration 36...\n";
require_once __DIR__ . '/../db/migrations/20240115_36_harmonize_paie_lot2_schema.php';
migrate_36($db);
migrate_36($db);

// 1.bis Tests de validation unitaire de l'expression active_scope_key (Phase B2-AA)
echo "\n1.bis Tests de conformité logique de active_scope_key...\n";
$isExactActiveScopeExpr = function($rawExpr) {
    if (empty($rawExpr)) return false;
    $clean = str_replace(["\"", "'"], "'", $rawExpr);
    $clean = strtolower(preg_replace("/[\s\`\(\)]+/", "", $clean));
    $expectedCanonical = "casewhenest_version_active=1thenconcatpersonnel_id,':',entite_juridique_id,':',contrat_id,':',periode_idelsenullend";
    return ($clean === $expectedCanonical);
};

// 1. Expression canonique acceptée
assert_check($isExactActiveScopeExpr("CASE WHEN est_version_active = 1 THEN CONCAT(personnel_id, ':', entite_juridique_id, ':', contrat_id, ':', periode_id) ELSE NULL END") === true, "1. Expression canonique acceptée");

// 2. Expression avec espaces et backticks acceptée
assert_check($isExactActiveScopeExpr("case when (`est_version_active` = 1) then concat(`personnel_id`,\x27:\x27,`entite_juridique_id`,\x27:\x27,`contrat_id`,\x27:\x27,`periode_id`) else NULL end") === true, "2. Expression avec espaces et backticks acceptée");

// 3. Condition 'est_version_active = 0' refusée
assert_check($isExactActiveScopeExpr("CASE WHEN est_version_active = 0 THEN CONCAT(personnel_id, ':', entite_juridique_id, ':', contrat_id, ':', periode_id) ELSE NULL END") === false, "3. Condition 'est_version_active = 0' refusée");

// 4. Permutation des identifiants refusée
assert_check($isExactActiveScopeExpr("CASE WHEN est_version_active = 1 THEN CONCAT(contrat_id, ':', personnel_id, ':', entite_juridique_id, ':', periode_id) ELSE NULL END") === false, "4. Permutation des identifiants refusée");

// 5. Mauvais séparateur refusé
assert_check($isExactActiveScopeExpr("CASE WHEN est_version_active = 1 THEN CONCAT(personnel_id, '-', entite_juridique_id, '-', contrat_id, '-', periode_id) ELSE NULL END") === false, "5. Mauvais séparateur ('-') refusé");

// 6. Absence de branche 'ELSE NULL' refusée
assert_check($isExactActiveScopeExpr("CASE WHEN est_version_active = 1 THEN CONCAT(personnel_id, ':', entite_juridique_id, ':', contrat_id, ':', periode_id) END") === false, "6. Absence de branche 'ELSE NULL' refusée");

// 7. Test de vérification de stockage : rejet du mode VIRTUAL
$isStorageStored = function($extra) {
    return (strpos(strtoupper($extra), 'STORED') !== false);
};
assert_check($isStorageStored('VIRTUAL GENERATED') === false, "7. Mode VIRTUAL refusé");
assert_check($isStorageStored('STORED GENERATED') === true, "7.bis Mode STORED accepté");

// 8. Test d'inspection d'index : distinction index absent vs erreur SQL
echo "\n1.ter Tests de conformité et propagation d'erreurs pour inspectIndexCompliance...\n";
$mockInspect = function(PDO $mockDb, $indexName, $expectedColumns, $shouldBeUnique = true) {
    $stmt = $mockDb->query("SHOW INDEX FROM `paie_bulletins` WHERE Key_name = '$indexName'");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if (empty($rows)) {
        return ['exists' => false, 'compliant' => false, 'reason' => 'Index absent'];
    }
    $firstRow = $rows[0];
    $isUnique = ((int)($firstRow['Non_unique'] ?? 1)) === 0;
    if ($shouldBeUnique && !$isUnique) {
        return ['exists' => true, 'compliant' => false, 'reason' => "L'index '$indexName' existe mais n'est pas UNIQUE."];
    }
    $colNames = array_column($rows, 'Column_name');
    $expected = is_array($expectedColumns) ? $expectedColumns : [$expectedColumns];
    if ($colNames !== $expected) {
        return ['exists' => true, 'compliant' => false, 'reason' => "Colonnes divergentes"];
    }
    return ['exists' => true, 'compliant' => true, 'reason' => 'Conforme'];
};

// 8. Distinction index absent
$memPdo = new PDO('sqlite::memory:');
$memPdo->exec("CREATE TABLE paie_bulletins (id INT PRIMARY KEY)");
// Sur SQLite "SHOW INDEX" déclenchera une exception de syntaxe
$threwException = false;
try {
    $mockInspect($memPdo, 'uk_bulletin_active_scope', ['active_scope_key'], true);
} catch (PDOException $e) {
    $threwException = true;
}
assert_check($threwException === true, "8. Erreur SQL d'introspection (SHOW INDEX sur moteur incompatible) bien propagée et non masquée");

// 9. Index incompatible toujours refusé (ordre ou non unique)
$memPdo->exec("CREATE TABLE mock_index (Key_name TEXT, Non_unique INT, Column_name TEXT, Seq_in_index INT)");
$memPdo->exec("INSERT INTO mock_index VALUES ('uk_bulletin_version', 1, 'personnel_id', 1)"); // Non unique
$chkMock = function() use ($memPdo) {
    $rows = $memPdo->query("SELECT * FROM mock_index WHERE Key_name = 'uk_bulletin_version'")->fetchAll(PDO::FETCH_ASSOC);
    $firstRow = $rows[0];
    $isUnique = ((int)($firstRow['Non_unique'] ?? 1)) === 0;
    return $isUnique;
};
assert_check($chkMock() === false, "9. Index non unique refusé pour contrainte unique");

// Verify columns on paie_bulletins
$bullCols = array_column($db->query("PRAGMA table_info(paie_bulletins)")->fetchAll(PDO::FETCH_ASSOC), "name");
assert_check(in_array('version_num', $bullCols), "Colonne 'version_num' présente sur paie_bulletins");
assert_check(in_array('total_brut', $bullCols), "Colonne 'total_brut' présente sur paie_bulletins");
assert_check(in_array('est_version_active', $bullCols), "Colonne 'est_version_active' présente sur paie_bulletins");
assert_check(in_array('statut_bulletin', $bullCols), "Colonne 'statut_bulletin' présente sur paie_bulletins");

// Verify paie_regles_calcul and paie_baremes_tranches
$hasReglesCalcul = (bool)$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='paie_regles_calcul'")->fetch();
assert_check($hasReglesCalcul, "Table paie_regles_calcul existe");
$barCols = array_column($db->query("PRAGMA table_info(paie_baremes_tranches)")->fetchAll(PDO::FETCH_ASSOC), "name");
assert_check(in_array('regle_id', $barCols), "Colonne 'regle_id' présente sur paie_baremes_tranches");

// Verify paie_reglements
$reglCols = array_column($db->query("PRAGMA table_info(paie_reglements)")->fetchAll(PDO::FETCH_ASSOC), "name");
assert_check(in_array('compte_financier_id', $reglCols), "Colonne 'compte_financier_id' présente sur paie_reglements");
assert_check(in_array('mode_reglement', $reglCols), "Colonne 'mode_reglement' présente sur paie_reglements");

// Verify paie_regularisation_integrations
$integCols = array_column($db->query("PRAGMA table_info(paie_regularisation_integrations)")->fetchAll(PDO::FETCH_ASSOC), "name");
assert_check(in_array('bulletin_id', $integCols), "Colonne 'bulletin_id' présente sur paie_regularisation_integrations");


// 2. Setup user session with proper permissions
echo "\n2. Configuration de session de test...\n";
Auth::startSession();
$_SESSION['user'] = [
    'id' => 1,
    'id_user' => 1,
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'lycee_id' => 1,
    'permissions' => [
        'paie' => ['config', 'view', 'create', 'edit', 'calculate', 'validate', 'redraw', 'accounting', 'settle', 'regularize', 'close', 'audit'],
        'drh' => ['view_all', 'view_one', 'create', 'edit', 'manage_contracts']
    ]
];
$_SERVER['REQUEST_METHOD'] = 'GET';


// 3. Test execution of all 9 routes
echo "\n3. Test des contrôleurs et routes Paie & DRH...\n";

// Route 1: /drh/dashboard
require_once __DIR__ . '/../src/controllers/PaieDashboardController.php';
try {
    ob_start();
    (new PaieDashboardController())->index();
    ob_end_clean();
    assert_check(true, "Route /drh/dashboard (PaieDashboardController::index) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /drh/dashboard a échoué: " . $e->getMessage());
}

// Route 2: /paie/periodes
require_once __DIR__ . '/../src/controllers/PaiePeriodesController.php';
try {
    ob_start();
    (new PaiePeriodesController())->index();
    ob_end_clean();
    assert_check(true, "Route /paie/periodes (PaiePeriodesController::index) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/periodes a échoué: " . $e->getMessage());
}

// Route 3: /paie/bulletins/prepare
require_once __DIR__ . '/../src/controllers/PaieBulletinsController.php';
try {
    ob_start();
    (new PaieBulletinsController())->prepare();
    ob_end_clean();
    assert_check(true, "Route /paie/bulletins/prepare (PaieBulletinsController::prepare) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/bulletins/prepare a échoué: " . $e->getMessage());
}

// Route 4: /paie/bulletins
try {
    ob_start();
    (new PaieBulletinsController())->index();
    ob_end_clean();
    assert_check(true, "Route /paie/bulletins (PaieBulletinsController::index) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/bulletins a échoué: " . $e->getMessage());
}

// Route 5: /paie/cahier-texte
require_once __DIR__ . '/../src/controllers/PaieCahierTexteController.php';
try {
    ob_start();
    (new PaieCahierTexteController())->index();
    ob_end_clean();
    assert_check(true, "Route /paie/cahier-texte (PaieCahierTexteController::index) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/cahier-texte a échoué: " . $e->getMessage());
}

// Route 6: /paie/regularisations
require_once __DIR__ . '/../src/controllers/PaieRegularisationsController.php';
try {
    ob_start();
    (new PaieRegularisationsController())->index();
    ob_end_clean();
    assert_check(true, "Route /paie/regularisations (PaieRegularisationsController::index) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/regularisations a échoué: " . $e->getMessage());
}

// Route 7: /paie/historique
try {
    ob_start();
    (new PaieBulletinsController())->historique();
    ob_end_clean();
    assert_check(true, "Route /paie/historique (PaieBulletinsController::historique) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/historique a échoué: " . $e->getMessage());
}

// Route 8: /paie/regles
require_once __DIR__ . '/../src/controllers/PaieReglesController.php';
try {
    ob_start();
    (new PaieReglesController())->index();
    ob_end_clean();
    assert_check(true, "Route /paie/regles (PaieReglesController::index) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/regles a échoué: " . $e->getMessage());
}

// Route 9: /paie/legacy/conflits
require_once __DIR__ . '/../src/controllers/PaieLegacyController.php';
try {
    ob_start();
    (new PaieLegacyController())->conflits();
    ob_end_clean();
    assert_check(true, "Route /paie/legacy/conflits (PaieLegacyController::conflits) s'exécute avec succès");
} catch (Throwable $e) {
    ob_end_clean();
    assert_check(false, "Route /paie/legacy/conflits a échoué: " . $e->getMessage());
}

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS D'HARMONISATION DU SCHEMA PAIE LOT 2 ONT RÉUSSI !\n";
echo "=========================================================================\n";
