<?php
// tests/integration/DRHLegacyCompatibilityTest.php

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../../src/services/PersonnelService.php';
require_once __DIR__ . '/../../src/models/User.php';

echo "=== TEST: COMPATIBILITÉ ET RÉTRO-COMPATIBILITÉ LEGACY (DRHLegacyCompatibilityTest) ===\n";

$db = Database::getInstance();
$author_id = 1;

// 1. Create a user
$personnel_id = PersonnelService::savePersonnel([
    'nom' => 'Ndiaye',
    'prenom' => 'Moussa',
    'email' => 'moussa.ndiaye.' . time() . '@sgs-test.org',
    'mot_de_passe' => 'Password123!',
    'role_id' => 11,
    'lycee_id' => 1
], $author_id);

// 2. Save active contract
PersonnelContractService::saveContract([
    'personnel_id' => $personnel_id,
    'type_contrat_id' => 1,
    'date_debut' => '2024-01-01',
    'salaire_base' => 160000.00,
    'statut_contrat' => 'actif'
], $author_id);

// 3. Verify utilisateurs.contrat_id is synchronized with active contract type_contrat_id
$user = User::findById($personnel_id);
assert((int)$user['contrat_id'] === 1, "utilisateurs.contrat_id doit être synchronisé à 1");

// 4. Verify querying legacy fields works seamlessly
$stmt = $db->prepare("
    SELECT u.id_user, u.nom, u.prenom, tc.libelle AS tc_libelle
    FROM utilisateurs u
    LEFT JOIN type_contrat tc ON u.contrat_id = tc.id_contrat
    WHERE u.id_user = :id
");
$stmt->execute(['id' => $personnel_id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

assert(!empty($res), "La requête legacy doit retourner un résultat");
assert(!empty($res['tc_libelle']), "Le libellé du type de contrat legacy doit être renseigné");

echo "=========================================================================\n";
echo "🏆 DRHLegacyCompatibilityTest RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
