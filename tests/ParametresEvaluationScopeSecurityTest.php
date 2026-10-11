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
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/controllers/ParametresEvaluationController.php';
require_once __DIR__ . '/../src/controllers/DeblocageController.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';

class ParametresEvaluationScopeSecurityTest {

    private PDO $db;
    private int $lyceeIdA;
    private int $lyceeIdB;
    private int $cycleCegId;
    private int $cycleLyceeId;
    private int $classeLyceeA_Ceg;
    private int $classeLyceeA_Lyc;
    private int $classeLyceeB;
    private int $matiereLyceeA;
    private int $matiereLyceeB;
    private int $adminUserId;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function run() {
        echo "=========================================================\n";
        echo "RUNNING ParametresEvaluationScopeSecurityTest\n";
        echo "=========================================================\n";

        $this->setUpFixtures();

        $this->testStoreClasseOtherLyceeRejected();
        $this->testUpdateClasseOtherLyceeRejected();
        $this->testDeblocageClasseOtherLyceeRejected();

        $this->testStoreClasseUnauthorizedCycleRejected();
        $this->testUpdateClasseUnauthorizedCycleRejected();
        $this->testDeblocageClasseUnauthorizedCycleRejected();

        $this->testStoreMatiereOtherLyceeRejected();

        $this->testStoreInexistentClass404();

        $this->testStoreAuthorizedClassAndMatiereSuccess();

        echo "\nALL 5 SECURITY TEST CASES PASSED SUCCESSFULLY (100%)\n";
    }

    private function setUpFixtures() {
        // Create Role 1 and Seed Permission
        $this->db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (1, 'admin_local')");
        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action) VALUES ('evaluation', 'manage_settings')");
        $permId = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'evaluation' AND action = 'manage_settings'")->fetchColumn();
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, {$permId})");

        // Create 2 Lycees
        $this->db->exec("INSERT INTO param_lycee (nom_lycee, sigle) VALUES ('Lycée Test Scope A', 'LYC_A')");
        $this->lyceeIdA = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO param_lycee (nom_lycee, sigle) VALUES ('Lycée Test Scope B', 'LYC_B')");
        $this->lyceeIdB = (int)$this->db->lastInsertId();

        // Create Cycles for Lycee A
        $stmtC = $this->db->prepare("INSERT INTO cycles (lycee_id, nom_cycle) VALUES (:l, :n)");
        $stmtC->execute(['l' => $this->lyceeIdA, 'n' => 'CEG Scope']);
        $this->cycleCegId = (int)$this->db->lastInsertId();

        $stmtC->execute(['l' => $this->lyceeIdA, 'n' => 'Lycée Scope']);
        $this->cycleLyceeId = (int)$this->db->lastInsertId();

        // Create Classes
        $stmtCl = $this->db->prepare("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES (:l, :cy, :niv, :num)");
        $stmtCl->execute(['l' => $this->lyceeIdA, 'cy' => $this->cycleCegId, 'niv' => '6ème', 'num' => '1']);
        $this->classeLyceeA_Ceg = (int)$this->db->lastInsertId();

        $stmtCl->execute(['l' => $this->lyceeIdA, 'cy' => $this->cycleLyceeId, 'niv' => '2nde', 'num' => '1']);
        $this->classeLyceeA_Lyc = (int)$this->db->lastInsertId();

        $stmtCl->execute(['l' => $this->lyceeIdB, 'cy' => null, 'niv' => '3ème', 'num' => '1']);
        $this->classeLyceeB = (int)$this->db->lastInsertId();

        // Create Matieres
        $stmtM = $this->db->prepare("INSERT INTO matieres (lycee_id, nom_matiere) VALUES (:l, :n)");
        $stmtM->execute(['l' => $this->lyceeIdA, 'n' => 'Maths A']);
        $this->matiereLyceeA = (int)$this->db->lastInsertId();

        $stmtM->execute(['l' => $this->lyceeIdB, 'n' => 'Maths B']);
        $this->matiereLyceeB = (int)$this->db->lastInsertId();

        // Create Admin User for Lycee A
        $stmtU = $this->db->prepare("INSERT INTO utilisateurs (nom, prenom, email, identifiant_public, mot_de_passe, role_id, lycee_id, actif) VALUES ('Admin', 'Scope', 'admin@scope.a', 'admin_scope_a', 'hash', 1, :l, 1)");
        $stmtU->execute(['l' => $this->lyceeIdA]);
        $this->adminUserId = (int)$this->db->lastInsertId();

        // Assign user strictly to CEG cycle
        $stmtCyA = $this->db->prepare("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (:p, :c, '2020-01-01', 1)");
        $stmtCyA->execute(['p' => $this->adminUserId, 'c' => $this->cycleCegId]);
    }

    private function authenticateUser() {
        $_SESSION = [
            'user' => [
                'id_user' => $this->adminUserId,
                'id' => $this->adminUserId,
                'role_id' => 1,
                'nom' => 'Admin',
                'prenom' => 'Scope',
                'lycee_id' => $this->lyceeIdA,
                'role' => 'admin_local',
                'permissions' => [
                    'evaluation' => ['manage_settings' => true]
                ]
            ]
        ];
        AuthorizationScopeService::invalidateUserCache($this->adminUserId);
    }

    public function testStoreClasseOtherLyceeRejected() {
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'type' => 'classe',
            'classe_id' => $this->classeLyceeB,
            'date_ouverture' => '2025-01-01 00:00:00',
            'date_fermeture' => '2025-12-31 23:59:59'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();
        $controller = new ParametresEvaluationController();

        $rejected = false;
        ob_start();
        try {
            $controller->store();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès Interdit') !== false) {
                $rejected = true;
            }
        }
        ob_end_clean();

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "[TEST 1A] ParametresEvaluationController::store() - Classe Other Lycee -> PASS (403 Exception & 0 DB insertion)\n";
        } else {
            throw new Exception("FAIL: Store with class from another lycee was NOT rejected.");
        }
    }

    public function testUpdateClasseOtherLyceeRejected() {
        $this->authenticateUser();

        // Insert a valid existing rule first
        $this->db->exec("INSERT INTO parametres_evaluations (lycee_id, type, date_ouverture_saisie, date_fermeture_saisie) VALUES ({$this->lyceeIdA}, 'global', '2025-01-01', '2025-12-31')");
        $paramId = (int)$this->db->lastInsertId();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id' => $paramId,
            'type' => 'classe',
            'classe_id' => $this->classeLyceeB,
            'date_ouverture' => '2025-01-01 00:00:00',
            'date_fermeture' => '2025-12-31 23:59:59'
        ];

        $controller = new ParametresEvaluationController();
        $rejected = false;
        ob_start();
        try {
            $controller->update($paramId);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès Interdit') !== false) {
                $rejected = true;
            }
        }
        ob_end_clean();

        $updatedParam = ParametresEvaluation::findById($paramId);
        if ($rejected && empty($updatedParam['classe_id'])) {
            echo "[TEST 1B] ParametresEvaluationController::update() - Classe Other Lycee -> PASS (403 Exception & 0 DB mutation)\n";
        } else {
            throw new Exception("FAIL: Update with class from another lycee was NOT rejected.");
        }
    }

    public function testDeblocageClasseOtherLyceeRejected() {
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'type' => 'classe',
            'classe_id' => $this->classeLyceeB,
            'date_debut' => '2025-01-01 00:00:00',
            'date_fin' => '2025-12-31 23:59:59',
            'motif' => 'Test'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM deblocages_notes")->fetchColumn();
        $controller = new DeblocageController();

        $rejected = false;
        ob_start();
        try {
            $controller->store();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès Interdit') !== false) {
                $rejected = true;
            }
        }
        ob_end_clean();

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM deblocages_notes")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "[TEST 1C] DeblocageController::store() - Classe Other Lycee -> PASS (403 Exception & 0 DB insertion)\n";
        } else {
            throw new Exception("FAIL: Deblocage with class from another lycee was NOT rejected.");
        }
    }

    public function testStoreClasseUnauthorizedCycleRejected() {
        $this->authenticateUser(); // User authorized ONLY for CEG cycle, trying Lycée cycle class

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'type' => 'classe',
            'classe_id' => $this->classeLyceeA_Lyc, // Belongs to Lycee cycle!
            'date_ouverture' => '2025-01-01 00:00:00',
            'date_fermeture' => '2025-12-31 23:59:59'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();
        $controller = new ParametresEvaluationController();

        $rejected = false;
        ob_start();
        try {
            $controller->store();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès Interdit') !== false) {
                $rejected = true;
            }
        }
        ob_end_clean();

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "[TEST 2A] ParametresEvaluationController::store() - Unauthorized Cycle -> PASS (403 Exception & 0 DB insertion)\n";
        } else {
            throw new Exception("FAIL: Store with unauthorized cycle class was NOT rejected.");
        }
    }

    public function testUpdateClasseUnauthorizedCycleRejected() {
        $this->authenticateUser();

        $this->db->exec("INSERT INTO parametres_evaluations (lycee_id, type, date_ouverture_saisie, date_fermeture_saisie) VALUES ({$this->lyceeIdA}, 'global', '2025-01-01', '2025-12-31')");
        $paramId = (int)$this->db->lastInsertId();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id' => $paramId,
            'type' => 'classe',
            'classe_id' => $this->classeLyceeA_Lyc,
            'date_ouverture' => '2025-01-01 00:00:00',
            'date_fermeture' => '2025-12-31 23:59:59'
        ];

        $controller = new ParametresEvaluationController();
        $rejected = false;
        ob_start();
        try {
            $controller->update($paramId);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès Interdit') !== false) {
                $rejected = true;
            }
        }
        ob_end_clean();

        $updatedParam = ParametresEvaluation::findById($paramId);
        if ($rejected && empty($updatedParam['classe_id'])) {
            echo "[TEST 2B] ParametresEvaluationController::update() - Unauthorized Cycle -> PASS (403 Exception & 0 DB mutation)\n";
        } else {
            throw new Exception("FAIL: Update with unauthorized cycle class was NOT rejected.");
        }
    }

    public function testDeblocageClasseUnauthorizedCycleRejected() {
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'type' => 'classe',
            'classe_id' => $this->classeLyceeA_Lyc,
            'date_debut' => '2025-01-01 00:00:00',
            'date_fin' => '2025-12-31 23:59:59',
            'motif' => 'Test'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM deblocages_notes")->fetchColumn();
        $controller = new DeblocageController();

        $rejected = false;
        ob_start();
        try {
            $controller->store();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès Interdit') !== false) {
                $rejected = true;
            }
        }
        ob_end_clean();

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM deblocages_notes")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "[TEST 2C] DeblocageController::store() - Unauthorized Cycle -> PASS (403 Exception & 0 DB insertion)\n";
        } else {
            throw new Exception("FAIL: Deblocage with unauthorized cycle class was NOT rejected.");
        }
    }

    public function testStoreMatiereOtherLyceeRejected() {
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'type' => 'matiere',
            'matiere_id' => $this->matiereLyceeB,
            'date_ouverture' => '2025-01-01 00:00:00',
            'date_fermeture' => '2025-12-31 23:59:59'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();
        $controller = new ParametresEvaluationController();

        $rejected = false;
        ob_start();
        try {
            $controller->store();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Accès Interdit') !== false) {
                $rejected = true;
            }
        }
        ob_end_clean();

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();

        if ($rejected && $finalCount === $initialCount) {
            echo "[TEST 4] ParametresEvaluationController::store() - Matiere Other Lycee -> PASS (403 Exception & 0 DB insertion)\n";
        } else {
            throw new Exception("FAIL: Store with subject from another lycee was NOT rejected.");
        }
    }

    public function testStoreInexistentClass404() {
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'type' => 'classe',
            'classe_id' => 999999, // Inexistent ID
            'date_ouverture' => '2025-01-01 00:00:00',
            'date_fermeture' => '2025-12-31 23:59:59'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();
        $controller = new ParametresEvaluationController();

        ob_start();
        $controller->store();
        $out = ob_get_clean();

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();
        $code = http_response_code();

        if ($code === 404 && $finalCount === $initialCount) {
            echo "[TEST 5] ParametresEvaluationController::store() - Inexistent Class ID -> PASS (404 Not Found & 0 DB insertion)\n";
        } else {
            throw new Exception("FAIL: Store with inexistent class ID did NOT return 404. Got code $code");
        }
    }

    public function testStoreAuthorizedClassAndMatiereSuccess() {
        $this->authenticateUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'type' => 'classe_matiere',
            'classe_id' => $this->classeLyceeA_Ceg, // Authorized CEG class
            'matiere_id' => $this->matiereLyceeA,   // Authorized Lycee A subject
            'date_ouverture' => '2025-01-01 00:00:00',
            'date_fermeture' => '2025-12-31 23:59:59'
        ];

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();
        $controller = new ParametresEvaluationController();

        ob_start();
        try {
            $controller->store();
        } catch (Exception $e) {
            // Header redirect in CLI triggers exit or completes
        }
        ob_end_clean();

        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM parametres_evaluations")->fetchColumn();

        if ($finalCount === $initialCount + 1) {
            echo "[TEST 3] ParametresEvaluationController::store() - Authorized Class & Subject -> PASS (Successfully created rule for authorized class and subject)\n";
        } else {
            throw new Exception("FAIL: Store for authorized class and subject was NOT saved.");
        }
    }
}

$test = new ParametresEvaluationScopeSecurityTest();
$test->run();
