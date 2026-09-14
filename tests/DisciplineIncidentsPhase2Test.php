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
    private static int $classeIdA;
    private static int $eleveId1;
    private static int $eleveId2;
    private static int $eleveIdLyceeB;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean tables
        self::$db->exec("DELETE FROM discipline_incident_eleves");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (201, 202)");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE lycee_id IN (201, 202)");

        // Fetch active academic year and class for lycee
        $stmtAnnee = self::$db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $anneeRow = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        self::$anneeIdA = $anneeRow ? (int)$anneeRow['id'] : 1;

        $stmtClasse = self::$db->query("SELECT id_classe, lycee_id FROM classes LIMIT 1");
        $classeRow = $stmtClasse->fetch(PDO::FETCH_ASSOC);
        self::$classeIdA = $classeRow ? (int)$classeRow['id_classe'] : 1;
        self::$lyceeIdA = $classeRow ? (int)$classeRow['lycee_id'] : 201;

        // Seed Incident Type
        self::$db->exec("INSERT INTO discipline_types_incidents (id, lycee_id, code, libelle, niveau_gravite, actif) VALUES (2100, " . self::$lyceeIdA . ", 'BAGARRE', 'Bagarre physique', 'grave', 1)");
        self::$typeIncIdA = 2100;

        // Seed Eleves for testing
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (2101, " . self::$lyceeIdA . ", 'DUPONT', 'Jean'), (2102, " . self::$lyceeIdA . ", 'DURAND', 'Paul'), (2201, 9999, 'ATTACKER', 'Hacker')");
        self::$eleveId1 = 2101;
        self::$eleveId2 = 2102;
        self::$eleveIdLyceeB = 2201;

        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (2101, " . self::$classeIdA . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1), (2102, " . self::$classeIdA . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1)");
    }

    protected function tearDown(): void {
        unset($_SESSION['user']);
        unset($_SESSION['lycee_id']);
    }

    private function authenticateUser(int $userId, int $roleId, int $lyceeId): void {
        $_SESSION['user'] = [
            'id_user' => $userId,
            'role_id' => $roleId,
            'lycee_id' => $lyceeId,
            'username' => 'user_' . $userId
        ];
        $_SESSION['lycee_id'] = $lyceeId;
    }

    public function testAtomicMultiStudentIncidentCreation(): void {
        $this->authenticateUser(901, 3, self::$lyceeIdA);

        $incidentData = [
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-15',
            'heure_incident' => '10:30',
            'lieu' => 'Cour de récréation',
            'description_faits' => 'Altération verbale ayant dégénéré en bagarre',
            'annee_academique_id' => self::$anneeIdA
        ];

        $elevesData = [
            [
                'eleve_id' => self::$eleveId1,
                'role_implication' => 'auteur_principal',
                'observation_individuelle' => 'A porté le premier coup'
            ],
            [
                'eleve_id' => self::$eleveId2,
                'role_implication' => 'co_auteur',
                'observation_individuelle' => 'A répliqué immédiatement'
            ]
        ];

        $incidentId = DisciplineIncident::create($incidentData, $elevesData);
        $this->assertGreaterThan(0, $incidentId);

        $incident = DisciplineIncident::findById($incidentId, self::$lyceeIdA);
        $this->assertIsArray($incident);
        $this->assertEquals('BAGARRE', $incident['type_code']);
        $this->assertEquals('signale', $incident['statut']);
        $this->assertCount(2, $incident['eleves']);

        // Check captured historical class_id
        $this->assertEquals(self::$classeIdA, (int)$incident['eleves'][0]['classe_id']);
        $this->assertEquals('auteur_principal', $incident['eleves'][0]['role_implication']);
        $this->assertEquals('co_auteur', $incident['eleves'][1]['role_implication']);
    }

    public function testCrossTenantAttackRejection(): void {
        $this->authenticateUser(901, 3, self::$lyceeIdA);

        $incidentData = [
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-16',
            'description_faits' => 'Tentative de falsification',
            'annee_academique_id' => self::$anneeIdA
        ];

        // Attempting to include an eleve from Lycee B into Lycee A incident
        $elevesDataAttack = [
            [
                'eleve_id' => self::$eleveIdLyceeB,
                'role_implication' => 'auteur_principal'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        DisciplineIncident::create($incidentData, $elevesDataAttack);
    }

    public function testWorkflowStatusTransitions(): void {
        $this->authenticateUser(901, 3, self::$lyceeIdA);

        $incidentData = [
            'type_incident_id' => self::$typeIncIdA,
            'date_incident' => '2024-02-17',
            'description_faits' => 'Insolence en classe',
            'annee_academique_id' => self::$anneeIdA
        ];

        $elevesData = [
            ['eleve_id' => self::$eleveId1, 'role_implication' => 'auteur_principal']
        ];

        $incidentId = DisciplineIncident::create($incidentData, $elevesData);

        // Transition: signale -> en_instruction
        $res1 = DisciplineIncident::updateStatus($incidentId, 'en_instruction');
        $this->assertTrue($res1);

        $inc1 = DisciplineIncident::findById($incidentId, self::$lyceeIdA);
        $this->assertEquals('en_instruction', $inc1['statut']);

        // Transition: en_instruction -> traite
        $res2 = DisciplineIncident::updateStatus($incidentId, 'traite');
        $this->assertTrue($res2);

        $inc2 = DisciplineIncident::findById($incidentId, self::$lyceeIdA);
        $this->assertEquals('traite', $inc2['statut']);

        // Blocked Transition: once closed ('traite'), cannot change back to 'en_instruction'
        $this->expectException(InvalidArgumentException::class);
        DisciplineIncident::updateStatus($incidentId, 'en_instruction');
    }
}
?>