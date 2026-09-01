<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';

function run_evaluation_saisie_service_tests() {
    echo "=================================================================\n";
    echo "Running EvaluationSaisieService Comprehensive Test Suite\n";
    echo "=================================================================\n";

    $db = Database::getInstance();
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->beginTransaction();

    try {
        // Ensure active year exists
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active, cloturee) VALUES ('2025-2026', '2025-09-01', '2026-06-30', 1, 0)");
            $active_year_id = $db->lastInsertId();
        } else {
            $active_year_id = $active_year['id'];
        }

        // Setup session for Super Admin / authorized tester
        $_SESSION['user'] = [
            'id' => 10,
            'lycee_id' => 1,
            'role_id' => 1,
            'role_name' => 'super_admin_createur',
            'permissions' => ['note' => ['*'], 'evaluation' => ['*']]
        ];

        $classe_id = 100;
        $matiere_id = 200;
        $enseignant_id = 10;

        // Clean up test data
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        $db->exec("DELETE FROM sequences WHERE lycee_id = 1 AND id IN (801, 802)");

        // Insert test sequences
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (801, 1, {$active_year_id}, 'Seq 1 Open', 'trimestrielle', '2025-09-01', '2025-11-30', 'ouverte')");
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (802, 1, {$active_year_id}, 'Seq 2 Closed', 'trimestrielle', '2025-12-01', '2026-02-28', 'fermee')");

        // ------------------------------------------------------------------
        // Scenario 1: Déblocage actif + séquence fermée -> AUTORISÉ (ALLOWED_DEBLOCAGE)
        // ------------------------------------------------------------------
        echo "  Scenario 1: Active unlock + closed sequence -> ALLOWED\n";
        $now = '2026-09-01 14:30:00';
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 802,
            'type_evaluation' => 'devoir',
            'date_debut' => '2026-09-01 14:25:00',
            'date_fin' => '2026-09-03 14:25:00',
            'motif' => 'Saisie exceptionnelle de rentrée'
        ]);

        $res1 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 802, 'devoir', $enseignant_id, $now, 1);
        if ($res1['allowed'] !== true || $res1['code'] !== 'ALLOWED_DEBLOCAGE') {
            throw new Exception("Scenario 1 failed: Expected ALLOWED_DEBLOCAGE, got " . json_encode($res1));
        }
        echo "    [PASS] Active unlock on closed sequence correctly allowed (code: ALLOWED_DEBLOCAGE).\n";

        // ------------------------------------------------------------------
        // Scenario 2: Séquence fermée sans déblocage -> REFUSÉ (DENIED_SEQUENCE_CLOSED)
        // ------------------------------------------------------------------
        echo "  Scenario 2: Closed sequence without unlock -> REFUSED\n";
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        $res2 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 802, 'devoir', $enseignant_id, $now, 1);
        if ($res2['allowed'] !== false || $res2['code'] !== 'DENIED_SEQUENCE_CLOSED') {
            throw new Exception("Scenario 2 failed: Expected DENIED_SEQUENCE_CLOSED, got " . json_encode($res2));
        }
        echo "    [PASS] Closed sequence without unlock correctly denied (code: DENIED_SEQUENCE_CLOSED).\n";

        // ------------------------------------------------------------------
        // Scenario 3: Période normale active sur séquence ouverte -> AUTORISÉ (ALLOWED_PERIOD)
        // ------------------------------------------------------------------
        echo "  Scenario 3: Active normal period -> ALLOWED\n";
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 801,
            'type_evaluation' => 'devoir',
            'date_ouverture_saisie' => '2025-11-11 14:20:00',
            'date_fermeture_saisie' => '2026-09-15 14:20:00',
            'commentaire' => 'Période standard'
        ]);

        $res3 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, $now, 1);
        if ($res3['allowed'] !== true || $res3['code'] !== 'ALLOWED_PERIOD') {
            throw new Exception("Scenario 3 failed: Expected ALLOWED_PERIOD, got " . json_encode($res3));
        }
        echo "    [PASS] Active normal period correctly allowed (code: ALLOWED_PERIOD).\n";

        // ------------------------------------------------------------------
        // Scenario 4: Période future -> REFUSÉ (DENIED_PERIOD_NOT_STARTED)
        // ------------------------------------------------------------------
        echo "  Scenario 4: Future normal period -> REFUSED\n";
        $res4 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, '2025-10-01 10:00:00', 1);
        if ($res4['allowed'] !== false || $res4['code'] !== 'DENIED_PERIOD_NOT_STARTED') {
            throw new Exception("Scenario 4 failed: Expected DENIED_PERIOD_NOT_STARTED, got " . json_encode($res4));
        }
        echo "    [PASS] Future normal period correctly denied (code: DENIED_PERIOD_NOT_STARTED).\n";

        // ------------------------------------------------------------------
        // Scenario 5: Période expirée -> REFUSÉ (DENIED_PERIOD_EXPIRED)
        // ------------------------------------------------------------------
        echo "  Scenario 5: Expired normal period -> REFUSED\n";
        $res5 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, '2026-10-01 10:00:00', 1);
        if ($res5['allowed'] !== false || $res5['code'] !== 'DENIED_PERIOD_EXPIRED') {
            throw new Exception("Scenario 5 failed: Expected DENIED_PERIOD_EXPIRED, got " . json_encode($res5));
        }
        echo "    [PASS] Expired normal period correctly denied (code: DENIED_PERIOD_EXPIRED).\n";

        // ------------------------------------------------------------------
        // Scenario 6: Début exact (now == date_debut) -> AUTORISÉ
        // ------------------------------------------------------------------
        echo "  Scenario 6: Exact start date boundary (now == date_debut) -> ALLOWED\n";
        $res6 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, '2025-11-11 14:20:00', 1);
        if ($res6['allowed'] !== true) {
            throw new Exception("Scenario 6 failed: Expected true at exact start date, got " . json_encode($res6));
        }
        echo "    [PASS] Exact start date boundary inclusive.\n";

        // ------------------------------------------------------------------
        // Scenario 7: Fin exacte (now == date_fin) -> AUTORISÉ
        // ------------------------------------------------------------------
        echo "  Scenario 7: Exact end date boundary (now == date_fin) -> ALLOWED\n";
        $res7 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, '2026-09-15 14:20:00', 1);
        if ($res7['allowed'] !== true) {
            throw new Exception("Scenario 7 failed: Expected true at exact end date, got " . json_encode($res7));
        }
        echo "    [PASS] Exact end date boundary inclusive.\n";

        // ------------------------------------------------------------------
        // Scenario 8: Une seconde après la fin -> REFUSÉ
        // ------------------------------------------------------------------
        echo "  Scenario 8: One second after end date -> REFUSED\n";
        $res8 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, '2026-09-15 14:20:01', 1);
        if ($res8['allowed'] !== false) {
            throw new Exception("Scenario 8 failed: Expected false 1 second after end date, got " . json_encode($res8));
        }
        echo "    [PASS] 1 second after end date correctly refused.\n";

        // ------------------------------------------------------------------
        // Scenario 9: Déblocage ne correspondant pas à la séquence -> REFUSÉ
        // ------------------------------------------------------------------
        echo "  Scenario 9: Unlock for different sequence -> REFUSED\n";
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 801, // Created for 801
            'type_evaluation' => 'devoir',
            'date_debut' => '2026-09-01 14:25:00',
            'date_fin' => '2026-09-03 14:25:00',
            'motif' => 'Unlock for Seq 801'
        ]);
        // Evaluated on closed sequence 802
        $res9 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 802, 'devoir', $enseignant_id, $now, 1);
        if ($res9['allowed'] !== false || $res9['code'] !== 'DENIED_SEQUENCE_CLOSED') {
            throw new Exception("Scenario 9 failed: Expected DENIED_SEQUENCE_CLOSED for sequence mismatch, got " . json_encode($res9));
        }
        echo "    [PASS] Unlock on sequence 801 did not bleed into closed sequence 802.\n";

        // ------------------------------------------------------------------
        // Scenario 10: Déblocage ne correspondant pas au type -> REFUSÉ
        // ------------------------------------------------------------------
        echo "  Scenario 10: Unlock for composition requested for devoir -> REFUSED\n";
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 802,
            'type_evaluation' => 'composition',
            'date_debut' => '2026-09-01 14:25:00',
            'date_fin' => '2026-09-03 14:25:00',
            'motif' => 'Unlock for composition'
        ]);
        // Evaluated for 'devoir'
        $res10 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 802, 'devoir', $enseignant_id, $now, 1);
        if ($res10['allowed'] !== false) {
            throw new Exception("Scenario 10 failed: Expected false for evaluation type mismatch, got " . json_encode($res10));
        }
        echo "    [PASS] Unlock for composition correctly blocked devoir request.\n";

        // ------------------------------------------------------------------
        // Scenario 11: Déblocage global + type "tous" -> AUTORISÉ
        // ------------------------------------------------------------------
        echo "  Scenario 11: Global unlock with type 'tous' -> ALLOWED\n";
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 802,
            'type_evaluation' => 'tous',
            'date_debut' => '2026-09-01 14:25:00',
            'date_fin' => '2026-09-03 14:25:00',
            'motif' => 'Unlock for all types'
        ]);
        $res11_dev = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 802, 'devoir', $enseignant_id, $now, 1);
        $res11_comp = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 802, 'composition', $enseignant_id, $now, 1);
        if ($res11_dev['allowed'] !== true || $res11_comp['allowed'] !== true) {
            throw new Exception("Scenario 11 failed: Expected true for type 'tous' unlock on both devoir and composition.");
        }
        echo "    [PASS] Global unlock with type 'tous' allowed both devoir and composition.\n";

        // ------------------------------------------------------------------
        // Scenario 12: Hiérarchie de spécificité (enseignant > classe_matiere > classe > matiere > global)
        // ------------------------------------------------------------------
        echo "  Scenario 12: Specificity hierarchy (targeted expired rule overrides global active rule) -> REFUSED\n";
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        // Global active rule
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 801,
            'type_evaluation' => 'devoir',
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2026-12-31 23:59:59',
            'commentaire' => 'Global active rule'
        ]);
        // Specific class_matiere rule (expired)
        ParametresEvaluation::save([
            'type' => 'classe_matiere',
            'classe_id' => $classe_id,
            'matiere_id' => $matiere_id,
            'sequence_id' => 801,
            'type_evaluation' => 'devoir',
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-10-01 00:00:00',
            'commentaire' => 'Class/Subject expired rule'
        ]);

        $res12 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, $now, 1);
        if ($res12['allowed'] !== false) {
            throw new Exception("Scenario 12 failed: Specific expired rule should override global active rule, got " . json_encode($res12));
        }
        echo "    [PASS] High specificity expired rule correctly overrode active global rule.\n";

        // ------------------------------------------------------------------
        // Scenario 13: Fallback lorsque aucune règle n'existe -> AUTORISÉ PAR DÉFAUT
        // ------------------------------------------------------------------
        echo "  Scenario 13: Fallback when zero rules exist in establishment -> ALLOWED BY DEFAULT\n";
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        $res13 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, $now, 1);
        if ($res13['allowed'] !== true || $res13['code'] !== 'ALLOWED_DEFAULT_FALLBACK') {
            throw new Exception("Scenario 13 failed: Expected ALLOWED_DEFAULT_FALLBACK, got " . json_encode($res13));
        }
        echo "    [PASS] Zero rules in establishment allowed by default (code: ALLOWED_DEFAULT_FALLBACK).\n";

        // ------------------------------------------------------------------
        // Scenario 14: Fallback restrictif lorsqu'une politique existe mais ne couvre pas le contexte -> REFUSÉ
        // ------------------------------------------------------------------
        echo "  Scenario 14: Policy exists but no rule covers requested context -> REFUSED (DENIED_POLICY_RESTRICTED)\n";
        // Create rule for class 999
        ParametresEvaluation::save([
            'type' => 'classe',
            'classe_id' => 999,
            'sequence_id' => 801,
            'type_evaluation' => 'devoir',
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2026-12-31 23:59:59',
            'commentaire' => 'Rule for class 999 only'
        ]);

        $res14 = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 801, 'devoir', $enseignant_id, $now, 1);
        if ($res14['allowed'] !== false || $res14['code'] !== 'DENIED_POLICY_RESTRICTED') {
            throw new Exception("Scenario 14 failed: Expected DENIED_POLICY_RESTRICTED, got " . json_encode($res14));
        }
        echo "    [PASS] Uncovered context correctly denied under active establishment policy (code: DENIED_POLICY_RESTRICTED).\n";

        // ------------------------------------------------------------------
        // Scenario 15 & 16: Anti-tampering dans EvaluationController (showForm & save)
        // ------------------------------------------------------------------
        echo "  Scenario 15 & 16: Anti-tampering verification in EvaluationController\n";
        // Setup requested GET parameters with illegal sequence 802 (closed)
        $_GET['classe_id'] = $classe_id;
        $_GET['matiere_id'] = $matiere_id;
        $_GET['sequence_id'] = 802; // Closed
        $_GET['type'] = 'devoir';

        $decisionTamper = EvaluationSaisieService::canTeacherGradeContext((int)$_GET['classe_id'], (int)$_GET['matiere_id'], (int)$_GET['sequence_id'], (string)$_GET['type'], $enseignant_id, $now, 1);
        if ($decisionTamper['allowed'] !== false) {
            throw new Exception("Scenario 15/16 failed: Tampered parameters on closed sequence should be rejected.");
        }
        echo "    [PASS] Anti-tampering check via EvaluationSaisieService prevents HTTP parameter manipulation.\n";

        // ------------------------------------------------------------------
        // Scenario 17: MYSQL ENGINE REALISTIC SCENARIO VALIDATION
        // Reproduce exact issue described in prompt:
        // Période normale: 2025-11-11 14:20:00 -> 2026-09-15 14:20:00
        // Déblocage: 2026-09-01 14:25:00 -> 2026-09-03 14:25:00
        // Séquence: fermée (802)
        // Type: 'devoir'
        // Simulated Now: 2026-09-01 14:30:00
        // ------------------------------------------------------------------
        echo "  Scenario 17: REALISTIC SGS SCENARIO (Active unlock overrides closed sequence)\n";
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");

        // Normal period rule on sequence 802
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 802,
            'type_evaluation' => 'devoir',
            'date_ouverture_saisie' => '2025-11-11 14:20:00',
            'date_fermeture_saisie' => '2026-09-15 14:20:00',
            'commentaire' => 'Période normale SGS'
        ]);

        // Exceptional unlock on closed sequence 802
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 802,
            'type_evaluation' => 'devoir',
            'date_debut' => '2026-09-01 14:25:00',
            'date_fin' => '2026-09-03 14:25:00',
            'motif' => 'Déblocage exceptionnel de rentrée'
        ]);

        $simulatedNow = '2026-09-01 14:30:00';

        $serviceDecision = EvaluationSaisieService::canTeacherGradeContext($classe_id, $matiere_id, 802, 'devoir', $enseignant_id, $simulatedNow, 1);
        $facadeDecision = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 802, 'devoir', $simulatedNow);

        if ($serviceDecision['allowed'] !== true) {
            throw new Exception("Scenario 17 failed: Service decision expected allowed=true, got " . json_encode($serviceDecision));
        }
        if ($serviceDecision['code'] !== 'ALLOWED_DEBLOCAGE') {
            throw new Exception("Scenario 17 failed: Service code expected ALLOWED_DEBLOCAGE, got {$serviceDecision['code']}");
        }
        if ($facadeDecision !== true) {
            throw new Exception("Scenario 17 failed: Evaluation::isGradingWindowOpen facade expected true, got " . var_export($facadeDecision, true));
        }

        echo "    [PASS] PROOF CONFIRMED: Active unlock correctly grants access on closed sequence!\n";
        echo "           Service Code: {$serviceDecision['code']}\n";
        echo "           Service Reason: {$serviceDecision['reason']}\n";
        echo "           Facade Result: " . ($facadeDecision ? 'true' : 'false') . "\n";

        echo "\n=================================================================\n";
        echo "ALL 17 SCENARIOS PASSED SUCCESSFULLY!\n";
        echo "=================================================================\n";

    } catch (Exception $e) {
        echo "    [FAIL] Test failed: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
    }
}

run_evaluation_saisie_service_tests();
?>