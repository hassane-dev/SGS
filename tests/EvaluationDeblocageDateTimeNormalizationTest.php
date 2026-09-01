<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';

function assertTrue($cond, $msg = '') {
    if (!$cond) {
        throw new Exception("Assertion Failed: " . ($msg ?: 'Expected true'));
    }
}

function assertFalse($cond, $msg = '') {
    if ($cond) {
        throw new Exception("Assertion Failed: " . ($msg ?: 'Expected false'));
    }
}

function assertEquals($a, $b, $msg = '') {
    if ($a !== $b) {
        throw new Exception("Assertion Failed: Expected '$a' === '$b' " . ($msg ? "($msg)" : ''));
    }
}

function run_test() {
    echo "Running test: Evaluation Deblocage DateTime Normalization Test Suite...\n";

    $db = Database::getInstance();
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->beginTransaction();

    try {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 1,
            'role_id' => 1,
            'permissions' => ['note' => ['*'], 'evaluation' => ['*']]
        ];

        // Clean tables
        $db->exec("DELETE FROM deblocages_notes");
        $db->exec("DELETE FROM parametres_evaluations");
        $db->exec("DELETE FROM sequences");
        $db->exec("DELETE FROM annees_academiques");

        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (1, '2026-2027', '2026-09-01', '2027-06-30', 1, 0)");

        // --- Test A: datetime-local with T ('2026-09-01T12:28') -> stored normalized ('2026-09-01 12:28:00') ---
        echo " - Subtest A: Normalization of datetime-local string... ";
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 1,
            'type_evaluation' => 'tous',
            'date_debut' => '2026-09-01T12:28',
            'date_fin' => '2026-09-03T12:28',
            'motif' => 'Test A'
        ]);

        $rowA = $db->query("SELECT date_debut, date_fin FROM deblocages_notes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        assertEquals('2026-09-01 12:28:00', $rowA['date_debut'], 'date_debut normalized');
        assertEquals('2026-09-03 12:28:00', $rowA['date_fin'], 'date_fin normalized');
        echo "OK\n";

        // --- Test B: Active unlock (01/09/2026 12:28 to 03/09/2026 12:28) with Now = 01/09/2026 15:00 ---
        echo " - Subtest B: Active unlock condition SQL match... ";
        $stmtB = $db->prepare("SELECT id FROM deblocages_notes WHERE lycee_id = 1 AND annee_academique_id = 1 AND (type_evaluation = 'devoir' OR type_evaluation = 'tous') AND :now_time BETWEEN date_debut AND date_fin");
        $stmtB->execute(['now_time' => '2026-09-01 15:00:00']);
        assertTrue($stmtB->fetchColumn() !== false, "Now 15:00 is between start and end");
        echo "OK\n";

        // --- Test C: Unlock on CLOSED sequence (statut = 'fermee') ---
        echo " - Subtest C: Unlock on closed sequence overrides closure... ";
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (1, 1, 1, 'Trimestre 1', 'trimestrielle', '2026-09-01', '2026-11-30', 'fermee')");

        $now = date('Y-m-d H:i:s');
        $start = date('Y-m-d H:i:s', time() - 3600);
        $end = date('Y-m-d H:i:s', time() + 3600);

        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 1,
            'type_evaluation' => 'tous',
            'date_debut' => $start,
            'date_fin' => $end,
            'motif' => 'Test C'
        ]);

        assertTrue(Deblocage::isUnlocked(10, 5, 1, null, 'devoir'), 'Deblocage::isUnlocked returns true');
        assertTrue(Evaluation::isGradingWindowOpen(10, 5, 1, 'devoir'), 'Evaluation::isGradingWindowOpen returns true on closed sequence');
        echo "OK\n";

        // --- Test D: Before unlock start (Now = 01/09/2026 12:27:59) -> false ---
        echo " - Subtest D: Before unlock start returns false... ";
        $db->exec("DELETE FROM deblocages_notes");
        $db->exec("INSERT INTO deblocages_notes (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, enseignant_id, sequence_id, type_evaluation, date_debut, date_fin, motif, cree_par) VALUES (1, 1, 1, 'global', NULL, NULL, NULL, 1, 'tous', '2026-09-01 12:28:00', '2026-09-03 12:28:00', 'test', 1)");

        $stmtD = $db->prepare("SELECT id FROM deblocages_notes WHERE lycee_id = 1 AND annee_academique_id = 1 AND (type_evaluation = 'devoir' OR type_evaluation = 'tous') AND :now_time BETWEEN date_debut AND date_fin");
        $stmtD->execute(['now_time' => '2026-09-01 12:27:59']);
        assertFalse($stmtD->fetchColumn() !== false, "Now 12:27:59 is before 12:28:00");
        echo "OK\n";

        // --- Test E: Exact start boundary (Now = 01/09/2026 12:28:00) -> true ---
        echo " - Subtest E: Exact start boundary returns true... ";
        $stmtE = $db->prepare("SELECT id FROM deblocages_notes WHERE lycee_id = 1 AND annee_academique_id = 1 AND (type_evaluation = 'devoir' OR type_evaluation = 'tous') AND :now_time BETWEEN date_debut AND date_fin");
        $stmtE->execute(['now_time' => '2026-09-01 12:28:00']);
        assertTrue($stmtE->fetchColumn() !== false, "Now 12:28:00 matches start boundary");
        echo "OK\n";

        // --- Test F: Exact end boundary (Now = 03/09/2026 12:28:00) -> true ---
        echo " - Subtest F: Exact end boundary returns true... ";
        $stmtF = $db->prepare("SELECT id FROM deblocages_notes WHERE lycee_id = 1 AND annee_academique_id = 1 AND (type_evaluation = 'devoir' OR type_evaluation = 'tous') AND :now_time BETWEEN date_debut AND date_fin");
        $stmtF->execute(['now_time' => '2026-09-03 12:28:00']);
        assertTrue($stmtF->fetchColumn() !== false, "Now 12:28:00 matches end boundary");
        echo "OK\n";

        // --- Test G: After unlock end (Now = 03/09/2026 12:28:01) -> false ---
        echo " - Subtest G: After unlock end returns false... ";
        $stmtG = $db->prepare("SELECT id FROM deblocages_notes WHERE lycee_id = 1 AND annee_academique_id = 1 AND (type_evaluation = 'devoir' OR type_evaluation = 'tous') AND :now_time BETWEEN date_debut AND date_fin");
        $stmtG->execute(['now_time' => '2026-09-03 12:28:01']);
        assertFalse($stmtG->fetchColumn() !== false, "Now 12:28:01 is after end boundary");
        echo "OK\n";

        // --- Test H: Legacy data containing 'T' migrated by migrate.php logic -> becomes functional ---
        echo " - Subtest H: Legacy data with 'T' migrated cleanly... ";
        $db->exec("INSERT INTO deblocages_notes (id, lycee_id, annee_academique_id, type, classe_id, matiere_id, enseignant_id, sequence_id, type_evaluation, date_debut, date_fin, motif, cree_par) VALUES (99, 1, 1, 'global', NULL, NULL, NULL, 1, 'tous', '2026-09-01T12:28', '2026-09-03T12:28', 'legacy', 1)");

        // Run migration logic as in migrate.php
        $stmtH = $db->query("SELECT id, date_debut, date_fin FROM deblocages_notes WHERE date_debut LIKE '%T%' OR date_fin LIKE '%T%'");
        $rowsH = $stmtH->fetchAll(PDO::FETCH_ASSOC);
        $upStmtH = $db->prepare("UPDATE deblocages_notes SET date_debut = :date_debut, date_fin = :date_fin WHERE id = :id");
        foreach ($rowsH as $rH) {
            $cleanStart = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $rH['date_debut'])));
            $cleanEnd = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $rH['date_fin'])));
            $upStmtH->execute(['date_debut' => $cleanStart, 'date_fin' => $cleanEnd, 'id' => $rH['id']]);
        }

        $rowH = $db->query("SELECT date_debut, date_fin FROM deblocages_notes WHERE id = 99")->fetch(PDO::FETCH_ASSOC);
        assertEquals('2026-09-01 12:28:00', $rowH['date_debut']);
        assertEquals('2026-09-03 12:28:00', $rowH['date_fin']);
        echo "OK\n";

        // --- Test I: Full flow with active unlock on closed sequence ---
        echo " - Subtest I: Full flow active unlock on closed sequence... ";
        $startI = date('Y-m-d H:i:s', time() - 600);
        $endI = date('Y-m-d H:i:s', time() + 3600);

        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 1,
            'type_evaluation' => 'tous',
            'date_debut' => $startI,
            'date_fin' => $endI,
            'motif' => 'Flow Test'
        ]);

        assertTrue(Evaluation::isGradingWindowOpen(10, 5, 1, 'devoir'), 'isGradingWindowOpen returns true for unlocked closed sequence');
        echo "OK\n";

        // --- Test J: Uncovered attempt on closed sequence -> remains refused ---
        echo " - Subtest J: Uncovered attempt on closed sequence remains refused... ";
        $db->exec("DELETE FROM deblocages_notes");
        assertFalse(Evaluation::isGradingWindowOpen(10, 5, 1, 'devoir'), 'isGradingWindowOpen returns false when sequence is closed and no unlock exists');
        echo "OK\n";

        echo "All tests in EvaluationDeblocageDateTimeNormalizationTest passed successfully!\n";

    } finally {
        $db->rollBack();
    }
}

run_test();
