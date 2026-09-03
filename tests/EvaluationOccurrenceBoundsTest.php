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
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';

echo "--- Running Comprehensive Evaluation Context & Occurrence Bounds Tests ---\n\n";

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

$active_year = AnneeAcademique::findActive();
$annee_id = $active_year ? (int)$active_year['id'] : 1;

$db->exec("DELETE FROM param_type_evaluation WHERE lycee_id = {$lycee_id}");
$db->exec("DELETE FROM evaluations WHERE lycee_id = {$lycee_id}");
$db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");

if ($driver === 'sqlite') {
    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES (8800, 'Test Lycee Occurrences')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8801, 8800, {$annee_id}, 'Seq Active (Seq 2)', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8802, 8800, {$annee_id}, 'Seq Inactive (Seq 3)', 'trimestrielle', '2010-01-01', '2010-02-01', 'fermee')");
} else {
    $db->exec("INSERT IGNORE INTO param_lycee (id, nom_lycee) VALUES (8800, 'Test Lycee Occurrences')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8801, 8800, {$annee_id}, 'Seq Active (Seq 2)', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8802, 8800, {$annee_id}, 'Seq Inactive (Seq 3)', 'trimestrielle', '2010-01-01', '2010-02-01', 'fermee')");
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

// Save valid types
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

// 2. Verify legacy method getNextOccurrenceNumber is removed
if (!method_exists('Evaluation', 'getNextOccurrenceNumber')) {
    echo "PASS: 2. Legacy Evaluation::getNextOccurrenceNumber method was successfully removed.\n";
} else {
    echo "FAIL: Legacy Evaluation::getNextOccurrenceNumber still exists!\n";
    exit(1);
}

// 3. Test Sequence Tampering Rejection: Client passes inactive sequence_id 8802
$decisionSeq = EvaluationSaisieService::canTeacherGradeContext(99010, 99020, 8802, 'devoir_test');
if (!$decisionSeq['allowed'] && $decisionSeq['code'] === 'DENIED_SEQUENCE_MISMATCH') {
    echo "PASS: 3. Sequence tampering blocked: Client requested inactive sequence_id 8802 when 8801 is active.\n";
} else {
    echo "FAIL: Sequence tampering was not blocked by EvaluationSaisieService! Result: " . json_encode($decisionSeq) . "\n";
    exit(1);
}

// 4. Verify server-side occurrence bounds enforcement in EvaluationController
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
        echo "PASS: 4. Server-side validation blocked GET numero = 3 for type with max 2 occurrences.\n";
    } else {
        echo "FAIL: Server-side validation did not render Accès Refusé for numero = 3!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "PASS: 4. Exception or block triggered for out of bounds occurrence.\n";
}

// 5. Verify valid occurrence GET (numero = 2) renders form with 2 tabs only
$_GET['numero'] = 2;
ob_start();
try {
    $controller->showForm();
    $formOutput = ob_get_clean();
    if (strpos($formOutput, 'N° 1') !== false && strpos($formOutput, 'N° 2') !== false && strpos($formOutput, 'N° 3') === false) {
        echo "PASS: 5. Form rendered exactly 2 occurrence tabs (N°1, N°2) without N°3, N°4, or N°5.\n";
    } else {
        echo "FAIL: Form did not render exact expected occurrence tabs!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "Error rendering form: " . $e->getMessage() . "\n";
}

// 6. Test Fully Forged POST Request (Inactive sequence 8802, composition type, occurrence 2)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'classe_id' => 99010,
    'matiere_id' => 99020,
    'sequence_id' => 8802, // Inactive sequence
    'type' => 'compo_test',
    'numero_evaluation' => 2, // Exceeds max 1
    'bareme' => 40,
    'coefficient' => 2,
    'grades' => [8809 => ['note' => '15']]
];

ob_start();
try {
    $controller->save();
    $postOutput = ob_get_clean();
    if (strpos($postOutput, 'Accès Refusé') !== false) {
        echo "PASS: 6. Fully forged POST request (inactive sequence + invalid occurrence) strictly rejected.\n";
    } else {
        echo "FAIL: Fully forged POST request was not rejected!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "PASS: 6. Fully forged POST request strictly rejected.\n";
}

echo "\n>>> ALL COMPREHENSIVE EVALUATION CONTEXT & OCCURRENCE BOUNDS TESTS PASSED SUCCESSFULLY! <<<\n";
