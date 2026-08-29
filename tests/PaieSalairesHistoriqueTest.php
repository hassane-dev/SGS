<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/PaieBulletinsController.php';
require_once __DIR__ . '/../src/controllers/SalaireController.php';

function assert_test_hist($condition, $message) {
    if ($condition) {
        echo "  [PASS] $message\n";
    } else {
        echo "  [FAIL] $message\n";
        exit(1);
    }
}

echo "=== DÉBUT DU TEST PAIE SALAIRES HISTORIQUE ===\n\n";

$db = Database::getInstance();

// Clean up test data
$db->exec("DELETE FROM paie_bulletins WHERE personnel_id IN (901, 902, 903)");
$db->exec("DELETE FROM paie_periodes WHERE id IN (901, 902)");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN (901, 902, 903)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (901, 902, 903)");

// Insert test Users for Lycée #1 and Lycée #2
$db->exec("
    INSERT INTO utilisateurs (id_user, nom, prenom, email, identifiant_public, lycee_id, actif)
    VALUES
    (901, 'Martin', 'Claire', 'claire.martin@test.com', 'EMP-901', 1, 1),
    (902, 'Dubois', 'Pierre', 'pierre.dubois@test.com', 'EMP-902', 1, 1),
    (903, 'Kouassi', 'Jean', 'jean.kouassi@test.com', 'EMP-903', 2, 1)
");

// Insert test Contracts
$db->exec("
    INSERT INTO personnel_contrats_historique
    (id, contrat_souche_id, version_num, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat)
    VALUES
    (901, 901, 1, 901, 1, '2024-01-01', 1, 'forfait_fixe', 250000.00, 'FCFA', 'actif'),
    (902, 902, 1, 902, 2, '2024-01-01', 1, 'taux_horaire', 0.00, 'FCFA', 'actif'),
    (903, 903, 1, 903, 1, '2024-01-01', 1, 'forfait_fixe', 300000.00, 'FCFA', 'actif')
");

// Insert test Paie Periods for Lycée #1
$db->exec("
    INSERT INTO paie_periodes (id, lycee_id, periode_comptable_id, code_periode, mois, annee, date_debut, date_fin, statut, cree_par, created_at, updated_at)
    VALUES
    (901, 1, 1, 'PAIE-2024-01', 1, 2024, '2024-01-01', '2024-01-31', 'valide', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    (902, 1, 1, 'PAIE-2024-02', 2, 2024, '2024-02-01', '2024-02-29', 'valide', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
");

// Insert Bulletins
$db->exec("
    INSERT INTO paie_bulletins
    (id, periode_id, personnel_id, contrat_id, version_num, est_version_active, entite_juridique_id,
     salaire_base, total_brut, total_cotisations_salariales, total_impots, total_retenues, net_imposable, net_a_payer,
     devise, statut_bulletin, statut_comptabilisation, statut_reglement, est_reprise_legacy, created_at, updated_at)
    VALUES
    (901, 901, 901, 901, 1, 1, 1, 250000.00, 250000.00, 25000.00, 15000.00, 0.00, 210000.00, 210000.00, 'FCFA', 'valide', 'comptabilise', 'paye', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    (902, 902, 901, 901, 1, 1, 1, 250000.00, 250000.00, 25000.00, 15000.00, 0.00, 210000.00, 210000.00, 'FCFA', 'valide', 'non_comptabilise', 'non_paye', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    (903, 901, 902, 902, 1, 1, 1, 180000.00, 180000.00, 18000.00, 10000.00, 0.00, 152000.00, 152000.00, 'FCFA', 'valide', 'comptabilise', 'paye', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
");

// TEST 1: Multi-tenant isolation (Lycée 1 only)
echo "1. Test Isolation Multi-tenant (Lycée #1 vs Lycée #2)\n";
$historyL1 = PaieBulletin::findHistory(1);
$pids = array_column($historyL1, 'personnel_id');
assert_test_hist(in_array(901, $pids) && in_array(902, $pids), "L'historique du Lycée 1 contient les employés 901 et 902.");
assert_test_hist(!in_array(903, $pids), "L'historique du Lycée 1 ne contient PAS l'employé 903 du Lycée 2.");

// TEST 2: Filter by personnel
echo "\n2. Test Filtre par Personnel\n";
$historyP901 = PaieBulletin::findHistory(1, ['personnel_id' => 901]);
assert_test_hist(count($historyP901) === 2, "L'employé #901 possède exactement 2 bulletins dans l'historique.");

// TEST 3: Filter by period
echo "\n3. Test Filtre par Période\n";
$historyPer901 = PaieBulletin::findHistory(1, ['periode_id' => 901]);
assert_test_hist(count($historyPer901) === 2, "La période #901 possède 2 bulletins.");

// TEST 4: Filter by settlement status
echo "\n4. Test Filtre par Statut de Règlement\n";
$paidHistory = PaieBulletin::findHistory(1, ['statut_reglement' => 'paye']);
$paidIds = array_column($paidHistory, 'id');
assert_test_hist(in_array(901, $paidIds) && in_array(903, $paidIds) && !in_array(902, $paidIds), "Le filtre 'paye' retourne uniquement les bulletins réglés.");

// TEST 5: Reprise Legacy Badge Indicator
echo "\n5. Test Détection Reprise Legacy\n";
$legacyHistory = PaieBulletin::findHistory(1, ['personnel_id' => 902]);
assert_test_hist(count($legacyHistory) === 1 && (int)$legacyHistory[0]['est_reprise_legacy'] === 1, "Le bulletin #903 porte l'indicateur est_reprise_legacy = 1.");

// TEST 6: Multi-tenant protection in show()
echo "\n6. Test Sécurisation du détail d'un bulletin (Lycée mismatch)\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user'] = [
    'id_user' => 999,
    'lycee_id' => 2,
    'role_id' => 1,
    'permissions' => ['paie:view']
];

$controller = new PaieBulletinsController();
ob_start();
try {
    $controller->show(901); // Bulletin of Lycée 1 requested by Lycée 2 user
    $out = ob_get_clean();
} catch (Throwable $e) {
    $out = ob_get_clean();
}
assert_test_hist(http_response_code() === 403, "L'accès à un bulletin d'un autre établissement via URL show() est bloqué avec HTTP 403.");

// Clean up test data
$db->exec("DELETE FROM paie_bulletins WHERE id IN (901, 902, 903)");
$db->exec("DELETE FROM paie_periodes WHERE id IN (901, 902)");
$db->exec("DELETE FROM personnel_contrats_historique WHERE id IN (901, 902, 903)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (901, 902, 903)");

echo "\n=== TOUS LES TESTS POUR PAIE SALAIRES HISTORIQUE ONT REUSSI ! ===\n";
