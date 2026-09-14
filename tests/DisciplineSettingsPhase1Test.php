<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineTypeSanction.php';

class DisciplineSettingsPhase1Test extends TestCase {

    private static PDO $db;
    private static int $lyceeIdA = 101;
    private static int $lyceeIdB = 102;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Ensure clean test state for lycee 101 and 102
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE lycee_id IN (101, 102)");
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE lycee_id IN (101, 102)");
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
            'username' => 'testuser_' . $userId
        ];
        $_SESSION['lycee_id'] = $lyceeId;
    }

    public function testIncidentCreationValidationAndIsolation(): void {
        // 1. Authenticate as Admin of Lycee A
        $this->authenticateUser(901, 3, self::$lyceeIdA);

        // Save incident type for Lycee A
        $created = DisciplineTypeIncident::save([
            'code' => 'INSOLENCE',
            'libelle' => 'Insolence envers un enseignant',
            'niveau_gravite' => 'moyen',
            'actif' => 1
        ]);
        $this->assertTrue($created, "Incident type should be saved for Lycee A.");

        $incA = DisciplineTypeIncident::findByCode('INSOLENCE', self::$lyceeIdA);
        $this->assertIsArray($incA);
        $this->assertEquals('INSOLENCE', $incA['code']);
        $this->assertEquals('Insolence envers un enseignant', $incA['libelle']);

        // 2. Multi-tenant Isolation Check: Lycee B should NOT see Lycee A's incident
        $this->authenticateUser(902, 3, self::$lyceeIdB);
        $incB = DisciplineTypeIncident::findByCode('INSOLENCE', self::$lyceeIdB);
        $this->assertFalse($incB, "Lycee B must NOT see Lycee A's incident type.");

        // Lycee B creates its own INSOLENCE code (should be allowed without collision with Lycee A)
        $createdB = DisciplineTypeIncident::save([
            'code' => 'INSOLENCE',
            'libelle' => 'Insolence grave Lycee B',
            'niveau_gravite' => 'grave',
            'actif' => 1
        ]);
        $this->assertTrue($createdB, "Lycee B should be able to create same code independently.");

        // 3. Duplicate code collision within same Lycee
        $this->expectException(InvalidArgumentException::class);
        DisciplineTypeIncident::save([
            'code' => 'INSOLENCE',
            'libelle' => 'Duplicate attempt',
            'niveau_gravite' => 'mineur'
        ]);
    }

    public function testSanctionCreationValidationAndToggle(): void {
        // Authenticate as Admin of Lycee A
        $this->authenticateUser(901, 3, self::$lyceeIdA);

        // Save sanction type for Lycee A
        $created = DisciplineTypeSanction::save([
            'code' => 'EXCLUSION_TEMP',
            'libelle' => "Exclusion temporaire de l'établissement",
            'demande_duree_jours' => 1,
            'demande_heures' => 0,
            'affiche_sur_bulletin' => 1,
            'autorite_min_requise' => 'proviseur',
            'actif' => 1
        ]);
        $this->assertTrue($created, "Sanction type should be saved for Lycee A.");

        $sancA = DisciplineTypeSanction::findByCode('EXCLUSION_TEMP', self::$lyceeIdA);
        $this->assertIsArray($sancA);
        $this->assertEquals(1, (int)$sancA['demande_duree_jours']);
        $this->assertEquals(1, (int)$sancA['affiche_sur_bulletin']);
        $this->assertEquals('proviseur', $sancA['autorite_min_requise']);

        // Toggle Active
        $toggled = DisciplineTypeSanction::toggleActive($sancA['id']);
        $this->assertTrue($toggled);

        $sancAUpdated = DisciplineTypeSanction::findById($sancA['id'], self::$lyceeIdA);
        $this->assertEquals(0, (int)$sancAUpdated['actif']);

        // Cross-Tenant Toggle Attack Prevention
        $this->authenticateUser(902, 3, self::$lyceeIdB);
        $attackToggled = DisciplineTypeSanction::toggleActive($sancA['id']);
        $this->assertTrue($attackToggled); // Query should affect 0 rows because of lycee_id filter

        // Verify status was NOT changed for Lycee A
        $sancACheck = DisciplineTypeSanction::findById($sancA['id'], self::$lyceeIdA);
        $this->assertEquals(0, (int)$sancACheck['actif'], "Lycee B must NOT be able to toggle Lycee A's sanction.");
    }
}
?>