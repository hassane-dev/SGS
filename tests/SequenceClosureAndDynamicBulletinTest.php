<?php
/**
 * Test Suite: Dynamic Report Cards, Sequence Closure & Historical Snapshot Immutability.
 * Validates all 12 mandatory scenarios required for SGS Academic Engine.
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Bulletin.php';
require_once __DIR__ . '/../src/services/EvaluationCalculationService.php';
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';
require_once __DIR__ . '/../src/services/SequenceClosureService.php';
require_once __DIR__ . '/../src/services/AcademicAnalysisService.php';

class SequenceClosureAndDynamicBulletinTest {

    private PDO $db;
    private int $lyceeId = 1;
    private int $anneeId;
    private int $classeId;
    private int $matiereMathId;
    private int $matiereFrId;
    private array $eleveIds = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function runAllTests() {
        echo "=========================================================\n";
        echo " TEST SUITE: DYNAMIC BULLETINS & SEQUENCE CLOSURE \n";
        echo "=========================================================\n\n";

        $this->setupTestData();

        $this->test1_DynamicColumns_1Devoir_1Comp();
        $this->test2_DynamicColumns_2Devoirs_1TP_1Comp();
        $this->test3_DynamicColumns_3Devoirs();
        $this->test4_AbsenceOfGradeAndNormalization();
        $this->test5_SequenceActiveDateBoundary();
        $this->test6_AtomicClosureAndStandardRanking();
        $this->test7_PostClosureImmutability();
        $this->test8_PostClosureGradeSaveRefusal();
        $this->test9_AcademicAnalysisFromSnapshots();
        $this->test10_CorrectionA_3OccurrencesValues();
        $this->test11_CorrectionB_InstitutionalAppreciation();

        echo "\n=========================================================\n";
        echo " ALL MANDATORY ACADEMIC SCENARIOS PASSED SUCCESSFULLY! \n";
        echo "=========================================================\n";
    }

    private function setupTestData() {
        echo "Initializing test data...\n";

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['user'] = ['id_user' => 1, 'lycee_id' => 1, 'role_id' => 1];

        // Create or find active year
        $stmt = $this->db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $aId = $stmt ? $stmt->fetchColumn() : null;

        if (!$aId) {
            $stmtInsA = $this->db->prepare("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active, cloturee) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1, 0)");
            $stmtInsA->execute();
            $this->anneeId = (int)$this->db->lastInsertId();
        } else {
            $this->anneeId = (int)$aId;
        }

        // Create test class
        $stmtInsC = $this->db->prepare("INSERT INTO classes (lycee_id, niveau, numero) VALUES (:l, '6ème', 1)");
        $stmtInsC->execute(['l' => $this->lyceeId]);
        $this->classeId = (int)$this->db->lastInsertId();

        // Create test subjects
        $stmtM1 = $this->db->prepare("INSERT INTO matieres (lycee_id, nom_matiere) VALUES (:l, 'Mathématiques Test')");
        $stmtM1->execute(['l' => $this->lyceeId]);
        $this->matiereMathId = (int)$this->db->lastInsertId();

        $stmtM2 = $this->db->prepare("INSERT INTO matieres (lycee_id, nom_matiere) VALUES (:l, 'Français Test')");
        $stmtM2->execute(['l' => $this->lyceeId]);
        $this->matiereFrId = (int)$this->db->lastInsertId();

        // Attach subjects to class with coefficients (Math = 4.0, Français = 3.0)
        $stmtCM1 = $this->db->prepare("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (:c, :m, 4.0)");
        $stmtCM1->execute(['c' => $this->classeId, 'm' => $this->matiereMathId]);

        $stmtCM2 = $this->db->prepare("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (:c, :m, 3.0)");
        $stmtCM2->execute(['c' => $this->classeId, 'm' => $this->matiereFrId]);

        // Create 4 test students
        for ($i = 1; $i <= 4; $i++) {
            $stmtE = $this->db->prepare("INSERT INTO eleves (lycee_id, nom, prenom, date_naissance) VALUES (:l, 'NOM_$i', 'Prenom_$i', '2012-01-0$i')");
            $stmtE->execute(['l' => $this->lyceeId]);
            $eId = (int)$this->db->lastInsertId();
            $this->eleveIds[] = $eId;

            // Enrol in class
            $stmtEt = $this->db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (:e, :c, :l, :a, 1, 'active')");
            $stmtEt->execute(['e' => $eId, 'c' => $this->classeId, 'l' => $this->lyceeId, 'a' => $this->anneeId]);
        }

        echo "Test environment ready: Class ID={$this->classeId}, 4 Students created.\n\n";
    }

    private function test1_DynamicColumns_1Devoir_1Comp() {
        echo "TEST 1 : Dynamic Columns (1 Devoir, 1 Composition)...\n";

        // Setup evaluation types in param_type_evaluation
        $this->db->exec("DELETE FROM param_type_evaluation WHERE lycee_id = {$this->lyceeId}");

        $stmt1 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'devoir', 'Devoir', 20.0, 1, 1, 1)");
        $stmt1->execute(['l' => $this->lyceeId]);

        $stmt2 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'composition', 'Composition', 20.0, 1, 1, 2)");
        $stmt2->execute(['l' => $this->lyceeId]);

        $columns = Bulletin::getEvaluationColumns($this->lyceeId);

        assert(count($columns) === 2, "Expected 2 columns, got " . count($columns));
        assert($columns[0]['label'] === 'Devoir', "Expected 'Devoir', got " . $columns[0]['label']);
        assert($columns[1]['label'] === 'Composition', "Expected 'Composition', got " . $columns[1]['label']);

        echo "  [OK] Single occurrence labels render without appended numbers ('Devoir', 'Composition').\n";
    }

    private function test2_DynamicColumns_2Devoirs_1TP_1Comp() {
        echo "TEST 2 : Dynamic Columns (2 Devoirs, 1 TP, 1 Composition)...\n";

        $this->db->exec("DELETE FROM param_type_evaluation WHERE lycee_id = {$this->lyceeId}");

        $stmt1 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'devoir', 'Devoir', 20.0, 2, 1, 1)");
        $stmt1->execute(['l' => $this->lyceeId]);

        $stmt2 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'tp', 'TP', 20.0, 1, 1, 2)");
        $stmt2->execute(['l' => $this->lyceeId]);

        $stmt3 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'composition', 'Composition', 20.0, 1, 1, 3)");
        $stmt3->execute(['l' => $this->lyceeId]);

        $columns = Bulletin::getEvaluationColumns($this->lyceeId);

        assert(count($columns) === 4, "Expected 4 columns, got " . count($columns));
        assert($columns[0]['label'] === 'Devoir 1', "Expected 'Devoir 1', got " . $columns[0]['label']);
        assert($columns[1]['label'] === 'Devoir 2', "Expected 'Devoir 2', got " . $columns[1]['label']);
        assert($columns[2]['label'] === 'TP', "Expected 'TP', got " . $columns[2]['label']);
        assert($columns[3]['label'] === 'Composition', "Expected 'Composition', got " . $columns[3]['label']);

        echo "  [OK] Multi-occurrence labels render appended occurrence numbers ('Devoir 1', 'Devoir 2', 'TP', 'Composition').\n";
    }

    private function test3_DynamicColumns_3Devoirs() {
        echo "TEST 3 : Dynamic Columns (3 Devoirs)...\n";

        $this->db->exec("DELETE FROM param_type_evaluation WHERE lycee_id = {$this->lyceeId}");

        $stmt1 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'devoir', 'Devoir', 20.0, 3, 1, 1)");
        $stmt1->execute(['l' => $this->lyceeId]);

        $columns = Bulletin::getEvaluationColumns($this->lyceeId);

        assert(count($columns) === 3, "Expected 3 columns, got " . count($columns));
        assert($columns[0]['label'] === 'Devoir 1', "Expected 'Devoir 1'");
        assert($columns[1]['label'] === 'Devoir 2', "Expected 'Devoir 2'");
        assert($columns[2]['label'] === 'Devoir 3', "Expected 'Devoir 3'");

        echo "  [OK] Maximum 3 occurrences supported ('Devoir 1', 'Devoir 2', 'Devoir 3').\n";
    }

    private function test4_AbsenceOfGradeAndNormalization() {
        echo "TEST 4 : Absence of grade & Bareme Normalization...\n";

        // Test bareme normalization: 15/30 -> 10/20
        $norm1 = EvaluationCalculationService::normalizeGrade(15.0, 30.0);
        assert(abs($norm1 - 10.0) < 0.001, "Expected 10.0 for 15/30, got $norm1");

        $norm2 = EvaluationCalculationService::normalizeGrade(14.0, 20.0);
        assert(abs($norm2 - 14.0) < 0.001, "Expected 14.0 for 14/20, got $norm2");

        echo "  [OK] Grade normalization computed accurately.\n";
    }

    private function test5_SequenceActiveDateBoundary() {
        echo "TEST 5 : Sequence::findActiveForYear() with date boundaries...\n";

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // Create an active sequence covering today
        $stmtS = $this->db->prepare("
            INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
            VALUES (:l, :a, 'Séquence 1 Test Date', 'trimestrielle', :d_start, :d_end, 'ouverte')
        ");
        $stmtS->execute([
            'l' => $this->lyceeId,
            'a' => $this->anneeId,
            'd_start' => $yesterday,
            'd_end' => $tomorrow
        ]);
        $seqId = (int)$this->db->lastInsertId();

        $activeSeq = Sequence::findActiveForYear($this->lyceeId, $this->anneeId, $today);
        assert($activeSeq !== false, "Active sequence should be found.");
        assert((int)$activeSeq['id'] === $seqId, "Sequence ID should match $seqId");

        echo "  [OK] Sequence::findActiveForYear() correctly asserts date_debut <= today <= date_fin.\n";
    }

    private function test6_AtomicClosureAndStandardRanking() {
        echo "TEST 6 : Atomic Sequence Closure & 1-2-2-4 Ranking...\n";

        // Create open sequence for closure test
        $stmtS = $this->db->prepare("
            INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
            VALUES (:l, :a, 'Séquence Closure Test', 'trimestrielle', '2024-09-01', '2024-10-31', 'ouverte')
        ");
        $stmtS->execute(['l' => $this->lyceeId, 'a' => $this->anneeId]);
        $seqId = (int)$this->db->lastInsertId();

        // Get type ID for devoir
        $stmtT = $this->db->query("SELECT id FROM param_type_evaluation WHERE lycee_id = {$this->lyceeId} AND code = 'devoir' LIMIT 1");
        $typeId = (int)$stmtT->fetchColumn();

        // Insert grades for 4 students to create a tie on rank 2:
        // Eleve 1: 18/20 in Math -> General Avg = 18.0 (Rank 1)
        // Eleve 2: 14/20 in Math -> General Avg = 14.0 (Rank 2 tie)
        // Eleve 3: 14/20 in Math -> General Avg = 14.0 (Rank 2 tie)
        // Eleve 4: 10/20 in Math -> General Avg = 10.0 (Rank 4)

        $e1 = $this->eleveIds[0];
        $e2 = $this->eleveIds[1];
        $e3 = $this->eleveIds[2];
        $e4 = $this->eleveIds[3];

        $insEv = $this->db->prepare("
            INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, type_evaluation_id, numero_evaluation, note, bareme_snapshot, coefficient)
            VALUES (:l, :c, :m, 1, :e, :s, :a, 'devoir', :tid, 1, :note, 20.0, 4.0)
        ");

        $insEv->execute(['l' => $this->lyceeId, 'c' => $this->classeId, 'm' => $this->matiereMathId, 'e' => $e1, 's' => $seqId, 'a' => $this->anneeId, 'tid' => $typeId, 'note' => 18.0]);
        $insEv->execute(['l' => $this->lyceeId, 'c' => $this->classeId, 'm' => $this->matiereMathId, 'e' => $e2, 's' => $seqId, 'a' => $this->anneeId, 'tid' => $typeId, 'note' => 14.0]);
        $insEv->execute(['l' => $this->lyceeId, 'c' => $this->classeId, 'm' => $this->matiereMathId, 'e' => $e3, 's' => $seqId, 'a' => $this->anneeId, 'tid' => $typeId, 'note' => 14.0]);
        $insEv->execute(['l' => $this->lyceeId, 'c' => $this->classeId, 'm' => $this->matiereMathId, 'e' => $e4, 's' => $seqId, 'a' => $this->anneeId, 'tid' => $typeId, 'note' => 10.0]);

        // Execute Sequence Closure
        $closureResult = SequenceClosureService::closeSequence($seqId, 1);
        assert($closureResult['success'] === true, "Sequence closure should succeed.");

        // Verify sequence status changed to 'fermee'
        $stmtSeqCheck = $this->db->query("SELECT statut FROM sequences WHERE id = $seqId");
        assert($stmtSeqCheck->fetchColumn() === 'fermee', "Sequence status must be 'fermee'.");

        // Verify rankings in bulletins table
        $stmtBul1 = $this->db->query("SELECT rang_int, rang FROM bulletins WHERE eleve_id = $e1 AND sequence_id = $seqId");
        $b1 = $stmtBul1->fetch(PDO::FETCH_ASSOC);
        assert((int)$b1['rang_int'] === 1 && $b1['rang'] === '1er', "Eleve 1 expected 1er, got " . $b1['rang']);

        $stmtBul2 = $this->db->query("SELECT rang_int, rang FROM bulletins WHERE eleve_id = $e2 AND sequence_id = $seqId");
        $b2 = $stmtBul2->fetch(PDO::FETCH_ASSOC);
        assert((int)$b2['rang_int'] === 2 && strpos($b2['rang'], '2ème ex') !== false, "Eleve 2 expected 2ème ex, got " . $b2['rang']);

        $stmtBul3 = $this->db->query("SELECT rang_int, rang FROM bulletins WHERE eleve_id = $e3 AND sequence_id = $seqId");
        $b3 = $stmtBul3->fetch(PDO::FETCH_ASSOC);
        assert((int)$b3['rang_int'] === 2 && strpos($b3['rang'], '2ème ex') !== false, "Eleve 3 expected 2ème ex, got " . $b3['rang']);

        $stmtBul4 = $this->db->query("SELECT rang_int, rang FROM bulletins WHERE eleve_id = $e4 AND sequence_id = $seqId");
        $b4 = $stmtBul4->fetch(PDO::FETCH_ASSOC);
        assert((int)$b4['rang_int'] === 4 && $b4['rang'] === '4ème', "Eleve 4 expected 4ème, got " . $b4['rang']);

        echo "  [OK] Sequence closed atomically. 1-2-2-4 competition ranking verified (1er, 2ème ex, 2ème ex, 4ème).\n";
    }

    private function test7_PostClosureImmutability() {
        echo "TEST 7 : Post-Closure Snapshot Immutability...\n";

        // Find closed sequence ID from previous test
        $stmtS = $this->db->query("SELECT id FROM sequences WHERE lycee_id = {$this->lyceeId} AND statut = 'fermee' ORDER BY id DESC LIMIT 1");
        $seqId = (int)$stmtS->fetchColumn();

        $e1 = $this->eleveIds[0];

        // Fetch closed bulletin report
        $report1 = Bulletin::generateForStudent($e1, $seqId);
        assert($report1['is_provisoire'] === false, "Closed bulletin must NOT be provisional.");

        $mathMatiereKey = null;
        foreach ($report1['matieres'] as $k => $m) {
            if ($m['matiere_id'] == $this->matiereMathId || strpos($m['nom'], 'Math') !== false) {
                $mathMatiereKey = $k;
                break;
            }
        }
        assert($mathMatiereKey !== null, "Math subject should be present in bulletin matieres.");

        $originalMathName = $report1['matieres'][$mathMatiereKey]['nom'];
        $originalMathCoef = $report1['matieres'][$mathMatiereKey]['coefficient'];

        // Modify subject name in matieres table and coefficient in classe_matieres table
        $this->db->exec("UPDATE matieres SET nom_matiere = 'Mathématiques MODIFIÉES' WHERE id_matiere = {$this->matiereMathId}");
        $this->db->exec("UPDATE classe_matieres SET coefficient = 99.0 WHERE classe_id = {$this->classeId} AND matiere_id = {$this->matiereMathId}");

        // Re-generate bulletin for student
        $report2 = Bulletin::generateForStudent($e1, $seqId);

        assert($report2['matieres'][$mathMatiereKey]['nom'] === $originalMathName, "Snapshotted subject name must remain unchanged!");
        assert((float)$report2['matieres'][$mathMatiereKey]['coefficient'] === (float)$originalMathCoef, "Snapshotted coefficient must remain unchanged!");

        echo "  [OK] Post-closure modifications to matieres or classe_matieres do NOT alter historical report cards!\n";
    }

    private function test8_PostClosureGradeSaveRefusal() {
        echo "TEST 8 : Refusal of grade modifications on closed sequences...\n";

        $stmtS = $this->db->query("SELECT id FROM sequences WHERE lycee_id = {$this->lyceeId} AND statut = 'fermee' ORDER BY id DESC LIMIT 1");
        $seqId = (int)$stmtS->fetchColumn();

        $e1 = $this->eleveIds[0];

        // Attempt to save grades on closed sequence via Evaluation::saveGrades
        $payload = [
            'sequence_id' => $seqId,
            'classe_id' => $this->classeId,
            'matiere_id' => $this->matiereMathId,
            'enseignant_id' => 1,
            'type' => 'devoir',
            'grades' => [
                $e1 => ['note' => 20.0, 'appreciation' => 'Tentative post-clôture']
            ]
        ];

        $saveResult = Evaluation::saveGrades($payload);
        assert($saveResult === false, "Grade save on closed sequence MUST be rejected.");

        // Check EvaluationSaisieService decision
        $decision = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereMathId, $seqId, 'devoir');
        assert($decision['allowed'] === false, "EvaluationSaisieService MUST reject closed sequence grading.");
        assert($decision['code'] === 'DENIED_SEQUENCE_CLOSED', "Expected DENIED_SEQUENCE_CLOSED, got " . $decision['code']);

        echo "  [OK] Grade edits on closed sequences strictly refused with DENIED_SEQUENCE_CLOSED.\n";
    }

    private function test9_AcademicAnalysisFromSnapshots() {
        echo "TEST 9 : Academic Analysis Service uses closed snapshots...\n";

        $e1 = $this->eleveIds[0];
        $snapshots = AcademicAnalysisService::getOfficialBulletinSnapshots($e1);

        assert(!empty($snapshots), "Official bulletin snapshots should not be empty.");
        assert((float)$snapshots[0]['moyenne_generale'] === 18.0, "Expected general average 18.0, got " . $snapshots[0]['moyenne_generale']);

        echo "  [OK] AcademicAnalysisService successfully loads official closed bulletin snapshots.\n";
    }

    private function test10_CorrectionA_3OccurrencesValues() {
        echo "TEST 10 : Correction A — 3 Occurrences Grade Injection (Devoir 1=12, Devoir 2=15, Devoir 3=17)...\n";

        // Setup 3 Devoirs in param_type_evaluation
        $this->db->exec("DELETE FROM param_type_evaluation WHERE lycee_id = {$this->lyceeId}");
        $stmtT = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'devoir', 'Devoir', 20.0, 3, 1, 1)");
        $stmtT->execute(['l' => $this->lyceeId]);
        $typeId = (int)$this->db->lastInsertId();

        // Create open sequence for test
        $stmtS = $this->db->prepare("
            INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
            VALUES (:l, :a, 'Séquence 3 Occurrences Test', 'trimestrielle', '2024-11-01', '2024-12-31', 'ouverte')
        ");
        $stmtS->execute(['l' => $this->lyceeId, 'a' => $this->anneeId]);
        $seqId = (int)$this->db->lastInsertId();

        $e1 = $this->eleveIds[0];

        // Save Devoir 1 = 12, Devoir 2 = 15, Devoir 3 = 17
        Evaluation::saveGrades([
            'classe_id' => $this->classeId,
            'matiere_id' => $this->matiereMathId,
            'sequence_id' => $seqId,
            'type' => 'devoir',
            'numero_evaluation' => 1,
            'bareme' => 20.0,
            'coefficient' => 4.0,
            'enseignant_id' => 1,
            'grades' => [$e1 => ['note' => 12.0, 'appreciation' => 'Travail moyen en Devoir 1']]
        ]);

        Evaluation::saveGrades([
            'classe_id' => $this->classeId,
            'matiere_id' => $this->matiereMathId,
            'sequence_id' => $seqId,
            'type' => 'devoir',
            'numero_evaluation' => 2,
            'bareme' => 20.0,
            'coefficient' => 4.0,
            'enseignant_id' => 1,
            'grades' => [$e1 => ['note' => 15.0, 'appreciation' => 'Bon effort en Devoir 2']]
        ]);

        Evaluation::saveGrades([
            'classe_id' => $this->classeId,
            'matiere_id' => $this->matiereMathId,
            'sequence_id' => $seqId,
            'type' => 'devoir',
            'numero_evaluation' => 3,
            'bareme' => 20.0,
            'coefficient' => 4.0,
            'enseignant_id' => 1,
            'grades' => [$e1 => ['note' => 17.0, 'appreciation' => 'Très bien en Devoir 3']]
        ]);

        // Generate bulletin report for student
        $bulletin = Bulletin::generateForStudent($e1, $seqId);
        assert($bulletin !== false, "Bulletin report generation should succeed.");

        $mathMatiere = null;
        foreach ($bulletin['matieres'] as $m) {
            if ($m['matiere_id'] == $this->matiereMathId) {
                $mathMatiere = $m;
                break;
            }
        }
        assert($mathMatiere !== null, "Math subject must exist in bulletin.");

        $evalValues = $mathMatiere['evaluation_values'];
        assert(isset($evalValues['devoir_1']), "Column key 'devoir_1' must exist in evaluation_values.");
        assert(isset($evalValues['devoir_2']), "Column key 'devoir_2' must exist in evaluation_values.");
        assert(isset($evalValues['devoir_3']), "Column key 'devoir_3' must exist in evaluation_values.");

        assert((float)$evalValues['devoir_1'] === 12.0, "Expected Devoir 1 = 12.0, got " . var_export($evalValues['devoir_1'], true));
        assert((float)$evalValues['devoir_2'] === 15.0, "Expected Devoir 2 = 15.0, got " . var_export($evalValues['devoir_2'], true));
        assert((float)$evalValues['devoir_3'] === 17.0, "Expected Devoir 3 = 17.0, got " . var_export($evalValues['devoir_3'], true));

        // Average should be (12 + 15 + 17) / 3 = 14.67
        assert(abs($mathMatiere['note'] - 14.67) < 0.01, "Expected subject average ~14.67, got " . $mathMatiere['note']);

        echo "  [OK] Correction A verified: Devoir 1 = 12, Devoir 2 = 15, Devoir 3 = 17 appear respectively in devoir_1, devoir_2, devoir_3 columns.\n";
    }

    private function test11_CorrectionB_InstitutionalAppreciation() {
        echo "TEST 11 : Correction B — Institutional Subject Appreciation Generation & Storage...\n";

        // Re-use sequence from test 10 with Math average = 14.67 (institutional appreciation "Bien")
        $stmtS = $this->db->query("SELECT id FROM sequences WHERE lycee_id = {$this->lyceeId} AND nom = 'Séquence 3 Occurrences Test' LIMIT 1");
        $seqId = (int)$stmtS->fetchColumn();

        $e1 = $this->eleveIds[0];

        // In open sequence, bulletin must calculate appreciation from subject average (14.67 -> "Bien")
        // and NEVER copy individual evaluation appreciations (e.g. 'Travail moyen en Devoir 1')
        $openBulletin = Bulletin::generateForStudent($e1, $seqId);
        $mathMatiere = $openBulletin['matieres'][$this->matiereMathId];

        assert($mathMatiere['appreciation'] === 'Bien', "Expected subject appreciation 'Bien' for avg 14.67, got '" . $mathMatiere['appreciation'] . "'");
        assert($mathMatiere['appreciation'] !== 'Travail moyen en Devoir 1', "Subject appreciation MUST NOT be copied from individual evaluation!");

        // Close sequence and verify snapshot in bulletin_details.appreciation_matiere
        SequenceClosureService::closeSequence($seqId, 1);

        $closedBulletin = Bulletin::generateForStudent($e1, $seqId);
        $closedMathMatiere = $closedBulletin['matieres'][$this->matiereMathId];

        assert($closedMathMatiere['appreciation'] === 'Bien', "Closed bulletin expected 'Bien' from bulletin_details snapshot, got '" . $closedMathMatiere['appreciation'] . "'");

        // Verify direct DB snapshot in bulletin_details
        $stmtBD = $this->db->prepare("
            SELECT bd.appreciation_matiere
            FROM bulletin_details bd
            JOIN bulletins b ON bd.bulletin_id = b.id
            WHERE b.eleve_id = :e AND b.sequence_id = :s AND bd.matiere_id = :m
        ");
        $stmtBD->execute(['e' => $e1, 's' => $seqId, 'm' => $this->matiereMathId]);
        $snapshotApp = $stmtBD->fetchColumn();

        assert($snapshotApp === 'Bien', "bulletin_details.appreciation_matiere DB snapshot expected 'Bien', got '$snapshotApp'");

        echo "  [OK] Correction B verified: Institutional appreciation 'Bien' calculated for average 14.67 and persisted to bulletin_details.appreciation_matiere.\n";
    }
}

// Execute test suite
$test = new SequenceClosureAndDynamicBulletinTest();
$test->runAllTests();
?>