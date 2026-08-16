<?php
// tests/integration/DRHLegacyContractMigrationTest.php

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../../src/services/PersonnelService.php';
require_once __DIR__ . '/../../src/models/User.php';
require_once __DIR__ . '/../../migrate.php';

echo "=== TEST: MIGRATION DES CONTRATS LEGACY (DRHLegacyContractMigrationTest) ===\n";

$db = Database::getInstance();

// 1. Ensure type_contrat exists
$stmt_tc = $db->query("SELECT id_contrat FROM type_contrat LIMIT 1");
$tc_id = $stmt_tc ? $stmt_tc->fetchColumn() : null;
if (!$tc_id) {
    $db->exec("INSERT INTO type_contrat (libelle, description, type_paiement, prise_en_charge) VALUES ('CDI Test Legacy', 'Description test', 'fixe', 'Ecole')");
    $tc_id = (int)$db->lastInsertId();
}

// 2. Create a legacy user in `utilisateurs` with `contrat_id` set, but NO contract in `personnel_contrats_historique`
$email = 'legacy.user.' . time() . '@sgs-test.org';
$stmt_u = $db->prepare("
    INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, contrat_id, date_embauche, actif)
    VALUES ('Dupont', 'Jean', :email, 'hash_pass', 11, 1, :cid, '2023-09-01', 1)
");
$stmt_u->execute(['email' => $email, 'cid' => $tc_id]);
$legacy_user_id = (int)$db->lastInsertId();

echo " [PASS] Création de l'utilisateur legacy réussi (ID: $legacy_user_id, contrat_id: $tc_id).\n";

// Confirm no history exists yet
$stmt_check = $db->prepare("SELECT COUNT(*) FROM personnel_contrats_historique WHERE personnel_id = :pid");
$stmt_check->execute(['pid' => $legacy_user_id]);
assert((int)$stmt_check->fetchColumn() === 0, "Aucun contrat ne doit exister avant la migration");

// 3. Run migration routine
$migrated = PersonnelContractService::migrateLegacyContracts();
assert($migrated >= 1, "Au moins un contrat legacy doit avoir été migré");
echo " [PASS] Routine de migration des contrats legacy exécutée ($migrated contrat(s) migré(s)).\n";

// 4. Verify migrated record
$contracts = PersonnelContractService::getContractsForPersonnel($legacy_user_id);
assert(count($contracts) === 1, "L'utilisateur legacy doit désormais posséder 1 contrat enregistré");
assert((int)$contracts[0]['type_contrat_id'] === (int)$tc_id, "Le type de contrat doit correspondre");
assert($contracts[0]['statut_contrat'] === 'actif', "Le statut doit être actif");
assert((int)$contracts[0]['version_num'] === 1, "La version doit être 1");
assert((int)$contracts[0]['contrat_souche_id'] === (int)$contracts[0]['id'], "Le contrat souche ID doit être autolinké");

echo "=========================================================================\n";
echo "🏆 DRHLegacyContractMigrationTest RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
