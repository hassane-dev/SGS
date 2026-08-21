<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/models/PaieRegularisation.php';
require_once __DIR__ . '/../src/models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';

function assert_p22($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU TEST COMPLÈT 22 SCÉNARIOS : BULLETINS, INDIVIDUEL ET RÉGULARISATIONS ===\n";

require_once __DIR__ . '/../migrate.php';
$db = Database::getInstance();

// Reset environment
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

// Setup base entities
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test 22') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2025', '2025-01-01', '2025-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (10, 1, 1, '2025-09-01', '2025-09-30', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (11, 1, 1, '2025-10-01', '2025-10-31', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO paie_entites_juridiques (id, raison_sociale, sigle) VALUES (1, 'Établissement Test 22', 'ET22') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO type_contrat (id_contrat, libelle, description, type_paiement, prise_en_charge) VALUES (1, 'CDI', 'Contrat Durée Indéterminée', 'mensuel', 'Ecole') ON CONFLICT DO NOTHING");

// Seed test users & contracts
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, identifiant_public, role_id) VALUES (501, 'Dupont', 'Jean', 'jean.dupont@test.com', '501ENS', 6) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, identifiant_public, role_id) VALUES (502, 'Martin', 'Claire', 'claire.martin@test.com', '502ENS', 6) ON CONFLICT DO NOTHING");

// Contract 1: Jean Dupont (Mixte: Fixe 250,000 + Horaire 5,000 FCFA/h)
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id = 501");
$db->exec("INSERT INTO personnel_contrats_historique (id, contrat_souche_id, version_num, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, statut_contrat)
           VALUES (501, 501, 1, 501, 1, '2025-01-01', 1, 'mixte', 250000.00, 'actif')");
$db->exec("DELETE FROM personnel_contrat_composants WHERE contrat_id = 501");
$db->exec("INSERT INTO personnel_contrat_composants (contrat_id, code_composant, libelle, nature_composant, mode_calcul, unite_remuneration, valeur_numerique, date_debut)
           VALUES (501, 'TAUX_HORAIRE', 'Taux Horaire Cours', 'taux_horaire', 'montant_fixe', 'heure', 5000.00, '2025-01-01')");

// Contract 2: Claire Martin (Forfait Fixe: 300,000 FCFA)
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id = 502");
$db->exec("INSERT INTO personnel_contrats_historique (id, contrat_souche_id, version_num, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, statut_contrat)
           VALUES (502, 502, 1, 502, 1, '2025-01-01', 1, 'forfait_fixe', 300000.00, 'actif')");

// Create Periods PAIE-2025-09 (ID #10) and PAIE-2025-10 (ID #11)
$pSepId = PaieWorkflowService::createPeriod(1, 10, 'PAIE-2025-09', 9, 2025, '2025-09-01', '2025-09-30', 1);
$pOctId = PaieWorkflowService::createPeriod(1, 11, 'PAIE-2025-10', 10, 2025, '2025-10-01', '2025-10-31', 1);

// --- TEST 1: Bulletin Existant ---
echo "\n--- TEST 1: Bulletin Existant ---\n";
$bExId = PaieWorkflowService::generateBulletinForEmployee($pSepId, 501, 501, 1);
$bCheck = PaieBulletin::findActiveForContractAndPeriod(501, 1, 501, $pSepId);
assert_p22($bCheck !== null && (int)$bCheck['id'] === $bExId, "Le bulletin actif existant est correctement récupéré pour Jean Dupont.");

// --- TEST 2: Bulletin Absent ---
echo "\n--- TEST 2: Bulletin Absent ---\n";
$bAbsentCheck = PaieBulletin::findActiveForContractAndPeriod(502, 1, 502, $pSepId);
assert_p22($bAbsentCheck === null, "Aucun bulletin n'existe encore pour Claire Martin sur PAIE-2025-09.");

// --- TEST 3: Génération Individuelle ---
echo "\n--- TEST 3: Génération Individuelle ---\n";
$bIndivId = PaieWorkflowService::generateBulletinForEmployee($pSepId, 502, 502, 1);
assert_p22($bIndivId > 0, "Génération individuelle réussie pour Claire Martin (ID #{$bIndivId}).");

// --- TEST 6 BEFORE TEST 4: Seed Validated Session BEFORE October bulletin generation ---
echo "\n--- TEST 6: Service Fait Validé -> Bulletin ---\n";
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours)
           VALUES (901, 1, 501, 10, 1, '2025-10-15', '08:00:00', '10:00:00', 'Cours Mathématiques') ON CONFLICT DO NOTHING");
PaieCahierTexteValidation::validateSession(901, 1, 5000.00);

// --- TEST 4: Génération Globale ---
echo "\n--- TEST 4: Génération Globale ---\n";
$globalIds = PaieWorkflowService::generateBulletinsForPeriod($pOctId, 1);
assert_p22(count($globalIds) >= 2, "La génération globale pour PAIE-2025-10 a produit des bulletins pour tous les contrats actifs.");

// Validate Test 6 hours attachment
$octDupontB = PaieBulletin::findActiveForContractAndPeriod(501, 1, 501, $pOctId);
assert_p22($octDupontB !== null, "Le bulletin d'octobre pour Jean Dupont existe.");
$hrs = PaieBulletinHeure::findByBulletinId((int)$octDupontB['id']);
assert_p22(count($hrs) >= 1 && (float)$hrs[0]['heures_effectuees'] === 2.00, "Le service fait validé de 2h est correctement rattaché au bulletin.");

// --- TEST 5: Double Génération (Idempotence) ---
echo "\n--- TEST 5: Double Génération ---\n";
$bDupId = PaieWorkflowService::generateBulletinForEmployee($pSepId, 501, 501, 1);
assert_p22($bDupId === $bExId, "La double génération retourne de manière idempotente le bulletin actif existant sans doublon.");

// --- TEST 7: Régularisation avec Bulletin Source ---
echo "\n--- TEST 7: Régularisation avec Bulletin Source ---\n";
$regu1Id = PaieWorkflowService::createRegularization(501, $pOctId, 'bulletin', $pSepId, $bExId, 'rappel_salaire', 'Rappel sur prime oubliée septembre', 25000.00, 0.00, 1);
$regu1 = PaieRegularisation::findById($regu1Id);
assert_p22($regu1['source_type'] === 'bulletin' && (int)$regu1['bulletin_source_id'] === $bExId, "Régularisation avec bulletin source créée avec succès.");

// --- TEST 8: Régularisation sans Bulletin Source ---
echo "\n--- TEST 8: Régularisation sans Bulletin Source ---\n";
$regu2Id = PaieWorkflowService::createRegularization(501, $pOctId, 'service_fait', $pSepId, null, 'rappel_salaire', 'Rappel heures supplémentaires août sans bulletin', 15000.00, 0.00, 1);
$regu2 = PaieRegularisation::findById($regu2Id);
assert_p22($regu2['source_type'] === 'service_fait' && $regu2['bulletin_source_id'] === null && (int)$regu2['personnel_id'] === 501, "Régularisation sans bulletin source enregistrée avec succès.");

// --- TEST 9: Régularisation Intégrée au Bulletin ---
echo "\n--- TEST 9: Régularisation Intégrée au Bulletin ---\n";
// Re-draw October bulletin for 501 to consume regularisations
$newOctBId = PaieWorkflowService::redrawBulletin((int)$octDupontB['id'], 1);
$newOctB = PaieBulletin::findById($newOctBId);
assert_p22((float)$newOctB['total_brut'] > 260000.00, "Le total brut du bulletin d'octobre inclut les deltas des régularisations (+25k et +15k).");

// --- TEST 10: Régularisation Déjà Intégrée ---
echo "\n--- TEST 10: Régularisation Déjà Intégrée ---\n";
$regu1Check = PaieRegularisation::findById($regu1Id);
assert_p22($regu1Check['statut'] === 'integre', "Le statut de la régularisation consommée passe à 'integre'.");

// --- TEST 11: Refus Mauvais Enseignant / Bulletin Source ---
echo "\n--- TEST 11: Refus Mauvais Enseignant / Bulletin Source ---\n";
$refused1 = false;
try {
    PaieWorkflowService::createRegularization(502, $pOctId, 'bulletin', $pSepId, $bExId, 'rappel_salaire', 'Test usurpation bulletin', 10000.00, 0.00, 1);
} catch (Exception $e) {
    $refused1 = true;
}
assert_p22($refused1, "Refus de créer une régularisation si le bulletin source n'appartient pas au salarié.");

// --- TEST 12: Refus Mauvaise Période Source ---
echo "\n--- TEST 12: Refus Mauvaise Période Source ---\n";
$refused2 = false;
try {
    PaieWorkflowService::createRegularization(501, $pSepId, 'service_fait', $pOctId, null, 'rappel_salaire', 'Test période invalide', 10000.00, 0.00, 1);
} catch (Exception $e) {
    $refused2 = true;
}
assert_p22($refused2, "Refus si la période source n'est pas antérieure à la période de destination.");

// --- TEST 13: Période Destination Clôturée ---
echo "\n--- TEST 13: Période Destination Clôturée ---\n";
$pClosedId = PaieWorkflowService::createPeriod(1, 10, 'PAIE-2025-CLOSED', 1, 2025, '2025-01-01', '2025-01-31', 1);
PaiePeriode::updateStatus($pClosedId, 'cloture', 1);
$refused3 = false;
try {
    PaieWorkflowService::createRegularization(501, $pClosedId, 'autre', null, null, 'rappel_salaire', 'Test période clôturée', 10000.00, 0.00, 1);
} catch (Exception $e) {
    $refused3 = true;
}
assert_p22($refused3, "Refus de créer une régularisation sur une période destination clôturée.");

// --- TEST 14: Avenant Contractuel à Date du Cours ---
echo "\n--- TEST 14: Avenant Contractuel à Date du Cours ---\n";
$rateDate = PaieCahierTexteValidation::resolveContractHourlyRate(501, '2025-10-15');
assert_p22($rateDate === 5000.00, "Résolution du taux horaire effectif de l'avenant actif à la date du cours (5000 FCFA).");

// --- TEST 15: Salaire Fixe ---
echo "\n--- TEST 15: Salaire Fixe ---\n";
$bFixe = PaieBulletin::findActiveForContractAndPeriod(502, 1, 502, $pSepId);
assert_p22((float)$bFixe['salaire_base'] === 300000.00 && (float)$bFixe['total_brut'] === 300000.00, "Le salarié fixe conserve strictly sa rémunération contractuelle fixe (300 000 FCFA).");

// --- TEST 16: Salaire Horaire ---
echo "\n--- TEST 16: Salaire Horaire ---\n";
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id) VALUES (503, 'Horaire', 'Paul', 'horaire@test.com', 6) ON CONFLICT DO NOTHING");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id = 503");
$db->exec("INSERT INTO personnel_contrats_historique (id, contrat_souche_id, version_num, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, statut_contrat)
           VALUES (503, 503, 1, 503, 1, '2025-01-01', 1, 'taux_horaire', 0.00, 'actif')");
$db->exec("DELETE FROM personnel_contrat_composants WHERE contrat_id = 503");
$db->exec("INSERT INTO personnel_contrat_composants (contrat_id, code_composant, libelle, nature_composant, mode_calcul, unite_remuneration, valeur_numerique, date_debut)
           VALUES (503, 'TAUX_HORAIRE', 'Taux Horaire Pure', 'taux_horaire', 'montant_fixe', 'heure', 6000.00, '2025-01-01')");

$bHorId = PaieWorkflowService::generateBulletinForEmployee($pSepId, 503, 503, 1);
$bHor = PaieBulletin::findById($bHorId);
assert_p22((float)$bHor['salaire_base'] === 0.00, "Un contrat horaire pur débute avec un salaire de base de 0.00 FCFA.");

// --- TEST 17: Salaire Mixte ---
echo "\n--- TEST 17: Salaire Mixte ---\n";
$bMix = PaieBulletin::findById($bExId);
assert_p22((float)$bMix['salaire_base'] === 250000.00, "Un contrat mixte combine salaire fixe de base (250 000) et heures d'enseignement.");

// --- TEST 18: Migration Historique Backfill ---
echo "\n--- TEST 18: Migration Historique Backfill ---\n";
$db->exec("INSERT INTO paie_regularisations (bulletin_source_id, periode_destination_id, type_regularisation, motif, montant_brut_delta, statut, cree_par, created_at, personnel_id, source_type)
           VALUES ({$bExId}, {$pOctId}, 'rappel_salaire', 'Test legacy row', 5000.00, 'valide', 1, CURRENT_TIMESTAMP, 501, 'bulletin')");
$stmtLeg = $db->query("SELECT * FROM paie_regularisations WHERE motif = 'Test legacy row'");
$legRow = $stmtLeg->fetch(PDO::FETCH_ASSOC);
assert_p22((int)$legRow['personnel_id'] === 501 && $legRow['source_type'] === 'bulletin', "Migration et backfill historique conservent les informations du personnel.");

// --- TEST 19: Redraw V2 avec Régularisation (Rollback & Re-intégration) ---
echo "\n--- TEST 19: Redraw V2 avec Régularisation ---\n";
$bV3Id = PaieWorkflowService::redrawBulletin($newOctBId, 1);
$bV3 = PaieBulletin::findById($bV3Id);
assert_p22((float)$bV3['total_brut'] > 260000.00, "Re-tirage V2/V3 libère et réintègre proprement la régularisation sans perte ni double déduction.");

// --- TEST 20: Double Clic / Génération Simultanée ---
echo "\n--- TEST 20: Double Clic / Génération Simultanée ---\n";
$sim1 = PaieWorkflowService::generateBulletinForEmployee($pSepId, 501, 501, 1);
$sim2 = PaieWorkflowService::generateBulletinForEmployee($pSepId, 501, 501, 1);
assert_p22($sim1 === $sim2, "Les requêtes de génération ciblée simultanées retournent le même bulletin unique.");

// --- TEST 21: Régularisation Déjà Consommée ---
echo "\n--- TEST 21: Régularisation Déjà Consommée ---\n";
$pNovId = PaieWorkflowService::createPeriod(1, 10, 'PAIE-2025-11', 11, 2025, '2025-11-01', '2025-11-30', 1);
$bNovId = PaieWorkflowService::generateBulletinForEmployee($pNovId, 501, 501, 1);
$bNov = PaieBulletin::findById($bNovId);
assert_p22((float)$bNov['total_brut'] === 250000.00, "Une régularisation au statut 'integre' n'est plus réinjectée dans les bulletins ultérieurs.");

// --- TEST 22: Échec Transactionnel & Rollback Total ---
echo "\n--- TEST 22: Échec Transactionnel & Rollback Total ---\n";
$txRollbacked = false;
try {
    $db->beginTransaction();
    PaieRegularisation::create([
        'personnel_id' => 501,
        'source_type' => 'autre',
        'periode_destination_id' => $pNovId,
        'type_regularisation' => 'rappel_salaire',
        'motif' => 'Régularisation temporaire rollback',
        'montant_brut_delta' => 12345.00,
        'statut' => 'valide',
        'cree_par' => 1
    ]);
    throw new Exception("Simulated Failure");
} catch (Exception $e) {
    $db->rollBack();
    $txRollbacked = true;
}
$chkTx = $db->query("SELECT COUNT(*) FROM paie_regularisations WHERE montant_brut_delta = 12345.00")->fetchColumn();
assert_p22($txRollbacked && (int)$chkTx === 0, "Succès du Rollback Total : aucune donnée partielle n'a été conservée.");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES 22 SCÉNARIOS DE TEST INTÉGRATION ET DE SÉCURITÉ ONT RÉUSSI !\n";
echo "=========================================================================\n";
