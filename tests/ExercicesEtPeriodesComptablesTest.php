<?php

/**
 * Integration & Non-regression test suite for Exercices Financiers and Comptabilite Periodes
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';
require_once __DIR__ . '/../src/models/ComptabilitePeriode.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';

function assert_test($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        exit(1);
    }
}

echo "=== DEBUT DU TEST: EXERCICES ET PERIODES COMPTABLES ===\n\n";

$db = Database::getInstance();

// Clean test exercices for reproducible runs
$db->exec("DELETE FROM comptabilite_periodes WHERE lycee_id = 99");
$db->exec("DELETE FROM exercices_financiers WHERE lycee_id = 99");
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (99, 'Lycée de Test 99') ON CONFLICT DO NOTHING");

// Test 1: Création d'un exercice financier 2026
$exId = ExerciceFinancier::create([
    'lycee_id' => 99,
    'libelle' => 'Exercice 2026',
    'date_debut' => '2026-01-01',
    'date_fin' => '2026-12-31',
    'est_actif' => 1
]);

assert_test($exId > 0, "Exercice financier 2026 créé avec succès (ID #$exId)");

// Test 2: Génération par lot des 12 périodes mensuelles pour 2026
$countGen = ComptabilitePeriode::generateMonthlyForExercice(99, $exId);
assert_test($countGen === 12, "12 périodes comptables mensuelles ont été générées pour 2026");

// Test 3: Recherche de la période de septembre 2026
$periodes = ComptabilitePeriode::findAllForLycee(99, $exId);
$sept2026 = null;
foreach ($periodes as $p) {
    if ($p['date_debut'] === '2026-09-01' && $p['date_fin'] === '2026-09-30') {
        $sept2026 = $p;
        break;
    }
}

assert_test($sept2026 !== null, "Période comptable Septembre 2026 trouvée (ID #{$sept2026['id']})");

// Test 4: Création d'une période de paie pour Septembre 2026 liée à la période comptable
$paiePeriodeId = PaieWorkflowService::createPeriod(
    99,
    (int)$sept2026['id'],
    'PAIE-2026-09',
    9,
    2026,
    '2026-09-01',
    '2026-09-30',
    1
);

assert_test($paiePeriodeId > 0, "Période de paie Septembre 2026 créée et reliée à la période comptable #{$sept2026['id']}");

// Test 5: Clôture de la période comptable de septembre 2026
ComptabiliteService::validerCloturePeriode(99, (int)$sept2026['id'], 1);
$septClosed = ComptabilitePeriode::findById((int)$sept2026['id']);
assert_test(!empty($septClosed['est_cloturee']), "La période comptable Septembre 2026 a été clôturée");

// Test 6: Verrou de sécurité Paie - tentative d'opération sous période comptable clôturée
$refusalSuccess = false;
try {
    PaieWorkflowService::generateBulletinsForPeriod($paiePeriodeId, 1);
} catch (LogicException $e) {
    $refusalSuccess = true;
    echo "   -> Refus métier confirmé : " . $e->getMessage() . "\n";
}
assert_test($refusalSuccess, "La paie est bloquée sur une période comptable clôturée par Verrou V2");

// Test 7: Réouverture de la période comptable
ComptabiliteService::reouvrirPeriode(99, (int)$sept2026['id'], 1);
$septReopened = ComptabilitePeriode::findById((int)$sept2026['id']);
assert_test(empty($septReopened['est_cloturee']), "La période comptable Septembre 2026 a été réouverte");

// Test 8: Protection contre le chevauchement d'exercices financiers
$overlapFail = false;
try {
    ExerciceFinancier::create([
        'lycee_id' => 99,
        'libelle' => 'Exercice Chevauchant',
        'date_debut' => '2026-06-01',
        'date_fin' => '2027-05-31',
        'est_actif' => 0
    ]);
} catch (InvalidArgumentException $e) {
    $overlapFail = true;
    echo "   -> Refus chevauchement d'exercice : " . $e->getMessage() . "\n";
}
assert_test($overlapFail, "Création d'un exercice chevauchant rejetée avec succès");

// Test 9: Matrice RBAC et visibilité du menu sidebar
echo "\n--- TEST 9: Matrice RBAC et visibilité du menu sidebar ---\n";

function mockSessionForTest($roleId, $roleName) {
    Auth::startSession();
    $perms = Role::getPermissions($roleId);
    $_SESSION['user'] = [
        'id' => 888,
        'role_id' => $roleId,
        'role_name' => $roleName,
        'lycee_id' => 99,
        'permissions' => $perms
    ];
}

// 1. Admin Local (role 3)
mockSessionForTest(3, 'admin_local');
assert_test(Auth::can('view', 'comptabilite'), "Admin Local peut voir la comptabilité");
assert_test(Auth::can('close', 'comptabilite'), "Admin Local peut clôturer un exercice/période");

// 2. Chef Comptable (role 9)
mockSessionForTest(9, 'chef_comptable');
assert_test(Auth::can('view', 'comptabilite'), "Chef Comptable peut voir la comptabilité");
assert_test(Auth::can('close', 'comptabilite'), "Chef Comptable peut clôturer un exercice/période");

// 3. Comptable (role 7)
mockSessionForTest(7, 'comptable');
assert_test(Auth::can('view', 'comptabilite'), "Comptable peut voir la comptabilité");
assert_test(Auth::can('create', 'comptabilite'), "Comptable peut créer des exercices/périodes");
assert_test(!Auth::can('close', 'comptabilite'), "Comptable ne peut PAS clôturer un exercice/période");

// 4. Enseignant (role 6)
mockSessionForTest(6, 'enseignant');
assert_test(!Auth::can('view', 'comptabilite'), "Enseignant ne peut PAS voir la comptabilité");

// Cleanup test data
$db->exec("DELETE FROM paie_periodes WHERE lycee_id = 99");
$db->exec("DELETE FROM comptabilite_periodes WHERE lycee_id = 99");
$db->exec("DELETE FROM exercices_financiers WHERE lycee_id = 99");

echo "\n=== TOUS LES TESTS EXERCICES ET PERIODES COMPTABLES ONT REUSSI ===\n";
