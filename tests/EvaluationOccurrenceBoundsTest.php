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

echo "--- Running Comprehensive Evaluation Tests A through I ---\n\n";

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
$db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$lycee_id}");
$db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");

if ($driver === 'sqlite') {
    $db->exec("CREATE TABLE IF NOT EXISTS param_lycee (id INT PRIMARY KEY, nom_lycee VARCHAR(255))");
    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES (8800, 'Test Lycee Occurrences')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8801, 8800, {$annee_id}, 'Séquence 2 Active', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8802, 8800, {$annee_id}, 'Séquence 3 Inactive', 'trimestrielle', '2010-01-01', '2010-02-01', 'fermee')");
} else {
    $db->exec("INSERT IGNORE INTO param_lycee (id, nom_lycee) VALUES (8800, 'Test Lycee Occurrences')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8801, 8800, {$annee_id}, 'Séquence 2 Active', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (8802, 8800, {$annee_id}, 'Séquence 3 Inactive', 'trimestrielle', '2010-01-01', '2010-02-01', 'fermee')");
}

// Seed param_type_evaluation
ParamTypeEvaluation::save([
    'code' => 'devoir',
    'libelle' => 'Devoir',
    'bareme_defaut' => 20.00,
    'nombre_evaluation' => 2,
    'actif' => 1
]);

ParamTypeEvaluation::save([
    'code' => 'interro',
    'libelle' => 'Interrogation',
    'bareme_defaut' => 20.00,
    'nombre_evaluation' => 3,
    'actif' => 1
]);

ParamTypeEvaluation::save([
    'code' => 'composition',
    'libelle' => 'Composition',
    'bareme_defaut' => 40.00,
    'nombre_evaluation' => 1,
    'actif' => 1
]);

ParamTypeEvaluation::save([
    'code' => 'devoir_unique',
    'libelle' => 'Devoir Unique',
    'bareme_defaut' => 20.00,
    'nombre_evaluation' => 1,
    'actif' => 1
]);

// Configure parametres_evaluations: Devoir only open for sequence 8801
$db->exec("INSERT INTO parametres_evaluations (lycee_id, classe_id, matiere_id, sequence_id, annee_academique_id, type, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
           VALUES (8800, 99010, 99020, 8801, {$annee_id}, 'classe_matiere', 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");

$controller = new EvaluationController();

// TEST A — Devoir uniquement (nombre_evaluation = 2): Renders [Devoir 1] [Devoir 2], no Composition
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [
    'classe_id' => 99010,
    'matiere_id' => 99020,
    'sequence_id' => 8801,
    'type' => 'devoir',
    'numero' => 1
];

ob_start();
try {
    $controller->showForm();
    $htmlA = ob_get_clean();
    if (strpos($htmlA, 'Devoir 1') !== false && strpos($htmlA, 'Devoir 2') !== false && strpos($htmlA, 'Composition') === false) {
        echo "PASS: Test A — Devoir uniquement (nombre = 2) rendered [Devoir 1] [Devoir 2] without Composition.\n";
    } else {
        echo "FAIL: Test A output mismatch!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "FAIL Test A: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST B — Tentative de falsification: Authorized = Devoir, request sends type = composition -> REFUSED
$_GET['type'] = 'composition';
ob_start();
try {
    $controller->showForm();
    $htmlB = ob_get_clean();
    if (strpos($htmlB, 'Accès Refusé') !== false && strpos($htmlB, 'n\'est pas autorisée') !== false) {
        echo "PASS: Test B — Falsification attempt for type = composition when only devoir is open was REFUSED.\n";
    } else {
        echo "FAIL: Test B failed to refuse unauthorized composition type!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "PASS: Test B — Exception triggered for unauthorized composition type.\n";
}

// TEST C — Composition unique (nombre_evaluation = 1): Rendered [Composition] without '1'
// Configure parametres_evaluations: Composition open
$db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 8800");
$db->exec("INSERT INTO parametres_evaluations (lycee_id, classe_id, matiere_id, sequence_id, annee_academique_id, type, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
           VALUES (8800, 99010, 99020, 8801, {$annee_id}, 'classe_matiere', 'composition', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");

$_GET['type'] = 'composition';
$_GET['numero'] = 1;

ob_start();
try {
    $controller->showForm();
    $htmlC = ob_get_clean();
    if (strpos($htmlC, 'Composition') !== false && strpos($htmlC, 'Composition 1') === false) {
        echo "PASS: Test C — Composition unique (nombre = 1) rendered [Composition] without '1'.\n";
    } else {
        echo "FAIL: Test C output contained unexpected 'Composition 1'!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "FAIL Test C: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST D — Devoir unique (nombre_evaluation = 1): Rendered [Devoir Unique] without '1'
$db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 8800");
$db->exec("INSERT INTO parametres_evaluations (lycee_id, classe_id, matiere_id, sequence_id, annee_academique_id, type, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
           VALUES (8800, 99010, 99020, 8801, {$annee_id}, 'classe_matiere', 'devoir_unique', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");

$_GET['type'] = 'devoir_unique';
$_GET['numero'] = 1;

ob_start();
try {
    $controller->showForm();
    $htmlD = ob_get_clean();
    if (strpos($htmlD, 'Devoir Unique') !== false && strpos($htmlD, 'Devoir Unique 1') === false) {
        echo "PASS: Test D — Devoir unique (nombre = 1) rendered [Devoir Unique] without '1'.\n";
    } else {
        echo "FAIL: Test D output contained unexpected 'Devoir Unique 1'!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "FAIL Test D: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST E — Deux occurrences (nombre_evaluation = 2): [Devoir 1] [Devoir 2]
$db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 8800");
$db->exec("INSERT INTO parametres_evaluations (lycee_id, classe_id, matiere_id, sequence_id, annee_academique_id, type, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
           VALUES (8800, 99010, 99020, 8801, {$annee_id}, 'classe_matiere', 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");

$_GET['type'] = 'devoir';
$_GET['numero'] = 1;

ob_start();
try {
    $controller->showForm();
    $htmlE = ob_get_clean();
    if (strpos($htmlE, 'Devoir 1') !== false && strpos($htmlE, 'Devoir 2') !== false) {
        echo "PASS: Test E — Two occurrences (nombre = 2) rendered [Devoir 1] and [Devoir 2].\n";
    } else {
        echo "FAIL: Test E missing expected Devoir 1 or Devoir 2 labels!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "FAIL Test E: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST F — Trois occurrences (nombre_evaluation = 3): [Interrogation 1] [Interrogation 2] [Interrogation 3]
$db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 8800");
$db->exec("INSERT INTO parametres_evaluations (lycee_id, classe_id, matiere_id, sequence_id, annee_academique_id, type, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
           VALUES (8800, 99010, 99020, 8801, {$annee_id}, 'classe_matiere', 'interro', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");

$_GET['type'] = 'interro';
$_GET['numero'] = 1;

ob_start();
try {
    $controller->showForm();
    $htmlF = ob_get_clean();
    if (strpos($htmlF, 'Interrogation 1') !== false && strpos($htmlF, 'Interrogation 2') !== false && strpos($htmlF, 'Interrogation 3') !== false) {
        echo "PASS: Test F — Three occurrences (nombre = 3) rendered Interrogation 1, 2, and 3.\n";
    } else {
        echo "FAIL: Test F missing expected Interrogation 1, 2, or 3 labels!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "FAIL Test F: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST G — Numéro hors limites (nombre = 2, request numero = 3) -> REFUSED
$_GET['type'] = 'devoir';
$_GET['numero'] = 3;

ob_start();
try {
    $controller->showForm();
    $htmlG = ob_get_clean();
    if (strpos($htmlG, 'Accès Refusé') !== false) {
        echo "PASS: Test G — Occurrence numero = 3 for nombre_evaluation = 2 was REFUSED.\n";
    } else {
        echo "FAIL: Test G allowed out of bounds occurrence!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "PASS: Test G — Out of bounds occurrence rejected.\n";
}

// TEST H — Séquence falsifiée (request sequence_id = 8802 when 8801 is active) -> REFUSED
$decisionH = EvaluationSaisieService::canTeacherGradeContext(99010, 99020, 8802, 'devoir');
if (!$decisionH['allowed'] && $decisionH['code'] === 'DENIED_SEQUENCE_MISMATCH') {
    echo "PASS: Test H — Sequence forgery (sequence_id = 8802 when 8801 is active) was REFUSED.\n";
} else {
    echo "FAIL: Test H failed to refuse forged sequence!\n";
    exit(1);
}

// TEST I — Requête complètement forgée (classe 1, matiere 3, sequence 8802, composition, numero 2) -> REFUSED, no grades saved
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'classe_id' => 99010,
    'matiere_id' => 99020,
    'sequence_id' => 8802, // Inactive sequence
    'type' => 'composition',
    'numero_evaluation' => 2, // Invalid occurrence
    'bareme' => 40,
    'coefficient' => 2,
    'grades' => [8809 => ['note' => '18']]
];

ob_start();
try {
    $controller->save();
    $htmlI = ob_get_clean();
    if (strpos($htmlI, 'Accès Refusé') !== false) {
        echo "PASS: Test I — Fully forged POST request (inactive sequence + unauthorized type + invalid occurrence) was REFUSED.\n";
    } else {
        echo "FAIL: Test I allowed forged POST request!\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "PASS: Test I — Fully forged POST request rejected.\n";
}

// Verify no grades were inserted for forged POST
$countForged = $db->query("SELECT COUNT(*) FROM evaluations WHERE lycee_id = 8800 AND sequence_id = 8802")->fetchColumn();
if ((int)$countForged === 0) {
    echo "PASS: Test I Verification — 0 grades saved in database for forged request.\n";
} else {
    echo "FAIL: Forged POST saved grades in database!\n";
    exit(1);
}

echo "\n>>> ALL TESTS A THROUGH I PASSED WITH 100% SUCCESS! <<<\n";
