<?php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/CsrfService.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/ParamLycee.php';
require_once __DIR__ . '/../src/controllers/ReinscriptionController.php';

class ReinscriptionWorkflowTest {

    public static function run(): void {
        echo "=========================================================\n";
        echo " RUNNING RE-ENROLLMENT WORKFLOW END-TO-END TEST\n";
        echo "=========================================================\n";

        $testDb = new \PDO("sqlite:" . __DIR__ . "/../database.sqlite", null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        Database::setInstance($testDb);
        require_once __DIR__ . '/../migrate.php';
        $db = Database::getInstance();

        // 1. Setup Test Data
        $lyceeId = 901;
        $activeYearId = 1;
        $nextYearId = 2;

        // Cleanup
        $db->exec("DELETE FROM notifications WHERE lycee_id = $lyceeId");
        $db->exec("DELETE FROM etudes WHERE lycee_id = $lyceeId");
        $db->exec("DELETE FROM eleves WHERE lycee_id = $lyceeId");
        $db->exec("DELETE FROM classes WHERE lycee_id = $lyceeId");

        // Create School
        $db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ($lyceeId, 'Lycée Réinscription Test', 'prive') ON CONFLICT(id) DO UPDATE SET type_lycee='prive'");

        // Create Classes
        $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (9001, $lyceeId, 1, '6eme', 'G', 1)");
        $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (9002, $lyceeId, 1, '5eme', 'G', 1)");

        // Create Academic Years
        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES ($nextYearId, '2025-2026', '2025-09-01', '2026-06-30', 1) ON CONFLICT(id) DO UPDATE SET est_active=1");

        // Create Accountant User
        $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, actif) VALUES (9901, $lyceeId, 'COMPTABLE', 'Jean', 'comp9901@sgs.ci', 7, 1)");

        // Create Existing Student
        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (9101, $lyceeId, 'KOUAME', 'Paul', 'MAT-9101', 'actif')");

        // Student's former enrollment in Year 1
        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (9201, 9101, 9001, $lyceeId, $activeYearId, 0, 'inactive')");

        // 2. Execute Re-enrollment via ReinscriptionController
        $_SESSION['user'] = [
            'id' => 1,
            'id_user' => 1,
            'lycee_id' => $lyceeId,
            'role_name' => 'admin_local',
            'permissions' => ['eleve' => ['reinscrire', 'view_all']]
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => CsrfService::token(),
            'eleve_id' => 9101,
            'classe_id' => 9002, // Promoted to 5eme
            'annee_academique_id' => $nextYearId
        ];

        $controller = new ReinscriptionController();

        // Execute process() in test mode
        ob_start();
        try {
            $controller->process();
        } catch (Throwable $e) {}
        ob_get_clean();

        // 3. Verify Database & Business Assertions

        // Assertion A: No duplicate student record created
        $stmtCount = $db->query("SELECT COUNT(*) FROM eleves WHERE lycee_id = $lyceeId AND nom = 'KOUAME' AND prenom = 'Paul'");
        $studentCount = (int)$stmtCount->fetchColumn();
        if ($studentCount !== 1) {
            throw new Exception("[FAIL] Duplicate student record created during re-enrollment! Total: $studentCount");
        }
        echo " [PASS] No duplicate student created (Total student records = 1).\n";

        // Assertion B: New etudes record created linking same eleve_id to new academic year
        $stmtEtude = $db->query("SELECT * FROM etudes WHERE eleve_id = 9101 AND annee_academique_id = $nextYearId");
        $newEtude = $stmtEtude->fetch(PDO::FETCH_ASSOC);
        if (!$newEtude) {
            throw new Exception("[FAIL] New etudes record for year $nextYearId was not created.");
        }
        if ((int)$newEtude['classe_id'] !== 9002) {
            throw new Exception("[FAIL] New class_id is invalid: " . $newEtude['classe_id']);
        }
        if ($newEtude['status'] !== 'en_attente_paiement') {
            throw new Exception("[FAIL] Status for private school re-enrollment should be 'en_attente_paiement', got: " . $newEtude['status']);
        }
        echo " [PASS] New etudes record created (Year $nextYearId, Class 9002, Status 'en_attente_paiement').\n";

        // Assertion C: Notification created for accountants with link /paiements/show/9101
        $stmtNotif = $db->query("SELECT * FROM notifications WHERE lycee_id = $lyceeId ORDER BY id DESC LIMIT 1");
        $notif = $stmtNotif->fetch(PDO::FETCH_ASSOC);
        if (!$notif) {
            throw new Exception("[FAIL] Accountant notification was not created.");
        }
        if (strpos($notif['link'], '/paiements/show/9101') === false) {
            throw new Exception("[FAIL] Notification link invalid: " . $notif['link']);
        }
        echo " [PASS] Accountant notification created linking to cashier view (/paiements/show/9101).\n";

        echo "=========================================================\n";
        echo " SUCCESS: RE-ENROLLMENT WORKFLOW IS 100% OPERATIONAL!\n";
        echo "=========================================================\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    ReinscriptionWorkflowTest::run();
}
