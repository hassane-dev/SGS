<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/models/PaieRegularisation.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';

function assert_test($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU TEST DE PRÉPARATION, PRÉVISUALISATION ET GÉNÉRATION COLLECTIVE DE PAIE ===\n";

require_once __DIR__ . '/../migrate.php';

$db = Database::getInstance();

ComptabiliteService::seedDefaultChartOfAccounts();

// Reset test data
$db->exec("UPDATE comptabilite_periodes SET est_cloturee = 0");
$db->exec("DELETE FROM paie_audit_logs");
$db->exec("DELETE FROM paie_regularisation_integrations");
$db->exec("DELETE FROM paie_regularisations");
$db->exec("DELETE FROM paie_reglements");
$db->exec("DELETE FROM paie_bulletin_regle_tranches_snapshot");
$db->exec("DELETE FROM paie_bulletin_regles_snapshot");
$db->exec("DELETE FROM paie_bulletin_financements");
$db->exec("DELETE FROM paie_bulletin_contrat_snapshot");
$db->exec("DELETE FROM paie_bulletin_heures");
$db->exec("DELETE FROM paie_cahier_texte_validations");
$db->exec("DELETE FROM paie_bulletin_lignes");
$db->exec("DELETE FROM paie_bulletins");
$db->exec("DELETE FROM paie_periodes");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id >= 500");

// Seed test environment
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test Bulk') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (1, 1, 1, '2024-10-01', '2024-10-31', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO type_contrat (id_contrat, libelle, type_paiement, prise_en_charge) VALUES (1, 'CDI', 'mensuel', 'Ecole') ON CONFLICT DO NOTHING");

// Create test personnel 501, 502, 503 (eligible) and 504 (no active contract, ineligible)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (501, 1, 'Amina', 'Sow', '501ENS', 'amina@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (502, 1, 'Moussa', 'Diop', '502ENS', 'moussa@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (503, 1, 'Ibrahim', 'Kante', '503ENS', 'ibrahim@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (504, 1, 'Fatou', 'Ndiaye', '504ENS', 'fatou@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");

// Active contracts for 501, 502, 503
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (601, 501, 1, '2024-01-01', 1, 'forfait_fixe', 180000.00, 'FCFA', 'actif', 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (602, 502, 1, '2024-01-01', 1, 'forfait_fixe', 170000.00, 'FCFA', 'actif', 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (603, 503, 1, '2024-01-01', 1, 'forfait_fixe', 200000.00, 'FCFA', 'actif', 1) ON CONFLICT DO NOTHING");

// Inactive contract for 504 (closed)
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, date_fin, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (604, 504, 1, '2024-01-01', '2024-05-31', 1, 'forfait_fixe', 150000.00, 'FCFA', 'annule', 1) ON CONFLICT DO NOTHING");

$periodeId = PaieWorkflowService::createPeriod(1, 1, 'PAIE-2024-10', 10, 2024, '2024-10-01', '2024-10-31', 1);

// Add a pending regularisation for 501
$regId = PaieRegularisation::create([
    'personnel_id' => 501,
    'source_type' => 'autre',
    'periode_destination_id' => $periodeId,
    'type_regularisation' => 'rappel_salaire',
    'motif' => 'Rappel de prime exceptionnelle',
    'montant_brut_delta' => 20000.00,
    'montant_net_delta' => 20000.00,
    'statut' => 'valide',
    'cree_par' => 1
]);

// --- TEST 1: Source d'Éligibilité Officielle ---
echo "\n--- TEST 1: Source d'Éligibilité Officielle ---\n";
$eligibleContracts = PersonnelContractService::getEligibleContractsForPeriod($periodeId, 1);
$eligiblePids = array_map(function($c) { return (int)$c['personnel_id']; }, $eligibleContracts);

assert_test(count($eligibleContracts) >= 3, "getEligibleContractsForPeriod retourne les contrats actifs");
assert_test(in_array(501, $eligiblePids, true) && in_array(502, $eligiblePids, true) && in_array(503, $eligiblePids, true), "501, 502, 503 sont éligibles");
assert_test(!in_array(504, $eligiblePids, true), "504 (contrat fermé) est strictement exclu de l'éligibilité");

// --- TEST 2: Previsualisation Read-Only (Preview) ---
echo "\n--- TEST 2: Garanti Previsualisation Read-Only (Preview) ---\n";
$previewRes = PaieWorkflowService::previewBulletinsForPeriod($periodeId, null, 1);
$previewItems = $previewRes['items'];

assert_test(count($previewItems) >= 3, "La prévisualisation calcule le résumé pour tous les éligibles");

// Verify 0 bulletins, 0 lines, 0 regularisations consumed
$stmtBCount = $db->query("SELECT COUNT(*) FROM paie_bulletins WHERE periode_id = {$periodeId}");
$bCount = (int)$stmtBCount->fetchColumn();
assert_test($bCount === 0, "Preview n'a créé AUCUN bulletin dans paie_bulletins");

$regRow = PaieRegularisation::findById($regId);
assert_test($regRow['statut'] === 'valide', "Preview n'a PAS consommé la régularisation (toujours statut = 'valide')");

// --- TEST 3: Intersections de Sélection & Rejet d'Inéligibles ---
echo "\n--- TEST 3: Intersections de Sélection & Rejet d'Inéligibles ---\n";
// Pass 501, 502, and 504 (504 should be filtered out by intersection)
$previewSel = PaieWorkflowService::previewBulletinsForPeriod($periodeId, [501, 502, 504], 1);
$selPids = array_map(function($item) { return (int)$item['personnel_id']; }, $previewSel['items']);

assert_test(in_array(501, $selPids, true) && in_array(502, $selPids, true), "501 et 502 sont inclus dans la sélection");
assert_test(!in_array(504, $selPids, true), "504 est rejeté malgré sa présence dans la demande utilisateur");

// --- TEST 4: Génération Réelle vs Preview (Match Exact) ---
echo "\n--- TEST 4: Génération Réelle vs Preview (Match Exact des Montants) ---\n";
$preview501 = null;
foreach ($previewItems as $pi) {
    if ((int)$pi['personnel_id'] === 501) $preview501 = $pi;
}

$createdIds = PaieWorkflowService::generateBulletinsForPeriod($periodeId, 1, [501]);
assert_test(count($createdIds) === 1, "Bulletin unique généré pour 501");

$realB = PaieBulletin::findById($createdIds[0]);
assert_test((float)$realB['total_brut'] == (float)$preview501['total_brut'], "Le Salaire Brut réel (" . $realB['total_brut'] . ") est EXACTEMENT égal au montant prévisualisé (" . $preview501['total_brut'] . ")");
assert_test((float)$realB['net_a_payer'] == (float)$preview501['net_a_payer'], "Le Net à payer réel (" . $realB['net_a_payer'] . ") est EXACTEMENT égal au montant prévisualisé (" . $preview501['net_a_payer'] . ")");

$regRowAfter = PaieRegularisation::findById($regId);
assert_test($regRowAfter['statut'] === 'integre', "Après la VRAIE génération, la régularisation est désormais statut = 'integre'");

// --- TEST 5: Génération Collective (scope=all) ---
echo "\n--- TEST 5: Génération Collective (scope=all) ---\n";
$allCreatedIds = PaieWorkflowService::generateBulletinsForPeriod($periodeId, 1, null);
assert_test(count($allCreatedIds) >= 3, "Génération collective (scope=all) traite tous les membres du personnel éligibles");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS DE PRÉPARATION, PRÉVISUALISATION ET GÉNÉRATION COLLECTIVE ONT RÉUSSI !\n";
echo "=========================================================================\n";
