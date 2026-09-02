<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';
require_once __DIR__ . '/../src/services/EvaluationCalculationService.php';
require_once __DIR__ . '/../src/controllers/EvaluationController.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/models/Bulletin.php';

class EvaluationDynamicTypesAndCalculationRunner {

    private static function assertTrue($cond, $msg) {
        if (!$cond) {
            throw new Exception("Assertion Failed: " . $msg);
        }
        echo "  ✓ " . $msg . "\n";
    }

    public static function runAllTests() {
        echo "=================================================================\n";
        echo "Running Dynamic Evaluation Types and Calculation Test Suite\n";
        echo "=================================================================\n";

        $db = Database::getInstance();
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        require_once __DIR__ . '/../migrate.php';

        $lyceeId = 9900;
        $anneeId = 99000;
        $seqId = 9901;
        $classeId = 99010;
        $matiereId = 99020;
        $eleveId = 99030;
        $teacherId = 99040;

        // Clean
        $db->exec("DELETE FROM evaluations WHERE lycee_id = {$lyceeId}");
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lyceeId}");
        $db->exec("DELETE FROM annees_academiques WHERE id = {$anneeId}");
        $db->exec("DELETE FROM param_type_evaluation WHERE lycee_id = {$lyceeId}");
        $db->exec("DELETE FROM param_lycee WHERE id = {$lyceeId}");

        $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES ({$lyceeId}, 'Lycee Dynamic Test')");
        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES ({$anneeId}, '2025-2026', '2025-09-01', '2026-06-30', 1, 0)");
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES ({$seqId}, {$lyceeId}, {$anneeId}, 'Séquence 1 Test', 'trimestrielle', '2020-01-01', '2099-12-31', 'ouverte')");

        $_SESSION['user'] = ['id_user' => $teacherId, 'role_id' => 1, 'lycee_id' => $lyceeId, 'role_name' => 'super_admin_createur'];

        // TEST 1: Create dynamic evaluation types
        echo "\n1. Testing dynamic evaluation type creation in param_type_evaluation...\n";
        $id1 = ParamTypeEvaluation::save([
            'lycee_id' => $lyceeId,
            'code' => 'devoir',
            'libelle' => 'Devoir de Contrôle',
            'bareme_defaut' => 20.00,
            'actif' => 1,
            'ordre_affichage' => 1
        ]);
        self::assertTrue($id1 !== false, "Custom type 'devoir' created.");

        $id2 = ParamTypeEvaluation::save([
            'lycee_id' => $lyceeId,
            'code' => 'tp_pratique',
            'libelle' => 'Travaux Pratiques',
            'bareme_defaut' => 20.00,
            'actif' => 1,
            'ordre_affichage' => 2
        ]);
        self::assertTrue($id2 !== false, "Custom type 'tp_pratique' created.");

        $id3 = ParamTypeEvaluation::save([
            'lycee_id' => $lyceeId,
            'code' => 'composition',
            'libelle' => 'Composition Semestrielle',
            'bareme_defaut' => 40.00,
            'actif' => 1,
            'ordre_affichage' => 3
        ]);
        self::assertTrue($id3 !== false, "Custom type 'composition' created.");

        $types = ParamTypeEvaluation::findActive($lyceeId);
        self::assertTrue(count($types) === 3, "3 active types fetched for lycee.");

        // TEST 2: Multi-occurrence saving (Devoir 1, Devoir 2, Devoir 3) in same sequence
        echo "\n2. Testing multiple occurrences (Devoir 1, Devoir 2, Devoir 3) in same sequence...\n";
        $_SESSION['user'] = ['id_user' => $teacherId, 'role_id' => 1, 'lycee_id' => $lyceeId, 'role_name' => 'super_admin_createur'];

        $res1 = Evaluation::saveGrades([
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'sequence_id' => $seqId,
            'type' => 'devoir',
            'numero_evaluation' => 1,
            'bareme' => 20.00,
            'coefficient' => 4.00,
            'enseignant_id' => $teacherId,
            'grades' => [$eleveId => ['note' => '12.00', 'appreciation' => 'Devoir 1 passable']]
        ]);
        self::assertTrue($res1, "Devoir 1 saved (12.00/20).");

        $res2 = Evaluation::saveGrades([
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'sequence_id' => $seqId,
            'type' => 'devoir',
            'numero_evaluation' => 2,
            'bareme' => 20.00,
            'coefficient' => 4.00,
            'enseignant_id' => $teacherId,
            'grades' => [$eleveId => ['note' => '16.00', 'appreciation' => 'Devoir 2 bien']]
        ]);
        self::assertTrue($res2, "Devoir 2 saved in same sequence (16.00/20).");

        // Save Composition 1 on scale 40 (note 28/40 = 14/20)
        $res3 = Evaluation::saveGrades([
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'sequence_id' => $seqId,
            'type' => 'composition',
            'numero_evaluation' => 1,
            'bareme' => 40.00,
            'coefficient' => 4.00,
            'enseignant_id' => $teacherId,
            'grades' => [$eleveId => ['note' => '28.00', 'appreciation' => 'Composition 1 assez bien']]
        ]);
        self::assertTrue($res3, "Composition 1 saved on scale 40 (28.00/40).");

        // TEST 3: Scale normalization & EvaluationCalculationService
        echo "\n3. Testing scale normalization & subject average computation...\n";
        $normDev1 = EvaluationCalculationService::normalizeGrade(12.00, 20.00); // 12
        $normDev2 = EvaluationCalculationService::normalizeGrade(16.00, 20.00); // 16
        $normComp = EvaluationCalculationService::normalizeGrade(28.00, 40.00); // 14

        self::assertTrue($normDev1 === 12.00, "12/20 normalized = 12.00");
        self::assertTrue($normDev2 === 16.00, "16/20 normalized = 16.00");
        self::assertTrue($normComp === 14.00, "28/40 normalized = 14.00");

        $subjectAvg = EvaluationCalculationService::computeSubjectAverage($eleveId, $matiereId, $seqId);
        // (12 + 16 + 14) / 3 = 14.00
        self::assertTrue($subjectAvg === 14.00, "Subject average is (12 + 16 + 14) / 3 = 14.00");

        echo "\n=================================================================\n";
        echo "🏆 ALL DYNAMIC EVALUATION TYPES & CALCULATION TESTS PASSED!\n";
        echo "=================================================================\n";
    }
}

EvaluationDynamicTypesAndCalculationRunner::runAllTests();
?>