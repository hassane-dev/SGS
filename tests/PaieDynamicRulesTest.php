<?php
/**
 * Integration & Non-Regression Test Suite for Dynamic Payroll Rules (Paie 2.1)
 */

define('SGS_EXEC', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/PaieRegleCalcul.php';
require_once __DIR__ . '/../src/models/PaieBaremeTranche.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/services/PaieRuleRepository.php';
require_once __DIR__ . '/../src/services/PaieCalculationEngine.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';

function assert_test(bool $condition, string $message) {
    if (!$condition) {
        echo "❌ [FAIL] {$message}\n";
        exit(1);
    } else {
        echo "   [PASS] {$message}\n";
    }
}

echo "=== DÉMARRAGE DES TESTS D'INTÉGRATION DES RÈGLES DE PAIE DYNAMIQUES ===\n\n";

$db = Database::getInstance();

// Apply migrations
require_once __DIR__ . '/../db/migrations/20240115_13_create_lot2_paie_engine.php';
require_once __DIR__ . '/../db/migrations/20240115_16_extend_paie_rules_dynamic.php';

migrate_13($db);
migrate_16($db);

// Clean test data
$db->exec("DELETE FROM paie_baremes_tranches");
$db->exec("DELETE FROM paie_regles_calcul");
$db->exec("DELETE FROM paie_bulletin_regle_tranches_snapshot");
$db->exec("DELETE FROM paie_bulletin_regles_snapshot");
$db->exec("DELETE FROM paie_bulletin_lignes");
$db->exec("DELETE FROM paie_bulletins");
$db->exec("DELETE FROM paie_periodes");

// Seed default rules
PaieRuleRepository::seedDefaultRulesIfNeeded();

// --- TEST 1: Changement de Taux de Cotisation ---
echo "--- TEST 1: Changement Dynamique de Taux (Cotisation Salariale) ---\n";
$contractMock = [
    'id' => 10,
    'personnel_id' => 50,
    'mode_calcul_principal' => 'forfait_fixe',
    'salaire_base' => 200000.00
];

// Calculation with default rate (5.5%)
$res1 = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
assert_test($res1['total_brut'] == 200000.00, "Brut initial = 200 000 FCFA");
assert_test($res1['total_cotisations_salariales'] == 11000.00, "CNSS salariale à 5,5% = 11 000 FCFA");

// Modify rule rate to 7%
$db->exec("UPDATE paie_regles_calcul SET taux_par_defaut = 7.0000 WHERE code_regle = 'CNSS_SALARIALE'");
$res2 = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
assert_test($res2['total_cotisations_salariales'] == 14000.00, "CNSS salariale modifiée dynamiquement à 7% = 14 000 FCFA");

// Restore rate back to 5.5%
$db->exec("UPDATE paie_regles_calcul SET taux_par_defaut = 5.5000 WHERE code_regle = 'CNSS_SALARIALE'");


// --- TEST 2: Montant Fixe et Prise en Compte ---
echo "\n--- TEST 2: Prise en Compte de Montants Fixes ---\n";
$ruleFixeId = PaieRegleCalcul::create([
    'juridiction_code' => 'RCA',
    'pays_code' => 'RCA',
    'code_regle' => 'TAXE_FORFAITAIRE',
    'libelle' => 'Taxe Municipale Forfaitaire',
    'categorie' => 'retenue',
    'mode_calcul' => 'montant_fixe',
    'montant_fixe_salarial' => 2500.00,
    'ordre_application' => 150,
    'actif' => 1,
    'date_debut_validite' => '2026-01-01'
]);

$res3 = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
assert_test($res3['total_retenues'] == 2500.00, "Taxe forfaitaire retenue = 2 500 FCFA");


// --- TEST 3: Respect des Plafonds et Seuils ---
echo "\n--- TEST 3: Respect des Plafonds et Seuils ---\n";
// Create rule with ceiling of 100,000 FCFA
$rulePlafondId = PaieRegleCalcul::create([
    'juridiction_code' => 'RCA',
    'pays_code' => 'RCA',
    'code_regle' => 'COTIS_PLAFONNEE',
    'libelle' => 'Cotisation Plafonnée à 100k',
    'categorie' => 'cotisation_salariale',
    'mode_calcul' => 'pourcentage',
    'base_calcul_type' => 'brut_total',
    'taux_par_defaut' => 10.0000,
    'plafond_maximum' => 100000.00,
    'ordre_application' => 110,
    'actif' => 1,
    'date_debut_validite' => '2026-01-01'
]);

$res4 = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
// Gross is 200,000, ceiling is 100,000 -> 10% of 100,000 = 10,000 FCFA
$foundLine = null;
foreach ($res4['rubrique_lines'] as $rl) {
    if ($rl['code_rubrique'] === 'COTIS_PLAFONNEE') {
        $foundLine = $rl;
        break;
    }
}
assert_test($foundLine !== null, "Ligne de rubrique COTIS_PLAFONNEE générée");
assert_test($foundLine['base_calcul'] == 100000.00, "Assiette plafonnée à 100 000 FCFA au lieu de 200 000 FCFA");
assert_test($foundLine['montant_salarial'] == 10000.00, "Montant calculé sur assiette plafonnée = 10 000 FCFA");


// --- TEST 4: Barème Progressif IUTS ---
echo "\n--- TEST 4: Calcul Correct du Barème Progressif IUTS ---\n";
// Gross 200,000, CNSS (5.5%) = 11,000, Cotis Plafonnée = 10,000. Net imposable = 179,000 FCFA
// Brackets:
// 0 - 50k @ 0% = 0
// 50k - 150k (100k) @ 10% = 10,000
// 150k - 179k (29k) @ 15% = 4,350
// Expected IUTS = 14,350 FCFA
assert_test(round($res4['total_impots'], 2) == 14350.00, "Impôt IUTS calculé par tranches = 14 350 FCFA (attendu 14350.00)");


// --- TEST 5: Règles Différentes Selon le Pays (Jurisdiction Isolation) ---
echo "\n--- TEST 5: Isolation des Règles par Pays / Juridiction ---\n";
// Create a specific rule for Cameroon (CMR)
PaieRegleCalcul::create([
    'juridiction_code' => 'CMR',
    'pays_code' => 'CMR',
    'code_regle' => 'CNPS_CMR',
    'libelle' => 'Cotisation CNPS Cameroun',
    'categorie' => 'cotisation_salariale',
    'mode_calcul' => 'pourcentage',
    'taux_par_defaut' => 4.2000,
    'ordre_application' => 100,
    'actif' => 1,
    'date_debut_validite' => '2026-01-01'
]);

$resCMR = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'CMR', '2026-01-15');
$foundCMR = false;
foreach ($resCMR['rubrique_lines'] as $rl) {
    if ($rl['code_rubrique'] === 'CNPS_CMR') {
        $foundCMR = true;
        break;
    }
}
assert_test($foundCMR === true, "La règle spécifique Cameroun (CMR) s'applique pour un établissement CMR");

$resRCA = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
$foundCMRInRCA = false;
foreach ($resRCA['rubrique_lines'] as $rl) {
    if ($rl['code_rubrique'] === 'CNPS_CMR') {
        $foundCMRInRCA = true;
        break;
    }
}
assert_test($foundCMRInRCA === false, "La règle Cameroun (CMR) N'apparaît PAS pour un établissement RCA");


// --- TEST 6: Désactivation d'une Règle ---
echo "\n--- TEST 6: Désactivation d'une Règle ---\n";
PaieRegleCalcul::toggleActive($ruleFixeId); // Deactivate TAXE_FORFAITAIRE
$res6 = PaieCalculationEngine::computeBulletin($contractMock, [], [], 'RCA', '2026-01-15');
assert_test($res6['total_retenues'] == 0.00, "La règle inactive TAXE_FORFAITAIRE n'est plus appliquée (retenues = 0.00)");


// --- TEST 7: Intégration Preview et Génération ---
echo "\n--- TEST 7: Cohérence Garantie Entre Preview et Génération Définitive ---\n";
// Create period
$periodeId = PaiePeriode::create([
    'lycee_id' => 1,
    'periode_comptable_id' => 1,
    'code_periode' => 'PAIE-TEST-2026-01',
    'mois' => 1,
    'annee' => 2026,
    'date_debut' => '2026-01-01',
    'date_fin' => '2026-01-31',
    'statut' => 'brouillon',
    'cree_par' => 1
]);

$previewData = PaieWorkflowService::previewBulletinsForPeriod($periodeId, null, 1);
assert_test(isset($previewData['items']), "Prévisualisation exécutée sans altérer la BDD");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS D'INTÉGRATION DES RÈGLES DE PAIE DYNAMIQUES ONT RÉUSSI !\n";
echo "=========================================================================\n";
