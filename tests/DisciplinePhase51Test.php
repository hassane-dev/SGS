<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineTypeSanction.php';
require_once __DIR__ . '/../src/models/DisciplineIncident.php';
require_once __DIR__ . '/../src/models/DisciplineSanction.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';

class DisciplinePhase51Test extends TestCase {

    private static PDO $db;
    private static int $lyceeA = 501;
    private static int $lyceeB = 502;
    private static int $anneeId = 1;
    private static int $classeCEG = 5001; // CEG Class in Lycee A
    private static int $classeLycee = 5002; // Lycée Class in Lycee A
    private static int $classeOther = 5003; // Other Class in Lycee A
    private static int $classeTenantB = 5004; // Class in Lycee B
    private static int $eleveCEG = 5101; // Student in CEG Class 5001
    private static int $eleveLycee = 5102; // Student in Lycée Class 5002
    private static int $eleveOther = 5103; // Student in Other Class 5003
    private static int $eleveTenantB = 5104; // Student in Lycee B 5004
    private static int $teacherA = 5201; // Teacher assigned to CEG 5001
    private static int $teacherB = 5202; // Teacher who reported incident but NOT assigned to 5001
    private static int $typeIncId;
    private static int $typeSancId;
    private static int $incidentId;
    private static int $sanctionId;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean phase 5.1 test records
        self::$db->exec("DELETE FROM discipline_notifications WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_documents WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_historique WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_sanctions WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (501, 502))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (5101, 5102, 5103, 5104)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (5101, 5102, 5103, 5104)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (5001, 5002, 5003, 5004)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 5301");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 5301");

        // Create test classes (CEG cycle = 1, Lycée cycle = 2)
        self::$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, nom_classe, niveau, serie, numero) VALUES
            (5001, 501, 1, '6eme A', '6eme', 'G', '01'),
            (5002, 501, 2, '2nde C', '2nde', 'C', '01'),
            (5003, 501, 1, '5eme B', '5eme', 'G', '02'),
            (5004, 502, 1, '6eme Z', '6eme', 'G', '01')
        ");

        // Create test students
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, cycle_id, matricule, nom, prenom, sexe, actif) VALUES
            (5101, 501, 1, 'MAT-5101', 'KOUASSI', 'Jean', 'M', 1),
            (5102, 501, 2, 'MAT-5102', 'BAMBA', 'Awa', 'F', 1),
            (5103, 501, 1, 'MAT-5103', 'DIOP', 'Moussa', 'M', 1),
            (5104, 502, 1, 'MAT-5104', 'BAH', 'Fatou', 'F', 1)
        ");

        // Enrollments
        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id) VALUES
            (5101, 5001, 1),
            (5102, 5002, 1),
            (5103, 5003, 1),
            (5104, 5004, 1)
        ");

        // Active pedagogical assignment for Teacher A (5201) in CEG Class 5001
        self::$db->exec("INSERT INTO affectations_pedagogiques (id, lycee_id, annee_academique_id, enseignant_id, classe_id, matiere_id, statut, date_debut) VALUES
            (5901, 501, 1, 5201, 5001, 1, 'actif', '2023-09-01')
        ");

        // Referentials
        self::$typeIncId = DisciplineTypeIncident::create([
            'id' => 5301,
            'lycee_id' => 501,
            'code' => 'TEST_INC_51',
            'libelle' => 'Insubordination Test 51',
            'niveau_gravite' => 'Grave',
            'actif' => 1,
        ]);

        self::$typeSancId = DisciplineTypeSanction::create([
            'id' => 5301,
            'lycee_id' => 501,
            'code' => 'TEST_SANC_51',
            'libelle' => 'Avertissement Test 51',
            'demande_duree_jours' => 0,
            'demande_heures' => 0,
            'affiche_sur_bulletin' => 1,
            'autorite_min_requise' => 'Censeur',
            'actif' => 1,
        ]);

        // Incident created by Teacher B (5202) concerning Student A (5101)
        self::$incidentId = DisciplineIncident::create([
            'lycee_id' => 501,
            'annee_academique_id' => 1,
            'code' => 'INC-501-TEST',
            'type_incident_id' => self::$typeIncId,
            'date_incident' => date('Y-m-d'),
            'heure_incident' => '10:00:00',
            'lieu' => 'Cour de récréation',
            'description' => 'Incident créé par Enseignant B',
            'statut' => 'en_instruction',
            'signale_par_user_id' => self::$teacherB,
        ], [
            [
                'eleve_id' => self::$eleveCEG,
                'role_implication' => 'auteur_principal',
                'observations_eleve' => 'Avoue les faits',
                'classe_id' => 5001,
            ]
        ]);

        // Sanction for Student A (5101)
        self::$sanctionId = DisciplineSanction::create([
            'lycee_id' => 501,
            'annee_academique_id' => 1,
            'incident_id' => self::$incidentId,
            'eleve_id' => self::$eleveCEG,
            'classe_id' => 5001,
            'type_sanction_id' => self::$typeSancId,
            'date_decision' => date('Y-m-d'),
            'motif_decision' => 'Décision disciplinaire Phase 5.1',
            'par_user_id' => 1,
            'statut' => 'en_cours',
        ]);
    }

    protected function setUp(): void {
        Auth::logout();
    }

    /**
     * TEST A: Enseignant autorisé avec affectation pédagogique ACTIVE sur la classe de l'élève.
     * Même si l'incident a été signalé par l'enseignant B, l'enseignant A a un cours actif dans la classe -> ACCÈS AUTORISÉ (200).
     */
    public function testTestA_AuthorizedTeacherWithActiveAssignmentSuccess(): void {
        $_SESSION['user'] = [
            'id' => self::$teacherA,
            'lycee_id' => 501,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['id'] = self::$eleveCEG;

        ob_start();
        $controller = new EleveController();
        $controller->discipline();
        $output = ob_get_clean();

        $this->assertStringContainsString('Vie Scolaire &amp; Discipline', $output);
        $this->assertStringContainsString('INC-501-TEST', $output);
        $this->assertStringContainsString('MAT-5101', $output);
    }

    /**
     * TEST B: Ancien rapporteur hors scope.
     * L'enseignant B a signalé l'incident historiquement, mais n'a AUCUNE affectation pédagogique active dans la classe 5001 de l'élève -> ACCÈS REFUSÉ (403).
     */
    public function testTestB_FormerReporterWithoutActiveAssignmentForbidden(): void {
        $_SESSION['user'] = [
            'id' => self::$teacherB,
            'lycee_id' => 501,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['id'] = self::$eleveCEG;

        ob_start();
        $controller = new EleveController();
        try {
            $controller->discipline();
        } catch (Throwable $e) {
            // Forbidden expected
        }
        $output = ob_get_clean();

        $this->assertStringNotContainsString('MAT-5101', $output);
        $this->assertStringNotContainsString('INC-501-TEST', $output);
    }

    /**
     * TEST B2: Ancien enseignant sur ancienne classe de l'élève.
     * L'enseignant A avait un cours actif dans l'ancienne classe 5003 de l'élève l'année N-1 (annee_academique_id = 99), mais n'a AUCUNE affectation active dans la classe ACTUELLE 5003 (annee_academique_id = 1) de l'élève D -> ACCÈS REFUSÉ (403).
     */
    public function testTestB2_FormerClassTeacherInPreviousAcademicYearForbidden(): void {
        // Create historical enrollment for Student D (5103) in previous academic year 99
        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id) VALUES (5103, 5001, 99)");

        // Teacher A (5201) has an assignment in Class 5001 for current year 1, but Student 5103 is NOW in Class 5003 for current year 1
        $_SESSION['user'] = [
            'id' => self::$teacherA,
            'lycee_id' => 501,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['id'] = self::$eleveOther; // Student 5103 (currently in 5003 for year 1)

        ob_start();
        $controller = new EleveController();
        $controller->discipline();
        $output = ob_get_clean();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('Accès Refusé', $output);
        $this->assertStringNotContainsString('MAT-5103', $output);

        // Clean up temporary historical row
        self::$db->exec("DELETE FROM etudes WHERE eleve_id = 5103 AND annee_academique_id = 99");
    }

    /**
     * TEST C: CEG -> Lycée.
     * L'enseignant A a un scope pédagogique actif en CEG (Classe 5001).
     * Il tente d'accéder à l'élève B (5102) inscrit au Lycée (Classe 5002) -> ACCÈS REFUSÉ (403).
     */
    public function testTestC_CEGTeacherToLyceeStudentForbidden(): void {
        $_SESSION['user'] = [
            'id' => self::$teacherA,
            'lycee_id' => 501,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['id'] = self::$eleveLycee;

        ob_start();
        $controller = new EleveController();
        try {
            $controller->discipline();
        } catch (Throwable $e) {
            // Forbidden expected
        }
        $output = ob_get_clean();

        $this->assertStringNotContainsString('MAT-5102', $output);
    }

    /**
     * TEST D: Cross-tenant.
     * Utilisateur du Lycée A (501) tente d'accéder à l'élève du Lycée B (5104) -> ACCÈS REFUSÉ (403).
     */
    public function testTestD_CrossTenantForbidden(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 501,
            'role_name' => 'admin',
            'permissions' => ['eleve:view_all', 'discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['id'] = self::$eleveTenantB;

        ob_start();
        $controller = new EleveController();
        try {
            $controller->discipline();
        } catch (Throwable $e) {
            // Forbidden expected
        }
        $output = ob_get_clean();

        $this->assertStringNotContainsString('MAT-5104', $output);
    }

    /**
     * TEST E: IDOR hors classe.
     * L'enseignant A intervient dans la classe 5001 (6eme A).
     * Il tente d'accéder à l'élève 5103 inscrit dans la classe 5003 (5eme B) du même établissement -> ACCÈS REFUSÉ (403).
     */
    public function testTestE_IDOROtherClassForbidden(): void {
        $_SESSION['user'] = [
            'id' => self::$teacherA,
            'lycee_id' => 501,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_incidents', 'discipline:view_sanctions']
        ];

        $_GET['id'] = self::$eleveOther;

        ob_start();
        $controller = new EleveController();
        try {
            $controller->discipline();
        } catch (Throwable $e) {
            // Forbidden expected
        }
        $output = ob_get_clean();

        $this->assertStringNotContainsString('MAT-5103', $output);
    }

    public static function tearDownAfterClass(): void {
        self::$db->exec("DELETE FROM discipline_notifications WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_documents WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_historique WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_sanctions WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (501, 502))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (501, 502)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (5101, 5102, 5103, 5104)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (5101, 5102, 5103, 5104)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (5001, 5002, 5003, 5004)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 5301");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 5301");
    }
}
