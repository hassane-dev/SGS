<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';

class ParametresEvaluationCrudRunner {

    public static function runAllTests() {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            echo "--- Running ParametresEvaluation CRUD & Scope Transitions Integration Tests ---\n";

            // Setup dummy lycee & active academic year
            $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (200, 'Lycee Alpha')");
            $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (201, 'Lycee Foreign')");
            $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (200, '2024-2025', '2024-09-01', '2025-06-30', 1)");

            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear['id'];

            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (200, 200, {$anneeId}, 'Seq Alpha', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");

            $_SESSION['user'] = [
                'id' => 200,
                'lycee_id' => 200,
                'role_id' => 3,
                'permissions' => ['evaluation' => ['manage_settings']]
            ];

            // Test 1: Create a initial period (scope: enseignant)
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, enseignant_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie, commentaire)
                       VALUES (200, 200, {$anneeId}, 'enseignant', 10, 20, 30, 200, 'tous', '2020-01-01 00:00:00', '2020-02-01 00:00:00', 'Comment initial')");

            $created = ParametresEvaluation::findById(200);
            self::assertTrue($created !== false, "1. Record 200 exists and is found by findById.");
            self::assertEquals('enseignant', $created['type'], "1b. Scope type is enseignant.");

            // Test 2: Update scope transition: 'enseignant' -> 'global' (must clean classe_id, matiere_id, enseignant_id to NULL)
            $updateDataGlobal = [
                'type' => 'global',
                'classe_id' => 10,
                'matiere_id' => 20,
                'enseignant_id' => 30,
                'sequence_id' => 200,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => '2020-01-01 00:00:00',
                'date_fermeture_saisie' => '2099-12-31 23:59:59',
                'commentaire' => 'Updated to global'
            ];

            $resGlobal = ParametresEvaluation::update(200, $updateDataGlobal);
            self::assertTrue($resGlobal, "2. Update to global scope succeeded.");

            $updatedGlobal = ParametresEvaluation::findById(200);
            self::assertEquals('global', $updatedGlobal['type'], "2b. Scope type changed to global.");
            self::assertNull($updatedGlobal['classe_id'], "2c. classe_id cleaned to NULL.");
            self::assertNull($updatedGlobal['matiere_id'], "2d. matiere_id cleaned to NULL.");
            self::assertNull($updatedGlobal['enseignant_id'], "2e. enseignant_id cleaned to NULL.");

            // Test 3: Immediate reflection in Evaluation::isGradingWindowOpen()
            $isOpenNow = Evaluation::isGradingWindowOpen(10, 20, 200, 'devoir');
            self::assertTrue($isOpenNow, "3. Updated active period immediately opens grading window.");

            // Test 4: Scope transition: 'global' -> 'classe_matiere'
            $updateDataCM = [
                'type' => 'classe_matiere',
                'classe_id' => 15,
                'matiere_id' => 25,
                'enseignant_id' => null,
                'sequence_id' => 200,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => '2020-01-01 00:00:00',
                'date_fermeture_saisie' => '2020-02-01 00:00:00', // Expired
                'commentaire' => 'Expired CM'
            ];

            $resCM = ParametresEvaluation::update(200, $updateDataCM);
            self::assertTrue($resCM, "4. Update to classe_matiere scope succeeded.");

            $updatedCM = ParametresEvaluation::findById(200);
            self::assertEquals(15, (int)$updatedCM['classe_id'], "4b. classe_id is set to 15.");
            self::assertEquals(25, (int)$updatedCM['matiere_id'], "4c. matiere_id is set to 25.");
            self::assertNull($updatedCM['enseignant_id'], "4d. enseignant_id cleaned to NULL.");

            // Test 5: Immediate reflection of expired rule
            $isOpenCM = Evaluation::isGradingWindowOpen(15, 25, 200, 'composition');
            self::assertFalse($isOpenCM, "5. Expired specific rule immediately blocks grading window.");

            // Test 6: Foreign Lycee Tenant Isolation
            $_SESSION['user']['lycee_id'] = 201;
            $resForeign = ParametresEvaluation::update(200, $updateDataGlobal);
            self::assertFalse($resForeign, "6. Updating record belonging to another lycee must fail.");

            $foreignFind = ParametresEvaluation::findById(200);
            self::assertFalse($foreignFind, "6b. findById for foreign lycee record returns false (404).");

            echo "\n>>> ALL PARAMETRES_EVALUATION CRUD INTEGRATION TESTS PASSED! <<<\n";

        } finally {
            $db->rollBack();
        }
    }

    private static function assertTrue($cond, $msg) {
        if (!$cond) {
            throw new Exception("Assertion Failed: " . $msg);
        }
        echo "PASS: " . $msg . "\n";
    }

    private static function assertFalse($cond, $msg) {
        if ($cond) {
            throw new Exception("Assertion Failed: " . $msg);
        }
        echo "PASS: " . $msg . "\n";
    }

    private static function assertEquals($expected, $actual, $msg) {
        if ($expected !== $actual) {
            throw new Exception("Assertion Failed: {$msg} Expected: " . var_export($expected, true) . " Got: " . var_export($actual, true));
        }
        echo "PASS: " . $msg . "\n";
    }

    private static function assertNull($actual, $msg) {
        if ($actual !== null) {
            throw new Exception("Assertion Failed: {$msg} Expected null, got: " . var_export($actual, true));
        }
        echo "PASS: " . $msg . "\n";
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    ParametresEvaluationCrudRunner::runAllTests();
}
