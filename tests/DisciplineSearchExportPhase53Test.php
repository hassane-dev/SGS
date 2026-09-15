<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineTypeSanction.php';
require_once __DIR__ . '/../src/models/DisciplineIncident.php';
require_once __DIR__ . '/../src/models/DisciplineSanction.php';
require_once __DIR__ . '/../src/services/DisciplineSearchService.php';
require_once __DIR__ . '/../src/controllers/DisciplineSearchController.php';

class DisciplineSearchExportPhase53Test extends TestCase {

    private static PDO $db;
    private static int $lyceeA = 701;
    private static int $lyceeB = 702;
    private static int $classeCEG = 7001; // CEG Class in Lycee A (active year 1)
    private static int $classeLycee = 7002; // Lycée Class in Lycee A (active year 1)
    private static int $classeTenantB = 7003; // Class in Lycee B
    private static int $eleveA = 7101; // Student in Class 7001
    private static int $eleveB = 7102; // Student in Class 7002
    private static int $eleveTenantB = 7103; // Student in Lycee B
    private static int $teacherA = 7201; // Teacher assigned to CEG 7001
    private static int $typeIncId;
    private static int $typeSancId;
    private static int $inc1;
    private static int $sanc1;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean test records
        self::$db->exec("DELETE FROM discipline_sanctions WHERE lycee_id IN (701, 702)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (701, 702))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (701, 702)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (701, 702)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (7101, 7102, 7103)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (7101, 7102, 7103)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (7001, 7002, 7003)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 7301");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 7301");

        // Create test classes
        self::$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, nom_classe, niveau, serie, numero) VALUES
            (7001, 701, 1, '6eme A', '6eme', 'G', '01'),
            (7002, 701, 2, '2nde C', '2nde', 'C', '01'),
            (7003, 702, 1, '6eme Z', '6eme', 'G', '01')
        ");

        // Create test students
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, cycle_id, matricule, nom, prenom, sexe, actif) VALUES
            (7101, 701, 1, 'MAT-7101', 'KOUASSI', 'Jean', 'M', 1),
            (7102, 701, 2, 'MAT-7102', 'BAMBA', 'Awa', 'F', 1),
            (7103, 702, 1, 'MAT-7103', 'BAH', 'Fatou', 'F', 1)
        ");

        // Enrollments for active academic year (id = 1)
        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id) VALUES
            (7101, 7001, 1),
            (7102, 7002, 1),
            (7103, 7003, 1)
        ");

        // Active pedagogical assignment for Teacher A (7201) in CEG Class 7001
        self::$db->exec("INSERT INTO affectations_pedagogiques (id, lycee_id, annee_academique_id, enseignant_id, classe_id, matiere_id, statut, date_debut) VALUES
            (7901, 701, 1, 7201, 7001, 1, 'actif', '2023-09-01')
        ");

        // Referentials
        self::$typeIncId = DisciplineTypeIncident::create([
            'id' => 7301,
            'lycee_id' => 701,
            'code' => 'TEST_INC_53',
            'libelle' => 'Absence Test 53',
            'niveau_gravite' => 'Moyen',
            'actif' => 1,
        ]);

        self::$typeSancId = DisciplineTypeSanction::create([
            'id' => 7301,
            'lycee_id' => 701,
            'code' => 'TEST_SANC_53',
            'libelle' => 'Retenue Test 53',
            'demande_duree_jours' => 0,
            'demande_heures' => 2,
            'affiche_sur_bulletin' => 1,
            'autorite_min_requise' => 'Surveillant',
            'actif' => 1,
        ]);

        // Incident in Lycee A Class 7001
        self::$inc1 = DisciplineIncident::create([
            'lycee_id' => 701,
            'annee_academique_id' => 1,
            'code' => 'INC-701-01',
            'type_incident_id' => self::$typeIncId,
            'date_incident' => date('Y-m-d', strtotime('-2 days')),
            'heure_incident' => '08:00:00',
            'lieu' => 'Classe 6eme A',
            'description' => 'Incident Test Phase 5.3 =FormulaTest',
            'statut' => 'traite',
            'signale_par_user_id' => 1,
        ], [
            ['eleve_id' => self::$eleveA, 'role_implication' => 'auteur_principal', 'classe_id' => 7001],
        ]);

        // Sanction in Lycee A Class 7001
        self::$sanc1 = DisciplineSanction::create([
            'lycee_id' => 701,
            'annee_academique_id' => 1,
            'incident_id' => self::$inc1,
            'eleve_id' => self::$eleveA,
            'classe_id' => 7001,
            'type_sanction_id' => self::$typeSancId,
            'date_decision' => date('Y-m-d', strtotime('-1 days')),
            'motif' => 'Retenue de test Phase 5.3',
            'par_user_id' => 1,
            'statut' => 'en_cours',
        ]);
    }

    protected function setUp(): void {
        Auth::logout();
        unset($_GET['tab'], $_GET['annee_academique_id'], $_GET['classe_id'], $_GET['cycle_id'], $_GET['niveau'], $_GET['serie'], $_GET['eleve_id'], $_GET['type_incident_id'], $_GET['type_sanction_id'], $_GET['niveau_gravite'], $_GET['statut'], $_GET['role_implication'], $_GET['date_debut'], $_GET['date_fin']);
    }

    /**
     * 1. Search Incidents & Sanctions with Combined Filters (Admin)
     */
    public function test1_AdminSearchIncidentsAndSanctionsCombinedFiltersSuccess(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 701,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        // Search Incidents
        $_GET['tab'] = 'incidents';
        $_GET['classe_id'] = 7001;
        $_GET['niveau_gravite'] = 'Moyen';

        ob_start();
        $controller = new DisciplineSearchController();
        $controller->index();
        $outputInc = ob_get_clean();

        $this->assertStringContainsString('Recherche &amp; Registres Disciplinaires', $outputInc);
        $this->assertStringContainsString('INC-701-01', $outputInc);
        $this->assertStringContainsString('MAT-7101', $outputInc);

        // Search Sanctions
        $_GET['tab'] = 'sanctions';
        $_GET['statut'] = 'en_cours';

        ob_start();
        $controller->index();
        $outputSanc = ob_get_clean();

        $this->assertStringContainsString('Retenue Test 53', $outputSanc);
        $this->assertStringContainsString('MAT-7101', $outputSanc);
    }

    /**
     * 2. Teacher Scope Restriction: Allowed Assigned Class vs Forbidden Class
     */
    public function test2_TeacherScopeSearchRestriction(): void {
        $_SESSION['user'] = [
            'id' => self::$teacherA, // Assigned only to 7001
            'lycee_id' => 701,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        // Allowed Class 7001
        $_GET['tab'] = 'incidents';
        $_GET['classe_id'] = 7001;

        ob_start();
        $controller = new DisciplineSearchController();
        $controller->index();
        $outputAllowed = ob_get_clean();

        $this->assertStringContainsString('INC-701-01', $outputAllowed);

        // Forbidden Class 7002 (Lycée) -> HTTP 403
        $_GET['classe_id'] = 7002;

        ob_start();
        try {
            $controller->index();
        } catch (Throwable $e) {}
        $outputForbidden = ob_get_clean();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('Accès Refusé', $outputForbidden);
    }

    /**
     * 3. Teacher Attempting Historical Year -> HTTP 403
     */
    public function test3_TeacherAttemptingHistoricalYearForbidden(): void {
        $_SESSION['user'] = [
            'id' => self::$teacherA,
            'lycee_id' => 701,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['annee_academique_id'] = 99; // Historical year request

        ob_start();
        $controller = new DisciplineSearchController();
        try {
            $controller->index();
        } catch (Throwable $e) {}
        $output = ob_get_clean();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('Accès Refusé', $output);
    }

    /**
     * 4. CSV Export Format, BOM & Formula Injection Sanitization
     */
    public function test4_CsvExportFormatAndFormulaInjectionSanitization(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 701,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['tab'] = 'incidents';
        $_GET['classe_id'] = 7001;

        ob_start();
        $controller = new DisciplineSearchController();
        try {
            $controller->exportCsv();
        } catch (Throwable $e) {}
        $outputCsv = ob_get_clean();

        // Must start with UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $outputCsv);
        $this->assertStringContainsString('INC-701-01', $outputCsv);
        $this->assertStringContainsString('MAT-7101', $outputCsv);
        // Formula injection protection: '=FormulaTest' sanitized to "'=FormulaTest"
        $this->assertStringContainsString("'=FormulaTest", $outputCsv);
    }

    /**
     * 5. PDF / Print Template Export Renders Cleanly with Administrative Header
     */
    public function test5_PdfPrintTemplateExportSuccess(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 701,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['tab'] = 'sanctions';
        $_GET['classe_id'] = 7001;

        ob_start();
        $controller = new DisciplineSearchController();
        $controller->exportPdf();
        $outputPdf = ob_get_clean();

        $this->assertStringContainsString('REGISTRE OFFICIEL DES SANCTIONS DISCIPLINAIRES', $outputPdf);
        $this->assertStringContainsString('Retenue Test 53', $outputPdf);
        $this->assertStringContainsString('MAT-7101', $outputPdf);
    }

    /**
     * 6. Multi-tenant Search and Export Server-Side Isolation
     */
    public function test6_MultiTenantSearchAndExportIsolation(): void {
        $_SESSION['user'] = [
            'id' => 2,
            'lycee_id' => 702, // User in Lycee B
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['tab'] = 'incidents';

        ob_start();
        $controller = new DisciplineSearchController();
        $controller->index();
        $output = ob_get_clean();

        // Must not contain Lycee A records
        $this->assertStringNotContainsString('INC-701-01', $output);
        $this->assertStringNotContainsString('MAT-7101', $output);
    }

    public static function tearDownAfterClass(): void {
        self::$db->exec("DELETE FROM discipline_sanctions WHERE lycee_id IN (701, 702)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (701, 702))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (701, 702)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (701, 702)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (7101, 7102, 7103)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (7101, 7102, 7103)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (7001, 7002, 7003)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 7301");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 7301");
    }
}
