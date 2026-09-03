<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';

echo "--- Running Evaluation Occurrence Bounds & Dynamic Config Tests ---\n\n";

$db = Database::getInstance();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

// Setup test tenant
$lycee_id = 8800;
$_SESSION['user'] = [
    'id_user' => 8801,
    'role_id' => 1,
    'lycee_id' => $lycee_id,
    'role_name' => 'super_admin_createur'
];

if ($driver === 'sqlite') {
    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES (8800, 'Test Lycee Occurrences')");
    $db->exec("INSERT OR IGNORE INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (880, '2024-2025', '2024-09-01', '2025-06-30', 1)");
    $db->exec("INSERT OR IGNORE INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8801, 8800, 880, 'Seq 1', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
} else {
    $db->exec("INSERT IGNORE INTO param_lycee (id, nom_lycee) VALUES (8800, 'Test Lycee Occurrences')");
    $db->exec("INSERT IGNORE INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (880, '2024-2025', '2024-09-01', '2025-06-30', 1)");
    $db->exec("INSERT IGNORE INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8801, 8800, 880, 'Seq 1', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
}

// 1. Verify ParamTypeEvaluation save validation for nombre_evaluation
try {
    ParamTypeEvaluation::save([
        'code' => 'invalid_type',
        'libelle' => 'Invalid Type',
        'nombre_evaluation' => 5
    ]);
    echo "FAIL: Invalid nombre_evaluation = 5 was accepted!\n";
    exit(1);
} catch (InvalidArgumentException $e) {
    echo "PASS: 1. Invalid nombre_evaluation = 5 correctly threw InvalidArgumentException: " . $e->getMessage() . "\n";
}

try {
    ParamTypeEvaluation::save([
        'code' => 'invalid_zero',
        'libelle' => 'Invalid Zero',
        'nombre_evaluation' => 0
    ]);
    echo "FAIL: Invalid nombre_evaluation = 0 was accepted!\n";
    exit(1);
} catch (InvalidArgumentException $e) {
    echo "PASS: 2. Invalid nombre_evaluation = 0 correctly threw InvalidArgumentException: " . $e->getMessage() . "\n";
}

// Save valid types: 'devoir_test' (nombre = 2) and 'compo_test' (nombre = 1)
ParamTypeEvaluation::save([
    'code' => 'devoir_test',
    'libelle' => 'Devoir Test',
    'bareme_defaut' => 20.00,
    'nombre_evaluation' => 2,
    'actif' => 1
]);

ParamTypeEvaluation::save([
    'code' => 'compo_test',
    'libelle' => 'Composition Test',
    'bareme_defaut' => 40.00,
    'nombre_evaluation' => 1,
    'actif' => 1
]);

$typeDevoir = ParamTypeEvaluation::findByCode('devoir_test', $lycee_id);
if ($typeDevoir && (int)$typeDevoir['nombre_evaluation'] === 2) {
    echo "PASS: 3. Devoir test saved with nombre_evaluation = 2.\n";
} else {
    echo "FAIL: Devoir test nombre_evaluation mismatch!\n";
    exit(1);
}

$typeCompo = ParamTypeEvaluation::findByCode('compo_test', $lycee_id);
if ($typeCompo && (int)$typeCompo['nombre_evaluation'] === 1) {
    echo "PASS: 4. Composition test saved with nombre_evaluation = 1.\n";
} else {
    echo "FAIL: Composition test nombre_evaluation mismatch!\n";
    exit(1);
}

// 2. Verify legacy method getNextOccurrenceNumber is removed
if (!method_exists('Evaluation', 'getNextOccurrenceNumber')) {
    echo "PASS: 5. Legacy Evaluation::getNextOccurrenceNumber method was successfully removed.\n";
} else {
    echo "FAIL: Legacy Evaluation::getNextOccurrenceNumber still exists!\n";
    exit(1);
}

// 3. Verify server-side occurrence bounds enforcement in EvaluationController
$_GET = [
    'classe_id' => 99010,
    'matiere_id' => 99020,
    'sequence_id' => 8801,
    'type' => 'devoir_test',
    'numero' => 3 // Exceeds nombre_evaluation = 2
];

ob_start();
$controller = new EvaluationController();
try {
    $controller->showForm();
    $output = ob_get_clean();
    if (strpos($output, 'Accès Refusé') !== false && strpos($output, 'n\'est pas autorisée') !== false) {
        echo "PASS: 6. Server-side validation blocked GET numero = 3 for type with max 2 occurrences.\n";
    } else {
        echo "FAIL: Server-side validation did not render Accès Refusé for numero = 3!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "PASS: 6. Exception or block triggered for out of bounds occurrence.\n";
}

// 4. Verify valid occurrence GET (numero = 2) renders form with 2 tabs only
$_GET['numero'] = 2;
ob_start();
try {
    $controller->showForm();
    $formOutput = ob_get_clean();
    if (strpos($formOutput, 'N° 1') !== false && strpos($formOutput, 'N° 2') !== false && strpos($formOutput, 'N° 3') === false) {
        echo "PASS: 7. Form rendered exactly 2 occurrence tabs (N°1, N°2) without N°3, N°4, or N°5.\n";
    } else {
        echo "FAIL: Form did not render exact expected occurrence tabs!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "Error rendering form: " . $e->getMessage() . "\n";
}

// 5. Verify POST save validation for out of bounds occurrence
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'classe_id' => 99010,
    'matiere_id' => 99020,
    'sequence_id' => 8801,
    'type' => 'compo_test',
    'numero_evaluation' => 2, // Exceeds compo_test max 1
    'bareme' => 40,
    'coefficient' => 2,
    'grades' => []
];

ob_start();
try {
    $controller->save();
    $postOutput = ob_get_clean();
    if (strpos($postOutput, 'Accès Refusé') !== false && strpos($postOutput, 'n\'est pas autorisée') !== false) {
        echo "PASS: 8. Server-side POST save blocked numero_evaluation = 2 for composition with max 1 occurrence.\n";
    } else {
        echo "FAIL: Server-side POST save allowed invalid occurrence!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "PASS: 8. POST save blocked invalid occurrence.\n";
}

echo "\n>>> ALL EVALUATION OCCURRENCE BOUNDS TESTS PASSED SUCCESSFULLY! <<<\n";
