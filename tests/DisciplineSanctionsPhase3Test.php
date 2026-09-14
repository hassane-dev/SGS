<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineTypeSanction.php';
require_once __DIR__ . '/../src/models/DisciplineIncident.php';
require_once __DIR__ . '/../src/models/DisciplineSanction.php';

class DisciplineSanctionsPhase3Test extends TestCase {

    private static PDO $db;
    private static int $lyceeIdA;
    private static int $anneeIdA;
    private static int $classeIdA;
    private static int $typeIncId;
    private static int $typeSancId;
    private static int $eleve1;
    private static int $eleve2;
    private static int $eleveNonImplique;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean tables
        self::$db->exec("DELETE FROM discipline_sanctions");
        self::$db->exec("DELETE FROM discipline_incident_eleves");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (301, 302)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 3200");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 3100");
        self::$db->exec("DELETE FROM etudes WHERE eleve_id IN (3101, 3102, 3103)");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve IN (3101, 3102, 3103)");

        // Get active academic year & class
        $stmtAnnee = self::$db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $anneeRow = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        self::$anneeIdA = $anneeRow ? (int)$anneeRow['id'] : 1;

        $stmtClasse = self::$db->query("SELECT id_classe, lycee_id FROM classes LIMIT 1");
        $classeRow = $stmtClasse->fetch(PDO::FETCH_ASSOC);
        self::$classeIdA = (int)$classeRow['id_classe'];
        self::$lyceeIdA = (int)$classeRow['lycee_id'];

        // Seed Incident Type
        self::$db->exec("INSERT INTO discipline_types_incidents (id, lycee_id, code, libelle, niveau_gravite, actif) VALUES (3100, " . self::$lyceeIdA . ", 'BAGARRE_P3', 'Bagarre', 'grave', 1)");
        self::$typeIncId = 3100;

        // Seed Sanction Type
        self::$db->exec("INSERT INTO discipline_types_sanctions (id, lycee_id, code, libelle, demande_duree_jours, demande_heures, affiche_sur_bulletin, autorite_min_requise, actif) VALUES (3200, " . self::$lyceeIdA . ", 'EXCLUSION_3J', 'Exclusion 3 jours', 1, 0, 1, 'censeur', 1)");
        self::$typeSancId = 3200;

        // Seed Eleves
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (3101, " . self::$lyceeIdA . ", 'ALPHA', 'Eleve1'), (3102, " . self::$lyceeIdA . ", 'BETA', 'Eleve2'), (3103, " . self::$lyceeIdA . ", 'GAMMA', 'EleveNonImplique')");
        self::$eleve1 = 3101;
        self::$eleve2 = 3102;
        self::$eleveNonImplique = 3103;

        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (3101, " . self::$classeIdA . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1), (3102, " . self::$classeIdA . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1), (3103, " . self::$classeIdA . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1)");
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

    public function testDirectSanctionCreationWithoutIncident(): void {
        $this->authenticateUser(9001, 3, self::$lyceeIdA); // Admin Local

        $sanctionData = [
            'eleve_id' => self::$eleve1,
            'type_sanction_id' => self::$typeSancId,
            'motif' => 'Avertissement direct de la direction',
            'date_decision' => '2024-02-18',
            'duree_jours' => 3,
            'annee_academique_id' => self::$anneeIdA
        ];

        $sancId = DisciplineSanction::create($sanctionData);
        $this->assertGreaterThan(0, $sancId);

        $sanction = DisciplineSanction::findById($sancId, self::$lyceeIdA);
        $this->assertIsArray($sanction);
        $this->assertNull($sanction['incident_id']);
        $this->assertEquals('EXCLUSION_3J', $sanction['type_code']);
        $this->assertEquals('prononcee', $sanction['statut']);
        $this->assertEquals(self::$classeIdA, (int)$sanction['classe_id']);
    }

    public function testSanctionLinkedToIncidentAndNonInvolvedStudentRejection(): void {
        $this->authenticateUser(9001, 3, self::$lyceeIdA);

        // Create Incident involving eleve1 and eleve2
        $incId = DisciplineIncident::create([
            'type_incident_id' => self::$typeIncId,
            'date_incident' => '2024-02-19',
            'description_faits' => 'Altération physique',
            'annee_academique_id' => self::$anneeIdA
        ], [
            ['eleve_id' => self::$eleve1, 'role_implication' => 'auteur_principal'],
            ['eleve_id' => self::$eleve2, 'role_implication' => 'co_auteur']
        ]);

        // Create Sanction for involved Eleve 1 -> SUCCESS
        $sancId1 = DisciplineSanction::create([
            'incident_id' => $incId,
            'eleve_id' => self::$eleve1,
            'type_sanction_id' => self::$typeSancId,
            'motif' => 'Auteur principal bagarre',
            'date_decision' => '2024-02-19',
            'duree_jours' => 3,
            'annee_academique_id' => self::$anneeIdA
        ]);
        $this->assertGreaterThan(0, $sancId1);

        // Attempt Sanction for non-involved Eleve 3 for this incident -> REJECTED
        $this->expectException(InvalidArgumentException::class);
        DisciplineSanction::create([
            'incident_id' => $incId,
            'eleve_id' => self::$eleveNonImplique,
            'type_sanction_id' => self::$typeSancId,
            'motif' => 'Attribution arbitraire',
            'date_decision' => '2024-02-19',
            'duree_jours' => 3,
            'annee_academique_id' => self::$anneeIdA
        ]);
    }

    public function testStatusTransitionsAndLock(): void {
        $this->authenticateUser(9001, 3, self::$lyceeIdA);

        $sancId = DisciplineSanction::create([
            'eleve_id' => self::$eleve2,
            'type_sanction_id' => self::$typeSancId,
            'motif' => 'Test transition',
            'date_decision' => '2024-02-20',
            'duree_jours' => 2,
            'annee_academique_id' => self::$anneeIdA
        ]);

        // Transition: prononcee -> en_cours
        $res1 = DisciplineSanction::updateStatus($sancId, 'en_cours');
        $this->assertTrue($res1);

        $sanc1 = DisciplineSanction::findById($sancId, self::$lyceeIdA);
        $this->assertEquals('en_cours', $sanc1['statut']);

        // Transition: en_cours -> executee
        $res2 = DisciplineSanction::updateStatus($sancId, 'executee');
        $this->assertTrue($res2);

        $sanc2 = DisciplineSanction::findById($sancId, self::$lyceeIdA);
        $this->assertEquals('executee', $sanc2['statut']);

        // Attempt invalid transition back from 'executee' to 'en_cours' -> REJECTED
        $this->expectException(InvalidArgumentException::class);
        DisciplineSanction::updateStatus($sancId, 'en_cours');
    }
}
?>