<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineIncident.php';
require_once __DIR__ . '/../src/models/DisciplineIncidentEleve.php';

class DisciplineIncidentsPhase2Test extends TestCase {

    private static PDO $db;
    private static int $lyceeIdA = 201;
    private static int $typeIncIdA;
    private static int $anneeIdA;
    private static int $classeCeg;
    private static int $classeLycee;
    private static int $teacherCeg;
    private static int $teacherLycee;
    private static int $eleveCeg;
    private static int $eleveLycee;
    private static int $eleveIdLyceeB;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean tables
        self::$db->exec("DELETE FROM discipline_incident_eleves");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (201, 202)");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE lycee_id IN (201, 202)");
        self::$db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id IN (9001, 9002)");

        // Fetch active academic year
        $stmtAnnee = self::$db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $anneeRow = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        self::$anneeIdA = $anneeRow ? (int)$anneeRow['id'] : 1;

        // Fetch two distinct classes (CEG & Lycee) for school
        $stmtClasses = self::$db->query("SELECT id_classe, lycee_id FROM classes LIMIT 2");
        $classesRows = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
        self::$classeCeg = (int)$classesRows[0]['id_classe'];
        self::$classeLycee = isset($classesRows[1]) ? (int)$classesRows[1]['id_classe'] : (self::$classeCeg + 1);
        self::$lyceeIdA = (int)$classesRows[0]['lycee_id'];

        // Seed Teachers
        $stmtUserCheck1 = self::$db->prepare("SELECT id_user FROM utilisateurs WHERE id_user = 9001");
        $stmtUserCheck1->execute();
        if (!$stmtUserCheck1->fetch()) {
            self::$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, identifiant_public, nom, prenom, email, mot_de_passe) VALUES (9001, " . self::$lyceeIdA . ", 6, 'PROF-CEG', 'Prof', 'CEG', 'profceg@test.com', 'hash'), (9002, " . self::$lyceeIdA . ", 6, 'PROF-LYCEE', 'Prof', 'LYCEE', 'proflycee@test.com', 'hash')");
        }
        self::$teacherCeg = 9001;
        self::$teacherLycee = 9002;

        // Seed Pedagogical Assignments: Teacher 9001 assigned to CEG class, Teacher 9002 assigned to Lycee class
        self::$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut) VALUES (9001, " . self::$classeCeg . ", 1, " . self::$anneeIdA . ", '2023-09-01', 'actif')");
        self::$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut) VALUES (9002, " . self::$classeLycee . ", 1, " . self::$anneeIdA . ", '2023-09-01', 'actif')");

        // Seed Incident Type
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 2100");
        self::$db->exec("INSERT INTO discipline_types_incidents (id, lycee_id, code, libelle, niveau_gravite, actif) VALUES (2100, " . self::$lyceeIdA . ", 'BAGARRE_P2_SEC', 'Bagarre physique', 'grave', 1)");
        self::$typeIncIdA = 2100;

        // Seed Eleves
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (2101, 2102, 2201)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (2101, 2102, 2201)");
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (2101, " . self::$lyceeIdA . ", 'ELEVE-CEG', 'Jean'), (2102, " . self::$lyceeIdA . ", 'ELEVE-LYCEE', 'Paul'), (2201, 9999, 'ATTACKER', 'Hacker')");
        self::$eleveCeg = 2101;
        self::$eleveLycee = 2102;
        self::$eleveIdLyceeB = 2201;

        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (2101, " . self::$classeCeg . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1), (2102, " . self::$classeLycee . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1)");
    }

    protected function tearDown(): void {
        unset($_SESSION['user']);
        unset($_SESSION['lycee_id']);
    }

    private function authenticateUser(int $userId, int $roleId, int $lyceeId): void {
        $_SESSION['user'] = [
            'id' => $userId,
            'id_user' => $userId,
            'role_id' => $roleId,
            'lycee_id' => $lyceeId,
            'username' => 'user_' . $userId
        ];
        $_SESSION['lycee_id'] = $lyceeId;
    }

    public function testTeacherReportingScopeIsolation(): void {
        // CEG Teacher (9001) attempts to report incident for CEG student -> SUCCESS
        $this->authenticateUser(self::$teacherCeg, 6, self::$lyceeIdA);

        $incidentData = [
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-15',
            'description_faits' => 'Bagarre CEG',
            'annee_academique_id' => self::$anneeIdA
        ];

        $elevesCeg = [['eleve_id' => self::$eleveCeg, 'role_implication' => 'auteur_principal']];
        $incId = DisciplineIncident::create($incidentData, $elevesCeg, true, [self::$classeCeg]);
        $this->assertGreaterThan(0, $incId);

        // CEG Teacher attempts to report incident for Lycee student (out of scope class) -> REJECTED
        $elevesLycee = [['eleve_id' => self::$eleveLycee, 'role_implication' => 'auteur_principal']];
        $this->expectException(InvalidArgumentException::class);
        DisciplineIncident::create($incidentData, $elevesLycee, true, [self::$classeCeg]);
    }

    public function testTeacherConsultationScope(): void {
        // CEG Teacher (9001) searches incidents
        $this->authenticateUser(self::$teacherCeg, 6, self::$lyceeIdA);

        // Incident in CEG class reported by Lycee teacher (9002)
        $this->authenticateUser(self::$teacherLycee, 6, self::$lyceeIdA);
        $incIdCeg = DisciplineIncident::create([
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-16',
            'description_faits' => 'Incident en 6e signale par collègue',
            'annee_academique_id' => self::$anneeIdA
        ], [['eleve_id' => self::$eleveCeg, 'role_implication' => 'auteur_principal']]);

        // CEG Teacher (9001) should be able to consult CEG class incident even if reported by 9002
        $this->authenticateUser(self::$teacherCeg, 6, self::$lyceeIdA);
        $incidentsCeg = DisciplineIncident::search([], self::$lyceeIdA, true, self::$teacherCeg, [self::$classeCeg]);

        $found = false;
        foreach ($incidentsCeg as $inc) {
            if ((int)$inc['id'] === $incIdCeg) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Teacher should see incidents in assigned CEG class reported by colleague.");

        // Incident in Lycee class reported by Lycee teacher
        $this->authenticateUser(self::$teacherLycee, 6, self::$lyceeIdA);
        $incIdLycee = DisciplineIncident::create([
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-16',
            'description_faits' => 'Incident en Terminale',
            'annee_academique_id' => self::$anneeIdA
        ], [['eleve_id' => self::$eleveLycee, 'role_implication' => 'auteur_principal']]);

        // CEG Teacher (9001) MUST NOT see Lycee class incident
        $this->authenticateUser(self::$teacherCeg, 6, self::$lyceeIdA);
        $incidentsCegStrict = DisciplineIncident::search([], self::$lyceeIdA, true, self::$teacherCeg, [self::$classeCeg]);

        $foundForbidden = false;
        foreach ($incidentsCegStrict as $inc) {
            if ((int)$inc['id'] === $incIdLycee) {
                $foundForbidden = true;
                break;
            }
        }
        $this->assertFalse($foundForbidden, "CEG Teacher MUST NOT see Lycee class incident.");
    }

    public function testTeacherEditingOwnershipAndStatusLock(): void {
        // Teacher 9001 creates incident
        $this->authenticateUser(self::$teacherCeg, 6, self::$lyceeIdA);
        $incId = DisciplineIncident::create([
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-17',
            'description_faits' => 'Insolence initiale',
            'annee_academique_id' => self::$anneeIdA
        ], [['eleve_id' => self::$eleveCeg, 'role_implication' => 'auteur_principal']], true, [self::$classeCeg]);

        // Author (9001) updates their own incident while 'signale' -> SUCCESS
        $updated = DisciplineIncident::update($incId, [
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-17',
            'description_faits' => 'Insolence initiale - texte corrige'
        ], [['eleve_id' => self::$eleveCeg, 'role_implication' => 'auteur_principal']], true, self::$teacherCeg, [self::$classeCeg]);
        $this->assertTrue($updated);

        // Other teacher (9002) attempts to edit 9001's incident -> REJECTED
        $this->expectException(InvalidArgumentException::class);
        DisciplineIncident::update($incId, [
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-17',
            'description_faits' => 'Hacked by colleague'
        ], [['eleve_id' => self::$eleveCeg, 'role_implication' => 'auteur_principal']], true, self::$teacherLycee, [self::$classeLycee]);
    }
}
?>