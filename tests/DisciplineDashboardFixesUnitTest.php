<?php

use PHPUnit\Framework\TestCase;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/controllers/DisciplineDashboardController.php';

class DisciplineDashboardFixesUnitTest extends TestCase {

    private static PDO $db;

    public static function setUpBeforeClass(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        self::$db = new PDO('sqlite::memory:');
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Register custom SQLite functions matching MySQL functions used in queries
        self::$db->sqliteCreateFunction('DATEDIFF', function($a, $b) {
            if (!$a || !$b) return null;
            $timeA = strtotime($a);
            $timeB = strtotime($b);
            if ($timeA === false || $timeB === false) return null;
            return (int)round(($timeA - $timeB) / 86400);
        }, 2);

        self::$db->sqliteCreateFunction('DATE_FORMAT', function($date, $format) {
            if (!$date) return null;
            $time = strtotime($date);
            if ($time === false) return null;
            return date('Y-m', $time);
        }, 2);

        self::$db->sqliteCreateFunction('TRIM', function($str) {
            return trim($str ?? '');
        }, 1);

        // Create Schema
        self::$db->exec("
            CREATE TABLE param_lycee (
                id INTEGER PRIMARY KEY,
                nom_lycee TEXT NOT NULL
            );

            CREATE TABLE cycles (
                id_cycle INTEGER PRIMARY KEY,
                nom_cycle TEXT NOT NULL,
                lycee_id INTEGER NOT NULL,
                actif INTEGER NOT NULL DEFAULT 1
            );

            CREATE TABLE annees_academiques (
                id INTEGER PRIMARY KEY,
                libelle TEXT NOT NULL,
                date_debut TEXT NOT NULL,
                date_fin TEXT NOT NULL,
                est_active INTEGER NOT NULL DEFAULT 1,
                cloturee INTEGER NOT NULL DEFAULT 0
            );

            CREATE TABLE classes (
                id_classe INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                cycle_id INTEGER NOT NULL,
                niveau TEXT NOT NULL,
                serie TEXT NULL,
                numero TEXT NULL,
                nom_classe TEXT NULL
            );

            CREATE TABLE matieres (
                id_matiere INTEGER PRIMARY KEY,
                nom_matiere TEXT NOT NULL,
                lycee_id INTEGER NOT NULL
            );

            CREATE TABLE utilisateurs (
                id_user INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                nom TEXT NOT NULL,
                prenom TEXT NOT NULL,
                role_name TEXT NOT NULL,
                auth_version INTEGER NOT NULL DEFAULT 1
            );

            CREATE TABLE notifications (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                titre TEXT NOT NULL,
                message TEXT NOT NULL,
                is_read INTEGER NOT NULL DEFAULT 0,
                est_lu INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE roles (
                id INTEGER PRIMARY KEY,
                nom TEXT NOT NULL
            );

            CREATE TABLE permissions (
                id_permission INTEGER PRIMARY KEY,
                resource TEXT NOT NULL,
                action TEXT NOT NULL
            );

            CREATE TABLE role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL
            );

            CREATE TABLE personnel_cycles_assignments (
                id INTEGER PRIMARY KEY,
                personnel_id INTEGER NOT NULL,
                cycle_id INTEGER NOT NULL,
                lycee_id INTEGER NOT NULL,
                actif INTEGER NOT NULL DEFAULT 1,
                date_debut TEXT NULL,
                date_fin TEXT NULL
            );

            CREATE TABLE affectations_pedagogiques (
                id INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                annee_academique_id INTEGER NOT NULL,
                enseignant_id INTEGER NOT NULL,
                classe_id INTEGER NOT NULL,
                matiere_id INTEGER NOT NULL,
                statut TEXT NOT NULL DEFAULT 'actif'
            );

            CREATE TABLE eleves (
                id_eleve INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                nom TEXT NOT NULL,
                prenom TEXT NOT NULL,
                identifiant_public TEXT NOT NULL
            );

            CREATE TABLE discipline_types_incidents (
                id INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                code TEXT NOT NULL,
                libelle TEXT NOT NULL,
                niveau_gravite TEXT NOT NULL DEFAULT 'moyen',
                actif INTEGER NOT NULL DEFAULT 1
            );

            CREATE TABLE discipline_types_sanctions (
                id INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                code TEXT NOT NULL,
                libelle TEXT NOT NULL,
                actif INTEGER NOT NULL DEFAULT 1
            );

            CREATE TABLE discipline_incidents (
                id INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                annee_academique_id INTEGER NOT NULL,
                type_incident_id INTEGER NOT NULL,
                date_incident TEXT NOT NULL,
                heure_incident TEXT NULL,
                lieu TEXT NULL,
                description_faits TEXT NOT NULL,
                signale_par_user_id INTEGER NOT NULL,
                statut TEXT NOT NULL DEFAULT 'signale'
            );

            CREATE TABLE discipline_incident_eleves (
                id INTEGER PRIMARY KEY,
                incident_id INTEGER NOT NULL,
                eleve_id INTEGER NOT NULL,
                classe_id INTEGER NOT NULL,
                role_implication TEXT NOT NULL DEFAULT 'auteur_principal',
                observation_individuelle TEXT NULL
            );

            CREATE TABLE discipline_sanctions (
                id INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                annee_academique_id INTEGER NOT NULL,
                incident_id INTEGER NULL,
                eleve_id INTEGER NOT NULL,
                classe_id INTEGER NOT NULL,
                type_sanction_id INTEGER NOT NULL,
                motif TEXT NOT NULL,
                prononcee_par_user_id INTEGER NOT NULL,
                date_decision TEXT NOT NULL,
                statut TEXT NOT NULL DEFAULT 'prononcee'
            );
        ");

        $today = date('Y-m-d');

        // Seed Core Data
        self::$db->exec("
            INSERT INTO param_lycee (id, nom_lycee) VALUES (10, 'Lycée Excellence');

            INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, actif) VALUES
            (101, 'Collège (CEG)', 10, 1),
            (102, 'Lycée', 10, 1);

            INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES
            (2024, '2024-2025', '2024-09-01', '2025-06-30', 1);

            INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES
            (301, 10, 101, '6ème', '', '1'),
            (302, 10, 101, '5ème', 'A', '2'),
            (303, 10, 102, '2nde', 'C', '1');

            INSERT INTO matieres (id_matiere, nom_matiere, lycee_id) VALUES (10, 'Mathématiques', 10);

            INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_name, auth_version) VALUES
            (1, 10, 'Admin', 'Global', 'admin', 1),
            (2, 10, 'Professeur', 'Jean', 'enseignant', 1);

            INSERT INTO roles (id, nom) VALUES (1, 'Admin'), (2, 'Enseignant');

            INSERT INTO permissions (id_permission, resource, action) VALUES
            (1, 'discipline', 'view_incidents'),
            (2, 'discipline', 'view_sanctions'),
            (3, 'eleve', 'view_all'),
            (4, 'lycee', 'view_all_lycees'),
            (5, 'cycle', 'view_all_cycles');

            INSERT INTO role_permissions (role_id, permission_id) VALUES
            (1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
            (2, 1);

            INSERT INTO personnel_cycles_assignments (id, personnel_id, cycle_id, lycee_id, actif, date_debut, date_fin) VALUES
            (1, 1, 101, 10, 1, '$today', '2030-12-31'),
            (2, 1, 102, 10, 1, '$today', '2030-12-31'),
            (3, 2, 101, 10, 1, '$today', '2030-12-31');

            INSERT INTO affectations_pedagogiques (id, lycee_id, annee_academique_id, enseignant_id, classe_id, matiere_id, statut) VALUES
            (1, 10, 2024, 2, 301, 10, 'actif');

            INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public) VALUES
            (501, 10, 'Dupont', 'Alice', 'E001'),
            (502, 10, 'Martin', 'Bob', 'E002');

            INSERT INTO discipline_types_incidents (id, lycee_id, code, libelle, niveau_gravite) VALUES
            (1, 10, 'BAV', 'Bavardage', 'faible'),
            (2, 10, 'VIO', 'Violence', 'grave');

            INSERT INTO discipline_types_sanctions (id, lycee_id, code, libelle) VALUES
            (1, 10, 'AVT', 'Avertissement'),
            (2, 10, 'EXC', 'Exclusion');
        ");

        Database::setInstance(self::$db);
    }

    public static function tearDownAfterClass(): void {
        Database::setInstance(null);
    }

    protected function setUp(): void {
        $_GET = [];
        $_POST = [];
        $_SESSION = [
            'user' => [
                'id' => 1,
                'id_user' => 1,
                'role_id' => 1,
                'lycee_id' => 10,
                'role_name' => 'admin',
                'nom' => 'Admin',
                'prenom' => 'Global',
                'auth_version' => 1
            ]
        ];

        // Clear incidents & sanctions
        self::$db->exec("DELETE FROM discipline_sanctions");
        self::$db->exec("DELETE FROM discipline_incident_eleves");
        self::$db->exec("DELETE FROM discipline_incidents");
    }

    /**
     * Test 1 & 2 & 3 & 4: Verified Delay Calculation (Linked Sanctions vs Direct Sanctions vs Date Anomaly)
     */
    public function testDelayCalculationRules(): void {
        // Incident 1: Date 2024-10-01
        self::$db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, date_incident, description_faits, signale_par_user_id)
            VALUES (1, 10, 2024, 1, '2024-10-01', 'Test incident 1', 1)");
        self::$db->exec("INSERT INTO discipline_incident_eleves (id, incident_id, eleve_id, classe_id) VALUES (1, 1, 501, 301)");

        // Incident 2: Date 2024-10-10
        self::$db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, date_incident, description_faits, signale_par_user_id)
            VALUES (2, 10, 2024, 2, '2024-10-10', 'Test incident 2', 1)");
        self::$db->exec("INSERT INTO discipline_incident_eleves (id, incident_id, eleve_id, classe_id) VALUES (2, 2, 502, 302)");

        // Sanction 1: Linked to Incident 1, Decision 2024-10-05 -> Delay = 4 days
        self::$db->exec("INSERT INTO discipline_sanctions (id, lycee_id, annee_academique_id, incident_id, eleve_id, classe_id, type_sanction_id, motif, prononcee_par_user_id, date_decision)
            VALUES (1, 10, 2024, 1, 501, 301, 1, 'Motif 1', 1, '2024-10-05')");

        // Sanction 2: Linked to Incident 2, Decision 2024-10-20 -> Delay = 10 days
        self::$db->exec("INSERT INTO discipline_sanctions (id, lycee_id, annee_academique_id, incident_id, eleve_id, classe_id, type_sanction_id, motif, prononcee_par_user_id, date_decision)
            VALUES (2, 10, 2024, 2, 502, 302, 1, 'Motif 2', 1, '2024-10-20')");

        // Sanction 3: DIRECT SANCTION (incident_id IS NULL), Decision 2024-10-15 -> Should NOT affect delay average
        self::$db->exec("INSERT INTO discipline_sanctions (id, lycee_id, annee_academique_id, incident_id, eleve_id, classe_id, type_sanction_id, motif, prononcee_par_user_id, date_decision)
            VALUES (3, 10, 2024, NULL, 501, 301, 2, 'Sanction directe', 1, '2024-10-15')");

        // Sanction 4: INCOHERENT DATE (date_decision 2024-09-20 < date_incident 2024-10-01) -> Excluded from average
        self::$db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, date_incident, description_faits, signale_par_user_id)
            VALUES (3, 10, 2024, 1, '2024-10-01', 'Test incident 3', 1)");
        self::$db->exec("INSERT INTO discipline_incident_eleves (id, incident_id, eleve_id, classe_id) VALUES (3, 3, 501, 301)");
        self::$db->exec("INSERT INTO discipline_sanctions (id, lycee_id, annee_academique_id, incident_id, eleve_id, classe_id, type_sanction_id, motif, prononcee_par_user_id, date_decision)
            VALUES (4, 10, 2024, 3, 501, 301, 1, 'Anomalie date', 1, '2024-09-20')");

        // Execute controller action
        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        // Total sanctions should be 4 (including direct sanction and date anomaly)
        $this->assertStringContainsText('Sanctions Totales', $output);
        $this->assertStringContainsText('4</h3>', $output);

        // Average delay should be (4 + 10) / 2 = 7.0 days
        $this->assertStringContainsText('Délai moy. (sanctions liées) :', $output);
        $this->assertStringContainsText('7 j</strong>', $output);
    }

    /**
     * Test 5: Absence of data yields null delay
     */
    public function testAbsenceOfDataYieldsNullDelay(): void {
        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsText('Prononcées / En cours', $output);
        $this->assertStringNotContainsText('Délai moy. (sanctions liées) :', $output);
    }

    /**
     * Test 6: Cycle Filter
     */
    public function testCycleFilterScoping(): void {
        // Incident in Collège (Cycle 101, Class 301)
        self::$db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, date_incident, description_faits, signale_par_user_id)
            VALUES (10, 10, 2024, 1, '2024-10-01', 'Incident Collège', 1)");
        self::$db->exec("INSERT INTO discipline_incident_eleves (id, incident_id, eleve_id, classe_id) VALUES (10, 10, 501, 301)");

        // Incident in Lycée (Cycle 102, Class 303)
        self::$db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, date_incident, description_faits, signale_par_user_id)
            VALUES (20, 10, 2024, 2, '2024-10-02', 'Incident Lycée', 1)");
        self::$db->exec("INSERT INTO discipline_incident_eleves (id, incident_id, eleve_id, classe_id) VALUES (20, 20, 502, 303)");

        // Filter on Cycle 101 (Collège)
        $_GET['cycle_id'] = '101';
        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        // Check cycle select rendering
        $this->assertStringContainsText('Tous les cycles', $output);
        $this->assertStringContainsText('Collège (CEG)', $output);
        $this->assertStringContainsText('value="101" selected', $output);
    }

    /**
     * Test 7: Teacher Scope Label ("Toutes mes classes")
     */
    public function testTeacherScopeLabel(): void {
        $_SESSION['user'] = [
            'id' => 2,
            'id_user' => 2,
            'role_id' => 2,
            'lycee_id' => 10,
            'role_name' => 'enseignant',
            'nom' => 'Professeur',
            'prenom' => 'Jean',
            'auth_version' => 1
        ];

        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsText('Toutes mes classes', $output);
        $this->assertStringNotContainsText('Toutes les classes', $output);
    }

    private function assertStringContainsText(string $needle, string $haystack): void {
        if (!str_contains($haystack, $needle)) {
            echo "\n--- OUTPUT DEBUG START ---\n" . substr($haystack, 0, 1000) . "\n--- OUTPUT DEBUG END ---\n";
        }
        $this->assertTrue(str_contains($haystack, $needle), "Failed asserting that output contains '$needle'.");
    }

    private function assertStringNotContainsText(string $needle, string $haystack): void {
        $this->assertFalse(str_contains($haystack, $needle), "Failed asserting that output does NOT contain '$needle'.");
    }
}
