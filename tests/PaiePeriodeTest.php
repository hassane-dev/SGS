<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/models/ComptabilitePeriode.php';
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

echo "=== DÉMARRAGE DE LA SUITE DE TESTS : PÉRIODES DE PAIE, ÉDITION & CYCLE DE VIE ===\n";

require_once __DIR__ . '/../migrate.php';

$db = Database::getInstance();

ComptabiliteService::seedDefaultChartOfAccounts();

// Reset test environment
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
$db->exec("DELETE FROM comptabilite_periodes WHERE lycee_id >= 90");
$db->exec("DELETE FROM exercices_financiers WHERE lycee_id >= 90");
$db->exec("DELETE FROM param_lycee WHERE id >= 90");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id >= 700");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 700");

// Seed Lycée A (id = 91) and Lycée B (id = 92) for multi-tenant tests
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (91, 'Lycée Test Paie A') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (92, 'Lycée Test Paie B') ON CONFLICT DO NOTHING");

// Seed Exercices Financiers & Périodes Comptables
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (910, 91, 'Exercice 2025 A', '2025-01-01', '2025-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (920, 92, 'Exercice 2025 B', '2025-01-01', '2025-12-31', 1, 0) ON CONFLICT DO NOTHING");

// Accounting periods
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (911, 91, 910, '2025-01-01', '2025-01-31', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (912, 91, 910, '2025-02-01', '2025-02-28', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (913, 91, 910, '2025-03-01', '2025-03-31', 1) ON CONFLICT DO NOTHING"); // Closed compta period!
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (921, 92, 920, '2025-01-01', '2025-01-31', 0) ON CONFLICT DO NOTHING");

// Users and contracts for Lycée A
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (701, 91, 'Diallo', 'Amadou', '701ENS', 'amadou@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (801, 701, 1, '2025-01-01', 1, 'forfait_fixe', 250000.00, 'FCFA', 'actif', 1) ON CONFLICT DO NOTHING");

// --- TEST 1: Création initiale d'une période en statut brouillon ---
echo "\n--- TEST 1: Création de Période & Statut Initial ---\n";
$p1Id = PaieWorkflowService::createPeriod(91, 911, 'PAIE-2025-01', 1, 2025, '2025-01-01', '2025-01-31', 1);
$p1 = PaiePeriode::findById($p1Id);

assert_test($p1 !== null, "Période de paie #{$p1Id} créée avec succès");
assert_test($p1['statut'] === 'brouillon', "Statut initial de la période est 'brouillon'");
assert_test(PaiePeriode::getLockReason($p1) === null, "Période en 'brouillon' sans bulletins n'est pas verrouillée (lockReason est null)");

// --- TEST 2: Modification d'une période brouillon autorisée ---
echo "\n--- TEST 2: Modification de Période Brouillon (Nominale) ---\n";
$updateSuccess = PaiePeriode::update($p1Id, [
    'periode_comptable_id' => 912,
    'code_periode' => 'PAIE-2025-02',
    'mois' => 2,
    'annee' => 2025,
    'date_debut' => '2025-02-01',
    'date_fin' => '2025-02-28'
]);
assert_test($updateSuccess, "La mise à jour de la période #{$p1Id} retourne true");

$p1Updated = PaiePeriode::findById($p1Id);
assert_test((int)$p1Updated['mois'] === 2 && (int)$p1Updated['annee'] === 2025, "Mois et année mis à jour vers 02/2025");
assert_test($p1Updated['code_periode'] === 'PAIE-2025-02', "Code période mis à jour vers 'PAIE-2025-02'");
assert_test($p1Updated['date_debut'] === '2025-02-01' && $p1Updated['date_fin'] === '2025-02-28', "Dates mises à jour vers 01/02/2025 -> 28/02/2025");

// Remettre $p1Id à 01/2025 sur la période comptable 911
PaiePeriode::update($p1Id, [
    'periode_comptable_id' => 911,
    'code_periode' => 'PAIE-2025-01',
    'mois' => 1,
    'annee' => 2025,
    'date_debut' => '2025-01-01',
    'date_fin' => '2025-01-31'
]);

// --- TEST 3: Unicité (lycee_id, annee, mois) & Détection de Chevauchement ---
echo "\n--- TEST 3: Unicité Mois/Année & Chevauchement de Dates ---\n";
// Créer une 2ème période pour février 2025
$p2Id = PaieWorkflowService::createPeriod(91, 912, 'PAIE-2025-02', 2, 2025, '2025-02-01', '2025-02-28', 1);

// Vérifier l'unicité
$existing = PaiePeriode::findByLyceeAndMoisAnnee(91, 2025, 2);
assert_test((int)$existing['id'] === $p2Id, "findByLyceeAndMoisAnnee trouve bien la période de février 2025");

// Tentative de chevauchement de dates
$hasOverlap = PaiePeriode::hasOverlap(91, '2025-01-15', '2025-02-15', $p2Id);
assert_test($hasOverlap, "hasOverlap détecte le chevauchement avec '2025-01-01 -> 2025-01-31'");

$noOverlap = PaiePeriode::hasOverlap(91, '2025-04-01', '2025-04-30');
assert_test(!$noOverlap, "hasOverlap retourne false pour des dates sans conflit");

// --- TEST 4: Verrouillage si Période Comptable Clôturée ---
echo "\n--- TEST 4: Protection Période Comptable Clôturée ---\n";
$pClosedComptaId = PaieWorkflowService::createPeriod(91, 913, 'PAIE-2025-03', 3, 2025, '2025-03-01', '2025-03-31', 1);
$pClosedCompta = PaiePeriode::findById($pClosedComptaId);
$lockReasonCompta = PaiePeriode::getLockReason($pClosedCompta);

assert_test($lockReasonCompta !== null && strpos($lockReasonCompta, 'comptable') !== false, "getLockReason bloque la période liée à une période comptable clôturée");

// --- TEST 5: Transition Automatique brouillon -> valide lors de la Génération Effective ---
echo "\n--- TEST 5: Transition Automatique 'brouillon' -> 'valide' lors de la Génération ---\n";

// A. La prévisualisation (preview) ne doit PAS modifier le statut brouillon
$previewRes = PaieWorkflowService::previewBulletinsForPeriod($p1Id, null, 91);
$p1AfterPreview = PaiePeriode::findById($p1Id);
assert_test($p1AfterPreview['statut'] === 'brouillon', "Preview conserve la période au statut 'brouillon'");

// B. La génération réelle déclenche le passage à 'valide'
$bulletinIds = PaieWorkflowService::generateBulletinsForPeriod($p1Id, 1, [701]);
assert_test(count($bulletinIds) === 1, "Bulletin généré pour le salarié #701");

$p1AfterGen = PaiePeriode::findById($p1Id);
assert_test($p1AfterGen['statut'] === 'valide', "La génération réelle de bulletins fait passer la période au statut 'valide'");

// --- TEST 6: Refus de Modification sur Période avec Bulletins ou Statut Valide ---
echo "\n--- TEST 6: Refus de Modification d'une Période Engagée ---\n";
assert_test(PaiePeriode::hasBulletins($p1Id), "PaiePeriode::hasBulletins retourne true pour la période #{$p1Id}");

$lockReasonValide = PaiePeriode::getLockReason($p1AfterGen);
assert_test($lockReasonValide !== null, "getLockReason retourne un motif de verrouillage pour la période validée : '{$lockReasonValide}'");

// --- TEST 7: Refus de Modification sur Période Clôturée ---
echo "\n--- TEST 7: Refus de Modification sur Période Clôturée ---\n";
PaiePeriode::updateStatus($p2Id, 'cloture', 1);
$p2Closed = PaiePeriode::findById($p2Id);
assert_test($p2Closed['statut'] === 'cloture', "Période #{$p2Id} est passée au statut 'cloture'");

$lockReasonCloture = PaiePeriode::getLockReason($p2Closed);
assert_test($lockReasonCloture !== null && strpos($lockReasonCloture, 'clôturée') !== false, "getLockReason bloque la période au statut 'cloture'");

// --- TEST 8: Multi-Tenant Isolation ---
echo "\n--- TEST 8: Multi-Tenant Isolation (Lycée A vs Lycée B) ---\n";
$pBId = PaieWorkflowService::createPeriod(92, 921, 'PAIE-2025-01-B', 1, 2025, '2025-01-01', '2025-01-31', 1);
$pB = PaiePeriode::findById($pBId);

assert_test((int)$pB['lycee_id'] === 92, "Période du Lycée B a lycee_id = 92");
assert_test((int)$p1['lycee_id'] === 91, "Période du Lycée A a lycee_id = 91");

// Insérer une période identique en mois/année sur Lycée B ne doit pas entrer en conflit avec Lycée A
$pBFebId = PaieWorkflowService::createPeriod(92, 921, 'PAIE-2025-02-B', 2, 2025, '2025-02-01', '2025-02-28', 1);
assert_test($pBFebId > 0, "Création de la période 02/2025 sur le Lycée B réussie malgré l'existence de 02/2025 sur le Lycée A");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS DE L'ÉDITION ET DU CYCLE DE VIE DES PÉRIODES ONT RÉUSSI !\n";
echo "=========================================================================\n";
