<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/AcademicFilterService.php';

class ClasseControllerCycleIdSecurityTest {

    private PDO $db;
    private int $lyceeIdA;
    private int $lyceeIdB;
    private int $cycleCollegeId;
    private int $cyclePremierId;
    private int $cycleLyceeBId;
    private int $adminUserId;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function run() {
        echo "=========================================================\n";
        echo "RUNNING ClasseControllerCycleIdSecurityTest\n";
        echo "=========================================================\n";

        $this->db->beginTransaction();

        try {
            $this->setUpFixtures();

            $this->test1_ValidAuthorizedCycleIdCreatesClass();
            $this->test2_MissingCycleIdRejected();
            $this->test3_InexistentCycleIdRejected();
            $this->test4_OtherLyceeCycleIdRejected();
            $this->test5_UnauthorizedCycleIdRejected();
            $this->test6_CustomNameCollegeCycleWorks();
            $this->test7_CustomNamePremierCycleWorks();
            $this->test8_SubmittedCycleIdNeverOverwritten();
            $this->test9_NoDbWriteOnRejection();

            echo "\nALL 9 CLASSE CONTROLLER CYCLE ID SECURITY TESTS PASSED (100%)\n";
        } finally {
            $this->db->rollBack();
        }
    }

    private function setUpFixtures() {
        // Ensure classes table has categorie column if SQLite schema needs it
        try {
            @$this->db->exec("ALTER TABLE classes ADD COLUMN categorie VARCHAR(50) NULL");
        } catch (Exception $e) {}

        // Seed Role 88 (restricted_class_creator)
        $this->db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (88, 'restricted_class_creator')");
        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action) VALUES ('class', 'create')");
        $pId = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'class' AND action = 'create'")->fetchColumn();
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (88, {$pId})");

        // Create Lycee A and Lycee B
        $this->db->exec("INSERT INTO param_lycee (nom_lycee, sigle) VALUES ('Lycée Custom Cycles A', 'LYC_CUST_A')");
        $this->lyceeIdA = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO param_lycee (nom_lycee, sigle) VALUES ('Lycée Custom Cycles B', 'LYC_CUST_B')");
        $this->lyceeIdB = (int)$this->db->lastInsertId();

        // Create Cycles with custom names on Lycee A ("Collège", "Premier cycle") - NO "CEG" or "Lycée" names!
        $stmtC = $this->db->prepare("INSERT INTO cycles (lycee_id, nom_cycle) VALUES (:l, :n)");
        $stmtC->execute(['l' => $this->lyceeIdA, 'n' => 'Collège']);
        $this->cycleCollegeId = (int)$this->db->lastInsertId();

        $stmtC->execute(['l' => $this->lyceeIdA, 'n' => 'Premier cycle']);
        $this->cyclePremierId = (int)$this->db->lastInsertId();

        $stmtC->execute(['l' => $this->lyceeIdB, 'n' => 'Secondaire B']);
        $this->cycleLyceeBId = (int)$this->db->lastInsertId();

        // Create Admin User for Lycee A
        $stmtU = $this->db->prepare("INSERT INTO utilisateurs (nom, prenom, email, identifiant_public, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES ('Admin', 'Custom', 'admin@cust.a', 'admin_cust_a', 'hash', 88, :l, 1, 1)");
        $stmtU->execute(['l' => $this->lyceeIdA]);
        $this->adminUserId = (int)$this->db->lastInsertId();

        // Assign user strictly to Collège cycle
        $stmtCyA = $this->db->prepare("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (:p, :c, '2020-01-01', 1)");
        $stmtCyA->execute(['p' => $this->adminUserId, 'c' => $this->cycleCollegeId]);
    }

    private function authenticateUser() {
        $_SESSION = [
            'user' => [
                'id_user' => $this->adminUserId,
                'id' => $this->adminUserId,
                'role_id' => 88,
                'nom' => 'Admin',
                'prenom' => 'Custom',
                'lycee_id' => $this->lyceeIdA,
                'auth_version' => 1,
                'role' => 'restricted_class_creator',
                'permissions' => ['class' => ['create']]
            ]
        ];
        AuthorizationScopeService::invalidateUserCache($this->adminUserId);
    }

    public function test1_ValidAuthorizedCycleIdCreatesClass() {
        echo "[TEST 1] Valid authorized cycle_id ({$this->cycleCollegeId}) creates class... ";
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => $this->cycleCollegeId,
            'niveau' => '6ème',
            'numero' => '10'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM classes WHERE lycee_id = {$this->lyceeIdA}")->fetchColumn();
        $controller = new ClasseController();

        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM classes WHERE lycee_id = {$this->lyceeIdA}")->fetchColumn();

        if ($finalCount === $initialCount + 1) {
            echo "PASS (Class successfully created with cycle_id {$this->cycleCollegeId})\n";
        } else {
            throw new Exception("FAIL: Valid class creation failed");
        }
    }

    public function test2_MissingCycleIdRejected() {
        echo "[TEST 2] Missing cycle_id (null) rejected... ";
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'niveau' => '6ème',
            'numero' => '11'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
        $controller = new ClasseController();

        $rejected = false;
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (InvalidArgumentException $e) {
            if (ob_get_level() > 0) ob_end_clean();
            if (strpos($e->getMessage(), 'cycle est obligatoire') !== false) {
                $rejected = true;
            }
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "PASS (Missing cycle_id rejected with InvalidArgumentException & 0 DB write)\n";
        } else {
            throw new Exception("FAIL: Missing cycle_id was NOT rejected");
        }
    }

    public function test3_InexistentCycleIdRejected() {
        echo "[TEST 3] Inexistent cycle_id (99999) rejected... ";
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => 99999,
            'niveau' => '6ème',
            'numero' => '12'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
        $controller = new ClasseController();

        $rejected = false;
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (InvalidArgumentException $e) {
            if (ob_get_level() > 0) ob_end_clean();
            if (strpos($e->getMessage(), 'invalide') !== false) {
                $rejected = true;
            }
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "PASS (Inexistent cycle_id rejected & 0 DB write)\n";
        } else {
            throw new Exception("FAIL: Inexistent cycle_id was NOT rejected");
        }
    }

    public function test4_OtherLyceeCycleIdRejected() {
        echo "[TEST 4] Cycle belonging to another Lycée B ({$this->cycleLyceeBId}) rejected... ";
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => $this->cycleLyceeBId, // Belongs to Lycée B!
            'niveau' => '6ème',
            'numero' => '13'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
        $controller = new ClasseController();

        $rejected = false;
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (InvalidArgumentException $e) {
            if (ob_get_level() > 0) ob_end_clean();
            if (strpos($e->getMessage(), 'invalide') !== false) {
                $rejected = true;
            }
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "PASS (Other lycee cycle_id rejected & 0 DB write)\n";
        } else {
            throw new Exception("FAIL: Other lycee cycle_id was NOT rejected");
        }
    }

    public function test5_UnauthorizedCycleIdRejected() {
        echo "[TEST 5] Cycle {$this->cyclePremierId} outside user's authorized scope rejected... ";
        $this->authenticateUser(); // Authorized ONLY for cycleCollegeId

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => $this->cyclePremierId, // Belongs to Lycée A, but user NOT assigned!
            'niveau' => '2nde',
            'numero' => '14'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
        $controller = new ClasseController();

        $rejected = false;
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
            if (strpos($e->getMessage(), 'Accès Interdit') !== false || strpos($e->getMessage(), 'invalide') !== false) {
                $rejected = true;
            }
        }

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "PASS (Unauthorized cycle_id rejected by AcademicFilterService & 0 DB write)\n";
        } else {
            throw new Exception("FAIL: Unauthorized cycle_id was NOT rejected");
        }
    }

    public function test6_CustomNameCollegeCycleWorks() {
        echo "[TEST 6] Cycle named 'Collège' (non-standard name) works 100%... ";
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => $this->cycleCollegeId,
            'niveau' => '5ème',
            'numero' => '15'
        ];

        $controller = new ClasseController();
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $created = $this->db->query("SELECT * FROM classes WHERE lycee_id = {$this->lyceeIdA} AND niveau = '5ème' AND numero = 15")->fetch(PDO::FETCH_ASSOC);

        if ($created && (int)$created['cycle_id'] === $this->cycleCollegeId) {
            echo "PASS (Class successfully created for custom cycle 'Collège' with cycle_id {$this->cycleCollegeId})\n";
        } else {
            throw new Exception("FAIL: Creation for custom named cycle 'Collège' failed");
        }
    }

    public function test7_CustomNamePremierCycleWorks() {
        echo "[TEST 7] Cycle named 'Premier cycle' works 100% when user authorized... ";

        // Assign user also to Premier cycle
        $stmtCyA = $this->db->prepare("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (:p, :c, '2020-01-01', 1)");
        $stmtCyA->execute(['p' => $this->adminUserId, 'c' => $this->cyclePremierId]);
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => $this->cyclePremierId,
            'niveau' => '4ème',
            'numero' => '16'
        ];

        $controller = new ClasseController();
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $created = $this->db->query("SELECT * FROM classes WHERE lycee_id = {$this->lyceeIdA} AND niveau = '4ème' AND numero = 16")->fetch(PDO::FETCH_ASSOC);

        if ($created && (int)$created['cycle_id'] === $this->cyclePremierId) {
            echo "PASS (Class successfully created for custom cycle 'Premier cycle' with cycle_id {$this->cyclePremierId})\n";
        } else {
            throw new Exception("FAIL: Creation for custom named cycle 'Premier cycle' failed");
        }
    }

    public function test8_SubmittedCycleIdNeverOverwritten() {
        echo "[TEST 8] Submitted cycle_id is NEVER overwritten by hardcoded names... ";
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => $this->cyclePremierId,
            'niveau' => '3ème', // Historically 3ème would have defaulted to "CEG"
            'numero' => '17'
        ];

        $controller = new ClasseController();
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $created = $this->db->query("SELECT * FROM classes WHERE lycee_id = {$this->lyceeIdA} AND niveau = '3ème' AND numero = 17")->fetch(PDO::FETCH_ASSOC);

        if ($created && (int)$created['cycle_id'] === $this->cyclePremierId) {
            echo "PASS (Submitted cycle_id {$this->cyclePremierId} preserved, NOT overwritten to CEG)\n";
        } else {
            throw new Exception("FAIL: Submitted cycle_id was overwritten");
        }
    }

    public function test9_NoDbWriteOnRejection() {
        echo "[TEST 9] Zero DB write on invalid / unauthorized requests... ";
        $this->authenticateUser();

        $countBefore = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'lycee_id' => $this->lyceeIdA,
            'cycle_id' => null,
            'niveau' => '6ème',
            'numero' => '99'
        ];

        $controller = new ClasseController();
        try {
            ob_start();
            $controller->store();
            ob_end_clean();
        } catch (Exception $e) {
            if (ob_get_level() > 0) ob_end_clean();
        }

        $countAfter = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();

        if ($countBefore === $countAfter) {
            echo "PASS (0 DB write verified on rejection)\n";
        } else {
            throw new Exception("FAIL: DB write occurred during rejected request");
        }
    }
}

$test = new ClasseControllerCycleIdSecurityTest();
$test->run();
