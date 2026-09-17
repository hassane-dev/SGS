<?php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/CsrfService.php';
require_once __DIR__ . '/../src/models/DisciplineConseil.php';
require_once __DIR__ . '/../src/models/DisciplineConseilMembre.php';
require_once __DIR__ . '/../src/models/DisciplineConseilEleve.php';
require_once __DIR__ . '/../src/controllers/DisciplineConseilController.php';

class DisciplineAjaxRoutesTest {

    public static function run(): void {
        echo "=========================================================\n";
        echo " RUNNING DISCIPLINE COUNCIL AJAX ROUTES TEST SUITE\n";
        echo "=========================================================\n";

        $testDb = new \PDO("sqlite:" . __DIR__ . "/../database.sqlite", null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        Database::setInstance($testDb);
        require_once __DIR__ . '/../migrate.php';
        $db = Database::getInstance();

        // 1. Setup Test Council & Members
        $lyceeA = 981;
        $lyceeB = 982;

        $db->exec("DELETE FROM discipline_types_incidents WHERE lycee_id IN ($lyceeA, $lyceeB)");
        $db->exec("DELETE FROM discipline_historique WHERE lycee_id IN ($lyceeA, $lyceeB)");
        $db->exec("DELETE FROM discipline_conseil_eleves WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN ($lyceeA, $lyceeB))");
        $db->exec("DELETE FROM discipline_conseil_membres WHERE conseil_id IN (SELECT id FROM discipline_conseils WHERE lycee_id IN ($lyceeA, $lyceeB))");
        $db->exec("DELETE FROM discipline_conseils WHERE lycee_id IN ($lyceeA, $lyceeB)");
        $db->exec("DELETE FROM eleves WHERE lycee_id IN ($lyceeA, $lyceeB)");
        $db->exec("DELETE FROM utilisateurs WHERE lycee_id IN ($lyceeA, $lyceeB)");

        // Users
        $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, actif) VALUES (9801, $lyceeA, 'ADMIN', 'LyceeA', 'adm9801@sgs.ci', 3, 1)");
        $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, actif) VALUES (9802, $lyceeB, 'ADMIN', 'LyceeB', 'adm9802@sgs.ci', 3, 1)");

        // Student
        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (9811, $lyceeA, 'DIBY', 'Marc', 'E9811', 'actif')");

        // Incident
        $db->exec("INSERT INTO discipline_types_incidents (id, lycee_id, code, libelle, actif) VALUES (9821, $lyceeA, 'TEST_INC', 'Test Incident', 1)");
        $db->exec("INSERT INTO discipline_incidents (id, lycee_id, annee_academique_id, type_incident_id, date_incident, description_faits, statut, signale_par_user_id) VALUES (9831, $lyceeA, 1, 9821, '2026-01-01', 'Description Test', 'traite', 9801)");
        $db->exec("INSERT INTO discipline_incident_eleves (incident_id, eleve_id, role_implication, classe_id) VALUES (9831, 9811, 'auteur_principal', 1)");

        // Council in Lycee A
        $councilId = DisciplineConseil::create([
            'lycee_id' => $lyceeA,
            'annee_academique_id' => 1,
            'code' => 'CD-981-01',
            'titre' => 'Conseil Test AJAX',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => 9801,
            'statut' => 'planifie'
        ]);

        DisciplineConseilMembre::addMembre([
            'conseil_id' => $councilId,
            'user_id' => 9801,
            'qualite_membre' => 'president',
            'a_droit_vote' => 1
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => 9811,
            'motif_convocation' => 'Test Convocation'
        ]);

        $controller = new DisciplineConseilController();

        // 2. Test updateMemberPresence Route
        $_SESSION['user'] = [
            'id' => 9801,
            'id_user' => 9801,
            'lycee_id' => $lyceeA,
            'role_name' => 'admin_local',
            'permissions' => ['discipline' => ['manage_councils', 'view_councils']]
        ];

        $_POST = [
            'csrf_token' => CsrfService::token(),
            'conseil_id' => $councilId,
            'user_id' => 9801,
            'est_present' => 1
        ];

        ob_start();
        $controller->updateMemberPresence();
        ob_get_clean();

        $membres = DisciplineConseilMembre::findByConseilId($councilId);
        if (empty($membres) || (int)$membres[0]['est_present'] !== 1) {
            throw new Exception("[FAIL] updateMemberPresence failed to update presence.");
        }
        echo " [PASS] Route /discipline/councils/update-presence executed successfully.\n";

        // 3. Test updateEleveConvocation Route
        $_POST = [
            'csrf_token' => CsrfService::token(),
            'conseil_id' => $councilId,
            'eleve_id' => 9811,
            'motif_convocation' => 'Motif Mis a Jour',
            'presence_eleve' => 'present',
            'presence_representant_legal' => 'present',
            'nom_representant_legal' => 'M. DIBY Père'
        ];

        ob_start();
        $controller->updateEleveConvocation();
        ob_get_clean();

        $eleves = DisciplineConseilEleve::findByConseilId($councilId);
        if (empty($eleves) || $eleves[0]['presence_eleve'] !== 'present' || $eleves[0]['nom_representant_legal'] !== 'M. DIBY Père') {
            throw new Exception("[FAIL] updateEleveConvocation failed to update convocation.");
        }
        echo " [PASS] Route /discipline/councils/update-convocation executed successfully.\n";

        // 4. Test Cross-Tenant Attack (User in Lycee B trying to update Lycee A's council)
        $_SESSION['user']['lycee_id'] = $lyceeB;
        $_POST['conseil_id'] = $councilId;

        ob_start();
        $controller->updateEleveConvocation();
        ob_get_clean();

        if (empty($_SESSION['error']) || strpos($_SESSION['error'], 'Conseil introuvable') === false) {
            throw new Exception("[FAIL] Cross-tenant access was not rejected with 'Conseil introuvable'. Got: " . ($_SESSION['error'] ?? 'None'));
        }
        echo " [PASS] Cross-tenant attempt was properly rejected ('Conseil introuvable').\n";

        echo "=========================================================\n";
        echo " SUCCESS: DISCIPLINE COUNCIL AJAX ROUTES ARE 100% OPERATIONAL!\n";
        echo "=========================================================\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    DisciplineAjaxRoutesTest::run();
}
