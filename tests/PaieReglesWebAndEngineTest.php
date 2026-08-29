<?php
/**
 * Comprehensive Integration and Web UI Test Suite for Payroll Rules Management (/paie/regles)
 */

define('SGS_EXEC', true);

require_once __DIR__ . '/../src/core/bootstrap_i18n.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/PaieRegleCalcul.php';
require_once __DIR__ . '/../src/models/PaieBaremeTranche.php';
require_once __DIR__ . '/../src/services/PaieRuleRepository.php';
require_once __DIR__ . '/../src/services/PaieCalculationEngine.php';
require_once __DIR__ . '/../src/controllers/PaieReglesController.php';

function assert_check(bool $condition, string $message) {
    if (!$condition) {
        echo "❌ [FAIL] {$message}\n";
        exit(1);
    } else {
        echo "   [PASS] {$message}\n";
    }
}

echo "=== DÉMARRAGE DES TESTS WEB & MOTEUR DE LA CONFIGURATION DES RÈGLES DE PAIE ===\n\n";

$db = Database::getInstance();

// 1. Re-run migration 16 to verify idempotency
require_once __DIR__ . '/../db/migrations/20240115_16_extend_paie_rules_dynamic.php';
migrate_16($db);

$ruleCountBefore = (int)$db->query("SELECT COUNT(*) FROM paie_regles_calcul")->fetchColumn();
migrate_16($db);
$ruleCountAfter = (int)$db->query("SELECT COUNT(*) FROM paie_regles_calcul")->fetchColumn();
assert_check($ruleCountBefore === $ruleCountAfter, "Ré-exécution de la migration 16 idempotente (aucun doublon ni corruption)");


// 2. Setup user sessions for testing permissions
Auth::startSession();
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'lycee_id' => 1,
    'permissions' => [
        'paie' => ['config', 'view', 'create', 'edit', 'calculate', 'validate', 'redraw', 'accounting', 'settle', 'regularize', 'close', 'audit']
    ]
];

// TEST WEB 1: /paie/regles -> HTTP 200 with authorized user & rules rendering
echo "\n--- TEST WEB 1: Affichage de /paie/regles avec utilisateur autorisé (paie.config) ---\n";
ob_start();
$controller = new PaieReglesController();
$controller->index();
$htmlOutput = ob_get_clean();

assert_check(strpos($htmlOutput, 'Configuration des Règles de Paie') !== false, "La page /paie/regles s'affiche correctement");
assert_check(strpos($htmlOutput, 'CNSS_SALARIALE') !== false, "Règle système CNSS_SALARIALE présente dans l'affichage");
assert_check(strpos($htmlOutput, 'CNSS_PATRONALE') !== false, "Règle système CNSS_PATRONALE présente dans l'affichage");
assert_check(strpos($htmlOutput, 'IUTS_IMPOT') !== false, "Règle système IUTS_IMPOT présente dans l'affichage");
assert_check(strpos($htmlOutput, '/paie/regles/create') !== false, "Bouton 'Nouvelle Règle' présent dans l'interface pour un profil avec paie.config");


// TEST WEB 2: Protection RBAC et masquage des boutons pour un utilisateur sans paie.config
echo "\n--- TEST WEB 2: Protection RBAC et masquage des éléments pour un profil sans paie.config ---\n";
$_SESSION['user'] = [
    'id' => 70,
    'role_id' => 7, // Standard Comptable without paie.config
    'role_name' => 'comptable',
    'lycee_id' => 1,
    'permissions' => [
        'paie' => ['view', 'settle']
    ]
];

assert_check(Auth::can('config', 'paie') === false, "Auth::can('config', 'paie') renvoie false pour un comptable standard");

// Render view directly with rules loaded to test UI button filtering
$rules = PaieRegleCalcul::findAll();
foreach ($rules as &$r) {
    $r['tiers'] = PaieBaremeTranche::findByRegleId((int)$r['id']);
    $r['is_used'] = PaieRegleCalcul::isRuleUsedInBulletins((int)$r['id']);
}
unset($r);

ob_start();
include __DIR__ . '/../src/views/paie/regles/index.php';
$htmlOutputNoConfig = ob_get_clean();

assert_check(strpos($htmlOutputNoConfig, '/paie/regles/create') === false, "Bouton 'Nouvelle Règle' masqué pour un profil sans paie.config");
assert_check(strpos($htmlOutputNoConfig, '/paie/regles/1/edit') === false, "Bouton 'Modifier' masqué pour un profil sans paie.config");
assert_check(strpos($htmlOutputNoConfig, '/paie/regles/toggle') === false, "Formulaire de basculement statut (Toggle) masqué pour un profil sans paie.config");


// Restore super admin user with paie.config permission
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'lycee_id' => 1,
    'permissions' => [
        'paie' => ['config', 'view', 'create', 'edit', 'calculate', 'validate', 'redraw', 'accounting', 'settle', 'regularize', 'close', 'audit']
    ]
];


// TEST WEB 3: Création d'une nouvelle règle dynamique avec tranches
echo "\n--- TEST WEB 3: Création d'une nouvelle règle de paie avec barème progressif ---\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'code_regle' => 'TAXE_INNOVATION',
    'libelle' => 'Taxe de Développement Numérique',
    'categorie' => 'impot',
    'mode_calcul' => 'bareme_progressif',
    'pays_code' => 'RCA',
    'base_calcul_type' => 'brut_total',
    'taux_par_defaut' => '0',
    'ordre_application' => '120',
    'date_debut_validite' => '2026-01-01',
    'tranches' => [
        1 => ['limite_inferieure' => '0', 'limite_superieure' => '100000', 'taux' => '0', 'montant_fixe' => '0'],
        2 => ['limite_inferieure' => '100000', 'limite_superieure' => '500000', 'taux' => '5', 'montant_fixe' => '0'],
        3 => ['limite_inferieure' => '500000', 'limite_superieure' => '', 'taux' => '10', 'montant_fixe' => '0']
    ]
];

try {
    $controller->store();
} catch (Throwable $e) {
    // Controller redirects on success via header()
}

$newRule = $db->query("SELECT * FROM paie_regles_calcul WHERE code_regle = 'TAXE_INNOVATION'")->fetch(PDO::FETCH_ASSOC);
assert_check(!empty($newRule), "Nouvelle règle TAXE_INNOVATION créée avec succès en BDD");
assert_check((int)$newRule['ordre_application'] === 120, "Ordre d'application = 120");

$tranches = PaieBaremeTranche::findByRegleId((int)$newRule['id']);
assert_check(count($tranches) === 3, "3 tranches enregistrées pour la règle progressive");


// TEST WEB 4: Modification et Versionnement d'une règle
echo "\n--- TEST WEB 4: Modification d'une règle existante ---\n";
$_POST = [
    'id' => $newRule['id'],
    'libelle' => 'Taxe de Développement Numérique (Mise à jour)',
    'categorie' => 'impot',
    'mode_calcul' => 'bareme_progressif',
    'base_calcul_type' => 'brut_total',
    'taux_par_defaut' => '0',
    'ordre_application' => '125',
    'actif' => '1',
    'date_debut_validite' => '2026-01-01',
    'tranches' => [
        1 => ['limite_inferieure' => '0', 'limite_superieure' => '100000', 'taux' => '0', 'montant_fixe' => '0'],
        2 => ['limite_inferieure' => '100000', 'limite_superieure' => '', 'taux' => '6', 'montant_fixe' => '0']
    ]
];

try {
    $controller->update($newRule['id']);
} catch (Throwable $e) {
    // Redirects
}

$updatedRule = PaieRegleCalcul::findById((int)$newRule['id']);
assert_check($updatedRule['libelle'] === 'Taxe de Développement Numérique (Mise à jour)', "Libellé de la règle mis à jour");
$updatedTranches = PaieBaremeTranche::findByRegleId((int)$newRule['id']);
assert_check(count($updatedTranches) === 2, "Tranches mises à jour (2 tranches)");


// TEST WEB 5: Activer / Désactiver une règle
echo "\n--- TEST WEB 5: Basculement Actif/Inactif (Toggle) ---\n";
$_POST = ['id' => $newRule['id']];
try {
    $controller->toggle($newRule['id']);
} catch (Throwable $e) {}

$toggledRule = PaieRegleCalcul::findById((int)$newRule['id']);
assert_check((int)$toggledRule['actif'] === 0, "Règle désactivée avec succès (actif = 0)");


// TEST MOTEUR 1: Une règle inactive n'est pas appliquée par le moteur
echo "\n--- TEST MOTEUR 1: Non-application d'une règle inactive dans le calcul de paie ---\n";
$contractMock = [
    'id' => 10,
    'personnel_id' => 50,
    'mode_calcul_principal' => 'forfait_fixe',
    'salaire_base' => 300000.00
];

$calcResInactive = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
$taxeFound = false;
foreach ($calcResInactive['rubrique_lines'] as $rl) {
    if ($rl['code_rubrique'] === 'TAXE_INNOVATION') {
        $taxeFound = true;
        break;
    }
}
assert_check($taxeFound === false, "La règle inactive TAXE_INNOVATION n'est pas appliquée par le moteur de paie");


// Reactivate rule
PaieRegleCalcul::toggleActive((int)$newRule['id']);
$calcResActive = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
$taxeFoundActive = false;
foreach ($calcResActive['rubrique_lines'] as $rl) {
    if ($rl['code_rubrique'] === 'TAXE_INNOVATION') {
        $taxeFoundActive = true;
        break;
    }
}
assert_check($taxeFoundActive === true, "La règle réactivée TAXE_INNOVATION est correctement appliquée par le moteur");


// TEST MOTEUR 2: Isolation par Pays / Juridiction (RCA vs CMR)
echo "\n--- TEST MOTEUR 2: Isolation des Règles par Juridiction (RCA / CMR) ---\n";
$cmrRuleId = PaieRegleCalcul::create([
    'juridiction_code' => 'CMR',
    'pays_code' => 'CMR',
    'code_regle' => 'CRTV_CAMEROUN',
    'libelle' => 'Redevance Audiovisuelle CRTV',
    'categorie' => 'retenue',
    'mode_calcul' => 'montant_fixe',
    'montant_fixe_salarial' => 1500.00,
    'ordre_application' => 180,
    'actif' => 1,
    'date_debut_validite' => '2026-01-01'
]);

$calcCMR = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'CMR', '2026-01-15');
$crtvFoundCMR = false;
foreach ($calcCMR['rubrique_lines'] as $rl) {
    if ($rl['code_rubrique'] === 'CRTV_CAMEROUN') {
        $crtvFoundCMR = true;
        break;
    }
}
assert_check($crtvFoundCMR === true, "La règle CRTV_CAMEROUN est appliquée pour un établissement CMR");

$calcRCA = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
$crtvFoundRCA = false;
foreach ($calcRCA['rubrique_lines'] as $rl) {
    if ($rl['code_rubrique'] === 'CRTV_CAMEROUN') {
        $crtvFoundRCA = true;
        break;
    }
}
assert_check($crtvFoundRCA === false, "La règle CRTV_CAMEROUN n'est PAS appliquée pour un établissement RCA");


// Cleanup test rules
$db->exec("DELETE FROM paie_baremes_tranches WHERE regle_id IN ({$newRule['id']}, {$cmrRuleId})");
$db->exec("DELETE FROM paie_regles_calcul WHERE id IN ({$newRule['id']}, {$cmrRuleId})");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS WEB ET MOTEUR DE CONFIGURATION DES RÈGLES ONT RÉUSSI !\n";
echo "=========================================================================\n";
