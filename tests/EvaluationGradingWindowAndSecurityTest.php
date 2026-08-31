<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';

class EvaluationGradingWindowAndSecurityRunner {

    public static function runAllTests() {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            echo "--- Running Evaluation Grading Window & Security Integration Tests ---\n";

            // Setup dummy lycee & active academic year
            $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (100, 'Test Lycee')");
            $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (100, '2024-2025', '2024-09-01', '2025-06-30', 1)");

            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear['id'];

            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, statut) VALUES (100, 100, {$anneeId}, 'Séquence 1', 'ouverte')");
            $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, statut) VALUES (101, 100, {$anneeId}, 'Séquence 2 Fermée', 'fermee')");

            $_SESSION['user'] = [
                'id' => 100,
                'lycee_id' => 100,
                'role_id' => 6,
                'permissions' => ['note' => ['create_own']]
            ];

            // 1. Fallback: Sequence open, no rules -> ALLOWED
            $isOpenFallback = Evaluation::isGradingWindowOpen(1, 1, 100, 'devoir');
            self::assertTrue($isOpenFallback, "1. Fallback: Sequence open, no rules -> ALLOWED");

            // 2. Sequence closed, no unlock -> BLOCKED
            $isOpenClosedSeq = Evaluation::isGradingWindowOpen(1, 1, 101, 'devoir');
            self::assertFalse($isOpenClosedSeq, "2. Sequence closed, no unlock -> BLOCKED");

            // 3. Sequence closed + active unlock -> ALLOWED
            $db->exec("INSERT INTO deblocages_notes (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, sequence_id, type_evaluation, date_debut, date_fin, cree_par)
                       VALUES (100, 100, {$anneeId}, 'classe_matiere', 1, 1, 101, 'devoir', '2020-01-01 00:00:00', '2099-12-31 23:59:59', 1)");
            $isOpenUnlocked = Evaluation::isGradingWindowOpen(1, 1, 101, 'devoir');
            self::assertTrue($isOpenUnlocked, "3. Sequence closed + active unlock -> ALLOWED");

            // 4. Scope Priority Conflict: Global OPEN vs classe_matiere EXPIRED
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (100, 100, {$anneeId}, 'global', 'tous', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (101, 100, {$anneeId}, 'classe_matiere', 1, 1, 'tous', '2020-01-01 00:00:00', '2020-02-01 00:00:00')");

            $isOpenTargetExpired = Evaluation::isGradingWindowOpen(1, 1, 100, 'devoir');
            self::assertFalse($isOpenTargetExpired, "4. Scope Priority Conflict: Specific classe_matiere expired rule overrides global open rule and blocks grading.");

            // 5. Scope Priority Conflict: Global CLOSED vs classe_matiere OPEN
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (102, 100, {$anneeId}, 'classe_matiere', 2, 2, 'tous', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");

            $isOpenTargetActive = Evaluation::isGradingWindowOpen(2, 2, 100, 'devoir');
            self::assertTrue($isOpenTargetActive, "5. Scope Priority Conflict: Specific classe_matiere open rule grants access.");

            // 6. Devoir vs Composition window separation
            $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie)
                       VALUES (103, 100, {$anneeId}, 'classe_matiere', 3, 3, 'composition', '2020-01-01 00:00:00', '2099-12-31 23:59:59')");

            $isDevoirOpenC3 = Evaluation::isGradingWindowOpen(3, 3, 100, 'devoir');
            $isCompositionOpenC3 = Evaluation::isGradingWindowOpen(3, 3, 100, 'composition');

            self::assertTrue($isCompositionOpenC3, "6a. Composition window is open for classe 3.");
            self::assertFalse($isDevoirOpenC3, "6b. Devoir is BLOCKED when explicit rule is composition-only.");

            echo "\n>>> ALL INTEGRATION TEST ASSERTIONS PASSED SUCCESSFULLY! <<<\n";

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
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    EvaluationGradingWindowAndSecurityRunner::runAllTests();
}
