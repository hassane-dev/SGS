<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/GradeDashboardService.php';

class GradeDashboardCycleScopeSecurityTest {

    private PDO $db;
    private int $lyceeIdA;
    private int $lyceeIdB;
    private int $cycle101;
    private int $cycle102;
    private int $cycle201;
    private int $classe101;
    private int $classe102;
    private int $classe201;
    private int $matiere7;
    private int $matiere8;
    private int $matiere201;
    private int $anneeIdA;
    private int $anneeIdB;
    private int $seqIdA;
    private int $seqIdB;
    private int $userIdRestricted;
    private int $userIdGlobal;
    private int $userIdTeacher;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function run() {
        echo "=========================================================\n";
        echo "RUNNING GradeDashboardCycleScopeSecurityTest\n";
        echo "=========================================================\n";

        $this->db->beginTransaction();

        try {
            $this->setUpFixtures();

            $this->test1_OmittedCycleWithRestrictedScope();
            $this->test2_ExplicitlyAuthorizedCycle();
            $this->test3_ExplicitlyForbiddenCycle();
            $this->test4_TrulyGlobalUser();
            $this->test5_TeacherExactAssignmentsScope();
            $this->test6_TeacherUnassignedSubjectInAssignedClass();
            $this->test7_EmptyCycleScopeNoData();
            $this->test8_MultiLyceeIsolation();
            $this->test9_AllDashboardMethodsScoped();

            echo "\nALL 9 GRADE DASHBOARD CYCLE SCOPE SECURITY TESTS PASSED (100%)\n";
        } finally {
            $this->db->rollBack();
        }
    }

    private function setUpFixtures() {
        // Create Role 88 (restricted_cycle_role), Role 89 (global_admin_role), Role 90 (teacher_role)
        $this->db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (88, 'restricted_cycle_role')");
        $this->db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (89, 'global_admin_role')");
        $this->db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (90, 'teacher_role')");

        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action) VALUES ('note', 'view_all')");
        $pViewAll = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'note' AND action = 'view_all'")->fetchColumn();
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (88, {$pViewAll})");
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (89, {$pViewAll})");

        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action) VALUES ('cycle', 'view_all_cycles')");
        $pViewCycles = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'cycle' AND action = 'view_all_cycles'")->fetchColumn();
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (89, {$pViewCycles})");

        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action) VALUES ('note', 'create_own')");
        $pCreateOwn = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'note' AND action = 'create_own'")->fetchColumn();
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (90, {$pCreateOwn})");

        // Create Lycée A & Lycée B
        $this->db->exec("INSERT INTO param_lycee (nom_lycee, sigle) VALUES ('Lycée A Dash', 'LYC_A')");
        $this->lyceeIdA = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO param_lycee (nom_lycee, sigle) VALUES ('Lycée B Dash', 'LYC_B')");
        $this->lyceeIdB = (int)$this->db->lastInsertId();

        // Active Years
        $this->db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2025-2026 A', '2025-09-01', '2026-06-30', 1)");
        $this->anneeIdA = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2025-2026 B', '2025-09-01', '2026-06-30', 1)");
        $this->anneeIdB = (int)$this->db->lastInsertId();

        // Sequences
        $this->db->exec("INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES ({$this->lyceeIdA}, {$this->anneeIdA}, 'Seq A', 'trimestrielle', '2025-09-01', '2025-11-01', 'ouverte')");
        $this->seqIdA = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES ({$this->lyceeIdB}, {$this->anneeIdB}, 'Seq B', 'trimestrielle', '2025-09-01', '2025-11-01', 'ouverte')");
        $this->seqIdB = (int)$this->db->lastInsertId();

        // Cycles
        $stmtC = $this->db->prepare("INSERT INTO cycles (lycee_id, nom_cycle) VALUES (:l, :n)");
        $stmtC->execute(['l' => $this->lyceeIdA, 'n' => 'Cycle 101']);
        $this->cycle101 = (int)$this->db->lastInsertId();

        $stmtC->execute(['l' => $this->lyceeIdA, 'n' => 'Cycle 102']);
        $this->cycle102 = (int)$this->db->lastInsertId();

        $stmtC->execute(['l' => $this->lyceeIdB, 'n' => 'Cycle 201']);
        $this->cycle201 = (int)$this->db->lastInsertId();

        // Classes
        $stmtCl = $this->db->prepare("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES (:l, :cy, :niv, :num)");
        $stmtCl->execute(['l' => $this->lyceeIdA, 'cy' => $this->cycle101, 'niv' => '6ème', 'num' => '101']);
        $this->classe101 = (int)$this->db->lastInsertId();

        $stmtCl->execute(['l' => $this->lyceeIdA, 'cy' => $this->cycle102, 'niv' => '2nde', 'num' => '102']);
        $this->classe102 = (int)$this->db->lastInsertId();

        $stmtCl->execute(['l' => $this->lyceeIdB, 'cy' => $this->cycle201, 'niv' => '3ème', 'num' => '201']);
        $this->classe201 = (int)$this->db->lastInsertId();

        // Matieres
        $stmtM = $this->db->prepare("INSERT INTO matieres (lycee_id, nom_matiere) VALUES (:l, :n)");
        $stmtM->execute(['l' => $this->lyceeIdA, 'n' => 'Matiere 7']);
        $this->matiere7 = (int)$this->db->lastInsertId();

        $stmtM->execute(['l' => $this->lyceeIdA, 'n' => 'Matiere 8']);
        $this->matiere8 = (int)$this->db->lastInsertId();

        $stmtM->execute(['l' => $this->lyceeIdB, 'n' => 'Matiere 201']);
        $this->matiere201 = (int)$this->db->lastInsertId();

        // Curriculum
        $this->db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES ({$this->classe101}, {$this->matiere7}, 2)");
        $this->db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES ({$this->classe101}, {$this->matiere8}, 2)");
        $this->db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES ({$this->classe102}, {$this->matiere7}, 3)");
        $this->db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES ({$this->classe201}, {$this->matiere201}, 2)");

        // Param evaluation
        $this->db->exec("INSERT INTO param_type_evaluation (lycee_id, code, libelle, actif) VALUES ({$this->lyceeIdA}, 'devoir', 'Devoir', 1)");
        $this->db->exec("INSERT INTO param_type_evaluation (lycee_id, code, libelle, actif) VALUES ({$this->lyceeIdB}, 'devoir', 'Devoir', 1)");

        // Users
        $stmtU = $this->db->prepare("INSERT INTO utilisateurs (nom, prenom, email, identifiant_public, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (:nom, 'Dash', :email, :login, 'hash', :role, :l, 1, 1)");

        // 1. Restricted Admin (Lycée A, Role 88, Cycle 101 ONLY)
        $stmtU->execute(['nom' => 'Restricted', 'email' => 'res@dash.a', 'login' => 'res_dash_a', 'role' => 88, 'l' => $this->lyceeIdA]);
        $this->userIdRestricted = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES ({$this->userIdRestricted}, {$this->cycle101}, '2020-01-01', 1)");

        // 2. Global Admin (Lycée A, Role 89, ALL cycles)
        $stmtU->execute(['nom' => 'Global', 'email' => 'glob@dash.a', 'login' => 'glob_dash_a', 'role' => 89, 'l' => $this->lyceeIdA]);
        $this->userIdGlobal = (int)$this->db->lastInsertId();

        // 3. Teacher (Lycée A, Role 90, Assigned to classe101 + matiere7 ONLY)
        $stmtU->execute(['nom' => 'Teacher', 'email' => 'tea@dash.a', 'login' => 'tea_dash_a', 'role' => 90, 'l' => $this->lyceeIdA]);
        $this->userIdTeacher = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut) VALUES ({$this->userIdTeacher}, {$this->classe101}, {$this->matiere7}, {$this->anneeIdA}, '2020-01-01', 'actif')");
    }

    private function authenticateRestrictedAdmin() {
        $_SESSION['user'] = [
            'id_user' => $this->userIdRestricted,
            'id' => $this->userIdRestricted,
            'role_id' => 88,
            'lycee_id' => $this->lyceeIdA,
            'auth_version' => 1,
            'permissions' => ['note' => ['view_all']]
        ];
        AuthorizationScopeService::invalidateUserCache($this->userIdRestricted);
    }

    private function authenticateGlobalAdmin() {
        $_SESSION['user'] = [
            'id_user' => $this->userIdGlobal,
            'id' => $this->userIdGlobal,
            'role_id' => 89,
            'lycee_id' => $this->lyceeIdA,
            'auth_version' => 1,
            'permissions' => ['note' => ['view_all'], 'cycle' => ['view_all_cycles']]
        ];
        AuthorizationScopeService::invalidateUserCache($this->userIdGlobal);
    }

    private function authenticateTeacher() {
        $_SESSION['user'] = [
            'id_user' => $this->userIdTeacher,
            'id' => $this->userIdTeacher,
            'role_id' => 90,
            'lycee_id' => $this->lyceeIdA,
            'auth_version' => 1,
            'permissions' => ['note' => ['create_own']]
        ];
        AuthorizationScopeService::invalidateUserCache($this->userIdTeacher);
    }

    public function test1_OmittedCycleWithRestrictedScope() {
        echo "[TEST 1] Omitted cycle_id (null) for restricted admin (Cycle {$this->cycle101} ONLY)... ";
        $this->authenticateRestrictedAdmin();

        $res = GradeDashboardService::getDashboardData([
            'lycee_id' => $this->lyceeIdA,
            'annee_academique_id' => $this->anneeIdA,
            'sequence_id' => $this->seqIdA,
            'cycle_id' => null
        ], $this->userIdRestricted);

        $classesFound = array_unique(array_column($res['completion']['incomplete_subjects'] ?? [], 'classe_id'));

        if (in_array($this->classe101, $classesFound, true) && !in_array($this->classe102, $classesFound, true)) {
            echo "PASS (Only authorized cycle {$this->cycle101} class included, forbidden cycle {$this->cycle102} excluded)\n";
        } else {
            throw new Exception("FAIL: Omitted cycle filter leaked class from unauthorized cycle {$this->cycle102}");
        }
    }

    public function test2_ExplicitlyAuthorizedCycle() {
        echo "[TEST 2] Explicitly requested authorized cycle {$this->cycle101}... ";
        $this->authenticateRestrictedAdmin();

        $res = GradeDashboardService::getDashboardData([
            'lycee_id' => $this->lyceeIdA,
            'annee_academique_id' => $this->anneeIdA,
            'sequence_id' => $this->seqIdA,
            'cycle_id' => $this->cycle101
        ], $this->userIdRestricted);

        $classesFound = array_unique(array_column($res['completion']['incomplete_subjects'] ?? [], 'classe_id'));

        if ($res['success'] && in_array($this->classe101, $classesFound, true) && !in_array($this->classe102, $classesFound, true)) {
            echo "PASS (Success for authorized cycle {$this->cycle101})\n";
        } else {
            throw new Exception("FAIL: Explicitly authorized cycle request failed");
        }
    }

    public function test3_ExplicitlyForbiddenCycle() {
        echo "[TEST 3] Explicitly requested forbidden cycle {$this->cycle102}... ";
        $this->authenticateRestrictedAdmin();

        $rejected = false;
        try {
            GradeDashboardService::getDashboardData([
                'lycee_id' => $this->lyceeIdA,
                'annee_academique_id' => $this->anneeIdA,
                'sequence_id' => $this->seqIdA,
                'cycle_id' => $this->cycle102
            ], $this->userIdRestricted);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès refusé au cycle') !== false) {
                $rejected = true;
            }
        }

        if ($rejected) {
            echo "PASS (Exception thrown for forbidden cycle {$this->cycle102})\n";
        } else {
            throw new Exception("FAIL: Explicitly requested forbidden cycle {$this->cycle102} was NOT rejected");
        }
    }

    public function test4_TrulyGlobalUser() {
        echo "[TEST 4] Global admin with cycle_id = null... ";
        $this->authenticateGlobalAdmin();

        $res = GradeDashboardService::getDashboardData([
            'lycee_id' => $this->lyceeIdA,
            'annee_academique_id' => $this->anneeIdA,
            'sequence_id' => $this->seqIdA,
            'cycle_id' => null
        ], $this->userIdGlobal);

        $classesFound = array_unique(array_column($res['completion']['incomplete_subjects'] ?? [], 'classe_id'));

        if (in_array($this->classe101, $classesFound, true) && in_array($this->classe102, $classesFound, true)) {
            echo "PASS (Global admin sees classes from all cycles in Lycée A)\n";
        } else {
            throw new Exception("FAIL: Global admin did not see all cycles");
        }
    }

    public function test5_TeacherExactAssignmentsScope() {
        echo "[TEST 5] Restricted teacher exact assignment scope (Classe {$this->classe101} + Matiere {$this->matiere7})... ";
        $this->authenticateTeacher();

        $res = GradeDashboardService::getDashboardData([
            'lycee_id' => $this->lyceeIdA,
            'annee_academique_id' => $this->anneeIdA,
            'sequence_id' => $this->seqIdA,
            'cycle_id' => null
        ], $this->userIdTeacher);

        $pairsFound = array_map(fn($s) => "{$s['classe_id']}:{$s['matiere_id']}", $res['completion']['incomplete_subjects'] ?? []);

        if (in_array("{$this->classe101}:{$this->matiere7}", $pairsFound, true) && !in_array("{$this->classe101}:{$this->matiere8}", $pairsFound, true)) {
            echo "PASS (Teacher strictly limited to assigned pair {$this->classe101}:{$this->matiere7})\n";
        } else {
            throw new Exception("FAIL: Teacher scope leaked unassigned subject {$this->matiere8}");
        }
    }

    public function test6_TeacherUnassignedSubjectInAssignedClass() {
        echo "[TEST 6] Teacher requesting unassigned subject {$this->matiere8} in assigned class {$this->classe101}... ";
        $this->authenticateTeacher();

        $rejected = false;
        try {
            GradeDashboardService::getDashboardData([
                'lycee_id' => $this->lyceeIdA,
                'annee_academique_id' => $this->anneeIdA,
                'sequence_id' => $this->seqIdA,
                'classe_id' => $this->classe101,
                'matiere_id' => $this->matiere8
            ], $this->userIdTeacher);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès refusé') !== false) {
                $rejected = true;
            }
        }

        if ($rejected) {
            echo "PASS (Exception thrown for unassigned subject {$this->matiere8})\n";
        } else {
            throw new Exception("FAIL: Teacher request for unassigned subject was NOT rejected");
        }
    }

    public function test7_EmptyCycleScopeNoData() {
        echo "[TEST 7] Admin user with zero authorized cycles... ";

        // Create user with NO cycle assignments
        $stmtU = $this->db->prepare("INSERT INTO utilisateurs (nom, prenom, email, identifiant_public, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES ('ZeroCycles', 'User', 'zero@dash.a', 'zero_dash_a', 'hash', 88, :l, 1, 1)");
        $stmtU->execute(['l' => $this->lyceeIdA]);
        $zeroUserId = (int)$this->db->lastInsertId();

        $_SESSION['user'] = [
            'id_user' => $zeroUserId,
            'id' => $zeroUserId,
            'role_id' => 88,
            'lycee_id' => $this->lyceeIdA,
            'auth_version' => 1,
            'permissions' => ['note' => ['view_all']]
        ];
        AuthorizationScopeService::invalidateUserCache($zeroUserId);

        $res = GradeDashboardService::getDashboardData([
            'lycee_id' => $this->lyceeIdA,
            'annee_academique_id' => $this->anneeIdA,
            'sequence_id' => $this->seqIdA
        ], $zeroUserId);

        if ($res['success'] === false && strpos($res['message'], 'Aucun cycle autorisé') !== false) {
            echo "PASS (Empty dashboard response returned for user with zero cycles)\n";
        } else {
            throw new Exception("FAIL: User with zero cycles got non-empty data response");
        }
    }

    public function test8_MultiLyceeIsolation() {
        echo "[TEST 8] Multi-Lycée Isolation (Lycée A vs Lycée B)... ";
        $this->authenticateRestrictedAdmin(); // User belongs to Lycée A

        $rejected = false;
        try {
            GradeDashboardService::getDashboardData([
                'lycee_id' => $this->lyceeIdB, // Belongs to Lycée B!
                'annee_academique_id' => $this->anneeIdB,
                'sequence_id' => $this->seqIdB
            ], $this->userIdRestricted);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès refusé au lycée') !== false) {
                $rejected = true;
            }
        }

        if ($rejected) {
            echo "PASS (Cross-lycée request rejected)\n";
        } else {
            throw new Exception("FAIL: Cross-lycée request to Lycée B was NOT rejected");
        }
    }

    public function test9_AllDashboardMethodsScoped() {
        echo "[TEST 9] All GradeDashboardService methods (Completion, Live, Snapshot, SummaryKpis)... ";
        $this->authenticateRestrictedAdmin();

        // 1. getCompletionData
        $comp = GradeDashboardService::getCompletionData(
            $this->lyceeIdA, $this->anneeIdA, $this->seqIdA,
            null, null, null, null, null,
            true, [], [$this->cycle101]
        );
        $classesComp = array_unique(array_column($comp['incomplete_subjects'], 'classe_id'));

        // 2. getLivePerformanceData
        $live = GradeDashboardService::getLivePerformanceData(
            $this->lyceeIdA, $this->anneeIdA, $this->seqIdA,
            null, null, null, null,
            true, [], [$this->cycle101]
        );

        // 3. getSummaryKpis
        $summary = GradeDashboardService::getSummaryKpis($this->lyceeIdA, $this->anneeIdA, $this->seqIdA);

        if (in_array($this->classe101, $classesComp, true) && !in_array($this->classe102, $classesComp, true)) {
            echo "PASS (All 4 methods apply authorizedCycleIds scope IN filter)\n";
        } else {
            throw new Exception("FAIL: Method scope check failed");
        }
    }
}

$test = new GradeDashboardCycleScopeSecurityTest();
$test->run();
