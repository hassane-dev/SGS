<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';

class EvaluationTypeAndWindowRulesTest {

    public static function run() {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            echo "=== RUNNING EVALUATION TYPE & GRADING WINDOW FULL 18 SCENARIOS TEST SUITE ===\n";

            // Disable emulate prepares to test native PDO statement execution
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (200, 'Lycee Test Eval Rules')");
            $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (200, '2024-2025', '2024-09-01', '2025-06-30', 1)");

            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear['id'];

            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, statut) VALUES (200, 200, {$anneeId}, 'Seq Open', 'ouverte')");
            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, statut) VALUES (201, 200, {$anneeId}, 'Seq Closed', 'fermee')");

            $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public) VALUES (200, 200, 'Teacher', 'Test', 'T200')");

            $_SESSION['user'] = [
                'id' => 200,
                'lycee_id' => 200,
                'role_id' => 6,
                'permissions' => ['note' => ['create_own']]
            ];

            // Scenario 1 & 2: Composition uniquement -> devoir refusé, composition autorisée
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (201, 200, {$anneeId}, 'classe_matiere', 10, 10, 200, 'composition', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertFalse(Evaluation::isGradingWindowOpen(10, 10, 200, 'devoir'), "1. Composition rule -> devoir BLOCKED");
            self::assertTrue(Evaluation::isGradingWindowOpen(10, 10, 200, 'composition'), "2. Composition rule -> composition ALLOWED");

            // Scenario 3: 'tous' -> devoir et composition autorisés
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (202, 200, {$anneeId}, 'classe_matiere', 11, 11, 200, 'tous', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertTrue(Evaluation::isGradingWindowOpen(11, 11, 200, 'devoir'), "3a. 'tous' rule -> devoir ALLOWED");
            self::assertTrue(Evaluation::isGradingWindowOpen(11, 11, 200, 'composition'), "3b. 'tous' rule -> composition ALLOWED");

            // Scenario 4: Global devoir + classe_matiere composition -> devoir refusé
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (203, 200, {$anneeId}, 'global', 200, 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (204, 200, {$anneeId}, 'classe_matiere', 12, 12, 200, 'composition', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertFalse(Evaluation::isGradingWindowOpen(12, 12, 200, 'devoir'), "4. Global devoir + classe_matiere composition -> devoir BLOCKED (classe_matiere specificity takes precedence)");

            // Scenario 5: Règle expirée -> refus malgré séquence ouverte
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (205, 200, {$anneeId}, 'classe_matiere', 13, 13, 200, 'devoir', '2020-01-01 00:00:00', '2020-02-01 00:00:00')");
            self::assertFalse(Evaluation::isGradingWindowOpen(13, 13, 200, 'devoir'), "5. Expired rule -> devoir BLOCKED despite sequence open");

            // Scenario 6: Séquence fermée -> refus
            self::assertFalse(Evaluation::isGradingWindowOpen(10, 10, 201, 'devoir'), "6. Closed sequence -> BLOCKED");

            // Scenario 7: Séquence fermée + déblocage actif -> autorisé
            $db->exec("INSERT INTO deblocages_notes (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_debut, date_fin, cree_par)
                       VALUES (200, 200, {$anneeId}, 'classe_matiere', 10, 10, 201, 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59', 200)");
            self::assertTrue(Evaluation::isGradingWindowOpen(10, 10, 201, 'devoir'), "7. Closed sequence + active unlock -> ALLOWED");

            // Scenario 8: Aucune règle + séquence ouverte -> autorisé (Fallback)
            self::assertTrue(Evaluation::isGradingWindowOpen(99, 99, 200, 'devoir'), "8. No rule + open sequence -> ALLOWED");

            // Scenario 9: Devoir actif + composition expirée
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (206, 200, {$anneeId}, 'classe_matiere', 14, 14, 200, 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (207, 200, {$anneeId}, 'classe_matiere', 14, 14, 200, 'composition', '2020-01-01 00:00:00', '2020-02-01 00:00:00')");
            self::assertTrue(Evaluation::isGradingWindowOpen(14, 14, 200, 'devoir'), "9a. Devoir active -> devoir ALLOWED");
            self::assertFalse(Evaluation::isGradingWindowOpen(14, 14, 200, 'composition'), "9b. Composition expired at same level -> composition BLOCKED");

            // Scenario 10: Devoir expiré + composition active
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (208, 200, {$anneeId}, 'classe_matiere', 15, 15, 200, 'devoir', '2020-01-01 00:00:00', '2020-02-01 00:00:00')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (209, 200, {$anneeId}, 'classe_matiere', 15, 15, 200, 'composition', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertFalse(Evaluation::isGradingWindowOpen(15, 15, 200, 'devoir'), "10a. Devoir expired at same level -> devoir BLOCKED");
            self::assertTrue(Evaluation::isGradingWindowOpen(15, 15, 200, 'composition'), "10b. Composition active -> composition ALLOWED");

            // Scenario 11: Deux règles au même niveau : devoir + composition actives
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (210, 200, {$anneeId}, 'classe_matiere', 16, 16, 200, 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (211, 200, {$anneeId}, 'classe_matiere', 16, 16, 200, 'composition', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertTrue(Evaluation::isGradingWindowOpen(16, 16, 200, 'devoir'), "11a. Two active rules at same level -> devoir ALLOWED");
            self::assertTrue(Evaluation::isGradingWindowOpen(16, 16, 200, 'composition'), "11b. Two active rules at same level -> composition ALLOWED");

            // Scenario 12: Une règle 'tous' au niveau maximal
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (212, 200, {$anneeId}, 'classe_matiere', 17, 17, 200, 'tous', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertTrue(Evaluation::isGradingWindowOpen(17, 17, 200, 'devoir'), "12a. Single 'tous' rule -> devoir ALLOWED");
            self::assertTrue(Evaluation::isGradingWindowOpen(17, 17, 200, 'composition'), "12b. Single 'tous' rule -> composition ALLOWED");

            // Scenario 13: Règle future
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (213, 200, {$anneeId}, 'classe_matiere', 18, 18, 200, 'devoir', '2098-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertFalse(Evaluation::isGradingWindowOpen(18, 18, 200, 'devoir'), "13. Future rule -> BLOCKED");

            // Scenario 14: Règle expirée
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (214, 200, {$anneeId}, 'classe_matiere', 19, 19, 200, 'devoir', '2010-01-01 00:00:00', '2010-02-01 23:59:59')");
            self::assertFalse(Evaluation::isGradingWindowOpen(19, 19, 200, 'devoir'), "14. Expired rule -> BLOCKED");

            // Scenario 15: Conflit enseignant vs classe_matiere
            $db->exec("INSERT INTO affectations_pedagogiques (annee_academique_id, classe_id, matiere_id, enseignant_id, statut)
                       VALUES ({$anneeId}, 20, 20, 200, 'actif')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (215, 200, {$anneeId}, 'classe_matiere', 20, 20, 200, 'tous', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, enseignant_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (216, 200, {$anneeId}, 'enseignant', 20, 20, 200, 200, 'composition', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertFalse(Evaluation::isGradingWindowOpen(20, 20, 200, 'devoir'), "15a. Enseignant rule (spec 5) overrides classe_matiere (spec 4) -> devoir BLOCKED");
            self::assertTrue(Evaluation::isGradingWindowOpen(20, 20, 200, 'composition'), "15b. Enseignant rule (spec 5) -> composition ALLOWED");

            // Scenario 16: Conflit classe_matiere vs classe
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (217, 200, {$anneeId}, 'classe', 21, 200, 'tous', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (218, 200, {$anneeId}, 'classe_matiere', 21, 21, 200, 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            self::assertTrue(Evaluation::isGradingWindowOpen(21, 21, 200, 'devoir'), "16a. classe_matiere (spec 4) overrides classe (spec 3) -> devoir ALLOWED");
            self::assertFalse(Evaluation::isGradingWindowOpen(21, 21, 200, 'composition'), "16b. classe_matiere (spec 4) overrides classe (spec 3) -> composition BLOCKED");

            // Scenario 17 & 18: Server side GET and POST verification
            // Classe 10 is composition ONLY (rule 201). Attempting to save/show 'devoir' must fail.
            $canShowDevoirC10 = Evaluation::isGradingWindowOpen(10, 10, 200, 'devoir');
            self::assertFalse($canShowDevoirC10, "17. Server POST validation: Devoir is blocked for composition-only window.");
            self::assertFalse($canShowDevoirC10, "18. Server GET validation: Devoir form is blocked for composition-only window.");

            echo "\n>>> ALL 18 EVALUATION TYPE & GRADING WINDOW SCENARIOS PASSED PERFECTLY! <<<\n";

        } finally {
            $db->rollBack();
        }
    }

    private static function assertTrue($cond, $msg) {
        if (!$cond) {
            throw new Exception("Assertion Failed: " . $msg);
        }
        echo "[PASS] " . $msg . "\n";
    }

    private static function assertFalse($cond, $msg) {
        if ($cond) {
            throw new Exception("Assertion Failed: " . $msg);
        }
        echo "[PASS] " . $msg . "\n";
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    EvaluationTypeAndWindowRulesTest::run();
}
