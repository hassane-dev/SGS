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
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';

class EvaluationTypeAndWindowRulesRunner {

    public static function runAllTests() {
        $db = Database::getInstance();
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->beginTransaction();

        try {
            echo "--- Running Mandatory 14-Scenario Evaluation Type & Grading Window Test Matrix ---\n";

            // Clean fixture records
            $db->exec("DELETE FROM evaluations WHERE lycee_id = 500");
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            $db->exec("DELETE FROM classe_matieres WHERE classe_id = 50010");
            $db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id = 500");
            $db->exec("DELETE FROM etudes WHERE eleve_id = 50030");
            $db->exec("DELETE FROM eleves WHERE lycee_id = 500");
            $db->exec("DELETE FROM classes WHERE lycee_id = 500");
            $db->exec("DELETE FROM matieres WHERE lycee_id = 500");
            $db->exec("DELETE FROM sequences WHERE lycee_id = 500");
            $db->exec("DELETE FROM utilisateurs WHERE id_user = 500");
            $db->exec("DELETE FROM annees_academiques WHERE id = 500");
            $db->exec("DELETE FROM param_lycee WHERE id = 500");

            // Setup dummy lycee & active academic year
            $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES (500, 'Test Lycee 500')");
            $activeYear = AnneeAcademique::findActive();
            if (!$activeYear) {
                $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (500, '2024-2025', '2024-09-01', '2025-06-30', 1)");
                $activeYear = AnneeAcademique::findActive();
            }
            $anneeId = $activeYear['id'];

            // Create open sequence & user
            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (500, 500, {$anneeId}, 'Séquence Open 500', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");
            $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id) VALUES (500, 500, 'Professeur', 'Test', 6)");

            // Setup test class A & B, matiere, student using unique IDs
            $db->exec("INSERT INTO classes (id_classe, lycee_id, niveau, serie, numero) VALUES (50010, 500, '6ème', 'A', '1')");
            $db->exec("INSERT INTO classes (id_classe, lycee_id, niveau, serie, numero) VALUES (50011, 500, '6ème', 'B', '2')");
            $db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (50020, 500, 'Mathématiques')");
            $db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (50010, 50020, 2)");
            $db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (50011, 50020, 2)");
            $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, date_naissance) VALUES (50030, 500, 'Dupont', 'Jean', '2012-01-01')");
            $db->exec("INSERT INTO etudes (eleve_id, classe_id, is_active, annee_academique_id) VALUES (50030, 50010, 1, {$anneeId})");
            $db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, statut, date_debut) VALUES (500, 50010, 50020, {$anneeId}, 'actif', '2024-09-01')");
            $db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, statut, date_debut) VALUES (500, 50011, 50020, {$anneeId}, 'actif', '2024-09-01')");

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
            $future_start = date('Y-m-d H:i:s', strtotime('+1 day'));
            $future_end = date('Y-m-d H:i:s', strtotime('+10 days'));

            // -------------------------------------------------------------
            // CAS 1: Période "type_evaluation = devoir" uniquement
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 1: Période 'devoir' uniquement...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 1: Devoir window MUST be open.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 1: Composition window MUST be closed when only devoir is configured.");

            // -------------------------------------------------------------
            // CAS 2: Période "type_evaluation = composition" uniquement
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 2: Période 'composition' uniquement...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 2: Devoir window MUST be closed when only composition is configured.");
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 2: Composition window MUST be open.");

            // -------------------------------------------------------------
            // CAS 3: Période "type_evaluation = tous"
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 3: Période 'tous'...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'tous',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 3: Devoir window MUST be open under 'tous'.");
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 3: Composition window MUST be open under 'tous'.");

            // -------------------------------------------------------------
            // CAS 4: Règle globale vs classe (Global -> devoir, Classe A -> composition, Classe B -> fallback sur Global)
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 4: Règle globale (devoir) vs règle classe (composition)...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'global',
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);
            ParametresEvaluation::save([
                'type' => 'classe',
                'classe_id' => 50010,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            // Class A (50010): Specific class rule (composition) MUST override global rule
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 4: Class A targeted rule (composition) MUST override global rule (devoir).");
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 4: Class A targeted rule (composition) MUST be open.");

            // Class B (50011): No class rule exists, so Global rule (devoir) MUST apply
            self::assertTrue(Evaluation::isGradingWindowOpen(50011, 50020, 500, 'devoir'), "Cas 4b: Class B (untargeted) MUST continue using Global rule (devoir).");
            self::assertFalse(Evaluation::isGradingWindowOpen(50011, 50020, 500, 'composition'), "Cas 4b: Class B MUST block composition under Global rule (devoir).");

            // -------------------------------------------------------------
            // CAS 4c: Validation des 3 instants temporels (10/11 AVANT, 15/11 PENDANT, 12/12 APRÈS)
            // Règle: Global + Trimestre 1 + Devoir + 11/11/2025 07:30 -> 11/12/2025 16:00
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 4c: Validation des 3 moments simulés (AVANT, PENDANT, APRÈS)...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'global',
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => '2025-11-11 07:30:00',
                'date_fermeture_saisie' => '2025-12-11 16:00:00'
            ]);

            $t1_before = '2025-11-10 07:00:00';
            $t2_during = '2025-11-15 10:00:00';
            $t3_after  = '2025-12-12 10:00:00';

            // Moment 1: 10/11/2025 07:00 -> Both closed
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir', $t1_before), "Cas 4c (10/11): Devoir MUST be closed before opening date.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition', $t1_before), "Cas 4c (10/11): Composition MUST be closed before opening date.");

            // Moment 2: 15/11/2025 10:00 -> Devoir open, Composition closed
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir', $t2_during), "Cas 4c (15/11): Devoir MUST be open during window.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition', $t2_during), "Cas 4c (15/11): Composition MUST be closed during devoir-only window.");

            // Moment 3: 12/12/2025 10:00 -> Both closed (Expired rule MUST NOT fall back)
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir', $t3_after), "Cas 4c (12/12): Devoir MUST be closed after closing date.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition', $t3_after), "Cas 4c (12/12): Composition MUST be closed after closing date (NO FALLBACK).");

            // -------------------------------------------------------------
            // CAS 5: Règle portée classe
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 5: Règle portée classe...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe',
                'classe_id' => 50010,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 5: Class scope composition rule MUST be open.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 5: Class scope composition rule MUST block devoir.");

            // -------------------------------------------------------------
            // CAS 6: Règle portée matière
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 6: Règle portée matière...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'matiere',
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 6: Matiere scope devoir rule MUST be open.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 6: Matiere scope devoir rule MUST block composition.");

            // -------------------------------------------------------------
            // CAS 7: Règle portée classe_matiere
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 7: Règle portée classe_matiere...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 7: Classe_matiere scope composition rule MUST be open.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 7: Classe_matiere scope composition rule MUST block devoir.");

            // -------------------------------------------------------------
            // CAS 8: Règle portée enseignant
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 8: Règle portée enseignant...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'enseignant',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'enseignant_id' => 500,
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 8: Teacher scope devoir rule MUST be open.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 8: Teacher scope devoir rule MUST block composition.");

            // -------------------------------------------------------------
            // CAS 9: Règle expirée
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 9: Règle expirée...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $past_start,
                'date_fermeture_saisie' => $past_end
            ]);
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 9: Expired composition rule MUST be closed.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 9: Devoir MUST NOT fall back when explicit rule exists.");

            // -------------------------------------------------------------
            // CAS 10: Règle future
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 10: Règle future...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $future_start,
                'date_fermeture_saisie' => $future_end
            ]);
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 10: Future devoir rule MUST be closed.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 10: Composition MUST NOT fall back when explicit rule exists.");

            // -------------------------------------------------------------
            // CAS 11: Aucune règle explicite + séquence ouverte
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 11: Aucune règle explicite + séquence ouverte...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 11: Devoir default fallback MUST be open.");
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 11: Composition default fallback MUST be open.");

            // -------------------------------------------------------------
            // CAS 12: Tentative GET frauduleuse (?type=devoir pendant période composition)
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 12: Tentative GET frauduleuse (?type=devoir pendant période composition)...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            ParametresEvaluation::save([
                'type' => 'classe_matiere',
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type_evaluation' => 'composition',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = [
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type' => 'devoir'
            ];

            ob_start();
            $controller = new EvaluationController();
            try {
                $controller->showForm();
            } catch (Exception $e) {
                // Ignore exit redirects
            }
            $showOutput = ob_get_clean();
            self::assertTrue(strpos($showOutput, 'Accès Refusé') !== false || strpos($showOutput, 'fermée') !== false, "Cas 12: GET frauduleux avec type=devoir MUST be refused server-side.");

            // -------------------------------------------------------------
            // CAS 13: Tentative POST frauduleuse (POST type=devoir pendant période composition)
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 13: Tentative POST frauduleuse (POST type=devoir pendant période composition)...\n";
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type' => 'devoir',
                'coefficient' => 2,
                'grades' => [50030 => ['note' => '18', 'appreciation' => 'Fraudulent grade']]
            ];

            ob_start();
            try {
                $controller->save();
            } catch (Exception $e) {
                // Ignore exit redirects
            }
            $saveOutput = ob_get_clean();
            self::assertTrue(strpos($saveOutput, 'Accès Refusé') !== false || strpos($saveOutput, 'fermée') !== false, "Cas 13: POST frauduleux avec type=devoir MUST be refused server-side.");

            // Verify no grades were inserted with type 'devoir'
            $stmtCheckDevoir = $db->prepare("SELECT COUNT(*) FROM evaluations WHERE lycee_id = 500 AND classe_id = 50010 AND type = 'devoir'");
            $stmtCheckDevoir->execute();
            self::assertTrue((int)$stmtCheckDevoir->fetchColumn() === 0, "Cas 13: Fraudulent devoir grade MUST NOT be persisted in evaluations table.");

            // -------------------------------------------------------------
            // CAS 14: Vérification de evaluations.type pour un POST valide
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 14: Sauvegarde valide et vérification de evaluations.type...\n";
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 500,
                'type' => 'composition',
                'coefficient' => 2,
                'grades' => [50030 => ['note' => '16.5', 'appreciation' => 'Excellente composition']]
            ];

            ob_start();
            try {
                $controller->save();
            } catch (Exception $e) {
                // Ignore exit redirects
            }
            $validSaveOutput = ob_get_clean();

            $stmtCheckComp = $db->prepare("SELECT * FROM evaluations WHERE lycee_id = 500 AND classe_id = 50010 AND eleve_id = 50030 AND sequence_id = 500");
            $stmtCheckComp->execute();
            $gradeRecord = $stmtCheckComp->fetch(PDO::FETCH_ASSOC);

            self::assertTrue(!empty($gradeRecord), "Cas 14: Grade record MUST be created for authorized composition type.");
            self::assertTrue($gradeRecord['type'] === 'composition', "Cas 14: evaluations.type MUST be persisted as 'composition'.");
            self::assertTrue((float)$gradeRecord['note'] === 16.5, "Cas 14: Note value MUST match saved grade.");


            // -------------------------------------------------------------
            // CAS 15: Deux séquences ouvertes simultanément (500 et 501) - Mismatch test
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 15: Deux séquences ouvertes simultanément (500 = Trimestre 1, 501 = Séquence 2)...\n";
            $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 500");
            $db->exec("DELETE FROM sequences WHERE id = 501 AND lycee_id = 500");
            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (501, 500, {$anneeId}, 'Séquence 2 Open', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");

            ParametresEvaluation::save([
                'type' => 'global',
                'sequence_id' => 500,
                'type_evaluation' => 'devoir',
                'date_ouverture_saisie' => $now_start,
                'date_fermeture_saisie' => $now_end
            ]);

            // Sequence 500 (matching rule): Devoir open, Composition closed
            self::assertTrue(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'devoir'), "Cas 15a: Sequence 500 Devoir MUST be open.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 500, 'composition'), "Cas 15a: Sequence 500 Composition MUST be closed.");

            // Sequence 501 (mismatched): BOTH Devoir and Composition MUST be closed (no implicit fallback!)
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 501, 'devoir'), "Cas 15b: Mismatched Sequence 501 Devoir MUST be closed when rule is set on Sequence 500.");
            self::assertFalse(Evaluation::isGradingWindowOpen(50010, 50020, 501, 'composition'), "Cas 15b: Mismatched Sequence 501 Composition MUST be closed when rule is set on Sequence 500.");

            // -------------------------------------------------------------
            // CAS 16: directSaisie() résolution dynamique de la séquence avec fenêtre active
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 16: directSaisie() sélectionne la séquence avec fenêtre active...\n";
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = [];

            ob_start();
            try {
                $controller->directSaisie(50010, 50020);
            } catch (Exception $e) {
                // Ignore exit redirects
            }
            $directOutput = ob_get_clean();

            // Should redirect to form with sequence_id=500 (which has active window), NOT sequence_id=501
            self::assertTrue(strpos($_SERVER['HTTP_LOCATION'] ?? '', 'sequence_id=500') !== false, "Cas 16: directSaisie MUST redirect to sequence_id=500 (active window sequence).");

            // -------------------------------------------------------------
            // CAS 17: GET/POST Anti-Tampering sur Séquence 501
            // -------------------------------------------------------------
            echo "Testing Matrix Cas 17: Anti-tampering POST sur Séquence 501 non autorisée...\n";
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'classe_id' => 50010,
                'matiere_id' => 50020,
                'sequence_id' => 501,
                'type' => 'devoir',
                'coefficient' => 2,
                'grades' => [50030 => ['note' => '15', 'appreciation' => 'Forbidden grade']]
            ];

            ob_start();
            try {
                $controller->save();
            } catch (Exception $e) {
                // Ignore exit redirects
            }
            $tamperPostOutput = ob_get_clean();

            self::assertTrue(strpos($tamperPostOutput, 'Accès Refusé') !== false || strpos($tamperPostOutput, 'fermée') !== false, "Cas 17: POST on unconfigured sequence 501 MUST be refused.");

            $stmtCheckTamper = $db->prepare("SELECT COUNT(*) FROM evaluations WHERE lycee_id = 500 AND sequence_id = 501");
            $stmtCheckTamper->execute();
            self::assertTrue((int)$stmtCheckTamper->fetchColumn() === 0, "Cas 17: No grade records MUST be created on unconfigured sequence 501.");


            echo "\n>>> ALL 17 TEST MATRIX SCENARIOS PASSED SUCCESSFULLY! <<<\n";

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
