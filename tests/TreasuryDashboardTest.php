<?php

if (!class_exists('PHPUnit\Framework\TestCase')) {
    abstract class CustomTestCase {
        protected function assertEquals($expected, $actual, $message = '') {
            if ($expected != $actual) {
                echo " [FAIL] $message (Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true) . ")\n";
                throw new Exception("Assertion failed: $message");
            } else {
                echo " [PASS] $message\n";
            }
        }
        protected function assertNotEquals($expected, $actual, $message = '') {
            if ($expected == $actual) {
                echo " [FAIL] $message\n";
                throw new Exception("Assertion failed: $message");
            } else {
                echo " [PASS] $message\n";
            }
        }
        protected function assertNotEmpty($actual, $message = '') {
            if (empty($actual)) {
                echo " [FAIL] $message\n";
                throw new Exception("Assertion failed: $message");
            } else {
                echo " [PASS] $message\n";
            }
        }
        protected function assertNotNull($actual, $message = '') {
            if ($actual === null) {
                echo " [FAIL] $message\n";
                throw new Exception("Assertion failed: $message");
            } else {
                echo " [PASS] $message\n";
            }
        }
        protected function assertStringContainsString($needle, $haystack, $message = '') {
            if (strpos($haystack, $needle) === false) {
                echo " [FAIL] $message\n";
                throw new Exception("Assertion failed: $message");
            } else {
                echo " [PASS] $message\n";
            }
        }
    }
    class_alias('CustomTestCase', 'PHPUnit\Framework\TestCase');
}

use PHPUnit\Framework\TestCase;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../src/models/TreasuryService.php';
require_once __DIR__ . '/../src/services/TreasuryDashboardService.php';

class TreasuryDashboardTest extends TestCase {

    private static $db;
    private static $lyceeId1;
    private static $lyceeId2;
    private static $userId1;
    private static $userId2;
    private static $cashierUserId;
    private static $exerciceId1;
    private static $exerciceId2;
    private static $caisseId1;
    private static $caisseId2;
    private static $caisseLycee2;
    private static $coffreId1;

    public static function setUpBeforeClass(): void {
        $dbFile = '/tmp/test_treasury_dashboard.sqlite';
        if (file_exists($dbFile)) {
            unlink($dbFile);
        }

        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Database::setInstance($pdo);
        self::$db = $pdo;

        // Create Schemas
        self::$db->exec("CREATE TABLE roles (id_role INTEGER PRIMARY KEY AUTOINCREMENT, nom_role VARCHAR(100));");
        self::$db->exec("CREATE TABLE permissions (id_permission INTEGER PRIMARY KEY AUTOINCREMENT, resource VARCHAR(100), action VARCHAR(100), description TEXT);");
        self::$db->exec("CREATE TABLE role_permissions (role_id INTEGER, permission_id INTEGER);");
        self::$db->exec("CREATE TABLE param_lycee (id INTEGER PRIMARY KEY AUTOINCREMENT, nom_lycee VARCHAR(100), code_lycee VARCHAR(50));");
        self::$db->exec("CREATE TABLE utilisateurs (id_user INTEGER PRIMARY KEY AUTOINCREMENT, nom VARCHAR(100), prenom VARCHAR(100), email VARCHAR(100), mot_de_passe VARCHAR(100), lycee_id INTEGER, id_role INTEGER);");
        self::$db->exec("CREATE TABLE exercices_financiers (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, code VARCHAR(50), libelle VARCHAR(100), date_debut DATE, date_fin DATE, statut VARCHAR(20));");
        self::$db->exec("CREATE TABLE comptes_financiers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER,
            nom_compte VARCHAR(150),
            type_compte VARCHAR(50),
            solde_courant DECIMAL(15,2) DEFAULT 0,
            devise VARCHAR(10) DEFAULT 'FCFA',
            responsable_id INTEGER,
            statut VARCHAR(20) DEFAULT 'actif',
            est_coffre TINYINT DEFAULT 0,
            compte_comptable_id INTEGER,
            compte_comptable_numero VARCHAR(50)
        );");
        self::$db->exec("CREATE TABLE sessions_caisse (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER,
            user_id INTEGER,
            compte_id INTEGER,
            date_ouverture DATETIME,
            date_fermeture DATETIME,
            solde_ouverture DECIMAL(15,2) DEFAULT 0,
            solde_theorique DECIMAL(15,2) DEFAULT 0,
            solde_reel DECIMAL(15,2),
            ecart DECIMAL(15,2) DEFAULT 0,
            justificatif_ecart TEXT,
            statut VARCHAR(20) DEFAULT 'ouverte',
            valide_par INTEGER,
            valide_le DATETIME,
            montant_remis DECIMAL(15,2),
            fonds_caisse_conserve DECIMAL(15,2)
        );");
        self::$db->exec("CREATE TABLE mouvements_tresorerie (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER,
            compte_id INTEGER,
            session_caisse_id INTEGER,
            exercice_financier_id INTEGER,
            transfert_id INTEGER,
            type_mouvement VARCHAR(10),
            montant DECIMAL(15,2),
            mode_paiement VARCHAR(50),
            reference_transaction VARCHAR(150),
            source_type VARCHAR(100),
            source_id INTEGER,
            evenement_type VARCHAR(50),
            motif VARCHAR(255),
            date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
            user_id INTEGER,
            is_aggregate_data TINYINT DEFAULT 0,
            date_reconstruite TINYINT DEFAULT 0,
            is_historical_migration TINYINT DEFAULT 0,
            mode_paiement_reconstruit TINYINT DEFAULT 0
        );");

        // Seed Roles & Permissions
        self::$db->exec("INSERT INTO roles (id_role, nom_role) VALUES (9, 'Chef Comptable'), (7, 'Comptable/Caissier');");
        self::$db->exec("INSERT INTO permissions (id_permission, resource, action) VALUES
            (1, 'sessions_caisse', 'view'),
            (2, 'sessions_caisse', 'create'),
            (3, 'sessions_caisse', 'edit'),
            (4, 'sessions_caisse', 'validate'),
            (5, 'comptes_financiers', 'view'),
            (6, 'mouvements_tresorerie', 'view'),
            (7, 'paiement', 'view'),
            (8, 'depense', 'pay');
        ");
        // Role 9 (Chef Comptable) gets all permissions
        self::$db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (9,1),(9,2),(9,3),(9,4),(9,5),(9,6),(9,7),(9,8);");
        // Role 7 (Caissier) gets view/create/edit sessions and paiement view
        self::$db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (7,1),(7,2),(7,3),(7,7);");

        // 1. Create 2 Lycées
        $stmtL = self::$db->prepare("INSERT INTO param_lycee (nom_lycee, code_lycee) VALUES ('Lycée Test Treasury A', 'LTTA')");
        $stmtL->execute();
        self::$lyceeId1 = (int)self::$db->lastInsertId();

        $stmtL2 = self::$db->prepare("INSERT INTO param_lycee (nom_lycee, code_lycee) VALUES ('Lycée Test Treasury B', 'LTTB')");
        $stmtL2->execute();
        self::$lyceeId2 = (int)self::$db->lastInsertId();

        // 2. Create Users
        $stmtU1 = self::$db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, lycee_id, id_role) VALUES ('Admin', 'Comptable', 'admin.treasury@test.com', 'hash', :l_id, 9)");
        $stmtU1->execute(['l_id' => self::$lyceeId1]);
        self::$userId1 = (int)self::$db->lastInsertId();

        $stmtU2 = self::$db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, lycee_id, id_role) VALUES ('User', 'LyceeB', 'user.b@test.com', 'hash', :l_id, 9)");
        $stmtU2->execute(['l_id' => self::$lyceeId2]);
        self::$userId2 = (int)self::$db->lastInsertId();

        $stmtU3 = self::$db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, lycee_id, id_role) VALUES ('Caissier', 'Jean', 'caissier.jean@test.com', 'hash', :l_id, 7)");
        $stmtU3->execute(['l_id' => self::$lyceeId1]);
        self::$cashierUserId = (int)self::$db->lastInsertId();

        // Set Auth session
        $_SESSION['user'] = [
            'id' => self::$userId1,
            'id_user' => self::$userId1,
            'role_id' => 9,
            'lycee_id' => self::$lyceeId1
        ];

        // 3. Create Active Financial Exercises
        $stmtEx1 = self::$db->prepare("INSERT INTO exercices_financiers (lycee_id, code, libelle, date_debut, date_fin, statut) VALUES (:l_id, 'EX2024A', 'Exercice 2024 A', '2024-01-01', '2024-12-31', 'ouvert')");
        $stmtEx1->execute(['l_id' => self::$lyceeId1]);
        self::$exerciceId1 = (int)self::$db->lastInsertId();

        $stmtEx2 = self::$db->prepare("INSERT INTO exercices_financiers (lycee_id, code, libelle, date_debut, date_fin, statut) VALUES (:l_id, 'EX2024B', 'Exercice 2024 B', '2024-01-01', '2024-12-31', 'ouvert')");
        $stmtEx2->execute(['l_id' => self::$lyceeId2]);
        self::$exerciceId2 = (int)self::$db->lastInsertId();

        // 4. Create Accounts
        // Caisse 1 (Lycée 1)
        $stmtC1 = self::$db->prepare("INSERT INTO comptes_financiers (lycee_id, nom_compte, type_compte, solde_courant, responsable_id, statut, est_coffre) VALUES (:l_id, 'Caisse Principale A', 'caisse', 150000.00, :resp, 'actif', 0)");
        $stmtC1->execute(['l_id' => self::$lyceeId1, 'resp' => self::$userId1]);
        self::$caisseId1 = (int)self::$db->lastInsertId();

        // Caisse 2 (Lycée 1) - No manager assigned
        $stmtC2 = self::$db->prepare("INSERT INTO comptes_financiers (lycee_id, nom_compte, type_compte, solde_courant, responsable_id, statut, est_coffre) VALUES (:l_id, 'Caisse Annexe A', 'caisse', 5000.00, NULL, 'actif', 0)");
        $stmtC2->execute(['l_id' => self::$lyceeId1]);
        self::$caisseId2 = (int)self::$db->lastInsertId();

        // Coffre Principal (Lycée 1)
        $stmtCoffre = self::$db->prepare("INSERT INTO comptes_financiers (lycee_id, nom_compte, type_compte, solde_courant, responsable_id, statut, est_coffre) VALUES (:l_id, 'Coffre Principal A', 'caisse', 500000.00, :resp, 'actif', 1)");
        $stmtCoffre->execute(['l_id' => self::$lyceeId1, 'resp' => self::$userId1]);
        self::$coffreId1 = (int)self::$db->lastInsertId();

        // Caisse (Lycée 2)
        $stmtCL2 = self::$db->prepare("INSERT INTO comptes_financiers (lycee_id, nom_compte, type_compte, solde_courant, responsable_id, statut, est_coffre) VALUES (:l_id, 'Caisse Lycée B', 'caisse', 999000.00, :resp, 'actif', 0)");
        $stmtCL2->execute(['l_id' => self::$lyceeId2, 'resp' => self::$userId2]);
        self::$caisseLycee2 = (int)self::$db->lastInsertId();
    }

    public function testSoldeCourantCaissesKpi() {
        $_SESSION['user'] = [
            'id' => self::$userId1,
            'id_user' => self::$userId1,
            'role_id' => 9,
            'lycee_id' => self::$lyceeId1
        ];

        $data = TreasuryDashboardService::getDashboardData(self::$lyceeId1, self::$userId1);

        $this->assertEquals(155000.00, $data['kpis']['solde_caisses'], "Solde caisses KPI must aggregate active non-vault cash accounts of Lycée 1.");
        $this->assertEquals(500000.00, $data['kpis']['solde_coffre'], "Solde coffre KPI must reflect vault current balance.");
    }

    public function testSessionStateClassificationAndKpis() {
        $_SESSION['user'] = [
            'id' => self::$userId1,
            'id_user' => self::$userId1,
            'role_id' => 9,
            'lycee_id' => self::$lyceeId1
        ];

        $nowStr = date('Y-m-d H:i:s');
        // Create 3 sessions for Lycée 1
        // Session 1: 'ouverte' on Caisse 1
        $stmtS1 = self::$db->prepare("
            INSERT INTO sessions_caisse (lycee_id, user_id, compte_id, date_ouverture, solde_ouverture, solde_theorique, statut)
            VALUES (:l_id, :u_id, :c_id, :d_open, 10000.00, 10000.00, 'ouverte')
        ");
        $stmtS1->execute(['l_id' => self::$lyceeId1, 'u_id' => self::$cashierUserId, 'c_id' => self::$caisseId1, 'd_open' => $nowStr]);
        $sessOpenId = (int)self::$db->lastInsertId();

        // Session 2: 'fermee_a_valider' on Caisse 2
        $stmtS2 = self::$db->prepare("
            INSERT INTO sessions_caisse (lycee_id, user_id, compte_id, date_ouverture, date_fermeture, solde_ouverture, solde_theorique, solde_reel, ecart, statut)
            VALUES (:l_id, :u_id, :c_id, :d_open, :d_open, 0.00, 50000.00, 48000.00, -2000.00, 'fermee_a_valider')
        ");
        $stmtS2->execute(['l_id' => self::$lyceeId1, 'u_id' => self::$userId1, 'c_id' => self::$caisseId2, 'd_open' => $nowStr]);
        $sessPendingId = (int)self::$db->lastInsertId();

        // Session 3: 'fermee_validee' on Caisse 1
        $stmtS3 = self::$db->prepare("
            INSERT INTO sessions_caisse (lycee_id, user_id, compte_id, date_ouverture, date_fermeture, solde_ouverture, solde_theorique, solde_reel, ecart, montant_remis, statut, valide_par, valide_le)
            VALUES (:l_id, :u_id, :c_id, :d_open, :d_open, 0.00, 20000.00, 20000.00, 0.00, 20000.00, 'fermee_validee', :v_par, :d_open)
        ");
        $stmtS3->execute(['l_id' => self::$lyceeId1, 'u_id' => self::$cashierUserId, 'c_id' => self::$caisseId1, 'v_par' => self::$userId1, 'd_open' => $nowStr]);
        $sessValidatedId = (int)self::$db->lastInsertId();

        $data = TreasuryDashboardService::getDashboardData(self::$lyceeId1, self::$userId1);

        // Verification of Session Classification
        $this->assertEquals(1, $data['kpis']['sessions_ouvertes_count'], "Only statut='ouverte' must be counted in open sessions KPI.");
        $this->assertEquals(1, $data['kpis']['sessions_a_valider_count'], "Only statut='fermee_a_valider' must be counted in pending sessions KPI.");
        $this->assertEquals(2000.00, $data['kpis']['sessions_a_valider_ecart_total'], "Pending ecart total must be absolute sum (2000 FCFA).");

        $this->assertEquals(1, $data['sessions']['ouvertes']['count']);
        $this->assertEquals(1, $data['sessions']['a_valider']['count']);
        $this->assertEquals(1, $data['sessions']['validees']['count']);

        // Clean up test sessions
        self::$db->exec("DELETE FROM sessions_caisse WHERE id IN ($sessOpenId, $sessPendingId, $sessValidatedId)");
    }

    public function testMovementTypeInflowsAndOutflowsClassification() {
        $_SESSION['user'] = [
            'id' => self::$userId1,
            'id_user' => self::$userId1,
            'role_id' => 9,
            'lycee_id' => self::$lyceeId1
        ];

        $today = date('Y-m-d H:i:s');

        // Insert distinct movements in mouvements_tresorerie
        // 1. encaissement (50 000 FCFA) -> Inflow +50000
        $stmtM1 = self::$db->prepare("
            INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, user_id, date_mouvement)
            VALUES (:l_id, :c_id, :ex_id, 'entree', 50000.00, 'Espèces', 'inscriptions', 1, 'encaissement', 'Paiement Inscription', :u_id, :d_mvt)
        ");
        $stmtM1->execute(['l_id' => self::$lyceeId1, 'c_id' => self::$caisseId1, 'ex_id' => self::$exerciceId1, 'u_id' => self::$userId1, 'd_mvt' => $today]);
        $mvt1 = self::$db->lastInsertId();

        // 2. annulation (5 000 FCFA) -> Inflow net reduction -5000
        $stmtM2 = self::$db->prepare("
            INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, user_id, date_mouvement)
            VALUES (:l_id, :c_id, :ex_id, 'sortie', 5000.00, 'Espèces', 'inscriptions', 1, 'annulation', 'Annulation reçu', :u_id, :d_mvt)
        ");
        $stmtM2->execute(['l_id' => self::$lyceeId1, 'c_id' => self::$caisseId1, 'ex_id' => self::$exerciceId1, 'u_id' => self::$userId1, 'd_mvt' => $today]);
        $mvt2 = self::$db->lastInsertId();

        // 3. reglement_fournisseur (12 000 FCFA) -> Outflow +12000
        $stmtM3 = self::$db->prepare("
            INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, user_id, date_mouvement)
            VALUES (:l_id, :c_id, :ex_id, 'sortie', 12000.00, 'Espèces', 'depenses', 10, 'reglement_fournisseur', 'Paiement Fournisseur', :u_id, :d_mvt)
        ");
        $stmtM3->execute(['l_id' => self::$lyceeId1, 'c_id' => self::$caisseId1, 'ex_id' => self::$exerciceId1, 'u_id' => self::$userId1, 'd_mvt' => $today]);
        $mvt3 = self::$db->lastInsertId();

        // 4. correction (3 000 FCFA) -> MUST BE EXCLUDED from operational inflows/outflows
        $stmtM4 = self::$db->prepare("
            INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, user_id, date_mouvement)
            VALUES (:l_id, :c_id, :ex_id, 'entree', 3000.00, 'Espèces', 'regularisations_ecarts', 1, 'correction', 'Régularisation écart', :u_id, :d_mvt)
        ");
        $stmtM4->execute(['l_id' => self::$lyceeId1, 'c_id' => self::$caisseId1, 'ex_id' => self::$exerciceId1, 'u_id' => self::$userId1, 'd_mvt' => $today]);
        $mvt4 = self::$db->lastInsertId();

        // 5. remise_coffre_entree (25 000 FCFA) -> Vault Transfer KPI, EXCLUDED from sales inflows
        $stmtM5 = self::$db->prepare("
            INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, user_id, date_mouvement)
            VALUES (:l_id, :c_id, :ex_id, 'entree', 25000.00, 'Espèces', 'sessions_caisse', 100, 'remise_coffre_entree', 'Réception Coffre', :u_id, :d_mvt)
        ");
        $stmtM5->execute(['l_id' => self::$lyceeId1, 'c_id' => self::$coffreId1, 'ex_id' => self::$exerciceId1, 'u_id' => self::$userId1, 'd_mvt' => $today]);
        $mvt5 = self::$db->lastInsertId();

        $data = TreasuryDashboardService::getDashboardData(self::$lyceeId1, self::$userId1, ['period' => 'today']);

        // Expected Net Inflows: 50 000 (encaissement) - 5 000 (annulation) = 45 000 FCFA
        $this->assertEquals(45000.00, $data['kpis']['encaissement_periode'], "Net inflows must equal encaissements minus annulations/refunds.");

        // Expected Outflows: 12 000 FCFA (reglement_fournisseur)
        $this->assertEquals(12000.00, $data['kpis']['decaissement_periode'], "Outflows must equal reglement_fournisseur.");

        // Expected Remis Coffre: 25 000 FCFA
        $this->assertEquals(25000.00, $data['kpis']['remis_coffre_periode'], "Remis coffre must equal remise_coffre_entree.");

        // Clean up
        self::$db->exec("DELETE FROM mouvements_tresorerie WHERE id IN ($mvt1, $mvt2, $mvt3, $mvt4, $mvt5)");
    }

    public function testMultiTenantIsolation() {
        $nowStr = date('Y-m-d H:i:s');
        // Create movement for Lycée 2
        $stmtM = self::$db->prepare("
            INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, user_id, date_mouvement)
            VALUES (:l_id, :c_id, :ex_id, 'entree', 99000.00, 'Espèces', 'inscriptions', 99, 'encaissement', 'Recette Lycée B', :u_id, :d_mvt)
        ");
        $stmtM->execute(['l_id' => self::$lyceeId2, 'c_id' => self::$caisseLycee2, 'ex_id' => self::$exerciceId2, 'u_id' => self::$userId2, 'd_mvt' => $nowStr]);
        $mvtLyceeB = self::$db->lastInsertId();

        // Query Dashboard for Lycée 1
        $_SESSION['user'] = [
            'id' => self::$userId1,
            'id_user' => self::$userId1,
            'role_id' => 9,
            'lycee_id' => self::$lyceeId1
        ];
        $data1 = TreasuryDashboardService::getDashboardData(self::$lyceeId1, self::$userId1, ['period' => 'today']);

        // Ensure 99000 FCFA from Lycée 2 is NEVER visible on Lycée 1
        $this->assertNotEquals(99000.00, $data1['kpis']['encaissement_periode'], "Lycée 1 dashboard must never leak data from Lycée 2.");

        // Query Dashboard for Lycée 2
        $_SESSION['user'] = [
            'id' => self::$userId2,
            'id_user' => self::$userId2,
            'role_id' => 9,
            'lycee_id' => self::$lyceeId2
        ];
        $data2 = TreasuryDashboardService::getDashboardData(self::$lyceeId2, self::$userId2, ['period' => 'today']);
        $this->assertEquals(99000.00, $data2['kpis']['encaissement_periode'], "Lycée 2 dashboard must correctly reflect Lycée 2 movements.");

        // Clean up
        self::$db->exec("DELETE FROM mouvements_tresorerie WHERE id = $mvtLyceeB");
    }

    public function testCashDeskComparisonPersonNameRules() {
        $_SESSION['user'] = [
            'id' => self::$userId1,
            'id_user' => self::$userId1,
            'role_id' => 9,
            'lycee_id' => self::$lyceeId1
        ];

        $nowStr = date('Y-m-d H:i:s');
        // Caisse 1 has active session opened by Cashier Jean ($cashierUserId)
        $stmtS = self::$db->prepare("
            INSERT INTO sessions_caisse (lycee_id, user_id, compte_id, date_ouverture, solde_ouverture, solde_theorique, statut)
            VALUES (:l_id, :u_id, :c_id, :d_open, 0.00, 0.00, 'ouverte')
        ");
        $stmtS->execute(['l_id' => self::$lyceeId1, 'u_id' => self::$cashierUserId, 'c_id' => self::$caisseId1, 'd_open' => $nowStr]);
        $sessId = self::$db->lastInsertId();

        $data = TreasuryDashboardService::getDashboardData(self::$lyceeId1, self::$userId1);

        $caissesComp = $data['caisses'];
        $this->assertNotEmpty($caissesComp);

        $caisse1Data = null;
        $caisse2Data = null;

        foreach ($caissesComp as $cc) {
            if ($cc['compte_id'] == self::$caisseId1) $caisse1Data = $cc;
            if ($cc['compte_id'] == self::$caisseId2) $caisse2Data = $cc;
        }

        // Caisse 1 has active session -> Person name MUST be session cashier "Jean Caissier"
        $this->assertNotNull($caisse1Data);
        $this->assertStringContainsString('Jean', $caisse1Data['person_name']);

        // Caisse 2 has no active session and no manager -> Person name MUST be "Non assigné"
        $this->assertNotNull($caisse2Data);
        $this->assertEquals('Non assigné', $caisse2Data['person_name']);

        // Clean up
        self::$db->exec("DELETE FROM sessions_caisse WHERE id = $sessId");
    }

    public static function tearDownAfterClass(): void {
        self::$db->exec("DELETE FROM mouvements_tresorerie WHERE lycee_id IN (" . self::$lyceeId1 . ", " . self::$lyceeId2 . ")");
        self::$db->exec("DELETE FROM sessions_caisse WHERE lycee_id IN (" . self::$lyceeId1 . ", " . self::$lyceeId2 . ")");
        self::$db->exec("DELETE FROM comptes_financiers WHERE lycee_id IN (" . self::$lyceeId1 . ", " . self::$lyceeId2 . ")");
        self::$db->exec("DELETE FROM exercices_financiers WHERE lycee_id IN (" . self::$lyceeId1 . ", " . self::$lyceeId2 . ")");
        self::$db->exec("DELETE FROM utilisateurs WHERE id_user IN (" . self::$userId1 . ", " . self::$userId2 . ", " . self::$cashierUserId . ")");
        self::$db->exec("DELETE FROM param_lycee WHERE id IN (" . self::$lyceeId1 . ", " . self::$lyceeId2 . ")");
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "=========================================================================\n";
    echo "📊 DÉMARRAGE SUITE DE TEST: TREASURY DASHBOARD PHASE 3\n";
    echo "=========================================================================\n";
    TreasuryDashboardTest::setUpBeforeClass();
    $test = new TreasuryDashboardTest();
    try {
        echo "\n1. Test KPI Solde Courant Caisses...\n";
        $test->testSoldeCourantCaissesKpi();

        echo "\n2. Test Classification des Sessions & KPI Écarts...\n";
        $test->testSessionStateClassificationAndKpis();

        echo "\n3. Test Classification Typée Mouvements (Encaissements vs Décaissements)...\n";
        $test->testMovementTypeInflowsAndOutflowsClassification();

        echo "\n4. Test Isolation Multi-Tenant (Lycée 1 vs Lycée 2)...\n";
        $test->testMultiTenantIsolation();

        echo "\n5. Test Règles d'Affichage Personne / Caissier / Responsable...\n";
        $test->testCashDeskComparisonPersonNameRules();

        echo "\n=========================================================================\n";
        echo "✅ TOUS LES TESTS TREASURY DASHBOARD ONT RÉUSSI AVEC SUCCÈS !\n";
        echo "=========================================================================\n";
    } finally {
        TreasuryDashboardTest::tearDownAfterClass();
    }
}
?>