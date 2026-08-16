<?php
// tests/integration/DRHContractNoDeleteTest.php

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../../src/services/PersonnelService.php';

echo "=== TEST: INTERDICTION DE SUPPRESSION PHYSIQUE ET ANNULATION (DRHContractNoDeleteTest) ===\n";

$db = Database::getInstance();
$author_id = 1;

$personnel_id = PersonnelService::savePersonnel([
    'nom' => 'Diallo',
    'prenom' => 'Aissatou',
    'email' => 'aissatou.diallo.' . time() . '@sgs-test.org',
    'mot_de_passe' => 'Password123!',
    'role_id' => 11,
    'lycee_id' => 1
], $author_id);

PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 130000.00,
    'statut_contrat' => 'actif'
], $author_id);

$contracts = PersonnelContractService::getContractsForPersonnel($personnel_id);
$c_id = (int)$contracts[0]['id'];

// 1. Verify deleteContract throws LogicException
try {
    PersonnelContractService::deleteContract($c_id);
    assert(false, "deleteContract doit lever une LogicException");
} catch (LogicException $e) {
    echo " [PASS] Interdiction de suppression physique validée par LogicException.\n";
}

// 2. Test Contract Cancellation with Motif
PersonnelContractService::cancelContract($c_id, $personnel_id, "Annulation administrative pour erreur de saisie", $author_id);

$updated = PersonnelContractService::getContractDetails($c_id);
assert($updated['statut_contrat'] === 'annule', "Le statut doit être passé à 'annule'");
assert(str_contains($updated['commentaire'], "Annulation administrative pour erreur de saisie"), "Le commentaire doit inclure le motif d'annulation");

// Verify row still exists physically in DB
$stmt = $db->prepare("SELECT COUNT(*) FROM personnel_contrats_historique WHERE id = :id");
$stmt->execute(['id' => $c_id]);
assert((int)$stmt->fetchColumn() === 1, "La ligne contractuelle doit être préservée en BDD");

echo "=========================================================================\n";
echo "🏆 DRHContractNoDeleteTest RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
