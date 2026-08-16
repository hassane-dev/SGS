<?php
// tests/integration/DRHContractViewTest.php

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../../src/services/PersonnelService.php';

echo "=== TEST: CONSULTATION DÉTAILLÉE DE CONTRAT (DRHContractViewTest) ===\n";

$db = Database::getInstance();
$author_id = 1;

$personnel_id = PersonnelService::savePersonnel([
    'nom' => 'Sow',
    'prenom' => 'Ibrahima',
    'email' => 'ibrahima.sow.' . time() . '@sgs-test.org',
    'mot_de_passe' => 'Password123!',
    'role_id' => 11,
    'lycee_id' => 1
], $author_id);

PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 200000.00,
    'devise' => 'XAF',
    'mode_calcul_principal' => 'forfait_fixe',
    'periodicite_paiement' => 'mensuel',
    'statut_contrat' => 'actif',
    'commentaire' => 'Contrat de cadre',
    'composants' => [
        ['code_composant' => 'PRIME_LOG', 'libelle' => 'Prime de logement', 'nature_composant' => 'prime', 'valeur_numerique' => 30000],
        ['code_composant' => 'IND_TRANS', 'libelle' => 'Indemnité de transport', 'nature_composant' => 'indemnite', 'valeur_numerique' => 20000]
    ],
    'financements' => [
        ['financeur_nom' => 'Lycée Principal', 'type_financeur' => 'etablissement', 'pourcentage_prise_en_charge' => 100]
    ]
], $author_id);

$contracts = PersonnelContractService::getContractsForPersonnel($personnel_id);
assert(!empty($contracts), "Le contrat doit exister");
$contract_id = (int)$contracts[0]['id'];

$details = PersonnelContractService::getContractDetails($contract_id);
assert($details !== null, "Les détails du contrat ne doivent pas être nuls");
assert((float)$details['salaire_base'] === 200000.00, "Le salaire de base doit correspondre");
assert(count($details['composants']) === 2, "Le contrat doit comporter 2 composants de rémunération");
assert(count($details['financements']) === 1, "Le contrat doit comporter 1 ligne de financement");

echo "=========================================================================\n";
echo "🏆 DRHContractViewTest RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
