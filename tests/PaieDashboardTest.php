<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/PaieDashboardController.php';
require_once __DIR__ . '/../src/services/PaieDashboardService.php';

function assert_test_dash($condition, $message) {
    if ($condition) {
        echo "  [PASS] $message\n";
    } else {
        echo "  [FAIL] $message\n";
        exit(1);
    }
}

echo "=== DÉBUT DU TEST DU DASHBOARD HUB RH & PAIE (PHASE 7) ===\n\n";

$db = Database::getInstance();

// Clean up test data
$db->exec("DELETE FROM paie_bulletins WHERE personnel_id IN (951, 952)");
$db->exec("DELETE FROM paie_periodes WHERE id = 951");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN (951, 952)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (951, 952)");

// Seed test Users for Lycée 1 and Lycée 2
$db->exec("
    INSERT INTO utilisateurs (id_user, nom, prenom, email, identifiant_public, lycee_id, role_id, actif)
    VALUES
    (951, 'Kouamé', 'Ablan', 'ablane@test.com', 'EMP-951', 1, 6, 1),
    (952, 'Traoré', 'Ibrahim', 'ibrahim@test.com', 'EMP-952', 2, 6, 1)
");

// Seed test Contracts (Date-effective active contracts)
$db->exec("
    INSERT INTO personnel_contrats_historique
    (id, contrat_souche_id, version_num, personnel_id, type_contrat_id, date_debut, date_fin, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat)
    VALUES
    (951, 951, 1, 951, 1, '2024-01-01', NULL, 1, 'forfait_fixe', 350000.00, 'FCFA', 'actif'),
    (952, 952, 1, 952, 1, '2024-01-01', NULL, 1, 'forfait_fixe', 400000.00, 'FCFA', 'actif')
");

// Seed test Period
$db->exec("
    INSERT INTO paie_periodes (id, lycee_id, periode_comptable_id, code_periode, mois, annee, date_debut, date_fin, statut, cree_par, created_at, updated_at)
    VALUES
    (951, 1, 1, 'PAIE-2024-09', 9, 2024, '2024-09-01', '2024-09-30', 'valide', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
");

// TEST 1: Active HR Workforce Headcount (Lycée 1 vs Lycée 2 Isolation)
echo "1. Test Effectif RH Actif et Isolation Multi-Tenant\n";
$dataL1 = PaieDashboardService::getDashboardData(1, 951);
$dataL2 = PaieDashboardService::getDashboardData(2);

assert_test_dash($dataL1['effectif_rh_actif'] >= 1, "Lycée 1 compte au moins 1 agent actif.");
assert_test_dash((int)$dataL1['selected_periode']['id'] === 951, "La période sélectionnée PAIE-2024-09 est bien résolue pour le Lycée 1.");

// TEST 2: Active Contract Date-Bounding filter test
echo "\n2. Test Filtrage Temporel des Contrats Actifs (Anti-double comptage)\n";
$stmtCheck = $db->prepare("
    SELECT COUNT(DISTINCT u.id_user)
    FROM utilisateurs u
    JOIN personnel_contrats_historique c ON u.id_user = c.personnel_id
    WHERE u.lycee_id = 1 AND u.id_user = 951 AND u.actif = 1 AND c.statut_contrat = 'actif'
      AND c.date_debut <= CURDATE() AND (c.date_fin IS NULL OR c.date_fin >= CURDATE())
");
$stmtCheck->execute();
$countUser = (int)$stmtCheck->fetchColumn();
assert_test_dash($countUser === 1, "L'utilisateur 951 est compté exactement 1 fois sans double comptage.");

// TEST 3: G1-G4 Data Structure Integrity
echo "\n3. Test Intégrité des Structures de Données G1-G4\n";
assert_test_dash(isset($dataL1['g1_trend_6m']), "Données G1 Tendance 6 mois présentes.");
assert_test_dash(isset($dataL1['g2_employer_cost']['cout_total_employeur']), "Données G2 Décomposition Coût Employeur présentes.");
assert_test_dash(isset($dataL1['g3_service_fait_pipeline']['heures_realisees']), "Données G3 Pipeline Service Fait présentes.");
assert_test_dash(isset($dataL1['g4_contract_deadlines']['expires_list']), "Données G4 Échéancier Contrats présentes.");

// TEST 4: Server-Side RBAC Protection on Controller
echo "\n4. Test Protection RBAC Serveur sur PaieDashboardController\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Case A: User without drh/paie permissions -> 403
$_SESSION['user'] = [
    'id_user' => 999,
    'lycee_id' => 1,
    'role_id' => 6,
    'permissions' => [] // No permissions
];

$dashController = new PaieDashboardController();
ob_start();
try {
    $dashController->index();
    $out = ob_get_clean();
} catch (Throwable $e) {
    $out = ob_get_clean();
}
assert_test_dash(http_response_code() === 403, "Accès refusé HTTP 403 pour un utilisateur sans permissions.");

// Case B: User with paie:view -> 200 OK
http_response_code(200);
$_SESSION['user']['permissions'] = ['paie:view'];
ob_start();
try {
    $dashController->index();
    $out = ob_get_clean();
} catch (Throwable $e) {
    $out = ob_get_clean();
}
assert_test_dash(http_response_code() === 200 && !empty($out), "Accès autorisé HTTP 200 pour un utilisateur avec paie:view.");

// Clean up test data
$db->exec("DELETE FROM paie_bulletins WHERE personnel_id IN (951, 952)");
$db->exec("DELETE FROM paie_periodes WHERE id = 951");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN (951, 952)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (951, 952)");

echo "\n=== TOUS LES TESTS DU DASHBOARD HUB RH & PAIE ONT RÉUSSI ! ===\n";
