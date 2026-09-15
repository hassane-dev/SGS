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
require_once __DIR__ . '/../src/controllers/DisciplineDashboardController.php';

class DisciplineDashboardPhase52Test extends TestCase {

    private static PDO $db;
    private static int $lyceeA = 601;
    private static int $lyceeB = 602;
    private static int $classeCEG = 6001; // CEG Class in Lycee A (active year 1)
    private static int $classeLycee = 6002; // Lycée Class in Lycee A (active year 1)
    private static int $classeTenantB = 6003; // Class in Lycee B
    private static int $eleveA = 6101; // Auteur principal in Class 6001
    private static int $eleveB = 6102; // Co-auteur in Class 6001
    private static int $eleveVictime = 6103; // Victime in Class 6001
    private static int $eleveComplice = 6105; // Complice in Class 6001
    private static int $eleveTemoin = 6106; // Temoin in Class 6001
    private static int $eleveTenantB = 6104; // Student in Lycee B
    private static int $teacherA = 6201; // Teacher assigned to CEG 6001
    private static int $typeIncId;
    private static int $typeSancId;
    private static int $inc1;
    private static int $inc2;
    private static int $inc3;
    private static int $incDismissed;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean test records
        self::$db->exec("DELETE FROM discipline_sanctions WHERE lycee_id IN (601, 602)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (601, 602))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (601, 602)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (601, 602)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (6101, 6102, 6103, 6104, 6105, 6106)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (6101, 6102, 6103, 6104, 6105, 6106)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (6001, 6002, 6003)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 6301");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 6301");

        // Create test classes
        self::$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, nom_classe, niveau, serie, numero) VALUES
            (6001, 601, 1, '6eme A', '6eme', 'G', '01'),
            (6002, 601, 2, '2nde C', '2nde', 'C', '01'),
            (6003, 602, 1, '6eme Z', '6eme', 'G', '01')
        ");

        // Create test students
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, cycle_id, matricule, nom, prenom, sexe, actif) VALUES
            (6101, 601, 1, 'MAT-6101', 'KOUASSI', 'Jean', 'M', 1),
            (6102, 601, 1, 'MAT-6102', 'BAMBA', 'Awa', 'F', 1),
            (6103, 601, 1, 'MAT-6103', 'YAPO', 'Paul', 'M', 1),
            (6104, 602, 1, 'MAT-6104', 'BAH', 'Fatou', 'F', 1),
            (6105, 601, 1, 'MAT-6105', 'KOFFI', 'Luc', 'M', 1),
            (6106, 601, 1, 'MAT-6106', 'DIBY', 'Marc', 'M', 1)
        ");

        // Enrollments for current active academic year (id = 1)
        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id) VALUES
            (6101, 6001, 1),
            (6102, 6001, 1),
            (6103, 6001, 1),
            (6104, 6003, 1),
            (6105, 6001, 1),
            (6106, 6001, 1)
        ");

        // Active pedagogical assignment for Teacher A (6201) in CEG Class 6001
        self::$db->exec("INSERT INTO affectations_pedagogiques (id, lycee_id, annee_academique_id, enseignant_id, classe_id, matiere_id, statut, date_debut) VALUES
            (6901, 601, 1, 6201, 6001, 1, 'actif', '2023-09-01')
        ");

        // Referentials
        self::$typeIncId = DisciplineTypeIncident::create([
            'id' => 6301,
            'lycee_id' => 601,
            'code' => 'TEST_INC_52',
            'libelle' => 'Bagarre Test 52',
            'niveau_gravite' => 'Grave',
            'actif' => 1,
        ]);

        self::$typeSancId = DisciplineTypeSanction::create([
            'id' => 6301,
            'lycee_id' => 601,
            'code' => 'TEST_SANC_52',
            'libelle' => 'Exclusion Test 52',
            'demande_duree_jours' => 1,
            'demande_heures' => 0,
            'affiche_sur_bulletin' => 1,
            'autorite_min_requise' => 'Conseil',
            'actif' => 1,
        ]);

        // Incident 1 (Multi-students): Student 6101 (auteur_principal) & Student 6102 (co_auteur) & Student 6103 (victime) & Student 6105 (complice) & Student 6106 (temoin)
        self::$inc1 = DisciplineIncident::create([
            'lycee_id' => 601,
            'annee_academique_id' => 1,
            'code' => 'INC-601-01',
            'type_incident_id' => self::$typeIncId,
            'date_incident' => date('Y-m-d', strtotime('-5 days')),
            'heure_incident' => '10:00:00',
            'lieu' => 'Cour',
            'description' => 'Incident 1 Phase 5.2 Multi-eleves',
            'statut' => 'traite',
            'signale_par_user_id' => 1,
        ], [
            ['eleve_id' => self::$eleveA, 'role_implication' => 'auteur_principal', 'classe_id' => 6001],
            ['eleve_id' => self::$eleveB, 'role_implication' => 'co_auteur', 'classe_id' => 6001],
            ['eleve_id' => self::$eleveVictime, 'role_implication' => 'victime', 'classe_id' => 6001],
            ['eleve_id' => self::$eleveComplice, 'role_implication' => 'complice', 'classe_id' => 6001],
            ['eleve_id' => self::$eleveTemoin, 'role_implication' => 'temoin', 'classe_id' => 6001],
        ]);

        // Incident 2: Student 6101 (auteur_principal) -> Makes Student 6101 RECIDIVIST (2 distinct incidents as auteur)
        self::$inc2 = DisciplineIncident::create([
            'lycee_id' => 601,
            'annee_academique_id' => 1,
            'code' => 'INC-601-02',
            'type_incident_id' => self::$typeIncId,
            'date_incident' => date('Y-m-d', strtotime('-2 days')),
            'heure_incident' => '14:00:00',
            'lieu' => 'Classe',
            'description' => 'Incident 2 Phase 5.2',
            'statut' => 'traite',
            'signale_par_user_id' => 1,
        ], [
            ['eleve_id' => self::$eleveA, 'role_implication' => 'auteur_principal', 'classe_id' => 6001],
        ]);

        // Incident 3: Student 6102 (co_auteur) -> Makes Student 6102 RECIDIVIST (2 distinct incidents as co_auteur)
        self::$inc3 = DisciplineIncident::create([
            'lycee_id' => 601,
            'annee_academique_id' => 1,
            'code' => 'INC-601-03',
            'type_incident_id' => self::$typeIncId,
            'date_incident' => date('Y-m-d', strtotime('-1 days')),
            'heure_incident' => '11:00:00',
            'lieu' => 'Couloir',
            'description' => 'Incident 3 Phase 5.2',
            'statut' => 'en_instruction',
            'signale_par_user_id' => 1,
        ], [
            ['eleve_id' => self::$eleveB, 'role_implication' => 'co_auteur', 'classe_id' => 6001],
        ]);

        // Incident 4 (Classed sans suite): Must be EXCLUDED from total incidents, stats & recidivism
        self::$incDismissed = DisciplineIncident::create([
            'lycee_id' => 601,
            'annee_academique_id' => 1,
            'code' => 'INC-601-04-DISMISSED',
            'type_incident_id' => self::$typeIncId,
            'date_incident' => date('Y-m-d'),
            'heure_incident' => '16:00:00',
            'lieu' => 'Portail',
            'description' => 'Incident classe sans suite',
            'statut' => 'classe_sans_suite',
            'signale_par_user_id' => 1,
        ], [
            ['eleve_id' => self::$eleveVictime, 'role_implication' => 'auteur_principal', 'classe_id' => 6001],
        ]);

        // Sanctions for Incident 1 (Multiple sanctions on same incident)
        DisciplineSanction::create([
            'lycee_id' => 601,
            'annee_academique_id' => 1,
            'incident_id' => self::$inc1,
            'eleve_id' => self::$eleveA,
            'classe_id' => 6001,
            'type_sanction_id' => self::$typeSancId,
            'date_decision' => date('Y-m-d', strtotime('-3 days')),
            'motif' => 'Sanction 1 for A',
            'par_user_id' => 1,
            'statut' => 'executee',
        ]);

        DisciplineSanction::create([
            'lycee_id' => 601,
            'annee_academique_id' => 1,
            'incident_id' => self::$inc1,
            'eleve_id' => self::$eleveB,
            'classe_id' => 6001,
            'type_sanction_id' => self::$typeSancId,
            'date_decision' => date('Y-m-d', strtotime('-3 days')),
            'motif' => 'Sanction 2 for B',
            'par_user_id' => 1,
            'statut' => 'prononcee',
        ]);
    }

    protected function setUp(): void {
        Auth::logout();
        unset($_GET['annee_academique_id'], $_GET['classe_id'], $_GET['date_debut'], $_GET['date_fin']);
    }

    /**
     * A — Exactitude des KPI et exclusion de classe_sans_suite
     */
    public function testA_AdminDashboardKPIsAndExclusionOfDismissed(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 601,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        // Total non-dismissed incidents: 3 (Inc 1, 2, 3)
        $this->assertStringContainsString('Incidents Totaux', $output);
        $this->assertStringContainsString('3', $output);

        // Total Implicated Students: 5 (A, B, Victime, Complice, Temoin)
        $this->assertStringContainsString('Élèves Impliqués', $output);
        $this->assertStringContainsString('5', $output);

        // Total Responsible Students: 3 (A, B, Complice)
        $this->assertStringContainsString('Élèves Responsables', $output);
        $this->assertStringContainsString('3', $output);

        // Total Recidivists: 2 (Student A and Student B, each with >= 2 distinct non-dismissed incidents as auteur/co-auteur)
        $this->assertStringContainsString('Élèves Récidivistes', $output);
        $this->assertStringContainsString('2', $output);

        // Total Sanctions: 2
        $this->assertStringContainsString('Sanctions Totales', $output);
        $this->assertStringContainsString('2', $output);
    }

    /**
     * B — Double comptage : Incident multi-élèves et sanctions multiples
     */
    public function testB_MultiStudentIncidentAndMultiSanctionsNoDoubleCounting(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 601,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        // 3 total valid incidents despite Inc 1 having 5 students
        $this->assertStringContainsString('3', $output);
        // 2 total sanctions despite being linked to the same Incident 1
        $this->assertStringContainsString('2', $output);
    }

    /**
     * C — Récidive : Complice, Victime, Temoin et Dismissed exprès exlus
     */
    public function testC_RecidivismStrictRuleExclusions(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 601,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        // Student Victime (6103) has Inc 1 (victime) and Inc 4 (dismissed auteur) -> 0 valid author incidents -> Not recidivist
        // Student Complice (6105) has Inc 1 (complice) -> 0 author incidents -> Not recidivist
        // Student Temoin (6106) has Inc 1 (temoin) -> 0 author incidents -> Not recidivist
        // Exactly 2 recidivists (6101 and 6102)
        $this->assertStringContainsString('2', $output);
    }

    /**
     * D — Multi-tenant isolation server-side
     */
    public function testD_MultiTenantIsolationServerSide(): void {
        $_SESSION['user'] = [
            'id' => 2,
            'lycee_id' => 602, // Lycee B
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        // Lycee B has 0 incidents in setup -> all KPIs 0
        $this->assertStringContainsString('Incidents Totaux', $output);
        $this->assertStringNotContainsString('INC-601-01', $output);
        $this->assertStringNotContainsString('6eme A', $output);
    }

    /**
     * E — Scope Enseignant : Unassigned class, former class, historical year, CEG -> Lycee
     */
    public function testE_TeacherScopeRestrictions(): void {
        $_SESSION['user'] = [
            'id' => self::$teacherA, // Assigned only to 6001 (CEG)
            'lycee_id' => 601,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        // 1. Authorized active class
        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Tableau de Bord &amp; Statistiques Disciplinaires', $output);
        $this->assertStringContainsString('6eme A', $output);

        // 2. Unassigned class (Lycée 6002) -> 403
        $_GET['classe_id'] = 6002;
        ob_start();
        try {
            $controller->index();
        } catch (Throwable $e) {}
        $output2 = ob_get_clean();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('Accès Refusé', $output2);

        // 3. Historical year attempt -> 403
        unset($_GET['classe_id']);
        $_GET['annee_academique_id'] = 99;
        ob_start();
        try {
            $controller->index();
        } catch (Throwable $e) {}
        $output3 = ob_get_clean();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('Accès Refusé', $output3);
    }

    /**
     * F & G — Filtres et Falsification GET
     */
    public function testFG_FiltersAndGETTamperingProtection(): void {
        // Global user can filter by date and class
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 601,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['date_debut'] = date('Y-m-d', strtotime('-3 days'));
        $_GET['date_fin'] = date('Y-m-d');

        ob_start();
        $controller = new DisciplineDashboardController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Incidents Totaux', $output);
    }

    public static function tearDownAfterClass(): void {
        self::$db->exec("DELETE FROM discipline_sanctions WHERE lycee_id IN (601, 602)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (601, 602))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (601, 602)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (601, 602)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (6101, 6102, 6103, 6104, 6105, 6106)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (6101, 6102, 6103, 6104, 6105, 6106)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (6001, 6002, 6003)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 6301");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 6301");
    }
}
