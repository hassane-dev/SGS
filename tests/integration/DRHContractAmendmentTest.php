<?php
// tests/integration/DRHContractAmendmentTest.php

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../../src/services/PersonnelService.php';

echo "=== TEST: CRÉATION D'AVENANTS ET IMMUABILITÉ (DRHContractAmendmentTest) ===\n";

$db = Database::getInstance();
$author_id = 1;

$personnel_id = PersonnelService::savePersonnel([
    'nom' => 'Koffi',
    'prenom' => 'Esnest',
    'email' => 'ernest.koffi.' . time() . '@sgs-test.org',
    'mot_de_passe' => 'Password123!',
    'role_id' => 11,
    'lycee_id' => 1
], $author_id);

// 1. Initial Contract Version v1
PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 150000.00,
    'statut_contrat' => 'actif'
], $author_id);

$contracts_v1 = PersonnelContractService::getContractsForPersonnel($personnel_id);
$v1_id = (int)$contracts_v1[0]['id'];

// 2. Issue Avenant v2
PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2024-06-01',
    'salaire_base' => 180000.00,
    'type_avenant' => 'revalorisation_salariale',
    'avenant_numero' => 'AV-2024-001',
    'statut_contrat' => 'actif'
], $author_id);

$contracts_v2 = PersonnelContractService::getContractsForPersonnel($personnel_id);
assert(count($contracts_v2) === 2, "Il doit y avoir 2 versions");

$v1_check = PersonnelContractService::getContractDetails($v1_id);
assert($v1_check['statut_contrat'] === 'avenant_remplace', "v1 doit être marquée 'avenant_remplace'");
assert($v1_check['date_fin'] === '2024-05-31', "v1 doit se terminer la veille de v2");

// 3. Attempt direct UPDATE on historical v1 (must throw InvalidArgumentException)
try {
    PersonnelContractService::saveContract([
        'id' => $v1_id,
        'personnel_id' => $personnel_id,
        'type_contrat_id' => 1,
        'date_debut' => '2024-01-01',
        'salaire_base' => 999999.00
    ], $author_id);
    assert(false, "Modifier directement v1 aurait dû lever une exception");
} catch (InvalidArgumentException $e) {
    echo " [PASS] Modification directe d'un contrat historique correctement rejetée.\n";
}

echo "=========================================================================\n";
echo "🏆 DRHContractAmendmentTest RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
