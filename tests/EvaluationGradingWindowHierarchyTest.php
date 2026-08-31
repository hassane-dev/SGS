<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';

function run_test() {
    echo "Running test: Evaluation Grading Window 4-Level Hierarchy Test Suite...\n";

    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // Ensure active year and sequence exist
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active, cloturee) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1, 0)");
            $active_year_id = $db->lastInsertId();
        } else {
            $active_year_id = $active_year['id'];
        }

        // Grant evaluation:manage_settings to Role 3 in permissions table
        $perm_stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'evaluation' AND action = 'manage_settings'");
        $perm_stmt->execute();
        $perm_66_id = $perm_stmt->fetchColumn();
        if (!$perm_66_id) {
            $db->exec("INSERT INTO permissions (id_permission, resource, action, description) VALUES (66, 'evaluation', 'manage_settings', 'Can configure evaluation periods')");
            $perm_66_id = 66;
        }
        $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, {$perm_66_id}), (1, {$perm_66_id})");

        // Mock authenticated user session (Admin Local Role 3)
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 1,
            'role_id' => 3,
            'role_name' => 'admin_local',
            'permissions' => ['evaluation' => ['manage_settings']]
        ];

        $classe_id = 10;
        $matiere_id = 20;

        // Clean up any test parameters or unlocks
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        $db->exec("DELETE FROM sequences WHERE lycee_id = 1 AND id IN (901, 902)");

        // Create test open sequence and closed sequence
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (901, 1, {$active_year_id}, 'Seq Open Test', 'trimestrielle', '2024-09-01', '2024-10-31', 'ouverte')");
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (902, 1, {$active_year_id}, 'Seq Closed Test', 'trimestrielle', '2024-11-01', '2024-12-31', 'fermee')");

        // --- Scenario 1: Séquence ouverte + aucune règle -> OUVERT (true) ---
        echo "  Scenario 1: Sequence open + no explicit rule -> ALLOWED BY DEFAULT\n";
        $res1 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 901, 'devoir');
        if ($res1 !== true) {
            throw new Exception("Scenario 1 failed: Expected true for open sequence without explicit rules, got " . var_export($res1, true));
        }
        echo "    [PASS] Open sequence with no rules allowed by default.\n";

        // --- Scenario 2: Séquence ouverte + règle active -> OUVERT (true) ---
        echo "  Scenario 2: Sequence open + active explicit rule -> ALLOWED\n";
        $start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $end = date('Y-m-d H:i:s', strtotime('+1 day'));
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 901,
            'type_evaluation' => 'tous',
            'date_ouverture_saisie' => $start,
            'date_fermeture_saisie' => $end,
            'commentaire' => 'Active global rule'
        ]);

        $res2 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 901, 'devoir');
        if ($res2 !== true) {
            throw new Exception("Scenario 2 failed: Expected true for active explicit rule, got " . var_export($res2, true));
        }
        echo "    [PASS] Active explicit rule allowed grading.\n";

        // --- Scenario 3: Séquence ouverte + règle expirée -> FERMÉ (false) ---
        echo "  Scenario 3: Sequence open + expired explicit rule -> BLOCKED\n";
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        $past_start = date('Y-m-d H:i:s', strtotime('-10 days'));
        $past_end = date('Y-m-d H:i:s', strtotime('-1 day'));
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 901,
            'type_evaluation' => 'tous',
            'date_ouverture_saisie' => $past_start,
            'date_fermeture_saisie' => $past_end,
            'commentaire' => 'Expired global rule'
        ]);

        $res3 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 901, 'devoir');
        if ($res3 !== false) {
            throw new Exception("Scenario 3 failed: Expected false for expired explicit rule, got " . var_export($res3, true));
        }
        echo "    [PASS] Expired explicit rule blocked grading.\n";

        // --- Scenario 4: Séquence ouverte + règle future -> FERMÉ (false) ---
        echo "  Scenario 4: Sequence open + future explicit rule -> BLOCKED\n";
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        $future_start = date('Y-m-d H:i:s', strtotime('+1 day'));
        $future_end = date('Y-m-d H:i:s', strtotime('+10 days'));
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 901,
            'type_evaluation' => 'tous',
            'date_ouverture_saisie' => $future_start,
            'date_fermeture_saisie' => $future_end,
            'commentaire' => 'Future global rule'
        ]);

        $res4 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 901, 'devoir');
        if ($res4 !== false) {
            throw new Exception("Scenario 4 failed: Expected false for future explicit rule, got " . var_export($res4, true));
        }
        echo "    [PASS] Future explicit rule blocked grading.\n";

        // --- Scenario 5: Séquence fermée + aucune règle -> FERMÉ (false) ---
        echo "  Scenario 5: Closed sequence + no rules -> BLOCKED\n";
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        $res5 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 902, 'devoir');
        if ($res5 !== false) {
            throw new Exception("Scenario 5 failed: Expected false for closed sequence, got " . var_export($res5, true));
        }
        echo "    [PASS] Closed sequence blocked grading.\n";

        // --- Scenario 6: Séquence fermée + déblocage actif -> OUVERT (true) ---
        echo "  Scenario 6: Closed sequence + active exceptional unlock -> ALLOWED\n";
        Deblocage::save([
            'type' => 'global',
            'sequence_id' => 902,
            'type_evaluation' => 'tous',
            'date_debut' => $start,
            'date_fin' => $end,
            'motif' => 'Emergency override for closed sequence'
        ]);

        $res6 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 902, 'devoir');
        if ($res6 !== true) {
            throw new Exception("Scenario 6 failed: Expected true for active exceptional unlock on closed sequence, got " . var_export($res6, true));
        }
        echo "    [PASS] Active exceptional unlock allowed grading on closed sequence.\n";

        // --- Scenario 7: Séquence fermée + règle parametres_evaluations active (sans déblocage) -> FERMÉ (false) ---
        echo "  Scenario 7: Closed sequence + active parameter rule (without unlock) -> BLOCKED\n";
        $db->exec("DELETE FROM deblocages_notes WHERE lycee_id = 1");
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 902,
            'type_evaluation' => 'tous',
            'date_ouverture_saisie' => $start,
            'date_fermeture_saisie' => $end,
            'commentaire' => 'Active parameter rule on closed sequence'
        ]);

        $res7 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 902, 'devoir');
        if ($res7 !== false) {
            throw new Exception("Scenario 7 failed: Expected false for active parameter rule on closed sequence without unlock, got " . var_export($res7, true));
        }
        echo "    [PASS] Parameter rule on closed sequence correctly blocked without exceptional unlock.\n";

        // --- Scenario 8: Target specificity priority (Expired targeted rule vs Active global rule) -> BLOCKED ---
        echo "  Scenario 8: Specific targeted rule (expired) overrides active global rule -> BLOCKED\n";
        $db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = 1");
        // Active global rule
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => 901,
            'type_evaluation' => 'tous',
            'date_ouverture_saisie' => $start,
            'date_fermeture_saisie' => $end,
            'commentaire' => 'Global active rule'
        ]);
        // Expired teacher/class/subject rule
        ParametresEvaluation::save([
            'type' => 'classe_matiere',
            'classe_id' => $classe_id,
            'matiere_id' => $matiere_id,
            'sequence_id' => 901,
            'type_evaluation' => 'tous',
            'date_ouverture_saisie' => $past_start,
            'date_fermeture_saisie' => $past_end,
            'commentaire' => 'Targeted expired rule'
        ]);

        $res8 = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, 901, 'devoir');
        if ($res8 !== false) {
            throw new Exception("Scenario 8 failed: Expected targeted expired rule to take priority and block grading, got " . var_export($res8, true));
        }
        echo "    [PASS] Targeted expired rule took precedence over active global rule.\n";

        // --- Scenario 9: RBAC & Sidebar Menu rendering test for /evaluations/settings ---
        echo "  Scenario 9: Sidebar menu contains /evaluations/settings for authorized users\n";
        $_SERVER['REQUEST_URI'] = '/evaluations/settings';
        ob_start();
        include __DIR__ . '/../src/views/layouts/sidebar_able.php';
        $sidebar_output = ob_get_clean();

        if (strpos($sidebar_output, '/evaluations/settings') === false) {
            throw new Exception("Scenario 9 failed: /evaluations/settings link not found in rendered sidebar.");
        }
        if (strpos($sidebar_output, 'Périodes de Saisie') === false) {
            throw new Exception("Scenario 9 failed: 'Périodes de Saisie' menu text not found in rendered sidebar.");
        }
        echo "    [PASS] Sidebar contains 'Périodes de Saisie' menu item.\n";

        echo "\nAll 9 scenarios passed successfully!\n";

    } catch (Exception $e) {
        echo "    [FAIL] Test failed: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
    }
}

run_test();
?>