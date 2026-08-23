<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';
require_once __DIR__ . '/../src/services/PaieCalculationEngine.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';

$test_counter = 0;
$assertion_counter = 0;

function assert_test($condition, $message) {
    global $assertion_counter;
    $assertion_counter++;
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU TEST DE SÉCURITÉ ET DE COHÉRENCE MÉTIER COMPLET (12 SCÉNARIOS) ===\n";

require_once __DIR__ . '/../migrate.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

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
$db->exec("DELETE FROM cahier_texte WHERE personnel_id >= 800 OR personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%test80%')");
$db->exec("DELETE FROM paie_bulletin_lignes");
$db->exec("DELETE FROM paie_bulletins");
$db->exec("DELETE FROM paie_periodes");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%test80%') OR personnel_id >= 800");
$db->exec("DELETE FROM personnel_details WHERE personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%test80%') OR personnel_id >= 800");
$db->exec("DELETE FROM utilisateurs WHERE email LIKE '%test80%' OR id_user >= 800");

// Seed test schools & general school params
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée A (Principal)') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (2, 'Lycée B (Secondaire)') ON CONFLICT DO NOTHING");

ParamGeneral::save(['lycee_id' => 1, 'monnaie' => 'FCFA']);
ParamGeneral::save(['lycee_id' => 2, 'monnaie' => 'FCFA']);

$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024 Lycée A', '2024-01-01', '2024-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (2, 2, 'Exercice 2024 Lycée B', '2024-01-01', '2024-12-31', 1, 0) ON CONFLICT DO NOTHING");

$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (1, 1, 1, '2024-12-01', '2024-12-31', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (2, 2, 2, '2024-12-01', '2024-12-31', 0) ON CONFLICT DO NOTHING");

$db->exec("INSERT INTO type_contrat (id_contrat, libelle, type_paiement, prise_en_charge) VALUES (1, 'CDI', 'mensuel', 'Ecole') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO matieres (id_matiere, nom_matiere) VALUES (1, 'Mathématiques') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, lycee_id) VALUES (1, '6eme', 'A', '1', 1) ON CONFLICT DO NOTHING");

// Create payroll periods for Lycée A (1) and Lycée B (2)
$periodeIdLyceeA = PaieWorkflowService::createPeriod(1, 1, 'PAIE-LYC-A-2024-12', 12, 2024, '2024-12-01', '2024-12-31', 1);
$periodeIdLyceeB = PaieWorkflowService::createPeriod(2, 2, 'PAIE-LYC-B-2024-12', 12, 2024, '2024-12-01', '2024-12-31', 1);

// --- SCÉNARIO 1: Création d'un nouveau personnel DRH sans erreur HY093 ---
$test_counter++;
echo "\n--- SCÉNARIO 1: Création d'un nouveau personnel DRH sans erreur HY093 ---\n";
$pFixeId = PersonnelService::savePersonnel([
    'nom' => 'Diallo',
    'prenom' => 'Mamadou',
    'email' => 'mamadou.diallo.test801@etablissement.com',
    'mot_de_passe' => 'MotDePasse801!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 200000.00,
    'statut_rh' => 'en_activite'
], 1);
assert_test($pFixeId > 0, "Création du personnel DRH Mamadou Diallo réussie sans déclencher SQLSTATE[HY093]");

// --- SCÉNARIO 2: Contrat actif + Séances validées -> Présence dans Préparer et heures exactes ---
$test_counter++;
echo "\n--- SCÉNARIO 2: Contrat actif + Séances validées -> Présence et heures exactes ---\n";
$pHoraireId = PersonnelService::savePersonnel([
    'nom' => 'Traore',
    'prenom' => 'Ousmane',
    'email' => 'ousmane.traore.test802@etablissement.com',
    'mot_de_passe' => 'MotDePasse802!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 5000.00,
    'statut_rh' => 'en_activite'
], 1);

$cHoraire = PersonnelContractService::getActiveContract($pHoraireId);
PersonnelContractService::saveContract([
    'id' => $cHoraire['id'],
    'personnel_id' => $pHoraireId,
    'type_contrat_id' => 1,
    'entite_juridique_id' => $cHoraire['entite_juridique_id'] ?? 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 5000.00,
    'mode_calcul_principal' => 'taux_horaire',
    'statut_contrat' => 'actif'
], 1);

// Add and validate 2 sessions (2h + 3h = 5h)
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (8020, 1, {$pHoraireId}, 1, 1, '2024-12-10', '08:00', '10:00', 'Séance 1')");
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (8021, 1, {$pHoraireId}, 1, 1, '2024-12-12', '14:00', '17:00', 'Séance 2')");
PaieCahierTexteValidation::validateSession(8020, 1, 5000.00);
PaieCahierTexteValidation::validateSession(8021, 1, 5000.00);

$prepListLyceeA = PaieWorkflowService::getEligibleContractsWithServiceFaitStatus($periodeIdLyceeA, 1);
$ousmanePrep = null;
foreach ($prepListLyceeA as $item) {
    if ((int)$item['personnel_id'] === $pHoraireId) $ousmanePrep = $item;
}

assert_test($ousmanePrep !== null, "Ousmane Traore est présent dans la liste de préparation des bulletins");
assert_test((float)$ousmanePrep['total_heures_validees'] == 5.0, "5.0 heures validées affichées exactement");

// --- SCÉNARIO 3: Contrat horaire + Aucune séance -> 0h et 0 FCFA brut ---
$test_counter++;
echo "\n--- SCÉNARIO 3: Contrat horaire + Aucune séance -> 0h et 0 FCFA brut ---\n";
$pHoraireSansServiceId = PersonnelService::savePersonnel([
    'nom' => 'Sow',
    'prenom' => 'Ibrahima',
    'email' => 'ibrahima.sow.test804@etablissement.com',
    'mot_de_passe' => 'MotDePasse804!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 6000.00,
    'statut_rh' => 'en_activite'
], 1);

$cHoraireSansService = PersonnelContractService::getActiveContract($pHoraireSansServiceId);
PersonnelContractService::saveContract([
    'id' => $cHoraireSansService['id'],
    'personnel_id' => $pHoraireSansServiceId,
    'type_contrat_id' => 1,
    'entite_juridique_id' => $cHoraireSansService['entite_juridique_id'] ?? 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 6000.00,
    'mode_calcul_principal' => 'taux_horaire',
    'statut_contrat' => 'actif'
], 1);

$bHoraireSansServiceId = PaieWorkflowService::generateBulletinForEmployee($periodeIdLyceeA, $pHoraireSansServiceId, (int)$cHoraireSansService['id'], 1);
$bHoraireSansService = PaieBulletin::findById($bHoraireSansServiceId);
$heuresSansService = PaieBulletinHeure::findByBulletinId($bHoraireSansServiceId);

assert_test(count($heuresSansService) === 0, "Contrat horaire sans service fait -> 0 ligne d'heures générée");
assert_test((float)$bHoraireSansService['total_brut'] == 0.00, "Contrat horaire sans service fait -> Salaire brut de 0.00 FCFA");

// --- SCÉNARIO 4: Contrat horaire + Séances NON validées -> 0h et 0 FCFA brut ---
$test_counter++;
echo "\n--- SCÉNARIO 4: Contrat horaire + Séances NON validées -> 0h et 0 FCFA brut ---\n";
$pNonValideId = PersonnelService::savePersonnel([
    'nom' => 'Koffi',
    'prenom' => 'Ablan',
    'email' => 'ablan.koffi.test805@etablissement.com',
    'mot_de_passe' => 'MotDePasse805!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 5500.00,
    'statut_rh' => 'en_activite'
], 1);

$cNonValide = PersonnelContractService::getActiveContract($pNonValideId);
PersonnelContractService::saveContract([
    'id' => $cNonValide['id'],
    'personnel_id' => $pNonValideId,
    'type_contrat_id' => 1,
    'entite_juridique_id' => $cNonValide['entite_juridique_id'] ?? 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 5500.00,
    'mode_calcul_principal' => 'taux_horaire',
    'statut_contrat' => 'actif'
], 1);

$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (8050, 1, {$pNonValideId}, 1, 1, '2024-12-14', '08:00', '11:00', 'Séance non validée')");

$bNonValideId = PaieWorkflowService::generateBulletinForEmployee($periodeIdLyceeA, $pNonValideId, (int)$cNonValide['id'], 1);
$bNonValide = PaieBulletin::findById($bNonValideId);
$heuresNonValide = PaieBulletinHeure::findByBulletinId($bNonValideId);

assert_test(count($heuresNonValide) === 0, "Séance non validée dans le cahier de texte -> 0 heure de paie");
assert_test((float)$bNonValide['total_brut'] == 0.00, "Rémunération horaire brute = 0.00 FCFA pour séance non validée");

// --- SCÉNARIO 5: Contrat Forfait Fixe -> Salaire fixe contractuel sans heures fictives ---
$test_counter++;
echo "\n--- SCÉNARIO 5: Contrat Forfait Fixe -> Salaire fixe contractuel sans heures fictives ---\n";
$bFixeId = PaieWorkflowService::generateBulletinForEmployee($periodeIdLyceeA, $pFixeId, (int)PersonnelContractService::getActiveContract($pFixeId)['id'], 1);
$bFixe = PaieBulletin::findById($bFixeId);
$heuresFixe = PaieBulletinHeure::findByBulletinId($bFixeId);

assert_test(count($heuresFixe) === 0, "Contrat forfait fixe -> Aucune ligne d'heures fictive générée");
assert_test((float)$bFixe['total_brut'] == 200000.00, "Contrat forfait fixe -> Salaire brut égal au fixe contractuel de 200 000.00 FCFA");

// --- SCÉNARIO 6: Contrat Taux Horaire -> Rémunération basée sur heures validées ---
$test_counter++;
echo "\n--- SCÉNARIO 6: Contrat Taux Horaire -> Rémunération basée sur heures validées ---\n";
$bHoraireId = PaieWorkflowService::generateBulletinForEmployee($periodeIdLyceeA, $pHoraireId, (int)$cHoraire['id'], 1);
$bHoraire = PaieBulletin::findById($bHoraireId);
assert_test((float)$bHoraire['total_brut'] == 25000.00, "5h x 5000 FCFA = 25 000.00 FCFA brut (sans base fixe fictive)");

// --- SCÉNARIO 7: Contrat Mixte -> Fixe contractuel + uniquement heures validées ---
$test_counter++;
echo "\n--- SCÉNARIO 7: Contrat Mixte -> Fixe contractuel + heures validées ---\n";
$pMixteId = PersonnelService::savePersonnel([
    'nom' => 'Yao',
    'prenom' => 'Koffi',
    'email' => 'koffi.yao.test803@etablissement.com',
    'mot_de_passe' => 'MotDePasse803!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 150000.00,
    'statut_rh' => 'en_activite'
], 1);

$cMixte = PersonnelContractService::getActiveContract($pMixteId);
PersonnelContractService::saveContract([
    'id' => $cMixte['id'],
    'personnel_id' => $pMixteId,
    'type_contrat_id' => 1,
    'entite_juridique_id' => $cMixte['entite_juridique_id'] ?? 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 150000.00,
    'mode_calcul_principal' => 'mixte',
    'statut_contrat' => 'actif'
], 1);

$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (8030, 1, {$pMixteId}, 1, 1, '2024-12-15', '08:00', '12:00', 'Séance mixte 4h')");
PaieCahierTexteValidation::validateSession(8030, 1, 4000.00);

// Run Preview BEFORE generating bulletin to test dry-run match
$previewAllBefore = PaieWorkflowService::previewBulletinsForPeriod($periodeIdLyceeA, null, 1);
$previewSelBefore = PaieWorkflowService::previewBulletinsForPeriod($periodeIdLyceeA, [$pMixteId], 1);

$itemAllBefore = null;
foreach ($previewAllBefore['items'] as $item) {
    if ((int)$item['personnel_id'] === $pMixteId) $itemAllBefore = $item;
}
$itemSelBefore = $previewSelBefore['items'][0] ?? null;

// Now generate bulletin
$bMixteId = PaieWorkflowService::generateBulletinForEmployee($periodeIdLyceeA, $pMixteId, (int)$cMixte['id'], 1);
$bMixte = PaieBulletin::findById($bMixteId);

assert_test((float)$bMixte['total_brut'] == 166000.00, "Calcul mixte : 150 000 FCFA fixe + (4h x 4000 FCFA) = 166 000 FCFA brut");
assert_test((float)$itemAllBefore['total_brut'] == (float)$bMixte['total_brut'], "Montant brut Preview (" . $itemAllBefore['total_brut'] . ") est EXACTEMENT ÉGAL au bulletin généré (" . $bMixte['total_brut'] . ")");
assert_test((float)$itemAllBefore['net_a_payer'] == (float)$bMixte['net_a_payer'], "Montant net Preview (" . $itemAllBefore['net_a_payer'] . ") est EXACTEMENT ÉGAL au bulletin généré (" . $bMixte['net_a_payer'] . ")");

// --- SCÉNARIO 8: Exclusion stricte des contrats expirés, annulés ou suspendus ---
$test_counter++;
echo "\n--- SCÉNARIO 8: Exclusion stricte des contrats expirés, annulés ou suspendus ---\n";
$pInactifId = PersonnelService::savePersonnel([
    'nom' => 'Bamba',
    'prenom' => 'Sekou',
    'email' => 'sekou.bamba.test807@etablissement.com',
    'mot_de_passe' => 'MotDePasse807!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2023-01-01',
    'salaire_base' => 180000.00,
    'statut_rh' => 'en_activite'
], 1);

$cInactif = PersonnelContractService::getActiveContract($pInactifId);
PersonnelContractService::cancelContract((int)$cInactif['id'], $pInactifId, 'Annulation test pour motif légal', 1);

$eligibleList = PersonnelContractService::getEligibleContractsForPeriod($periodeIdLyceeA, 1);
$pidsEligibles = array_map(function($c) { return (int)$c['personnel_id']; }, $eligibleList);

assert_test(!in_array($pInactifId, $pidsEligibles, true), "Le personnel Sekou Bamba au contrat annulé est STRICTEMENT EXCLU de l'éligibilité");

// --- SCÉNARIO 9: Exclusion stricte du personnel sans contrat ---
$test_counter++;
echo "\n--- SCÉNARIO 9: Exclusion stricte du personnel sans contrat ---\n";
$db->exec("INSERT INTO utilisateurs (nom, prenom, email, role_id, lycee_id, actif, mot_de_passe) VALUES ('SansContrat', 'Jean', 'sans.contrat.test808@etablissement.com', 6, 1, 1, 'Pass123!')");
$pSansContratId = (int)$db->lastInsertId();

$eligibleListAfter = PersonnelContractService::getEligibleContractsForPeriod($periodeIdLyceeA, 1);
$pidsEligiblesAfter = array_map(function($c) { return (int)$c['personnel_id']; }, $eligibleListAfter);

assert_test(!in_array($pSansContratId, $pidsEligiblesAfter, true), "Le personnel sans contrat est STRICTEMENT EXCLU de l'éligibilité paie");

// --- SCÉNARIO 10: Équivalence rigoureuse Preview vs Génération (Individuelle, Selection, All) ---
$test_counter++;
echo "\n--- SCÉNARIO 10: Équivalence Preview vs Génération (Individuelle, Selection, All) ---\n";
assert_test($itemAllBefore !== null && $itemSelBefore !== null, "Koffi Yao présent dans Preview All et Preview Selection");
assert_test((float)$itemAllBefore['total_brut'] == (float)$itemSelBefore['total_brut'], "Scope All (brut " . $itemAllBefore['total_brut'] . ") est STRICTEMENT ÉGAL à Scope Selection (brut " . $itemSelBefore['total_brut'] . ")");
assert_test((float)$itemAllBefore['net_a_payer'] == (float)$itemSelBefore['net_a_payer'], "Scope All (net " . $itemAllBefore['net_a_payer'] . ") est STRICTEMENT ÉGAL à Scope Selection (net " . $itemSelBefore['net_a_payer'] . ")");

// --- SCÉNARIO 11: Isolation Multi-Établissement (Lycée 1 vs Lycée 2 vs lycee_id NULL) ---
$test_counter++;
echo "\n--- SCÉNARIO 11: Isolation Multi-Établissement (Lycée 1 vs Lycée 2 vs lycee_id NULL) ---\n";
$pLyceeBId = PersonnelService::savePersonnel([
    'nom' => 'Konan',
    'prenom' => 'Bertin',
    'email' => 'bertin.konan.test806@etablissement.com',
    'mot_de_passe' => 'MotDePasse806!',
    'role_id' => 6,
    'lycee_id' => 2, // Lycée B
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 180000.00,
    'statut_rh' => 'en_activite'
], 1);

$db->exec("INSERT INTO utilisateurs (nom, prenom, email, role_id, lycee_id, actif, mot_de_passe) VALUES ('Unassigned', 'User', 'unassigned.user809@etablissement.com', 6, NULL, 1, 'Pass123!')");
$pUnassignedId = (int)$db->lastInsertId();
PersonnelContractService::saveContract([
    'personnel_id' => $pUnassignedId,
    'type_contrat_id' => 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 150000.00,
    'devise' => 'FCFA',
    'statut_contrat' => 'actif'
], 1);

$eligibleLyceeA = PersonnelContractService::getEligibleContractsForPeriod($periodeIdLyceeA, 1);
$eligiblePidsA = array_map(function($c) { return (int)$c['personnel_id']; }, $eligibleLyceeA);

$eligibleLyceeB = PersonnelContractService::getEligibleContractsForPeriod($periodeIdLyceeB, 2);
$eligiblePidsB = array_map(function($c) { return (int)$c['personnel_id']; }, $eligibleLyceeB);

assert_test(!in_array($pLyceeBId, $eligiblePidsA, true), "Bertin Konan (Lycée 2) est STRICTEMENT EXCLU de la paie du Lycée 1");
assert_test(in_array($pLyceeBId, $eligiblePidsB, true), "Bertin Konan est présent uniquement dans la paie du Lycée 2");
assert_test(!in_array($pUnassignedId, $eligiblePidsA, true), "User non affecté (lycee_id=NULL) est STRICTEMENT EXCLU de la paie du Lycée 1");
assert_test(!in_array($pUnassignedId, $eligiblePidsB, true), "User non affecté (lycee_id=NULL) est STRICTEMENT EXCLU de la paie du Lycée 2");

// --- SCÉNARIO 12: Chaîne DRH complète Avenant, Modification et Anti-Chevauchement sans SQLSTATE[HY093] ---
$test_counter++;
echo "\n--- SCÉNARIO 12: Chaîne DRH Avenant, Modification, Anti-chevauchement sans SQLSTATE[HY093] ---\n";

// Avenant creation
PersonnelContractService::saveContract([
    'personnel_id' => $pFixeId,
    'type_contrat_id' => 1,
    'date_debut' => '2024-06-01',
    'salaire_base' => 220000.00,
    'type_avenant' => 'augmentation_salaire',
    'avenant_numero' => 'AV-2024-001',
    'statut_contrat' => 'actif'
], 1);

$contractsFixe = PersonnelContractService::getContractsForPersonnel($pFixeId);
assert_test(count($contractsFixe) >= 2, "Création d'avenant DRH réussie sans SQLSTATE[HY093]");

// Update active contract
$latestContract = PersonnelContractService::getActiveContract($pFixeId);
PersonnelContractService::saveContract([
    'id' => $latestContract['id'],
    'personnel_id' => $pFixeId,
    'type_contrat_id' => 1,
    'date_debut' => '2024-06-01',
    'salaire_base' => 230000.00,
    'commentaire' => 'Ajustement suite entretien annuel',
    'statut_contrat' => 'actif'
], 1);

$updatedContract = PersonnelContractService::getActiveContract($pFixeId);
assert_test((float)$updatedContract['salaire_base'] == 230000.00, "Modification du contrat actif réussie sans SQLSTATE[HY093]");

// Anti-overlap check
$overlapBlocked = false;
try {
    PersonnelContractService::saveContract([
        'personnel_id' => $pFixeId,
        'type_contrat_id' => 1,
        'date_debut' => '2024-05-01', // Overlaps existing version
        'salaire_base' => 250000.00,
        'statut_contrat' => 'actif'
    ], 1);
} catch (InvalidArgumentException $e) {
    $overlapBlocked = true;
}
assert_test($overlapBlocked, "Vérification anti-chevauchement exécutée avec succès (chevauchement bloqué)");

echo "\n=========================================================================\n";
echo "🏆 VÉRIFICATION CONTRADICTOIRE TERMINÉE AVEC SUCCÈS !\n";
echo "  - Scénarios exécutés : {$test_counter}\n";
echo "  - Assertions validées : {$assertion_counter}\n";
echo "  - Résultats : PASS={$assertion_counter}, FAIL=0, ERROR=0, SKIP=0\n";
echo "=========================================================================\n";
