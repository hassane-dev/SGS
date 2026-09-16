<?php
/**
 * Integration Test Suite: Grade Dashboard (Live vs Official Snapshots, RBAC & Completion).
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/GradeDashboardService.php';
require_once __DIR__ . '/../src/services/EvaluationCalculationService.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';

class GradeDashboardTest {

    private PDO $db;
    private int $lyceeId;
    private int $anneeId;
    private int $sequenceOpenId;
    private int $sequenceClosedId;
    private int $classeId;
    private int $matiereId;
    private int $teacherUserId;
    private int $unassignedTeacherUserId;
    private int $student1Id;
    private int $student2Id;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function setUp(): void {
        $this->db->beginTransaction();

        // 1. Create test Lycée
        $stmtL = $this->db->prepare("INSERT INTO param_lycee (nom_lycee, sigle) VALUES ('Lycée Test Dashboard', 'LTD_TEST')");
        $stmtL->execute();
        $this->lyceeId = (int)$this->db->lastInsertId();

        // 2. Create test Cycle & Active Academic Year
        $stmtCy = $this->db->prepare("INSERT INTO cycles (lycee_id, nom_cycle) VALUES (:l, 'Cycle Secondaire Test')");
        $stmtCy->execute(['l' => $this->lyceeId]);
        $cycleId = (int)$this->db->lastInsertId();

        $stmtA = $this->db->prepare("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active, cloturee) VALUES ('2025-2026_TEST', '2025-09-01', '2026-06-30', 1, 0)");
        $stmtA->execute();
        $this->anneeId = (int)$this->db->lastInsertId();

        // 3. Create Open and Closed Sequences
        $stmtSo = $this->db->prepare("INSERT INTO sequences (nom, date_debut, date_fin, annee_academique_id, lycee_id, statut) VALUES ('Séquence 1 Test', '2025-09-01', '2025-10-31', :a, :l, 'ouverte')");
        $stmtSo->execute(['a' => $this->anneeId, 'l' => $this->lyceeId]);
        $this->sequenceOpenId = (int)$this->db->lastInsertId();

        $stmtSc = $this->db->prepare("INSERT INTO sequences (nom, date_debut, date_fin, annee_academique_id, lycee_id, statut) VALUES ('Séquence 2 Test', '2025-11-01', '2025-12-31', :a, :l, 'cloturee')");
        $stmtSc->execute(['a' => $this->anneeId, 'l' => $this->lyceeId]);
        $this->sequenceClosedId = (int)$this->db->lastInsertId();

        // 4. Create Class & Subject
        $stmtC = $this->db->prepare("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES (:l, :cy, '6eme', '1')");
        $stmtC->execute(['l' => $this->lyceeId, 'cy' => $cycleId]);
        $this->classeId = (int)$this->db->lastInsertId();

        $stmtM = $this->db->prepare("INSERT INTO matieres (nom_matiere, lycee_id) VALUES ('Mathématiques Test', :l)");
        $stmtM->execute(['l' => $this->lyceeId]);
        $this->matiereId = (int)$this->db->lastInsertId();

        $stmtCm = $this->db->prepare("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (:c, :m, 3.00)");
        $stmtCm->execute(['c' => $this->classeId, 'm' => $this->matiereId]);

        // 5. Create Test Users (Teachers)
        $stmtU1 = $this->db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, lycee_id) VALUES ('Prof', 'Assigné', 'prof1_test@sgs.local', 'hash', :l)");
        $stmtU1->execute(['l' => $this->lyceeId]);
        $this->teacherUserId = (int)$this->db->lastInsertId();

        $stmtU2 = $this->db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, lycee_id) VALUES ('Prof', 'NonAssigné', 'prof2_test@sgs.local', 'hash', :l)");
        $stmtU2->execute(['l' => $this->lyceeId]);
        $this->unassignedTeacherUserId = (int)$this->db->lastInsertId();

        // Pedagogical Assignment for Prof 1
        $stmtAssign = $this->db->prepare("
            INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut)
            VALUES (:u, :c, :m, :a, '2025-09-01', 'actif')
        ");
        $stmtAssign->execute(['u' => $this->teacherUserId, 'c' => $this->classeId, 'm' => $this->matiereId, 'a' => $this->anneeId]);

        // 6. Create Enrolled Students & Evaluations
        $stmtE1 = $this->db->prepare("INSERT INTO eleves (nom, prenom, lycee_id) VALUES ('Élève1', 'Test', :l)");
        $stmtE1->execute(['l' => $this->lyceeId]);
        $this->student1Id = (int)$this->db->lastInsertId();

        $stmtE2 = $this->db->prepare("INSERT INTO eleves (nom, prenom, lycee_id) VALUES ('Élève2', 'Test', :l)");
        $stmtE2->execute(['l' => $this->lyceeId]);
        $this->student2Id = (int)$this->db->lastInsertId();

        // Etudes enrollment
        $stmtEt1 = $this->db->prepare("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, is_active, status) VALUES (:e, :c, :a, 1, 'active')");
        $stmtEt1->execute(['e' => $this->student1Id, 'c' => $this->classeId, 'a' => $this->anneeId]);

        $stmtEt2 = $this->db->prepare("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, is_active, status) VALUES (:e, :c, :a, 1, 'active')");
        $stmtEt2->execute(['e' => $this->student2Id, 'c' => $this->classeId, 'a' => $this->anneeId]);

        // Insert Evaluations for Open Sequence
        $stmtEv1 = $this->db->prepare("
            INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, numero_evaluation, note, bareme_snapshot, coefficient)
            VALUES (:l, :c, :m, :u, :e, :s, :a, 'devoir', 1, 14.00, 20.00, 3.00)
        ");
        $stmtEv1->execute(['l' => $this->lyceeId, 'c' => $this->classeId, 'm' => $this->matiereId, 'u' => $this->teacherUserId, 'e' => $this->student1Id, 's' => $this->sequenceOpenId, 'a' => $this->anneeId]);

        $stmtEv2 = $this->db->prepare("
            INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, numero_evaluation, note, bareme_snapshot, coefficient)
            VALUES (:l, :c, :m, :u, :e, :s, :a, 'devoir', 1, 10.00, 20.00, 3.00)
        ");
        $stmtEv2->execute(['l' => $this->lyceeId, 'c' => $this->classeId, 'm' => $this->matiereId, 'u' => $this->teacherUserId, 'e' => $this->student2Id, 's' => $this->sequenceOpenId, 'a' => $this->anneeId]);

        // Insert Official Bulletins & BulletinDetails Snapshots for Closed Sequence
        $stmtB1 = $this->db->prepare("
            INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, lycee_id, classe_id, nom_classe_snapshot, effectif_classe, moyenne_generale, moyenne_classe, rang, rang_int, statut)
            VALUES (:e, :s, :a, :l, :c, '6eme 1', 2, 16.00, 14.00, '1er', 1, 'valide')
        ");
        $stmtB1->execute(['e' => $this->student1Id, 's' => $this->sequenceClosedId, 'a' => $this->anneeId, 'l' => $this->lyceeId, 'c' => $this->classeId]);
        $bul1Id = (int)$this->db->lastInsertId();

        $stmtBd1 = $this->db->prepare("
            INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes)
            VALUES (:b, :m, 'Mathématiques Test', 16.00, 3.00, 48.00)
        ");
        $stmtBd1->execute(['b' => $bul1Id, 'm' => $this->matiereId]);

        $stmtB2 = $this->db->prepare("
            INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, lycee_id, classe_id, nom_classe_snapshot, effectif_classe, moyenne_generale, moyenne_classe, rang, rang_int, statut)
            VALUES (:e, :s, :a, :l, :c, '6eme 1', 2, 12.00, 14.00, '2e', 2, 'valide')
        ");
        $stmtB2->execute(['e' => $this->student2Id, 's' => $this->sequenceClosedId, 'a' => $this->anneeId, 'l' => $this->lyceeId, 'c' => $this->classeId]);
        $bul2Id = (int)$this->db->lastInsertId();

        $stmtBd2 = $this->db->prepare("
            INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes)
            VALUES (:b, :m, 'Mathématiques Test', 12.00, 3.00, 36.00)
        ");
        $stmtBd2->execute(['b' => $bul2Id, 'm' => $this->matiereId]);

        // ParamTypeEvaluation default
        $stmtPte = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif) VALUES (:l, 'DEVOIR', 'Devoir', 20.00, 1, 1)");
        $stmtPte->execute(['l' => $this->lyceeId]);

        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_name' => 'enseignant'
        ];
        $_SESSION['permissions'] = ['note:create_own'];
        unset($_SESSION['user']['authorized_cycles']);
    }

    public function tearDown(): void {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    public function testOpenSequenceUsesLiveCalculations(): void {
        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);

        assert($data['success'] === true, "Dashboard call failed");
        assert($data['sequence']['is_closed'] === false, "Sequence should be open");
        assert($data['sequence']['status_label'] === "Résultats en temps réel", "Status label mismatch");

        // Expected live general average: (14.00 + 10.00) / 2 = 12.00
        assert($data['performance']['moyenne_generale'] == 12.00, "Live average mismatch");
        assert($data['performance']['min_moyenne'] == 10.00, "Min grade mismatch");
        assert($data['performance']['max_moyenne'] == 14.00, "Max grade mismatch");

        // Modify an evaluation in open sequence
        $stmtUp = $this->db->prepare("UPDATE evaluations SET note = 18.00 WHERE eleve_id = :e AND sequence_id = :s");
        $stmtUp->execute(['e' => $this->student2Id, 's' => $this->sequenceOpenId]);

        // Re-fetch dashboard data
        $dataUpdated = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);
        // New live average: (14.00 + 18.00) / 2 = 16.00
        assert($dataUpdated['performance']['moyenne_generale'] == 16.00, "Updated live average mismatch");
    }

    public function testClosedSequenceUsesOfficialSnapshots(): void {
        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceClosedId,
            'classe_id' => $this->classeId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);

        assert($data['success'] === true, "Dashboard call failed for closed sequence");
        assert($data['sequence']['is_closed'] === true, "Sequence should be closed");
        assert($data['sequence']['status_label'] === "Résultats officiels scellés", "Closed status label mismatch");

        // Official snapshot average: (16.00 + 12.00) / 2 = 14.00
        assert($data['performance']['moyenne_generale'] == 14.00, "Closed snapshot average mismatch");

        // Insert an evaluation into evaluations table for closed sequence post-closure
        $stmtEv3 = $this->db->prepare("
            INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, numero_evaluation, note, bareme_snapshot, coefficient)
            VALUES (:l, :c, :m, :u, :e, :s, :a, 'devoir', 1, 0.00, 20.00, 3.00)
        ");
        $stmtEv3->execute(['l' => $this->lyceeId, 'c' => $this->classeId, 'm' => $this->matiereId, 'u' => $this->teacherUserId, 'e' => $this->student1Id, 's' => $this->sequenceClosedId, 'a' => $this->anneeId]);

        // Re-fetch dashboard data for closed sequence
        $dataPostChange = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);
        // Official performance MUST REMAIN UNCHANGED (14.00) despite evaluations modification
        assert($dataPostChange['performance']['moyenne_generale'] == 14.00, "Closed performance changed post-closure!");
    }

    public function testUnassignedTeacherAccessIsBlocked(): void {
        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeId
        ];

        $exceptionCaught = false;
        try {
            GradeDashboardService::getDashboardData($filters, $this->unassignedTeacherUserId);
        } catch (Exception $e) {
            $exceptionCaught = true;
            assert(str_contains($e->getMessage(), "Accès refusé"), "Exception message mismatch");
        }

        assert($exceptionCaught === true, "Unassigned teacher was NOT blocked!");
    }

    public function testCompletionStatsOnClosedSequence(): void {
        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceClosedId,
            'classe_id' => $this->classeId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);

        assert($data['success'] === true, "Dashboard call failed for closed sequence completion test");
        assert(isset($data['completion']['expected_evaluations']), "Expected evaluations missing");
        assert(isset($data['completion']['recorded_evaluations']), "Recorded evaluations missing");
        assert($data['completion']['expected_evaluations'] > 0, "Expected evaluations should be > 0");
    }

    public function testCrossTenantLyceeIsolation(): void {
        $filters = [
            'lycee_id' => 999999, // Unpermitted Lycée ID
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId
        ];

        $exceptionCaught = false;
        try {
            GradeDashboardService::getDashboardData($filters, $this->teacherUserId);
        } catch (Exception $e) {
            $exceptionCaught = true;
            assert(str_contains($e->getMessage(), "Accès refusé au lycée"), "Cross-tenant exception message mismatch");
        }

        assert($exceptionCaught === true, "Cross-tenant access was NOT blocked!");
    }

    public function runAllTests(): void {
        echo "Running GradeDashboardTest...\n";
        $this->setUp();
        $this->testOpenSequenceUsesLiveCalculations();
        $this->tearDown();
        echo "  [PASS] testOpenSequenceUsesLiveCalculations\n";

        $this->setUp();
        $this->testClosedSequenceUsesOfficialSnapshots();
        $this->tearDown();
        echo "  [PASS] testClosedSequenceUsesOfficialSnapshots\n";

        $this->setUp();
        $this->testUnassignedTeacherAccessIsBlocked();
        $this->tearDown();
        echo "  [PASS] testUnassignedTeacherAccessIsBlocked\n";

        $this->setUp();
        $this->testCompletionStatsOnClosedSequence();
        $this->tearDown();
        echo "  [PASS] testCompletionStatsOnClosedSequence\n";

        $this->setUp();
        $this->testCrossTenantLyceeIsolation();
        $this->tearDown();
        echo "  [PASS] testCrossTenantLyceeIsolation\n";
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $test = new GradeDashboardTest();
    $test->runAllTests();
}
?>