<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineIncident.php';
require_once __DIR__ . '/../src/models/DisciplineConseil.php';
require_once __DIR__ . '/../src/models/DisciplineConseilMembre.php';
require_once __DIR__ . '/../src/models/DisciplineConseilEleve.php';
require_once __DIR__ . '/../src/controllers/DisciplineConseilController.php';

class DisciplineConseilPhase61Test extends TestCase {

    private static PDO $db;
    private static int $lyceeA = 801;
    private static int $lyceeB = 802;
    private static int $classeCEG = 8001; // CEG Class in Lycee A (active year 1)
    private static int $classeLycee = 8002; // Lycée Class in Lycee A (active year 1)
    private static int $classeTenantB = 8003; // Class in Lycee B
    private static int $eleveA = 8101; // Student in Class 8001
    private static int $eleveB = 8102; // Student in Class 8002
    private static int $eleveTenantB = 8103; // Student in Lycee B
    private static int $teacherA = 8201; // Teacher assigned to CEG 8001
    private static int $presidentA = 8202; // President in Lycee A
    private static int $typeIncId;
    private static int $inc1;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean test records
        self::$db->exec("DELETE FROM discipline_notifications WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_documents WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_historique WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_conseil_incidents WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_conseil_eleves WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_conseil_membres WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_conseils WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (8101, 8102, 8103)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (8101, 8102, 8103)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (8001, 8002, 8003)");
        self::$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8201, 8202)");

        // Create test classes
        self::$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, nom_classe, niveau, serie, numero) VALUES
            (8001, 801, 1, '6eme A', '6eme', 'G', '01'),
            (8002, 801, 2, '2nde C', '2nde', 'C', '01'),
            (8003, 802, 1, '6eme Z', '6eme', 'G', '01')
        ");

        // Create test students
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, cycle_id, matricule, nom, prenom, sexe, actif) VALUES
            (8101, 801, 1, 'MAT-8101', 'KOUASSI', 'Jean', 'M', 1),
            (8102, 801, 2, 'MAT-8102', 'BAMBA', 'Awa', 'F', 1),
            (8103, 802, 1, 'MAT-8103', 'BAH', 'Fatou', 'F', 1)
        ");

        // Enrollments for active academic year (id = 1)
        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id) VALUES
            (8101, 8001, 1),
            (8102, 8002, 1),
            (8103, 8003, 1)
        ");

        // Create test staff users
        self::$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, function, actif) VALUES
            (8201, 801, 'KOFFI', 'Enseignant', 'ens8201@sgs.ci', 6, 'Professeur de Maths', 1),
            (8202, 801, 'DIBY', 'Proviseur', 'pres8202@sgs.ci', 4, 'Proviseur', 1)
        ");

        // Active pedagogical assignment for Teacher A (8201) in CEG Class 8001
        self::$db->exec("INSERT INTO affectations_pedagogiques (id, lycee_id, annee_academique_id, enseignant_id, classe_id, matiere_id, statut, date_debut) VALUES
            (8901, 801, 1, 8201, 8001, 1, 'actif', '2023-09-01')
        ");

        // Type Incident
        self::$typeIncId = DisciplineTypeIncident::create([
            'id' => 8301,
            'lycee_id' => 801,
            'code' => 'TEST_INC_61',
            'libelle' => 'Insubordination Test 61',
            'niveau_gravite' => 'Grave',
            'actif' => 1,
        ]);

        // Incident in Lycee A Class 8001 involving Student 8101
        self::$inc1 = DisciplineIncident::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'INC-801-01',
            'type_incident_id' => self::$typeIncId,
            'date_incident' => date('Y-m-d', strtotime('-2 days')),
            'heure_incident' => '08:00:00',
            'lieu' => 'Classe 6eme A',
            'description' => 'Incident Test Phase 6.1',
            'statut' => 'traite',
            'signale_par_user_id' => 1,
        ], [
            ['eleve_id' => self::$eleveA, 'role_implication' => 'auteur_principal', 'classe_id' => 8001],
        ]);
    }

    protected function setUp(): void {
        Auth::logout();
        unset($_GET['id'], $_GET['annee_academique_id']);
    }

    /**
     * TEST A: Valid council creation with active academic year
     */
    public function testA_CreateValidCouncilSuccess(): void {
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => 801,
            'role_name' => 'admin',
            'permissions' => ['discipline:manage_councils', 'discipline:view_councils']
        ];

        $councilId = DisciplineConseil::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'CD-801-TESTA',
            'titre' => 'Conseil de Test A',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => self::$presidentA,
            'statut' => 'planifie'
        ]);

        $this->assertGreaterThan(0, $councilId);
        $fetched = DisciplineConseil::findById($councilId, 801);
        $this->assertSame('CD-801-TESTA', $fetched['code']);
        $this->assertSame('planifie', $fetched['statut']);
    }

    /**
     * TEST B: Multi-tenant isolation server-side
     */
    public function testB_MultiTenantIsolationForbidden(): void {
        $_SESSION['user'] = [
            'id' => 2,
            'lycee_id' => 802, // User in Lycee B
            'role_name' => 'admin',
            'permissions' => ['discipline:manage_councils', 'discipline:view_councils']
        ];

        $councilId = DisciplineConseil::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'CD-801-TESTB',
            'titre' => 'Conseil de Test B',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => self::$presidentA,
            'statut' => 'planifie'
        ]);

        // Lycee B user trying to view Lycee A council -> null or forbidden
        $fetched = DisciplineConseil::findById($councilId, 802);
        $this->assertNull($fetched);
    }

    /**
     * TEST C: Academic Year enforcement - Server resolves active academic year
     */
    public function testC_AcademicYearActiveServerResolution(): void {
        $activeYear = AnneeAcademique::findActive();
        $this->assertNotEmpty($activeYear['id']);
        $this->assertSame(1, (int)$activeYear['id']);
    }

    /**
     * TEST D: Member addition with snapshots and duplicate prevention
     */
    public function testD_AddMembreWithSnapshotsAndDuplicatePrevention(): void {
        $councilId = DisciplineConseil::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'CD-801-TESTD',
            'titre' => 'Conseil de Test D',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => self::$presidentA,
            'statut' => 'planifie'
        ]);

        $membreId = DisciplineConseilMembre::addMembre([
            'conseil_id' => $councilId,
            'user_id' => self::$teacherA,
            'qualite_membre' => 'representant_enseignant',
            'a_droit_vote' => 1,
            'est_present' => 1
        ]);

        $this->assertGreaterThan(0, $membreId);
        $membres = DisciplineConseilMembre::findByConseilId($councilId);
        $this->assertCount(1, $membres);
        $this->assertSame('Enseignant KOFFI', $membres[0]['nom_snapshot']);

        // Duplicate addition MUST throw InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        DisciplineConseilMembre::addMembre([
            'conseil_id' => $councilId,
            'user_id' => self::$teacherA,
            'qualite_membre' => 'representant_enseignant'
        ]);
    }

    /**
     * TEST E: Student convocation with server-resolved class snapshot & duplicate prevention
     */
    public function testE_AddEleveWithServerResolvedClassSnapshotAndDuplicatePrevention(): void {
        $councilId = DisciplineConseil::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'CD-801-TESTE',
            'titre' => 'Conseil de Test E',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => self::$presidentA,
            'statut' => 'planifie'
        ]);

        $eleveConvId = DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => self::$eleveA,
            'motif_convocation' => 'Motif de test E'
        ]);

        $this->assertGreaterThan(0, $eleveConvId);
        $eleves = DisciplineConseilEleve::findByConseilId($councilId);
        $this->assertCount(1, $eleves);
        $this->assertSame(8001, (int)$eleves[0]['classe_id']); // Server-resolved class 8001

        // Duplicate student convocation MUST throw InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => self::$eleveA,
            'motif_convocation' => 'Motif doublon'
        ]);
    }

    /**
     * TEST F: Incident-Student link verification: Only allow linking if student is genuinely implicated in incident
     */
    public function testF_LinkIncidentStudentImplicationVerification(): void {
        $councilId = DisciplineConseil::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'CD-801-TESTF',
            'titre' => 'Conseil de Test F',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => self::$presidentA,
            'statut' => 'planifie'
        ]);

        // Convoke Student A and Student B
        DisciplineConseilEleve::addEleve(['conseil_id' => $councilId, 'eleve_id' => self::$eleveA, 'motif_convocation' => 'Motif A']);
        DisciplineConseilEleve::addEleve(['conseil_id' => $councilId, 'eleve_id' => self::$eleveB, 'motif_convocation' => 'Motif B']);

        // Valid Link: Student A IS implicated in Incident 1
        $linked = DisciplineConseilEleve::linkIncident($councilId, self::$inc1, self::$eleveA);
        $this->assertTrue($linked);

        // Invalid Link: Student B is NOT implicated in Incident 1 -> MUST throw InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        DisciplineConseilEleve::linkIncident($councilId, self::$inc1, self::$eleveB);
    }

    /**
     * TEST G: Status transitions & Immutability when canceled/closed
     */
    public function testG_StatusTransitionsAndImmutability(): void {
        $councilId = DisciplineConseil::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'CD-801-TESTG',
            'titre' => 'Conseil de Test G',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => self::$presidentA,
            'statut' => 'planifie'
        ]);

        // Transition: planifie -> convoque
        $this->assertTrue(DisciplineConseil::updateStatus($councilId, 'convoque', 1));

        // Transition: convoque -> annule
        $this->assertTrue(DisciplineConseil::updateStatus($councilId, 'annule', 1));

        // Attempt transition on canceled council MUST throw InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        DisciplineConseil::updateStatus($councilId, 'en_session', 1);
    }

    /**
     * TEST H: Teacher scope restriction and NO bypass for former incident reporters
     */
    public function testH_TeacherScopeRestrictionNoReporterBypass(): void {
        $councilId = DisciplineConseil::create([
            'lycee_id' => 801,
            'annee_academique_id' => 1,
            'code' => 'CD-801-TESTH',
            'titre' => 'Conseil de Test H',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => self::$presidentA,
            'statut' => 'planifie'
        ]);

        // Convoke Student B (Lycée 8002)
        DisciplineConseilEleve::addEleve(['conseil_id' => $councilId, 'eleve_id' => self::$eleveB, 'motif_convocation' => 'Motif B']);

        // Teacher A (8201) is assigned to CEG (8001) only and is NOT a member of Council H
        $_SESSION['user'] = [
            'id' => self::$teacherA,
            'lycee_id' => 801,
            'role_name' => 'enseignant',
            'permissions' => ['discipline:view_councils']
        ];

        $_GET['id'] = $councilId;

        ob_start();
        $controller = new DisciplineConseilController();
        try {
            $controller->show();
        } catch (Throwable $e) {}
        $output = ob_get_clean();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('Accès Refusé', $output);
    }

    /**
     * TEST J: Phase 4 table extensions (discipline_documents & discipline_notifications) contain conseil_id
     */
    public function testJ_Phase4TableExtensionsContainConseilId(): void {
        $stmtDoc = self::$db->query("PRAGMA table_info(discipline_documents)");
        $docCols = array_column($stmtDoc->fetchAll(PDO::FETCH_ASSOC), 'name');
        $this->assertContains('conseil_id', $docCols);

        $stmtNotif = self::$db->query("PRAGMA table_info(discipline_notifications)");
        $notifCols = array_column($stmtNotif->fetchAll(PDO::FETCH_ASSOC), 'name');
        $this->assertContains('conseil_id', $notifCols);
    }

    public static function tearDownAfterClass(): void {
        self::$db->exec("DELETE FROM discipline_notifications WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_documents WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_historique WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_conseil_incidents WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_conseil_eleves WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_conseil_membres WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_conseils WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM discipline_incident_eleves WHERE incident_id IN (SELECT id FROM discipline_incidents WHERE lycee_id IN (801, 802))");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE lycee_id IN (801, 802)");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (8101, 8102, 8103)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (8101, 8102, 8103)");
        self::$db->exec("DELETE FROM classes WHERE id_classe IN (8001, 8002, 8003)");
        self::$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8201, 8202)");
    }
}
