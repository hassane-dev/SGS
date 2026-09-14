<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineTypeSanction.php';
require_once __DIR__ . '/../src/models/DisciplineIncident.php';
require_once __DIR__ . '/../src/models/DisciplineSanction.php';
require_once __DIR__ . '/../src/models/DisciplineHistorique.php';
require_once __DIR__ . '/../src/models/DisciplineDocument.php';
require_once __DIR__ . '/../src/models/DisciplineNotification.php';

class DisciplinePhase4Test extends TestCase {

    private static PDO $db;
    private static int $lyceeIdA;
    private static int $anneeIdA;
    private static int $classeIdA;
    private static int $typeIncId;
    private static int $typeSancId;
    private static int $eleve1;

    public static function setUpBeforeClass(): void {
        self::$db = Database::getInstance();
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Clean Phase 4 tables
        self::$db->exec("DELETE FROM discipline_notifications");
        self::$db->exec("DELETE FROM discipline_documents");
        self::$db->exec("DELETE FROM discipline_historique");
        self::$db->exec("DELETE FROM discipline_sanctions");
        self::$db->exec("DELETE FROM discipline_incident_eleves");
        self::$db->exec("DELETE FROM discipline_incidents WHERE lycee_id IN (401, 402)");

        // Get active academic year & class
        $stmtAnnee = self::$db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $anneeRow = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        self::$anneeIdA = $anneeRow ? (int)$anneeRow['id'] : 1;

        $stmtClasse = self::$db->query("SELECT id_classe, lycee_id FROM classes LIMIT 1");
        $classeRow = $stmtClasse->fetch(PDO::FETCH_ASSOC);
        self::$classeIdA = (int)$classeRow['id_classe'];
        self::$lyceeIdA = (int)$classeRow['lycee_id'];

        // Seed Incident & Sanction Types
        self::$db->exec("DELETE FROM discipline_types_sanctions WHERE id = 4200");
        self::$db->exec("DELETE FROM discipline_types_incidents WHERE id = 4100");
        self::$db->exec("INSERT INTO discipline_types_incidents (id, lycee_id, code, libelle, niveau_gravite, actif) VALUES (4100, " . self::$lyceeIdA . ", 'INC_P4', 'Incident P4', 'moyen', 1)");
        self::$db->exec("INSERT INTO discipline_types_sanctions (id, lycee_id, code, libelle, demande_duree_jours, demande_heures, affiche_sur_bulletin, autorite_min_requise, actif) VALUES (4200, " . self::$lyceeIdA . ", 'SANC_P4', 'Sanction P4', 0, 0, 0, 'censeur', 1)");
        self::$typeIncId = 4100;
        self::$typeSancId = 4200;

        // Seed Eleve
        self::$db->exec("DELETE FROM etudes WHERE eleve_id = 4101");
        self::$db->exec("DELETE FROM eleves WHERE id_eleve = 4101");
        self::$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (4101, " . self::$lyceeIdA . ", 'TESTER', 'Phase4')");
        self::$eleve1 = 4101;
        self::$db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (4101, " . self::$classeIdA . ", " . self::$lyceeIdA . ", " . self::$anneeIdA . ", 'inscrit', 1)");
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

    public function testAutomaticAuditLoggingForIncidentAndSanction(): void {
        $this->authenticateUser(9001, 3, self::$lyceeIdA);

        // 1. Create Incident -> Audit Logged
        $incId = DisciplineIncident::create([
            'type_incident_id' => self::$typeIncId,
            'date_incident' => '2024-02-20',
            'description_faits' => 'Audit logging test incident',
            'annee_academique_id' => self::$anneeIdA
        ], [['eleve_id' => self::$eleve1, 'role_implication' => 'auteur_principal']]);

        $histInc = DisciplineHistorique::findByTarget('incident', $incId, self::$lyceeIdA);
        $this->assertNotEmpty($histInc);
        $this->assertEquals('CREATION_INCIDENT', $histInc[0]['action']);

        // 2. Change Incident Status -> Audit Logged
        DisciplineIncident::updateStatus($incId, 'en_instruction');
        $histInc2 = DisciplineHistorique::findByTarget('incident', $incId, self::$lyceeIdA);
        $this->assertGreaterThanOrEqual(2, count($histInc2));
        $this->assertEquals('CHANGEMENT_STATUT_INCIDENT', $histInc2[0]['action']);
        $this->assertEquals('signale', $histInc2[0]['statut_avant']);
        $this->assertEquals('en_instruction', $histInc2[0]['statut_apres']);

        // 3. Create Sanction -> Audit Logged
        $sancId = DisciplineSanction::create([
            'incident_id' => $incId,
            'eleve_id' => self::$eleve1,
            'type_sanction_id' => self::$typeSancId,
            'motif' => 'Audit logging test sanction',
            'date_decision' => '2024-02-20',
            'annee_academique_id' => self::$anneeIdA
        ]);

        $histSanc = DisciplineHistorique::findByTarget('sanction', $sancId, self::$lyceeIdA);
        $this->assertNotEmpty($histSanc);
        $this->assertEquals('PRONONCE_SANCTION', $histSanc[0]['action']);

        // 4. Lift Sanction -> Audit Logged
        DisciplineSanction::updateStatus($sancId, 'en_cours');
        DisciplineSanction::updateStatus($sancId, 'levee');

        $histSancLift = DisciplineHistorique::findByTarget('sanction', $sancId, self::$lyceeIdA);
        $this->assertEquals('LEVEE_SANCTION', $histSancLift[0]['action']);
        $this->assertEquals('en_cours', $histSancLift[0]['statut_avant']);
        $this->assertEquals('levee', $histSancLift[0]['statut_apres']);
    }

    public function testDocumentManagementAndIsolation(): void {
        $this->authenticateUser(9001, 3, self::$lyceeIdA);

        $dummyPath = sys_get_temp_dir() . '/dummy_doc.pdf';
        file_put_contents($dummyPath, 'PDF dummy content');

        $docId = DisciplineDocument::create([
            'eleve_id' => self::$eleve1,
            'nom_original' => 'Rapport_Discipline.pdf',
            'nom_stockage' => 'disc_doc_test.pdf',
            'chemin_interne' => $dummyPath,
            'mime_type' => 'application/pdf',
            'taille' => 1024
        ]);

        $this->assertGreaterThan(0, $docId);

        $doc = DisciplineDocument::findById($docId, self::$lyceeIdA);
        $this->assertIsArray($doc);
        $this->assertEquals('Rapport_Discipline.pdf', $doc['nom_original']);

        // Cross Tenant Download IDOR Attempt
        $docCrossTenant = DisciplineDocument::findById($docId, 9999);
        $this->assertFalse($docCrossTenant, "Cross tenant document access MUST be rejected.");

        // Cleanup dummy file
        @unlink($dummyPath);
    }

    public function testParentNotificationTracking(): void {
        $this->authenticateUser(9001, 3, self::$lyceeIdA);

        $notifId = DisciplineNotification::create([
            'eleve_id' => self::$eleve1,
            'destinataire_nom' => 'M. TESTER Pierre',
            'destinataire_contact' => '+236 75 00 00 00',
            'mode_notification' => 'main_propre',
            'objet' => 'Notification de convocation',
            'message' => 'Remise en main propre contre décharge'
        ]);

        $this->assertGreaterThan(0, $notifId);

        $notifs = DisciplineNotification::findByTarget('eleve', self::$eleve1, self::$lyceeIdA);
        $this->assertNotEmpty($notifs);
        $this->assertEquals('M. TESTER Pierre', $notifs[0]['destinataire_nom']);
        $this->assertEquals('main_propre', $notifs[0]['mode_notification']);
    }
}
?>