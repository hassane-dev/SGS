<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/models/PaieBulletinLigne.php';
require_once __DIR__ . '/../src/models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../src/models/PaieBulletinHeure.php';
require_once __DIR__ . '/../src/models/PaieReglement.php';
require_once __DIR__ . '/../src/models/PaieRegularisation.php';
require_once __DIR__ . '/../src/services/PaieCalculationEngine.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';
require_once __DIR__ . '/../src/services/LegacySalaryFacade.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';

function assert_test($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU TEST D'INTÉGRATION ET DE SÉCURITÉ DU MODULE PAIE LOT 2.1 ===\n";

require_once __DIR__ . '/../migrate.php';

$db = Database::getInstance();

ComptabiliteService::seedDefaultChartOfAccounts();

// Clean up previous test runs
$db->exec("UPDATE comptabilite_periodes SET est_cloturee = 0");
$db->exec("DELETE FROM paie_audit_logs");
$db->exec("DELETE FROM paie_regularisation_lignes");
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

// Seed initial environment if needed
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test Paie') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (1, 1, 1, '2024-09-01', '2024-09-30', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, devise, statut) VALUES (1, 1, 'Banque Principale', 'banque', 1000000.00, 'FCFA', 'actif') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO paie_entites_juridiques (id, raison_sociale, sigle) VALUES (1, 'Établissement Principal', 'EP') ON CONFLICT DO NOTHING");

// Seed test staff user
$db->exec("INSERT INTO type_contrat (id_contrat, libelle, type_paiement, prise_en_charge) VALUES (1, 'CDI', 'mensuel', 'Ecole') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (101, 1, 'Professeur', 'Jean', '101ENS', 'jean@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, entite_juridique_id, salaire_base, devise, statut_contrat, version_num) VALUES (201, 101, 1, '2024-01-01', 1, 250000.00, 'FCFA', 'actif', 1) ON CONFLICT DO NOTHING");

// --- TEST 1: Nominal Workflow ---
echo "\n--- TEST 1: Workflow Nominal Paie (Création -> Bulletins V1 -> Compta -> Règlement) ---\n";
$periodeId = PaieWorkflowService::createPeriod(1, 1, 'PAIE-2024-09', 9, 2024, '2024-09-01', '2024-09-30', 1);
assert_test($periodeId > 0, "Période de paie créée ID #{$periodeId}");

$bulletinIds = PaieWorkflowService::generateBulletinsForPeriod($periodeId, 1);
assert_test(count($bulletinIds) > 0, "Génération bulletins V1 réussie (" . count($bulletinIds) . " bulletins)");

$bulletinId = $bulletinIds[0];
$bulletin = PaieBulletin::findById($bulletinId);
assert_test((float)$bulletin['total_brut'] >= 250000.00, "Calcul total brut conforme (" . $bulletin['total_brut'] . ")");

$pieceId = PaieWorkflowService::postAccounting($bulletinId, 1);
assert_test($pieceId > 0, "Comptabilisation au Grand Livre réussie (Pièce ID #{$pieceId})");

$mvtId = PaieWorkflowService::settlePayout($bulletinId, 1, 'virement', 1);
assert_test($mvtId > 0, "Règlement trésorerie effectué (Mouvement ID #{$mvtId})");

$bUpdated = PaieBulletin::findById($bulletinId);
assert_test($bUpdated['statut_comptabilisation'] === 'comptabilise', "Statut comptabilisation 'comptabilise'");
assert_test($bUpdated['statut_reglement'] === 'paye', "Statut règlement 'paye'");

// --- TEST 2: Idempotency Protection (V5) ---
echo "\n--- TEST 2: Idempotence & Double Appel (V5) ---\n";
$pieceId2 = PaieWorkflowService::postAccounting($bulletinId, 1);
assert_test($pieceId2 === 0, "Deuxième comptabilisation traitée de façon idempotente (aucun double enregistrement)");

$mvtId2 = PaieWorkflowService::settlePayout($bulletinId, 1, 'virement', 1);
assert_test($mvtId2 === 0, "Deuxième règlement traité de façon idempotente");

// --- TEST 3: Atomic Re-draw V1 -> V2 with Counterpassation ---
echo "\n--- TEST 3: Re-tirage Atomique V1 -> V2 avec Contrepassation ---\n";
$v2Id = PaieWorkflowService::redrawBulletin($bulletinId, 1, ['salaire_base' => 280000.00]);
assert_test($v2Id > $bulletinId, "Bulletin V2 créé avec succès ID #{$v2Id}");

$v1B = PaieBulletin::findById($bulletinId);
$v2B = PaieBulletin::findById($v2Id);
assert_test($v1B['est_version_active'] == 0, "Bulletin V1 désactivé (est_version_active = 0)");
assert_test($v2B['est_version_active'] == 1, "Bulletin V2 actif (est_version_active = 1)");
assert_test((float)$v2B['salaire_base'] == 280000.00, "Nouveau salaire de base V2 appliqué (280 000 FCFA)");

// --- TEST 4: Closed Accounting Period Protection (V2) ---
echo "\n--- TEST 4: Verrou Période Comptable Clôturée (V2) ---\n";
$db->exec("UPDATE comptabilite_periodes SET est_cloturee = 1 WHERE id = 1");

$blocked = false;
try {
    PaieWorkflowService::redrawBulletin($v2Id, 1, ['salaire_base' => 300000.00]);
} catch (LogicException $e) {
    $blocked = true;
}
assert_test($blocked, "Re-tirage V2 refusé car la période comptable est clôturée.");

// Re-open accounting period for subsequent tests
$db->exec("UPDATE comptabilite_periodes SET est_cloturee = 0 WHERE id = 1");

// --- TEST 5: Regularization N+1 ---
echo "\n--- TEST 5: Régularisation en Période N+1 ---\n";
$periodeN1Id = PaieWorkflowService::createPeriod(1, 1, 'PAIE-2024-10', 10, 2024, '2024-10-01', '2024-10-31', 1);
$reguId = PaieWorkflowService::createRegularizationInN1($bulletinId, $periodeN1Id, 'rappel_salaire', 'Rattrapage hausse salaire', 30000.00, 30000.00, 1);
assert_test($reguId > 0, "Régularisation N+1 créée avec succès ID #{$reguId}");

// --- TEST 6: Teacher Cahier de Texte Hourly Calculation ---
echo "\n--- TEST 6: Calcul des Heures Pédagogiques du Cahier de Texte ---\n";
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, date_cours, contenu_cours) VALUES (301, 1, 101, '2024-09-15', 'Cours Mathématiques') ON CONFLICT DO NOTHING");
$cahierValId = PaieCahierTexteValidation::create([
    'cahier_id' => 301,
    'enseignant_id' => 101,
    'duree_heures' => 4.0,
    'taux_horaire' => 5000.00,
    'statut_validation' => 'valide',
    'valide_par' => 1,
    'valide_le' => '2024-09-16 10:00:00'
]);
assert_test($cahierValId > 0, "Validation heure cahier de texte enregistrée ID #{$cahierValId}");

$computed = PaieCalculationEngine::computeBulletin(
    ['salaire_base' => 200000.00],
    [['id' => $cahierValId, 'duree_heures' => 4.0, 'taux_horaire' => 5000.00]],
    [],
    'DEFAULT'
);
assert_test((float)$computed['total_brut'] == 220000.00, "Calcul du brut incluant 4h x 5000 FCFA d'heures supplémentaires = 220 000 FCFA");

// --- TEST 7: Legacy Facade Import ---
echo "\n--- TEST 7: Façade Salaires Historiques Legacy ---\n";
$db->exec("INSERT INTO salaires (id_salaire, personnel_id, montant, date_paiement) VALUES (501, 101, 150000.00, '2024-09-20') ON CONFLICT DO NOTHING");
$importedCount = LegacySalaryFacade::importLegacySalairesToPaie($periodeId, 1);
assert_test($importedCount >= 0, "Reprise legacy exécutée sans erreur ($importedCount importés)");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS D'INTÉGRATION ET DE SÉCURITÉ DU MODULE PAIE ONT RÉUSSI !\n";
echo "=========================================================================\n";
