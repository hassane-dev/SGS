<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/controllers/ParametresEvaluationController.php';
require_once __DIR__ . '/../src/controllers/AffectationPedagogiqueController.php';

class ParametresEvaluationCascadeTestRunner {

    public static function runAllTests() {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            echo "--- Running ParametresEvaluation Cascade & Rehydration Integration Tests ---\n";

            // Setup dummy data
            $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (300, 'Lycee Cascade Alpha')");
            $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (301, 'Lycee Cascade Beta')");
            $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (300, '2024-2025', '2024-09-01', '2025-06-30', 1)");

            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear['id'];

            // Cycles
            $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (300, 'Collège', 300)");
            $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (301, 'Lycée', 300)");

            // Classes: Collège without series (6ème 1) and Lycée with series (2nde C 1)
            $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (300, '6ème', '', '1', 300, 300)");
            $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (301, '2nde', 'C', '1', 301, 300)");

            $_SESSION['user'] = [
                'id' => 300,
                'lycee_id' => 300,
                'role_id' => 3,
                'permissions' => [
                    'evaluation' => ['manage_settings'],
                    'pedagogy' => ['manage_affectations', 'view_affectations']
                ]
            ];

            // Test 1: Cascade API endpoint - getNiveaux by cycle
            $_GET['cycle_id'] = 300;
            $niveauxCollege = Classe::findDistinctNiveauxByCycle(300, 300);
            self::assertTrue(in_array('6ème', $niveauxCollege), "1. getNiveaux for Collège includes 6ème.");

            // Test 2: Cascade API endpoint - findAvailableNumeros without series
            $numerosCollege = Classe::findAvailableNumeros('6ème', '', 300, 300);
            self::assertTrue(in_array('1', $numerosCollege), "2. findAvailableNumeros for 6ème returns 1.");

            // Test 3: Cascade API endpoint - resolve classe_id
            $resolvedIdCollege = Classe::findIdByDetails(300, '6ème', '', '1', 300);
            self::assertEquals(300, (int)$resolvedIdCollege, "3. Resolved class ID for 6ème 1 is 300.");

            // Test 4: Cascade API endpoint - resolve class with series (2nde C 1)
            $resolvedIdLycee = Classe::findIdByDetails(300, '2nde', 'C', '1', 301);
            self::assertEquals(301, (int)$resolvedIdLycee, "4. Resolved class ID for 2nde C 1 is 301.");

            // Test 5: Save setting created via resolved cascade classe_id (scope: classe)
            $saveData = [
                'type' => 'classe',
                'classe_id' => 300,
                'matiere_id' => null,
                'enseignant_id' => null,
                'sequence_id' => null,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => '2025-01-01 00:00:00',
                'date_fermeture_saisie' => '2025-12-31 23:59:59',
                'commentaire' => 'Cascade Created'
            ];
            $resSave = ParametresEvaluation::save($saveData);
            self::assertTrue($resSave, "5. ParametresEvaluation saved successfully with resolved cascade classe_id.");

            // Test 6: Rehydration in edit mode - ParametresEvaluationController::edit() resolves initialValues
            // Find created id
            $createdSetting = $db->query("SELECT id FROM parametres_evaluations WHERE lycee_id = 300 AND classe_id = 300 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $settingId = $createdSetting['id'];

            $_GET['id'] = $settingId;
            $paramToEdit = ParametresEvaluation::findById($settingId);
            self::assertTrue($paramToEdit !== false, "6. Setting found for editing.");

            $classeDetails = Classe::findById($paramToEdit['classe_id']);
            $initialValues = [
                'cycleId' => $classeDetails['cycle_id'],
                'niveau' => $classeDetails['niveau'],
                'serie' => $classeDetails['serie'] ?? '',
                'numero' => $classeDetails['numero'] ?? '',
                'classeId' => $paramToEdit['classe_id'],
                'matiereId' => $paramToEdit['matiere_id'] ?? '',
                'teacherId' => $paramToEdit['enseignant_id'] ?? ''
            ];

            self::assertEquals(300, (int)$initialValues['cycleId'], "6b. Rehydration cycleId is 300.");
            self::assertEquals('6ème', (string)$initialValues['niveau'], "6c. Rehydration niveau is 6ème.");
            self::assertEquals('1', (string)$initialValues['numero'], "6d. Rehydration numero is 1.");

            // Test 7: Scope Transition & Field Cleanup on Edit (classe -> global)
            $updateDataGlobal = [
                'type' => 'global',
                'classe_id' => 300,
                'matiere_id' => null,
                'enseignant_id' => null,
                'sequence_id' => null,
                'type_evaluation' => 'tous',
                'date_ouverture_saisie' => '2025-01-01 00:00:00',
                'date_fermeture_saisie' => '2025-12-31 23:59:59',
                'commentaire' => 'Transitioned to Global'
            ];
            $resUpdateGlobal = ParametresEvaluation::update($settingId, $updateDataGlobal);
            self::assertTrue($resUpdateGlobal, "7. Update transition to global succeeded.");

            $paramAfterGlobal = ParametresEvaluation::findById($settingId);
            self::assertEquals('global', $paramAfterGlobal['type'], "7b. Type is global.");
            self::assertNull($paramAfterGlobal['classe_id'], "7c. classe_id neutralized to NULL.");

            // Test 8: Multi-tenant Lycee Isolation - Endpoints refuse foreign lycee_id
            $_SESSION['user']['lycee_id'] = 301; // Switch session to Lycee 301
            $foreignNiveaux = Classe::findDistinctNiveauxByCycle(300, 301);
            self::assertTrue(empty($foreignNiveaux), "8. Foreign lycee_id returns empty levels.");

            $foreignClass = Classe::findIdByDetails(301, '6ème', '', '1', 300);
            self::assertNull($foreignClass, "8b. Foreign lycee_id resolves NULL for class ID.");

            echo "\n>>> ALL CASCADE INTEGRATION TESTS PASSED SUCCESSFULLY! <<<\n";

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

    private static function assertEquals($expected, $actual, $msg) {
        if ((string)$expected !== (string)$actual) {
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
    ParametresEvaluationCascadeTestRunner::runAllTests();
}
