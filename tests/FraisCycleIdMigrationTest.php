<?php
// tests/FraisCycleIdMigrationTest.php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Frais.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/commands/FraisDiagnosticCommand.php';

class FraisCycleIdMigrationTest {

    private static $db;

    public static function setUpBeforeClass() {
        self::$db = Database::getInstance();
        self::$db->exec("DELETE FROM frais;");
        self::$db->exec("DELETE FROM classes;");
        self::$db->exec("DELETE FROM cycles;");

        // Seed 2 Lycées
        self::$db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test A'), (2, 'Lycée Test B');");

        // Seed Cycles for Lycée 1
        self::$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (10, 'Collège Privé', 1), (20, 'Lycée Général', 1);");
        // Seed Cycle for Lycée 2
        self::$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (30, 'Collège Privé', 2);");

        // Seed Year
        self::$db->exec("INSERT OR IGNORE INTO annees_academiques (id, libelle, est_active) VALUES (1, '2024-2025', 1);");

        // Seed Super Admin User & Permissions
        self::$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (1, 'super_admin_createur');");
        self::$db->exec("INSERT OR IGNORE INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif) VALUES (1, 'Admin', 'Super', 'admin@test.com', 1, 1, 1);");
        self::$db->exec("INSERT OR IGNORE INTO permissions (id_permission, resource, action) VALUES (101, 'lycee', 'view_all_lycees'), (102, 'cycle', 'view_all_cycles');");
        self::$db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 101), (1, 102);");
    }

    public static function runAllTests() {
        echo "=== DEBUT DES TESTS DE MIGRATION FRAIS.CYCLE_ID ===\n\n";

        self::setUpBeforeClass();

        $passed = 0;
        $failed = 0;

        $tests = [
            'testMigration31Idempotency',
            'testDoubleWriteWithCycleId',
            'testDoubleWriteWithLegacyCycleString',
            'testPriorityDoubleReadByCycleId',
            'testPriorityDoubleReadByLegacyStringFallback',
            'testCycleRenameResilience',
            'testMissingFeeRuleWarningAndFalse',
            'testExplicitZeroFeeVsMissingFeeRule',
            'testPlageNiveauMatching',
            'testPlageNiveauAndSerieMatching',
            'testDuplicateRulePrevention',
            'testMultiTenantFeeIsolation',
            'testDiagnosticCommandExecution',
            'testInvalidInputHandling',
            'testNullCycleIdGracefulHandling',
            'testCascadeIntegration'
        ];

        foreach ($tests as $method) {
            try {
                self::$method();
                echo "[PASS] $method\n";
                $passed++;
            } catch (Throwable $e) {
                echo "[FAIL] $method: " . $e->getMessage() . "\n";
                echo $e->getTraceAsString() . "\n";
                $failed++;
            }
        }

        echo "\n=== FIN DES TESTS: $passed REUSSIS, $failed ECHECS ===\n";
        if ($failed > 0) {
            exit(1);
        }
    }

    private static function assert($condition, $msg) {
        if (!$condition) {
            throw new Exception("Assertion failed: $msg");
        }
    }

    public static function testMigration31Idempotency() {
        require_once __DIR__ . '/../db/migrations/20240115_31_migrate_frais_cycle_id.php';
        migrate_31(self::$db);
        migrate_31(self::$db); // Second call must be idempotent
        self::assert(true, "Migration 31 execution is idempotent.");
    }

    public static function testDoubleWriteWithCycleId() {
        self::$db->exec("DELETE FROM frais;");

        $data = [
            'lycee_id' => 1,
            'annee_academique_id' => 1,
            'type_config' => 'cycle',
            'cycle_id' => 10,
            'frais_inscription' => 15000,
            'frais_mensuel' => 8000
        ];

        $id = Frais::save($data);
        self::assert($id !== false, "Frais::save should return new ID.");

        $stmt = self::$db->prepare("SELECT * FROM frais WHERE id_frais = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assert((int)$row['cycle_id'] === 10, "cycle_id must be stored as 10.");
        self::assert($row['cycle'] === 'Collège Privé', "cycle string must be automatically populated from Cycle model.");
    }

    public static function testDoubleWriteWithLegacyCycleString() {
        self::$db->exec("DELETE FROM frais;");

        $data = [
            'lycee_id' => 1,
            'annee_academique_id' => 1,
            'type_config' => 'cycle',
            'cycle' => 'Lycée Général',
            'frais_inscription' => 20000,
            'frais_mensuel' => 12000
        ];

        $id = Frais::save($data);
        self::assert($id !== false, "Frais::save should save legacy string input.");

        $stmt = self::$db->prepare("SELECT * FROM frais WHERE id_frais = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assert((int)$row['cycle_id'] === 20, "cycle_id must be automatically resolved to 20.");
        self::assert($row['cycle'] === 'Lycée Général', "cycle string must match input.");
    }

    public static function testPriorityDoubleReadByCycleId() {
        self::$db->exec("DELETE FROM frais;");

        // Insert rule with cycle_id = 10, cycle string = 'Ancien Nom Irrelevant'
        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, cycle_id, cycle, frais_inscription, frais_mensuel)
                         VALUES (1, 1, 10, 'Ancien Nom Irrelevant', 15000, 8000);");

        $classe = [
            'id_classe' => 101,
            'lycee_id' => 1,
            'cycle_id' => 10,
            'niveau' => '6ème',
            'serie' => null
        ];

        $fee = Frais::findForClasse($classe, 1);
        self::assert($fee !== false, "Fee rule should be found.");
        self::assert((int)$fee['cycle_id'] === 10, "Matched by cycle_id even when cycle string differs.");
    }

    public static function testPriorityDoubleReadByLegacyStringFallback() {
        self::$db->exec("DELETE FROM frais;");

        // Legacy record without cycle_id (cycle_id = NULL)
        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, cycle_id, cycle, frais_inscription, frais_mensuel)
                         VALUES (1, 1, NULL, 'Collège Privé', 15000, 8000);");

        $classe = [
            'id_classe' => 102,
            'lycee_id' => 1,
            'cycle_id' => 10, // Maps to 'Collège Privé' via Cycle model
            'niveau' => '5ème',
            'serie' => null
        ];

        $fee = Frais::findForClasse($classe, 1);
        self::assert($fee !== false, "Fee rule should be found via legacy string fallback.");
        self::assert($fee['cycle'] === 'Collège Privé', "Matched by cycle string fallback.");
    }

    public static function testCycleRenameResilience() {
        self::$db->exec("DELETE FROM frais;");

        // Fee rule linked with cycle_id = 10
        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, cycle_id, cycle, frais_inscription, frais_mensuel)
                         VALUES (1, 1, 10, 'Nom Initial', 15000, 8000);");

        // Rename cycle in BDD
        self::$db->exec("UPDATE cycles SET nom_cycle = 'Nouveau Nom Collège' WHERE id_cycle = 10;");

        $classe = [
            'id_classe' => 103,
            'lycee_id' => 1,
            'cycle_id' => 10,
            'niveau' => '4ème',
            'serie' => null
        ];

        $fee = Frais::findForClasse($classe, 1);
        self::assert($fee !== false, "Fee rule lookup must survive cycle renaming.");

        // Revert cycle name
        self::$db->exec("UPDATE cycles SET nom_cycle = 'Collège Privé' WHERE id_cycle = 10;");
    }

    public static function testMissingFeeRuleWarningAndFalse() {
        self::$db->exec("DELETE FROM frais;");

        $classe = [
            'id_classe' => 999,
            'nom_classe' => '3ème A',
            'lycee_id' => 1,
            'cycle_id' => 10,
            'niveau' => '3ème',
            'serie' => null
        ];

        $fee = Frais::findForClasse($classe, 1);
        self::assert($fee === false, "Missing fee rule must return false.");
    }

    public static function testExplicitZeroFeeVsMissingFeeRule() {
        self::$db->exec("DELETE FROM frais;");

        // 1. Missing fee rule: findForClasse returns false
        $classeMissing = [
            'id_classe' => 888,
            'lycee_id' => 1,
            'cycle_id' => 10,
            'niveau' => '6ème',
            'serie' => null
        ];
        $feeMissing = Frais::findForClasse($classeMissing, 1);
        self::assert($feeMissing === false, "Unconfigured class must return false.");

        // 2. Explicit 0 FCFA fee rule: findForClasse returns array with 0.00
        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, cycle_id, cycle, frais_inscription, frais_mensuel)
                         VALUES (1, 1, 10, 'Collège Privé', 0.00, 0.00);");

        $feeZero = Frais::findForClasse($classeMissing, 1);
        self::assert(is_array($feeZero), "Explicit zero fee rule must return a valid array.");
        self::assert((float)$feeZero['frais_inscription'] === 0.00, "frais_inscription must be 0.00.");
        self::assert((float)$feeZero['frais_mensuel'] === 0.00, "frais_mensuel must be 0.00.");

        // 3. Positive fee rule: findForClasse returns array with positive values
        self::$db->exec("DELETE FROM frais;");
        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, cycle_id, cycle, frais_inscription, frais_mensuel)
                         VALUES (1, 1, 10, 'Collège Privé', 15000.00, 8000.00);");

        $feePositive = Frais::findForClasse($classeMissing, 1);
        self::assert(is_array($feePositive), "Positive fee rule must return a valid array.");
        self::assert((float)$feePositive['frais_inscription'] === 15000.00, "frais_inscription must be 15000.00.");
        self::assert((float)$feePositive['frais_mensuel'] === 8000.00, "frais_mensuel must be 8000.00.");
    }

    public static function testPlageNiveauMatching() {
        self::$db->exec("DELETE FROM frais;");

        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, niveau_debut, niveau_fin, frais_inscription, frais_mensuel)
                         VALUES (1, 1, '6ème', '3ème', 18000, 9000);");

        $classe = [
            'id_classe' => 104,
            'lycee_id' => 1,
            'cycle_id' => 10,
            'niveau' => '4ème',
            'serie' => null
        ];

        $fee = Frais::findForClasse($classe, 1);
        self::assert($fee !== false, "Fee rule should match level range.");
        self::assert((int)$fee['frais_mensuel'] === 9000, "Matched correct fee amount.");
    }

    public static function testPlageNiveauAndSerieMatching() {
        self::$db->exec("DELETE FROM frais;");

        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, niveau_debut, niveau_fin, serie, frais_inscription, frais_mensuel)
                         VALUES (1, 1, 'Seconde', 'Terminale', 'C', 25000, 15000);");

        $classe = [
            'id_classe' => 105,
            'lycee_id' => 1,
            'cycle_id' => 20,
            'niveau' => 'Première',
            'serie' => 'C'
        ];

        $fee = Frais::findForClasse($classe, 1);
        self::assert($fee !== false, "Fee rule should match level range and serie.");
        self::assert((int)$fee['frais_mensuel'] === 15000, "Matched correct fee amount for Serie C.");
    }

    public static function testDuplicateRulePrevention() {
        self::$db->exec("DELETE FROM frais;");

        $data = [
            'lycee_id' => 1,
            'annee_academique_id' => 1,
            'type_config' => 'cycle',
            'cycle_id' => 10,
            'frais_inscription' => 15000,
            'frais_mensuel' => 8000
        ];

        Frais::save($data);

        $caught = false;
        try {
            Frais::save($data);
        } catch (InvalidArgumentException $e) {
            $caught = true;
        }

        self::assert($caught, "Duplicate fee rule creation must throw InvalidArgumentException.");
    }

    public static function testMultiTenantFeeIsolation() {
        self::$db->exec("DELETE FROM frais;");

        // Fee for Lycée 1
        self::$db->exec("INSERT INTO frais (lycee_id, annee_academique_id, cycle_id, cycle, frais_inscription, frais_mensuel)
                         VALUES (1, 1, 10, 'Collège Privé', 15000, 8000);");

        // Class in Lycée 2 with same cycle name
        $classeL2 = [
            'id_classe' => 201,
            'lycee_id' => 2,
            'cycle_id' => 30, // Cycle for Lycée 2
            'niveau' => '6ème',
            'serie' => null
        ];

        $fee = Frais::findForClasse($classeL2, 1);
        self::assert($fee === false, "Cross-tenant fee rule must NOT be returned for Lycée 2.");
    }

    public static function testDiagnosticCommandExecution() {
        ob_start();
        FraisDiagnosticCommand::run();
        $output = ob_get_clean();

        self::assert(strpos($output, "DIAGNOSTIC DES GRILLES TARIFAIRES") !== false, "Diagnostic command must run cleanly.");
    }

    public static function testInvalidInputHandling() {
        $caught = false;
        try {
            Frais::save([
                'lycee_id' => 1,
                'annee_academique_id' => 1,
                'type_config' => 'invalid_type',
                'frais_inscription' => 1000,
                'frais_mensuel' => 1000
            ]);
        } catch (InvalidArgumentException $e) {
            $caught = true;
        }
        self::assert($caught, "Invalid type_config must be rejected.");
    }

    public static function testNullCycleIdGracefulHandling() {
        $fee = Frais::findForClasse(null, 1);
        self::assert($fee === false, "Null class must return false without exception.");
    }

    public static function testCascadeIntegration() {
        $cycles = Cycle::findByLycee(1);
        self::assert(!empty($cycles), "Cycle::findByLycee must return cycles for Lycée 1.");
        $cycleIds = array_column($cycles, 'id_cycle');
        self::assert(in_array(10, $cycleIds), "Cycle ID 10 must be returned by Cycle::findByLycee.");

        $fraisList = Frais::findByLyceeAndYear(1, 1);
        self::assert(is_array($fraisList), "Frais::findByLyceeAndYear must return an array.");
    }
}

FraisCycleIdMigrationTest::runAllTests();
?>
