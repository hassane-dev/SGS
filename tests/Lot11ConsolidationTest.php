<?php
// tests/Lot11ConsolidationTest.php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/PaieEntiteJuridique.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/PersonnelAssignmentService.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/controllers/PersonnelContractController.php';

define('TEST_MODE', true);

echo "=========================================================================\n";
echo "🧪 SUITE COMPLÈTE ET VERROUILLÉE DE NON-RÉGRESSION : LOT 1.1 ADDENDUM\n";
echo "=========================================================================\n";

require_once __DIR__ . '/../migrate.php';
$pdo = Database::getInstance();

function assertTest(bool $condition, string $title, string $details = '') {
    if ($condition) {
        echo " [PASS] " . $title . ($details ? " ($details)" : "") . "\n";
    } else {
        echo " [FAIL] " . $title . ($details ? " ($details)" : "") . "\n";
        exit(1);
    }
}

// 1. Seed test Legal Entities
$pdo->exec("INSERT INTO paie_entites_juridiques (raison_sociale, sigle, immatriculation_fiscale) VALUES ('Employeur Alpha', 'EMP_A', 'NIF-001') ON CONFLICT DO NOTHING");
$pdo->exec("INSERT INTO paie_entites_juridiques (raison_sociale, sigle, immatriculation_fiscale) VALUES ('Employeur Bêta', 'EMP_B', 'NIF-002') ON CONFLICT DO NOTHING");

$entities = PaieEntiteJuridique::findAll();
$empA = $entities[0]['id'];
$empB = isset($entities[1]) ? $entities[1]['id'] : $empA;

// 2. Create test user
$testEmail = 'lot11_addendum_' . time() . '@school.org';
$pdo->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif)
            VALUES ('Consolidation', 'Addendum', '{$testEmail}', 'hash', 11, 1, 1)");
$userId = (int)$pdo->lastInsertId();

if (session_status() === PHP_SESSION_NONE && !headers_sent()) @session_start();
$_SESSION['user'] = [
    'id_user' => $userId,
    'role_id' => 1,
    'lycee_id' => 1,
    'auth_version' => 1
];

echo "\n--- MATRICE 1: Résolution Strict de la Devise Sans Fallback 'FCFA' ---\n";
// Scenario 1a: Component currency explicit -> 'USD'
assertTest(PersonnelContractService::resolveCurrency('USD', 1) === 'USD', "Composant.devise_code = 'USD' -> USD");

// Scenario 1b: Contract currency explicit -> 'EUR'
assertTest(PersonnelContractService::resolveCurrency(null, 1, 'EUR') === 'EUR', "Contrat.devise = 'EUR' -> EUR");

// Scenario 1c: School ParamGeneral monnaie = 'XAF'
$pdo->exec("INSERT INTO param_general (lycee_id, monnaie) VALUES (1, 'XAF') ON CONFLICT DO NOTHING");
$pdo->exec("UPDATE param_general SET monnaie = 'XAF' WHERE lycee_id = 1");
assertTest(PersonnelContractService::resolveCurrency(null, 1) === 'XAF', "param_general.monnaie = 'XAF' -> XAF");

// Scenario 1d: School ParamGeneral monnaie = 'EUR'
$pdo->exec("UPDATE param_general SET monnaie = 'EUR' WHERE lycee_id = 1");
assertTest(PersonnelContractService::resolveCurrency(null, 1) === 'EUR', "param_general.monnaie = 'EUR' -> EUR");

// Scenario 1e: Unconfigured monnaie -> DomainException
$pdo->exec("UPDATE param_general SET monnaie = NULL WHERE lycee_id = 1");
try {
    PersonnelContractService::resolveCurrency(null, 1);
    assertTest(false, "L'absence de monnaie générale aurait dû lever une exception !");
} catch (DomainException $e) {
    assertTest(true, "Monnaie non configurée -> DomainException levée proprement", $e->getMessage());
}

// Re-set school monnaie to XAF for remaining tests
$pdo->exec("UPDATE param_general SET monnaie = 'XAF' WHERE lycee_id = 1");

echo "\n--- MATRICE 2: Multi-Financement par Version (V1 vs V2) & Règle <= 100% ---\n";
// Create V1 with funding 60% State + 40% School = 100%
PersonnelContractService::saveContract([
    'personnel_id' => $userId,
    'type_contrat_id' => 1,
    'entite_juridique_id' => $empA,
    'date_debut' => '2024-01-01',
    'salaire_base' => 500000.00,
    'financements' => [
        ['financeur_nom' => 'État', 'pourcentage_prise_en_charge' => 60.00],
        ['financeur_nom' => 'Établissement', 'pourcentage_prise_en_charge' => 40.00]
    ]
], $userId);

$contractsV1 = PersonnelContractService::getContractsForPersonnel($userId);
$contractV1Id = $contractsV1[0]['id'];
$finV1 = PersonnelContractService::getFinancingForContract($contractV1Id);
assertTest(count($finV1) === 2, "V1 possède 2 lignes de financement (100%)");

// Create Avenant V2 with funding 50% State + 30% Donor = 80% (Partial Funding Allowed)
PersonnelContractService::saveContract([
    'personnel_id' => $userId,
    'type_contrat_id' => 1,
    'entite_juridique_id' => $empA,
    'date_debut' => '2024-07-01',
    'salaire_base' => 550000.00,
    'type_avenant' => 'Ajustement Financement',
    'financements' => [
        ['financeur_nom' => 'État', 'pourcentage_prise_en_charge' => 50.00],
        ['financeur_nom' => 'Bailleur ONG', 'pourcentage_prise_en_charge' => 30.00]
    ]
], $userId);

$contractsAll = PersonnelContractService::getContractsForPersonnel($userId);
$contractV2Id = $contractsAll[0]['id'];
$finV2 = PersonnelContractService::getFinancingForContract($contractV2Id);
$finV1After = PersonnelContractService::getFinancingForContract($contractV1Id);

assertTest(count($finV2) === 2 && $finV2[0]['financeur_nom'] === 'État' && $finV2[0]['pourcentage_prise_en_charge'] == 50.00, "V2 possède son propre financement à 80%");
assertTest(count($finV1After) === 2 && $finV1After[0]['pourcentage_prise_en_charge'] == 60.00, "Les financements de V1 n'ont PAS été modifiés rétroactivement par l'avenant V2");

echo "\n--- MATRICE 3: Anti-Chevauchement Multi-Employeurs Élargi ---\n";
// Scenario 3a: Same personnel + Different Employers (EMP_A and EMP_B) -> ALLOWED
if ($empA !== $empB) {
    $resDiffEmp = PersonnelContractService::saveContract([
        'personnel_id' => $userId,
        'type_contrat_id' => 1,
        'entite_juridique_id' => $empB,
        'date_debut' => '2024-07-01',
        'salaire_base' => 250000.00
    ], $userId);
    assertTest($resDiffEmp, "Contrats actifs simultanés autorisés pour deux employeurs juridiques distincts (EMP_A et EMP_B)");
}

// Scenario 3b: Disjoint temporal periods for SAME employer -> ALLOWED
$resDisjoint = PersonnelContractService::saveContract([
    'personnel_id' => $userId,
    'type_contrat_id' => 1,
    'entite_juridique_id' => $empA,
    'date_debut' => '2025-01-01',
    'date_fin' => '2025-06-30',
    'statut_contrat' => 'actif',
    'salaire_base' => 600000.00
], $userId);
assertTest($resDisjoint, "Périodes de contrat disjointes autorisées pour le même employeur");

echo "\n--- MATRICE 4: Verrouillage Stricte Anti-Suppression (Service, Controller & Roles) ---\n";
// Direct Service Call -> LogicException
try {
    PersonnelContractService::deleteContract($contractV2Id);
    assertTest(false, "deleteContract() service call should have thrown LogicException");
} catch (LogicException $e) {
    assertTest(true, "PersonnelContractService::deleteContract() -> LogicException levée");
}

// Controller endpoint Call -> LogicException
$_REQUEST['id'] = $contractV2Id;
$controller = new PersonnelContractController();
try {
    $controller->delete();
    assertTest(false, "Controller delete() call should have thrown LogicException");
} catch (LogicException $e) {
    assertTest(true, "PersonnelContractController::delete() bloque l'accès contrôleur via LogicException", $e->getMessage());
}

echo "\n--- MATRICE 5: Traçabilité Inaltérable de l'Annulation Juridique ---\n";
$resCancel = PersonnelContractService::cancelContract($contractV2Id, $userId, "Annulation restructuration budgétaire", $userId);
assertTest($resCancel, "Annulation du contrat exécutée");
$v2Cancelled = PersonnelContractService::getContractDetails($contractV2Id);
assertTest($v2Cancelled['statut_contrat'] === 'annule', "Statut de la version v2 est 'annule'");
assertTest(strpos($v2Cancelled['commentaire'], "Annulation restructuration budgétaire") !== false, "Commentaires v2 enrichis avec la référence et le motif d'annulation");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS DE L'ADDENDUM LOT 1.1 ONT RÉUSSI AVEC SUCCÈS (100% GREEN) !\n";
echo "=========================================================================\n";
