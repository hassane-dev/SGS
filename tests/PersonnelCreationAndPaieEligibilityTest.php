<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';

function assert_test($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU TEST DE NON-RÉGRESSION : CRÉATION DRH → CONTRAT → ÉLIGIBILITÉ PAIE → PRÉPARATION BULLETIN ===\n";

require_once __DIR__ . '/../migrate.php';

$db = Database::getInstance();
// Test with native prepare behavior
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
$db->exec("DELETE FROM paie_bulletin_lignes");
$db->exec("DELETE FROM paie_bulletins");
$db->exec("DELETE FROM paie_periodes");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%test70%') OR personnel_id >= 700");
$db->exec("DELETE FROM personnel_details WHERE personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%test70%') OR personnel_id >= 700");
$db->exec("DELETE FROM utilisateurs WHERE email LIKE '%test70%' OR id_user >= 700");

// Seed test school & accounting period
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test Non-Régression') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (1, 1, 1, '2024-11-01', '2024-11-30', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO type_contrat (id_contrat, libelle, type_paiement, prise_en_charge) VALUES (1, 'CDI', 'mensuel', 'Ecole') ON CONFLICT DO NOTHING");

// Create payroll period
$periodeId = PaieWorkflowService::createPeriod(1, 1, 'PAIE-2024-11', 11, 2024, '2024-11-01', '2024-11-30', 1);

// --- ÉTAPE 1: Création d'un nouveau personnel dans le DRH ---
echo "\n--- ÉTAPE 1: Création d'un nouveau personnel dans le DRH ---\n";
$personnelData = [
    'nom' => 'Kouassi',
    'prenom' => 'Jean',
    'email' => 'jean.kouassi.test701@etablissement.com',
    'mot_de_passe' => 'MotDePasse701!',
    'sexe' => 'Homme',
    'date_naissance' => '1988-04-12',
    'lieu_naissance' => 'Abidjan',
    'situation_matrimoniale' => 'marie',
    'nombre_enfants' => 2,
    'telephone' => '+22507000000',
    'adresse' => 'Cocody Rue 12',
    'fonction' => 'Enseignant de Mathématiques',
    'role_id' => 6, // Enseignant
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 180000.00,
    'num_cnss' => 'CNSS-701-TEST',
    'statut_rh' => 'en_activite'
];

$newPersonnelId = PersonnelService::savePersonnel($personnelData, 1);
assert_test($newPersonnelId > 0, "Nouveau membre du personnel créé avec succès sans erreur SQL/HY093 (ID: {$newPersonnelId})");

$u = User::findById($newPersonnelId);
assert_test($u !== false && $u['nom'] === 'Kouassi' && $u['lycee_id'] == 1, "Données utilisateur 'utilisateurs' enregistrées correctement avec lycee_id = 1");

// --- ÉTAPE 2: Création / Vérification du contrat associé ---
echo "\n--- ÉTAPE 2: Création / Vérification du contrat associé ---\n";
$contracts = PersonnelContractService::getContractsForPersonnel($newPersonnelId);
assert_test(count($contracts) === 1, "Le contrat initial a été automatiquement créé et lié dans personnel_contrats_historique");

$activeContract = PersonnelContractService::getActiveContract($newPersonnelId, '2024-11-15');
assert_test($activeContract !== null && $activeContract['statut_contrat'] === 'actif', "Le contrat actif de Kouassi Jean est présent et valide au 15/11/2024");

// Create a second test personnel (702) for selection tests
$p2Data = [
    'nom' => 'Ndiaye',
    'prenom' => 'Sokhna',
    'email' => 'sokhna.ndiaye.test702@etablissement.com',
    'mot_de_passe' => 'MotDePasse702!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => 1,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 175000.00,
    'statut_rh' => 'en_activite'
];
$p2Id = PersonnelService::savePersonnel($p2Data, 1);

// --- ÉTAPE 3: Récupération du personnel comme personnel éligible à la paie ---
echo "\n--- ÉTAPE 3: Récupération du personnel éligible via PersonnelContractService::getEligibleContractsForPeriod ---\n";
$eligibleContracts = PersonnelContractService::getEligibleContractsForPeriod($periodeId, 1);
$eligiblePersonnelIds = array_map(function($c) { return (int)$c['personnel_id']; }, $eligibleContracts);

assert_test(in_array($newPersonnelId, $eligiblePersonnelIds, true), "Kouassi Jean (ID {$newPersonnelId}) est bien retourné dans la liste des contrats éligibles à la paie");
assert_test(in_array($p2Id, $eligiblePersonnelIds, true), "Ndiaye Sokhna (ID {$p2Id}) est également retournée dans les contrats éligibles");

// --- ÉTAPE 4: Validation de l'affichage dans /paie/bulletins/prepare ---
echo "\n--- ÉTAPE 4: Validation de l'affichage dans /paie/bulletins/prepare ---\n";
$_GET['periode_id'] = $periodeId;

// Simulate controller execution logic
$selectedPeriode = PaiePeriode::findById($periodeId);
$eligibleContractsInController = PersonnelContractService::getEligibleContractsForPeriod($periodeId, 1);

assert_test(!empty($eligibleContractsInController), "Le contrôleur récupère un tableau non vide de contrats éligibles ({$periodeId})");
$foundInController = false;
foreach ($eligibleContractsInController as $c) {
    if ((int)$c['personnel_id'] === $newPersonnelId) {
        $foundInController = true;
        assert_test(!empty($c['nom']) && !empty($c['prenom']) && !empty($c['identifiant_public']), "Les champs requis pour le rendu HTML (nom, prénom, matricule) sont présents");
        break;
    }
}
assert_test($foundInController, "Le nouveau personnel est présent dans la variable \$eligibleContracts transmise à prepare.php");

// --- ÉTAPE 5: Génération Individuelle ---
echo "\n--- ÉTAPE 5: Génération Individuelle ---\n";
$indivBulletinId = PaieWorkflowService::generateBulletinForEmployee($periodeId, $newPersonnelId, (int)$activeContract['id'], 1);
assert_test($indivBulletinId > 0, "Génération réussie d'un bulletin individuel pour Kouassi Jean (Bulletin ID: {$indivBulletinId})");

$bulletin = PaieBulletin::findById($indivBulletinId);
assert_test((int)$bulletin['personnel_id'] === $newPersonnelId && (float)$bulletin['net_a_payer'] > 0, "Bulletin individuel sauvegardé avec montant net valide (" . $bulletin['net_a_payer'] . " FCFA)");

// Clean created bulletin before collective test steps
$db->exec("DELETE FROM paie_bulletin_lignes WHERE bulletin_id = {$indivBulletinId}");
$db->exec("DELETE FROM paie_bulletin_contrat_snapshot WHERE bulletin_id = {$indivBulletinId}");
$db->exec("DELETE FROM paie_bulletin_regles_snapshot WHERE bulletin_id = {$indivBulletinId}");
$db->exec("DELETE FROM paie_bulletins WHERE id = {$indivBulletinId}");

// --- ÉTAPE 6: Prévisualisation (Preview) ---
echo "\n--- ÉTAPE 6: Prévisualisation (Preview) ---\n";
$previewRes = PaieWorkflowService::previewBulletinsForPeriod($periodeId, null, 1);
$previewItems = $previewRes['items'];

assert_test(!empty($previewItems), "La prévisualisation hors-persistance a généré des éléments de précalcul");
$foundInPreview = false;
foreach ($previewItems as $pi) {
    if ((int)$pi['personnel_id'] === $newPersonnelId) {
        $foundInPreview = true;
        assert_test((float)$pi['net_a_payer'] > 0, "Montant net précalculé en preview > 0 (" . $pi['net_a_payer'] . ")");
        break;
    }
}
assert_test($foundInPreview, "Kouassi Jean est correctement inclus dans les résultats de prévisualisation");

// --- ÉTAPE 7: Génération Collective (scope = all) ---
echo "\n--- ÉTAPE 7: Génération Collective (scope = all) ---\n";
$allGeneratedIds = PaieWorkflowService::generateBulletinsForPeriod($periodeId, 1, null);
assert_test(count($allGeneratedIds) >= 2, "La génération collective scope=all a produit " . count($allGeneratedIds) . " bulletins");

$bJean = PaieBulletin::findActiveForContractAndPeriod($newPersonnelId, 1, (int)$activeContract['id'], $periodeId);
assert_test($bJean !== null, "Bulletin de Kouassi Jean généré en mode collective scope=all");

// Reset bulletins for scope = selection test
$db->exec("DELETE FROM paie_bulletin_lignes");
$db->exec("DELETE FROM paie_bulletin_contrat_snapshot");
$db->exec("DELETE FROM paie_bulletin_regles_snapshot");
$db->exec("DELETE FROM paie_bulletins");

// --- ÉTAPE 8: Génération Collective (scope = selection) ---
echo "\n--- ÉTAPE 8: Génération Collective (scope = selection) ---\n";
$selectedGeneratedIds = PaieWorkflowService::generateBulletinsForPeriod($periodeId, 1, [$newPersonnelId]);
assert_test(count($selectedGeneratedIds) === 1, "La génération restreinte (scope=selection pour 1 ID) a généré exactement 1 bulletin");

$bSelected = PaieBulletin::findById($selectedGeneratedIds[0]);
assert_test((int)$bSelected['personnel_id'] === $newPersonnelId, "Le bulletin généré correspond précisément au membre du personnel sélectionné (Kouassi Jean)");

echo "\n=========================================================================\n";
echo "🏆 TOUTES LES ÉTAPES DU SCÉNARIO D'INTÉGRATION ET DE NON-RÉGRESSION ONT RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
