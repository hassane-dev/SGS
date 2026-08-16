<?php
// tests/integration/DRHContractHistoryTest.php

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../../src/services/PersonnelService.php';
require_once __DIR__ . '/../../src/models/User.php';

echo "=== TEST: CONSULTATION DE L'HISTORIQUE DES CONTRATS (DRHContractHistoryTest) ===\n";

$db = Database::getInstance();
$author_id = 1;

// 1. Create staff member
$personnel_id = PersonnelService::savePersonnel([
    'nom' => 'Martin',
    'prenom' => 'Claire',
    'email' => 'claire.martin.' . time() . '@sgs-test.org',
    'telephone' => '+23675002233',
    'mot_de_passe' => 'Password123!',
    'role_id' => 11,
    'lycee_id' => 1
], $author_id);

// 2. Add multiple contract versions
PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2023-01-01',
    'salaire_base' => 120000.00,
    'statut_contrat' => 'actif',
    'commentaire' => 'Contrat Initial'
], $author_id);

PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2023-07-01',
    'salaire_base' => 140000.00,
    'statut_contrat' => 'actif',
    'type_avenant' => 'revalorisation_salariale',
    'commentaire' => 'Avenant v2'
], $author_id);

PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 160000.00,
    'statut_contrat' => 'actif',
    'type_avenant' => 'revalorisation_salariale',
    'commentaire' => 'Avenant v3'
], $author_id);

// 3. Fetch history
$history = PersonnelContractService::getContractsForPersonnel($personnel_id);
assert(count($history) === 3, "Il doit y avoir exactement 3 versions contractuelles dans l'historique");

// Verify ordering (most recent first)
assert((int)$history[0]['version_num'] === 3, "La version la plus récente doit être v3");
assert((int)$history[1]['version_num'] === 2, "La version intermédiaire doit être v2");
assert((int)$history[2]['version_num'] === 1, "La version initiale doit être v1");

// Verify statuses
assert($history[0]['statut_contrat'] === 'actif', "v3 doit être active");
assert($history[1]['statut_contrat'] === 'avenant_remplace', "v2 doit être avenant_remplace");
assert($history[2]['statut_contrat'] === 'avenant_remplace', "v1 doit être avenant_remplace");

echo "=========================================================================\n";
echo "🏆 DRHContractHistoryTest RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
