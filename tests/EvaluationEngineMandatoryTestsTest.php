<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';

function run_real_engine_tests() {
    echo "=================================================================\n";
    echo "Running Mandatory Real Engine Tests (Tests 1 - 8)\n";
    echo "=================================================================\n";

    $db = Database::getInstance();
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->beginTransaction();

    try {
        // Setup environment for lycee_id = 1, active academic year id = 1
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        $db->exec("DELETE FROM sequences WHERE lycee_id = 1");
        $db->exec("DELETE FROM annees_academiques WHERE id = 1");

        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee)
                   VALUES (1, '2025-2026', '2025-09-01', '2026-06-30', 1, 0)");

        // Sequences setup for Test 1
        // Trimestre 1: open (id = 1)
        // Trimestre 2: closed (id = 2)
        // Trimestre 3: closed (id = 3)
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (1, 1, 1, 'Trimestre 1', 'trimestrielle', '2025-09-01', '2025-11-30', 'ouverte')");
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (2, 1, 1, 'Trimestre 2', 'trimestrielle', '2025-12-01', '2026-02-28', 'fermee')");
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (3, 1, 1, 'Trimestre 3', 'trimestrielle', '2026-03-01', '2026-06-30', 'fermee')");

        $_SESSION['user'] = [
            'id' => 10,
            'lycee_id' => 1,
            'role_id' => 1,
            'role_name' => 'super_admin_createur',
            'permissions' => ['note' => ['*'], 'evaluation' => ['*']]
        ];

        // ------------------------------------------------------------------
        // TEST 1 — Séquence actuelle
        // Avec lycee_id = 1, annee_id = 1, la méthode doit récupérer id = 1, nom = 'Trimestre 1', statut = 'ouverte'
        // et ne jamais récupérer Trimestre 2 ou Trimestre 3.
        // ------------------------------------------------------------------
        echo "Test 1 — Séquence actuelle (findActiveForYear(1, 1))\n";
        $activeSeq = Sequence::findActiveForYear(1, 1);
        if (!$activeSeq || (int)$activeSeq['id'] !== 1 || $activeSeq['nom'] !== 'Trimestre 1' || $activeSeq['statut'] !== 'ouverte') {
            throw new Exception("Test 1 failed: Expected Trimestre 1 (id 1, open), got " . json_encode($activeSeq));
        }
        echo "  [PASS] Correctly retrieved active open sequence (Trimestre 1, id = 1, statut = ouverte).\n";

        // ------------------------------------------------------------------
        // TEST 2 — Séquence fermée
        // Si on tente de travailler avec une séquence fermée (ou si toutes les séquences sont fermées),
        // le moteur ne doit pas l'utiliser comme séquence active. Si aucune séquence n'est ouverte, DENIED_NO_OPEN_SEQUENCE.
        // ------------------------------------------------------------------
        echo "Test 2 — Séquence fermée (quand toutes les séquences sont fermées)\n";
        $db->exec("UPDATE sequences SET statut = 'fermee' WHERE lycee_id = 1 AND id = 1");
        $noActiveSeq = Sequence::findActiveForYear(1, 1);
        if ($noActiveSeq !== false) {
            throw new Exception("Test 2 failed: Sequence::findActiveForYear should return false when all sequences are closed.");
        }
        $resTest2 = EvaluationSaisieService::canTeacherGradeContext(100, 200, 2, 'devoir', 10, '2026-01-01 10:00:00', 1);
        if ($resTest2['allowed'] !== false || $resTest2['code'] !== 'DENIED_NO_OPEN_SEQUENCE') {
            throw new Exception("Test 2 failed: Expected DENIED_NO_OPEN_SEQUENCE when all sequences closed, got " . json_encode($resTest2));
        }
        echo "  [PASS] Closed sequence is never used as active sequence; returns DENIED_NO_OPEN_SEQUENCE when no open sequence exists.\n";

        // Restore sequence 1 as open for remaining tests
        $db->exec("UPDATE sequences SET statut = 'ouverte' WHERE lycee_id = 1 AND id = 1");

        // ------------------------------------------------------------------
        // TEST 3 — Année académique (est_active = 1, cloturee = 0)
        // ------------------------------------------------------------------
        echo "Test 3 — Année académique inactive ou clôturée\n";
        // Mark active year closed
        $db->exec("UPDATE annees_academiques SET cloturee = 1 WHERE id = 1");
        $seqForClosedYear = Sequence::findActiveForYear(1, 1);
        if ($seqForClosedYear !== false) {
            throw new Exception("Test 3 failed: findActiveForYear should fail for closed academic year.");
        }
        // Unclose academic year
        $db->exec("UPDATE annees_academiques SET cloturee = 0 WHERE id = 1");

        $seqForActiveYear = Sequence::findActiveForYear(1, 1);
        if (!$seqForActiveYear || (int)$seqForActiveYear['annee_academique_id'] !== 1) {
            throw new Exception("Test 3 failed: Sequence should belong to active unclosed academic year.");
        }
        echo "  [PASS] Sequence correctly asserts annee_academique.est_active = 1 and cloturee = 0.\n";

        // ------------------------------------------------------------------
        // TEST 4 — Paramètre global existant (id = 3, sequence_id = NULL, type_evaluation = 'devoir')
        // ------------------------------------------------------------------
        echo "Test 4 — Paramètre global existant (sequence_id = NULL)\n";
        $db->exec("INSERT INTO parametres_evaluations (id, lycee_id, annee_academique_id, type, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie, commentaire)
                   VALUES (3, 1, 1, 'global', NULL, 'devoir', '2025-11-11 14:20:00', '2026-09-15 14:20:00', 'Règle globale id 3')");

        $resTest4 = EvaluationSaisieService::canTeacherGradeContext(100, 200, 2, 'devoir', 10, '2026-02-01 10:00:00', 1);
        if ($resTest4['allowed'] !== true || (int)$resTest4['periode']['id'] !== 3) {
            throw new Exception("Test 4 failed: Engine failed to match global rule id 3 with sequence_id = NULL, got " . json_encode($resTest4));
        }
        echo "  [PASS] Engine correctly found global rule id 3 (sequence_id = NULL).\n";

        // ------------------------------------------------------------------
        // TEST 5 — Date actuelle dans la période (2025-11-11 14:20:00 -> 2026-09-15 14:20:00)
        // ------------------------------------------------------------------
        echo "Test 5 — Date actuelle dans la période -> ALLOWED_PERIOD\n";
        $resTest5 = EvaluationSaisieService::canTeacherGradeContext(100, 200, 1, 'devoir', 10, '2026-05-01 12:00:00', 1);
        if ($resTest5['allowed'] !== true || $resTest5['code'] !== 'ALLOWED_PERIOD') {
            throw new Exception("Test 5 failed: Expected ALLOWED_PERIOD, got " . json_encode($resTest5));
        }
        echo "  [PASS] Date within bounds returned allowed=true and code=ALLOWED_PERIOD.\n";

        // ------------------------------------------------------------------
        // TEST 6 — Avant ouverture (< 2025-11-11 14:20:00) -> DENIED_PERIOD_NOT_STARTED
        // ------------------------------------------------------------------
        echo "Test 6 — Avant ouverture -> DENIED_PERIOD_NOT_STARTED\n";
        $resTest6 = EvaluationSaisieService::canTeacherGradeContext(100, 200, 1, 'devoir', 10, '2025-10-01 10:00:00', 1);
        if ($resTest6['allowed'] !== false || $resTest6['code'] !== 'DENIED_PERIOD_NOT_STARTED') {
            throw new Exception("Test 6 failed: Expected DENIED_PERIOD_NOT_STARTED, got " . json_encode($resTest6));
        }
        echo "  [PASS] Date before opening returned allowed=false and code=DENIED_PERIOD_NOT_STARTED.\n";

        // ------------------------------------------------------------------
        // TEST 7 — Après fermeture (> 2026-09-15 14:20:00) -> DENIED_PERIOD_EXPIRED
        // ------------------------------------------------------------------
        echo "Test 7 — Après fermeture -> DENIED_PERIOD_EXPIRED\n";
        $resTest7 = EvaluationSaisieService::canTeacherGradeContext(100, 200, 1, 'devoir', 10, '2026-09-20 10:00:00', 1);
        if ($resTest7['allowed'] !== false || $resTest7['code'] !== 'DENIED_PERIOD_EXPIRED') {
            throw new Exception("Test 7 failed: Expected DENIED_PERIOD_EXPIRED, got " . json_encode($resTest7));
        }
        echo "  [PASS] Date after closing returned allowed=false and code=DENIED_PERIOD_EXPIRED.\n";

        // ------------------------------------------------------------------
        // TEST 8 — Déblocage exceptionnel sans erreur HY093
        // ------------------------------------------------------------------
        echo "Test 8 — Exceptional unlock matching without PDO HY093 error\n";
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 1,
            'type_evaluation' => 'devoir',
            'date_debut' => '2026-09-18 00:00:00',
            'date_fin' => '2026-09-25 23:59:59',
            'motif' => 'Rattrapage exceptionnel'
        ]);
        $resTest8 = EvaluationSaisieService::canTeacherGradeContext(100, 200, 1, 'devoir', 10, '2026-09-20 10:00:00', 1);
        if ($resTest8['allowed'] !== true || $resTest8['code'] !== 'ALLOWED_DEBLOCAGE') {
            throw new Exception("Test 8 failed: Exceptional unlock failed, got " . json_encode($resTest8));
        }
        echo "  [PASS] Exceptional unlock executed with native prepared statements without PDO HY093 error! (code: ALLOWED_DEBLOCAGE).\n";

        echo "\n=================================================================\n";
        echo "ALL 8 MANDATORY TESTS PASSED PERFECTLY!\n";
        echo "=================================================================\n";

    } catch (Exception $e) {
        echo "  [FAIL] " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
    }
}

run_real_engine_tests();
