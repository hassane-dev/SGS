<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';

class EvaluationTypeDecisionRunner {

    public static function runAllTests() {
        echo "=================================================================\n";
        echo "Running Evaluation Type Decision (Devoir / Composition / Tous) Test Suite\n";
        echo "=================================================================\n";

        $db = Database::getInstance();
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->beginTransaction();

        try {
            // Clean test data
            $db->exec("DELETE FROM evaluations WHERE lycee_id = 600");
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 600");
            $db->exec("DELETE FROM classe_matieres WHERE classe_id = 60010");
            $db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id = 600");
            $db->exec("DELETE FROM etudes WHERE eleve_id = 60030");
            $db->exec("DELETE FROM eleves WHERE lycee_id = 600");
            $db->exec("DELETE FROM classes WHERE lycee_id = 600");
            $db->exec("DELETE FROM matieres WHERE lycee_id = 600");
            $db->exec("DELETE FROM sequences WHERE lycee_id = 600");
            $db->exec("DELETE FROM utilisateurs WHERE id_user = 600");
            $db->exec("DELETE FROM annees_academiques WHERE id = 600");
            $db->exec("DELETE FROM param_lycee WHERE id = 600");

            // Setup lycee & active year
            $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES (600, 'Test Lycee 600')");
            $activeYear = AnneeAcademique::findActive();
            if (!$activeYear) {
                $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (600, '2024-2025', '2024-09-01', '2025-06-30', 1)");
                $activeYear = AnneeAcademique::findActive();
            }
            $anneeId = $activeYear['id'];

            // Create open sequence & user
            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (600, 600, {$anneeId}, 'Séquence Open 600', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
            $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id) VALUES (600, 600, 'Professeur', 'Test600', 1)");

            $db->exec("INSERT INTO classes (id_classe, lycee_id, niveau, serie, numero) VALUES (60010, 600, '6ème', 'A', '1')");
            $db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (60020, 600, 'Physique')");
            $db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (60010, 60020, 2)");
            $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, date_naissance) VALUES (60030, 600, 'Martin', 'Paul', '2012-05-05')");
            $db->exec("INSERT INTO etudes (eleve_id, classe_id, is_active, annee_academique_id) VALUES (60030, 60010, 1, {$anneeId})");
            $db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, statut, date_debut) VALUES (600, 60010, 60020, {$anneeId}, 'actif', '2024-09-01')");

            $_SESSION['user'] = [
                'id' => 600,
                'lycee_id' => 600,
                'role_id' => 1,
                'permissions' => ['note' => ['create_own', 'manage', '*']]
            ];

            $now_start = date('Y-m-d H:i:s', strtotime('-1 day'));
            $now_end = date('Y-m-d H:i:s', strtotime('+1 day'));

            // -------------------------------------------------------------
            // Test 1: type_evaluation = devoir, type demandé = devoir -> AUTORISÉ
            // -------------------------------------------------------------
            echo "Test 1: type_evaluation = devoir, type demandé = devoir -> AUTORISÉ...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 600");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            $d1 = EvaluationSaisieService::canTeacherGradeContext(60010, 60020, 600, 'devoir', 600, null, 600);
            self::assertTrue($d1['allowed'], "Test 1: Devoir asked under 'devoir' setting MUST be allowed.");

            // -------------------------------------------------------------
            // Test 2: type_evaluation = devoir, type demandé = composition -> REFUSÉ
            // -------------------------------------------------------------
            echo "Test 2: type_evaluation = devoir, type demandé = composition -> REFUSÉ...\n";
            $d2 = EvaluationSaisieService::canTeacherGradeContext(60010, 60020, 600, 'composition', 600, null, 600);
            self::assertFalse($d2['allowed'], "Test 2: Composition asked under 'devoir' setting MUST be refused.");

            // -------------------------------------------------------------
            // Test 3: type_evaluation = composition, type demandé = composition -> AUTORISÉ
            // -------------------------------------------------------------
            echo "Test 3: type_evaluation = composition, type demandé = composition -> AUTORISÉ...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 600");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            $d3 = EvaluationSaisieService::canTeacherGradeContext(60010, 60020, 600, 'composition', 600, null, 600);
            self::assertTrue($d3['allowed'], "Test 3: Composition asked under 'composition' setting MUST be allowed.");

            // -------------------------------------------------------------
            // Test 4: type_evaluation = composition, type demandé = devoir -> REFUSÉ
            // -------------------------------------------------------------
            echo "Test 4: type_evaluation = composition, type demandé = devoir -> REFUSÉ...\n";
            $d4 = EvaluationSaisieService::canTeacherGradeContext(60010, 60020, 600, 'devoir', 600, null, 600);
            self::assertFalse($d4['allowed'], "Test 4: Devoir asked under 'composition' setting MUST be refused.");

            // -------------------------------------------------------------
            // Test 5: type_evaluation = tous, type demandé = devoir -> AUTORISÉ
            // -------------------------------------------------------------
            echo "Test 5: type_evaluation = tous, type demandé = devoir -> AUTORISÉ...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 600");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600,
                'type_evaluation' => 'tous',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            $d5 = EvaluationSaisieService::canTeacherGradeContext(60010, 60020, 600, 'devoir', 600, null, 600);
            self::assertTrue($d5['allowed'], "Test 5: Devoir asked under 'tous' setting MUST be allowed.");

            // -------------------------------------------------------------
            // Test 6: type_evaluation = tous, type demandé = composition -> AUTORISÉ
            // -------------------------------------------------------------
            echo "Test 6: type_evaluation = tous, type demandé = composition -> AUTORISÉ...\n";
            $d6 = EvaluationSaisieService::canTeacherGradeContext(60010, 60020, 600, 'composition', 600, null, 600);
            self::assertTrue($d6['allowed'], "Test 6: Composition asked under 'tous' setting MUST be allowed.");

            // -------------------------------------------------------------
            // Test 7: type_evaluation = tous, aucun type demandé -> pas de "devoir" automatique, sollicitation du choix (redirect select_evaluation)
            // -------------------------------------------------------------
            echo "Test 7: type_evaluation = tous, aucun type demandé -> redirection vers sollicitation du choix (select_evaluation)...\n";
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = [];
            unset($_SERVER['HTTP_LOCATION']);

            $controller = new EvaluationController();
            ob_start();
            try {
                $controller->directSaisie(60010, 60020);
            } catch (Exception $e) {}
            ob_get_clean();

            $redirectLoc = $_SERVER['HTTP_LOCATION'] ?? '';
            self::assertTrue(strpos($redirectLoc, '/evaluations/select_evaluation') !== false, "Test 7: directSaisie under 'tous' with no type MUST redirect to select_evaluation to solicit choice, got '{$redirectLoc}'.");

            // Tester également showForm sans type
            $_GET = [
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600
            ];
            unset($_SERVER['HTTP_LOCATION']);

            ob_start();
            try {
                $controller->showForm();
            } catch (Exception $e) {}
            ob_get_clean();

            $redirectFormLoc = $_SERVER['HTTP_LOCATION'] ?? '';
            self::assertTrue(strpos($redirectFormLoc, '/evaluations/select_evaluation') !== false, "Test 7: showForm under 'tous' with no type MUST redirect to select_evaluation to solicit choice, got '{$redirectFormLoc}'.");

            // -------------------------------------------------------------
            // Test 8: Valeurs invalides (Composition, COMPOSITION, composition , foo) -> REFUSÉ
            // -------------------------------------------------------------
            echo "Test 8: Invalid type strings MUST be strictly refused...\n";
            $invalidTypes = ['Composition', 'COMPOSITION', 'composition ', ' foo ', 'devoir ', 'DEVOIR', 'bar'];
            foreach ($invalidTypes as $inv) {
                $dInv = EvaluationSaisieService::canTeacherGradeContext(60010, 60020, 600, $inv, 600, null, 600);
                self::assertFalse($dInv['allowed'], "Test 8: Invalid type '{$inv}' MUST be refused by EvaluationSaisieService.");
            }

            // -------------------------------------------------------------
            // Test 9: Contournement URL (param = composition, URL type=devoir) -> REFUS
            // -------------------------------------------------------------
            echo "Test 9: URL forced type=devoir when param=composition -> REFUSED...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 600");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = [
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600,
                'type' => 'devoir'
            ];

            $controller = new EvaluationController();
            ob_start();
            try {
                $controller->showForm();
            } catch (Exception $e) {}
            $out9 = ob_get_clean();
            self::assertTrue(strpos($out9, 'Accès Refusé') !== false, "Test 9: URL forced type=devoir under composition param MUST show Accès Refusé.");

            // -------------------------------------------------------------
            // Test 10: Contournement POST (param = devoir, POST type=composition) -> REFUS
            // -------------------------------------------------------------
            echo "Test 10: POST forced type=composition when param=devoir -> REFUSED...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 600");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'classe_id' => 60010,
                'matiere_id' => 60020,
                'sequence_id' => 600,
                'type' => 'composition',
                'coefficient' => 2,
                'grades' => [60030 => ['note' => '15', 'appreciation' => 'Forbidden composition grade']]
            ];

            ob_start();
            try {
                $controller->save();
            } catch (Exception $e) {}
            $out10 = ob_get_clean();
            self::assertTrue(strpos($out10, 'Accès Refusé') !== false, "Test 10: POST forced type=composition under devoir param MUST show Accès Refusé.");

            // Verify no grade was persisted
            $stmt10 = $db->prepare("SELECT COUNT(*) FROM evaluations WHERE lycee_id = 600 AND eleve_id = 60030");
            $stmt10->execute();
            self::assertTrue((int)$stmt10->fetchColumn() === 0, "Test 10: No grade record MUST be persisted upon POST refusal.");

            echo "\n>>> ALL 10 TYPE DECISION TESTS PASSED SUCCESSFULLY! <<<\n";

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
    EvaluationTypeDecisionRunner::runAllTests();
}
