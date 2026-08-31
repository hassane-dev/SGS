<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';

class EvaluationTypeAndWindowRulesRunner {

    public static function runAllTests() {
        $db = Database::getInstance();
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->beginTransaction();

        try {
            echo "--- Running Mandatory 6 Cases Evaluation Type & Grading Window Tests ---\n";

            // Setup dummy lycee & active academic year
            $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (500, 'Test Lycee 500')");
            $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (500, '2024-2025', '2024-09-01', '2025-06-30', 1)");

            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear['id'];

            // Create open sequence
            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, statut) VALUES (500, 500, {$anneeId}, 'Séquence Open 500', 'ouverte')");

            $_SESSION['user'] = [
                'id' => 500,
                'lycee_id' => 500,
                'role_id' => 6,
                'permissions' => ['note' => ['create_own', 'manage']]
            ];

            $now_start = date('Y-m-d H:i:s', strtotime('-1 day'));
            $now_end = date('Y-m-d H:i:s', strtotime('+1 day'));
            $past_start = date('Y-m-d H:i:s', strtotime('-10 days'));
            $past_end = date('Y-m-d H:i:s', strtotime('-1 day'));

            // -------------------------------------------------------------
            // CAS 1: Période "type_evaluation = composition", séquence ouverte
            // -> l'enseignant doit voir uniquement Composition.
            // -> accès à Devoir refusé.
            // -> POST forcé avec "type=devoir" refusé.
            // -------------------------------------------------------------
            echo "Testing Cas 1: Période 'composition' active, séquence ouverte...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 10,
                'matiere_id' => 20,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end,
                'commentaire' => 'Cas 1 Composition unique'
            ]);

            $isDevoirOpenC1 = Evaluation::isGradingWindowOpen(10, 20, 500, 'devoir');
            $isCompOpenC1 = Evaluation::isGradingWindowOpen(10, 20, 500, 'composition');

            self::assertFalse($isDevoirOpenC1, "Cas 1: Devoir window MUST be closed when only composition is configured.");
            self::assertTrue($isCompOpenC1, "Cas 1: Composition window MUST be open.");

            // Test POST forcé avec type=devoir in EvaluationController::save()
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'classe_id' => 10,
                'matiere_id' => 20,
                'sequence_id' => 500,
                'type' => 'devoir',
                'coefficient' => 1,
                'grades' => [1 => ['note' => '15', 'appreciation' => 'Test']]
            ];

            ob_start();
            $controller = new EvaluationController();
            try {
                $controller->save();
            } catch (Exception $e) {
                // Ignore exit redirects if any
            }
            $saveOutput = ob_get_clean();
            self::assertTrue(strpos($saveOutput, 'Accès Refusé') !== false || strpos($saveOutput, 'fermée') !== false, "Cas 1: POST forcé avec type=devoir MUST be refused server-side.");


            // -------------------------------------------------------------
            // CAS 2: Période "type_evaluation = devoir", séquence ouverte
            // -> uniquement Devoir.
            // -------------------------------------------------------------
            echo "Testing Cas 2: Période 'devoir' active, séquence ouverte...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 10,
                'matiere_id' => 20,
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end,
                'commentaire' => 'Cas 2 Devoir unique'
            ]);

            $isDevoirOpenC2 = Evaluation::isGradingWindowOpen(10, 20, 500, 'devoir');
            $isCompOpenC2 = Evaluation::isGradingWindowOpen(10, 20, 500, 'composition');

            self::assertTrue($isDevoirOpenC2, "Cas 2: Devoir window MUST be open.");
            self::assertFalse($isCompOpenC2, "Cas 2: Composition window MUST be closed when only devoir is configured.");


            // -------------------------------------------------------------
            // CAS 3: Période "type_evaluation = tous", séquence ouverte
            // -> Devoir et Composition disponibles.
            // -------------------------------------------------------------
            echo "Testing Cas 3: Période 'tous' active, séquence ouverte...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 10,
                'matiere_id' => 20,
                'sequence_id' => 500,
                'type_evaluation' => 'tous',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end,
                'commentaire' => 'Cas 3 Tous types'
            ]);

            $isDevoirOpenC3 = Evaluation::isGradingWindowOpen(10, 20, 500, 'devoir');
            $isCompOpenC3 = Evaluation::isGradingWindowOpen(10, 20, 500, 'composition');

            self::assertTrue($isDevoirOpenC3, "Cas 3: Devoir window MUST be open.");
            self::assertTrue($isCompOpenC3, "Cas 3: Composition window MUST be open.");


            // -------------------------------------------------------------
            // CAS 4: Période Composition expirée
            // -> Composition refusée.
            // -> aucune activation accidentelle du Devoir par fallback si cette règle est la règle explicite applicable.
            // -------------------------------------------------------------
            echo "Testing Cas 4: Période 'composition' expirée...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 10,
                'matiere_id' => 20,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $past_start,
                'date_fermeture_saisie' => $past_end,
                'commentaire' => 'Cas 4 Composition expirée'
            ]);

            $isDevoirOpenC4 = Evaluation::isGradingWindowOpen(10, 20, 500, 'devoir');
            $isCompOpenC4 = Evaluation::isGradingWindowOpen(10, 20, 500, 'composition');

            self::assertFalse($isCompOpenC4, "Cas 4: Expired composition window MUST be refused.");
            self::assertFalse($isDevoirOpenC4, "Cas 4: Devoir MUST NOT be accidentally activated by fallback when explicit rule exists.");


            // -------------------------------------------------------------
            // CAS 5: Période Devoir active + Composition non configurée
            // -> uniquement Devoir.
            // -------------------------------------------------------------
            echo "Testing Cas 5: Période 'devoir' active + composition non configurée...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 10,
                'matiere_id' => 20,
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end,
                'commentaire' => 'Cas 5 Devoir active'
            ]);

            $isDevoirOpenC5 = Evaluation::isGradingWindowOpen(10, 20, 500, 'devoir');
            $isCompOpenC5 = Evaluation::isGradingWindowOpen(10, 20, 500, 'composition');

            self::assertTrue($isDevoirOpenC5, "Cas 5: Devoir MUST be open.");
            self::assertFalse($isCompOpenC5, "Cas 5: Unconfigured composition MUST be refused when explicit devoir rule exists.");


            // -------------------------------------------------------------
            // CAS 6: Aucune règle explicite pour la cible + séquence ouverte
            // -> appliquer le fallback prévu par le moteur (Devoir = true, Composition = true).
            // -------------------------------------------------------------
            echo "Testing Cas 6: Aucune règle explicite + séquence ouverte...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");

            $isDevoirOpenC6 = Evaluation::isGradingWindowOpen(10, 20, 500, 'devoir');
            $isCompOpenC6 = Evaluation::isGradingWindowOpen(10, 20, 500, 'composition');

            self::assertTrue($isDevoirOpenC6, "Cas 6: Devoir fallback MUST be open when sequence is open.");
            self::assertTrue($isCompOpenC6, "Cas 6: Composition fallback MUST be open when sequence is open.");


            echo "\n>>> ALL 6 MANDATORY TEST CASES PASSED SUCCESSFULLY! <<<\n";

        } finally {
            $db->rollBack();
        }
    }

    private static function assertTrue($cond, $msg) {
        if (!$cond) {
            throw new Exception("Assertion Failed: " . $msg);
        }
        echo "  [PASS] " . $msg . "\n";
    }

    private static function assertFalse($cond, $msg) {
        if ($cond) {
            throw new Exception("Assertion Failed: " . $msg);
        }
        echo "  [PASS] " . $msg . "\n";
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    EvaluationTypeAndWindowRulesRunner::runAllTests();
}
