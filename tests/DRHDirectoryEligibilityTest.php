<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';

function assert_drh_test($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("DRH Directory Test failed: $message");
    }
}

echo "=== TEST D'INTÉGRATION DU COCKPIT DRH & ANNUAIRE (12 SCÉNARIOS) ===\n";

require_once __DIR__ . '/../migrate.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Clean test records
$db->exec("DELETE FROM utilisateurs WHERE id_user BETWEEN 950 AND 970 OR email LIKE '%drh_scenario_%@test.com'");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id BETWEEN 950 AND 970");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id BETWEEN 950 AND 970");

// Helper to insert test user
function helperCreateUser($db, $id, $nom, $prenom, $email, $actif, $lycee_id, $role_id = 11) {
    $sql = "INSERT INTO utilisateurs (id_user, nom, prenom, email, actif, lycee_id, role_id, identifiant_public)
            VALUES (:id, :nom, :prenom, :email, :actif, :lycee_id, :role_id, :ident)";
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $sql .= " ON CONFLICT(id_user) DO UPDATE SET nom=excluded.nom, prenom=excluded.prenom, email=excluded.email, actif=excluded.actif, lycee_id=excluded.lycee_id";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'id' => $id, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
        'actif' => $actif, 'lycee_id' => $lycee_id, 'role_id' => $role_id, 'ident' => 'DRHTEST' . $id
    ]);
}

// 1. User 951: actif=1, lycee_id=1, no contract, no assignment, no cycle (Scenario 1 & 5)
helperCreateUser($db, 951, 'S1_Nom', 'S1_Prenom', 'drh_scenario_1@test.com', 1, 1);

// 2. User 952: actif=1, lycee_id=1, active contract, no assignment (Scenario 2)
helperCreateUser($db, 952, 'S2_Nom', 'S2_Prenom', 'drh_scenario_2@test.com', 1, 1);
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat) VALUES (952, 1, '2024-01-01', 1000, 'actif')");

// 3. User 953: actif=1, lycee_id=1, no contract, assigned to cycle 1 (Scenario 3)
helperCreateUser($db, 953, 'S3_Nom', 'S3_Prenom', 'drh_scenario_3@test.com', 1, 1);
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (953, 1, '2024-01-01', 1)");

// 4. User 954: actif=1, lycee_id=1, active contract, assigned to cycle 1 (Scenario 4)
helperCreateUser($db, 954, 'S4_Nom', 'S4_Prenom', 'drh_scenario_4@test.com', 1, 1);
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat) VALUES (954, 1, '2024-01-01', 1000, 'actif')");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (954, 1, '2024-01-01', 1)");

// 6. User 956: actif=1, lycee_id=2 (Scenario 6)
helperCreateUser($db, 956, 'S6_Nom', 'S6_Prenom', 'drh_scenario_6@test.com', 1, 2);

// 7. User 957: actif=1, lycee_id=NULL (Scenario 7)
helperCreateUser($db, 957, 'S7_Nom', 'S7_Prenom', 'drh_scenario_7@test.com', 1, NULL);

// 8. User 958: actif=0, lycee_id=1 (Scenario 8)
helperCreateUser($db, 958, 'S8_Nom', 'S8_Prenom', 'drh_scenario_8@test.com', 0, 1);

// 9. User 959: actif=1, lycee_id=1, old expired contract (Scenario 9)
helperCreateUser($db, 959, 'S9_Nom', 'S9_Prenom', 'drh_scenario_9@test.com', 1, 1);
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, date_fin, salaire_base, statut_contrat) VALUES (959, 1, '2022-01-01', '2023-01-01', 1000, 'expire')");

// 10. User 960: actif=1, lycee_id=1, contract cancelled (Scenario 10)
helperCreateUser($db, 960, 'S10_Nom', 'S10_Prenom', 'drh_scenario_10@test.com', 1, 1);
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat) VALUES (960, 1, '2024-01-01', 1000, 'annule')");

// 11. User 961: actif=1, lycee_id=1, contract active + no cycle (Scenario 11)
helperCreateUser($db, 961, 'S11_Nom', 'S11_Prenom', 'drh_scenario_11@test.com', 1, 1);
$db->exec("INSERT INTO personnel_contrats_historique (personnel_id, type_contrat_id, date_debut, salaire_base, statut_contrat) VALUES (961, 1, '2024-01-01', 1000, 'actif')");

// 12. User 962: actif=1, lycee_id=1, cycle 2 assigned + no contract (Scenario 12)
helperCreateUser($db, 962, 'S12_Nom', 'S12_Prenom', 'drh_scenario_12@test.com', 1, 1);
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (962, 2, '2024-01-01', 1)");

// DRH User without teaching cycle assignment (Pure HR Manager)
helperCreateUser($db, 950, 'DRH_Manager', 'Admin', 'drh950@test.com', 1, 1);

$_SESSION['user'] = [
    'id' => 950,
    'lycee_id' => 1,
    'role_id' => 11,
    'permissions' => ['drh:view_all' => true],
    'auth_version' => 1
];
unset($_SESSION['user']['authorized_cycles']);

// Test searchPersonnel for DRH manager without cycle assignment
$listDRH = PersonnelService::searchPersonnel([]);
$idsFound = array_column($listDRH, 'id_user');

// Validate all 12 Scenarios
assert_drh_test(in_array(951, $idsFound), "SCÉNARIO 1 & 5: Personnel actif sans contrat/cycle apparaît dans le cockpit DRH");
assert_drh_test(in_array(952, $idsFound), "SCÉNARIO 2: Personnel actif avec contrat sans affectation apparaît dans le cockpit DRH");
assert_drh_test(in_array(953, $idsFound), "SCÉNARIO 3: Personnel actif avec affectation sans contrat apparaît dans le cockpit DRH");
assert_drh_test(in_array(954, $idsFound), "SCÉNARIO 4: Personnel actif avec contrat et affectation apparaît dans le cockpit DRH");
assert_drh_test(!in_array(956, $idsFound), "SCÉNARIO 6: Personnel du Lycée 2 n'apparaît pas dans la vue du Lycée 1 (Isolation Multi-tenant OK)");
assert_drh_test(!in_array(957, $idsFound), "SCÉNARIO 7: Personnel sans lycée (lycee_id=NULL) n'apparaît pas dans le Lycée 1 (Isolation OK)");
assert_drh_test(in_array(958, $idsFound), "SCÉNARIO 8: Personnel inactif (actif=0) est présent dans le registre DRH de l'établissement");
assert_drh_test(in_array(959, $idsFound), "SCÉNARIO 9: Personnel avec ancien contrat expiré apparaît dans le cockpit DRH");
assert_drh_test(in_array(960, $idsFound), "SCÉNARIO 10: Personnel avec contrat annulé apparaît dans le cockpit DRH");
assert_drh_test(in_array(961, $idsFound), "SCÉNARIO 11: Personnel avec contrat actif sans cycle apparaît dans le cockpit DRH");
assert_drh_test(in_array(962, $idsFound), "SCÉNARIO 12: Personnel affecté au Cycle 2 sans contrat apparaît dans le cockpit DRH du Lycée 1");

// Test explicit cycle filter when requested in search filters
$listCycle1 = PersonnelService::searchPersonnel(['cycle_id' => 1]);
$idsCycle1 = array_column($listCycle1, 'id_user');

assert_drh_test(in_array(953, $idsCycle1), "Filtre cycle_id=1 explicite inclut le personnel du Cycle 1");
assert_drh_test(!in_array(962, $idsCycle1), "Filtre cycle_id=1 explicite exclut le personnel du Cycle 2");

// Clean up test records
$db->exec("DELETE FROM utilisateurs WHERE id_user BETWEEN 950 AND 970 OR email LIKE '%drh_scenario_%@test.com'");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id BETWEEN 950 AND 970");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id BETWEEN 950 AND 970");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES SCÉNARIOS DU COCKPIT DRH ET DE L'ANNUAIRE ONT RÉUSSI AVEC SUCCÈS !\n";
echo "=========================================================================\n";
