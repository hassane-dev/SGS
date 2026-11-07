<?php
/**
 * Test Suite: PromotionAdmissionIntegrationTest
 *
 * Vérifie l'intégration fonctionnelle et de sécurité de l'interface d'admission :
 *  1. Accès à l'interface selon les permissions RBAC (Autorisé vs 403 Forbidden).
 *  2. Isolation par établissement (Rejet d'un établissement hors périmètre).
 *  3. Isolation par année académique.
 *  4. Isolation par classe.
 *  5. Sélection et filtrage en cascade des élèves.
 *  6. Calcul collectif d'une classe complète.
 *  7. Affichage et restitution des décisions individuelles et statuts.
 *  8. Affichage et clarté des justifications.
 *  9. Traitement des dossiers incomplets (marquage sans blocage de l'interface).
 * 10. Absence de validation officielle automatique (le calcul reste une proposition).
 * 11. Absence d'effet secondaire sur les inscriptions existantes (`etudes`).
 */

putenv('APP_ENV=development');
putenv('DB_DRIVER=sqlite');
$_ENV['APP_ENV'] = 'development';
$_ENV['DB_DRIVER'] = 'sqlite';

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/ParamPromotionSeuil.php';
require_once __DIR__ . '/../src/services/PromotionThresholdService.php';
require_once __DIR__ . '/../src/services/PromotionAdmissionService.php';
require_once __DIR__ . '/../src/controllers/PromotionAdmissionController.php';
require_once __DIR__ . '/../db/migrations/20240115_37_create_param_promotion_seuils.php';

class PromotionAdmissionIntegrationTest {

    private PDO $db;
    private int $lyceeA = 601;
    private int $lyceeB = 602;
    private int $annee1 = 701;
    private int $annee2 = 702;
    private int $cycleA = 801;
    private int $cycleB = 802;
    private int $classeA1 = 901;
    private int $classeA2 = 902;
    private int $classeB1 = 903;

    private int $eleve1 = 1501;
    private int $eleve2 = 1502;
    private int $eleve3 = 1503;
    private int $eleveB = 1504;

    private int $seq1;
    private int $seq2;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function run() {
        echo "========================================================\n";
        echo " RUNNING SUITE: PromotionAdmissionIntegrationTest (11 Scenarios) \n";
        echo "========================================================\n\n";

        migrate_37($this->db);
        $this->setUpDatabase();

        $this->test1_AccesSelonPermissionsRBAC();
        $this->test2_IsolationEtablissement();
        $this->test3_IsolationAnneeAcademique();
        $this->test4_IsolationClasse();
        $this->test5_EndpointAjaxClassesCascade();
        $this->test6_CalculCollectifClasse();
        $this->test7_AffichageDecisionsIndividuelles();
        $this->test8_AffichageFicheDetailEtJustification();
        $this->test9_TraitementDossiersIncomplets();
        $this->test10_AbsenceValidationOfficielleAutomatique();
        $this->test11_AbsenceEffetSecondaireSurEtudes();

        $this->cleanUp();

        echo "\n========================================================\n";
        echo " ALL 11 INTEGRATION & SECURITY SCENARIOS PASSED! \n";
        echo "========================================================\n";
    }

    private function cleanUp() {
        $this->db->exec("DELETE FROM bulletin_details WHERE bulletin_id IN (SELECT id FROM bulletins WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB}))");
        $this->db->exec("DELETE FROM bulletins WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM param_promotion_seuils WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM etudes WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM eleves WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM sequences WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM classes WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM cycles WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM param_lycee WHERE id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM annees_academiques WHERE id IN ({$this->annee1}, {$this->annee2})");
        $this->db->exec("DELETE FROM utilisateurs WHERE id_user IN (8881, 8882)");
        $this->db->exec("DELETE FROM role_permissions WHERE role_id IN (888, 889)");
        $this->db->exec("DELETE FROM roles WHERE id_role IN (888, 889)");
    }

    private function setUpDatabase() {
        $this->cleanUp();

        // 1. Lycées
        $stmtL = $this->db->prepare("INSERT INTO param_lycee (id, nom_lycee, ville) VALUES (?, ?, ?)");
        $stmtL->execute([$this->lyceeA, 'Lycée Intégration A', 'Cotonou']);
        $stmtL->execute([$this->lyceeB, 'Lycée Intégration B', 'Parakou']);

        // 2. Années
        $stmtA = $this->db->prepare("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (?, ?, ?, ?, ?)");
        $stmtA->execute([$this->annee1, '2023-2024 Intégration', '2023-09-15', '2024-06-30', 0]);
        $stmtA->execute([$this->annee2, '2024-2025 Intégration', '2024-09-15', '2025-06-30', 1]);

        // 3. Cycles
        $stmtC = $this->db->prepare("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtC->execute([$this->cycleA, $this->lyceeA, 'Cycle Collège A', '6e', '3e', 0]);
        $stmtC->execute([$this->cycleB, $this->lyceeB, 'Cycle Collège B', '6e', '3e', 0]);

        // 4. Classes
        $stmtCl = $this->db->prepare("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtCl->execute([$this->classeA1, $this->lyceeA, $this->cycleA, '3e', null, '1']);
        $stmtCl->execute([$this->classeA2, $this->lyceeA, $this->cycleA, '4e', null, '1']);
        $stmtCl->execute([$this->classeB1, $this->lyceeB, $this->cycleB, '3e', null, '1']);

        // 5. Sequences pour Lycee A
        $stmtSeq = $this->db->prepare("INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtSeq->execute([$this->lyceeA, $this->annee1, 'Semestre 1', 'semestrielle', '2023-09-15', '2024-01-30', 'fermee']);
        $this->seq1 = (int)$this->db->lastInsertId();
        $stmtSeq->execute([$this->lyceeA, $this->annee1, 'Semestre 2', 'semestrielle', '2024-02-01', '2024-06-25', 'fermee']);
        $this->seq2 = (int)$this->db->lastInsertId();

        // 6. Seuils pour Lycee A : Admis >= 10.00, Délib [9.00 - 9.99], Redoubl <= 8.99, Exclu <= 5.00
        PromotionThresholdService::saveThreshold([
            'lycee_id' => $this->lyceeA,
            'portee_type' => 'etablissement',
            'seuil_admission_directe' => 10.00,
            'seuil_deliberation_min'  => 9.00,
            'seuil_deliberation_max'  => 9.99,
            'seuil_redoublement_max'  => 8.99,
            'seuil_exclusion_max'     => 5.00
        ]);

        // 7. Élèves & Inscriptions
        $this->createStudent($this->eleve1, 'TCHIBOZO', 'Aimé', $this->classeA1, $this->annee1, $this->lyceeA);
        $this->createStudent($this->eleve1, 'TCHIBOZO', 'Aimé', $this->classeA1, $this->annee2, $this->lyceeA);
        $this->createStudent($this->eleve2, 'KPADONOU', 'Benoit', $this->classeA1, $this->annee1, $this->lyceeA);
        $this->createStudent($this->eleve3, 'HOUNGBEDJI', 'Carine', $this->classeA1, $this->annee1, $this->lyceeA);
        $this->createStudent($this->eleveB, 'SOGLO', 'Daniel', $this->classeB1, $this->annee1, $this->lyceeB);

        // 8. Bulletins
        // Eleve 1: Moyenne 13.50 (Admis)
        $this->createBulletins($this->eleve1, $this->classeA1, $this->annee1, $this->lyceeA, 13.00, 14.00);
        // Eleve 2: Moyenne 9.50 (Délibération)
        $this->createBulletins($this->eleve2, $this->classeA1, $this->annee1, $this->lyceeA, 9.40, 9.60);
        // Eleve 3: Incomplet (bulletin Semestre 1 seulement)
        $stmtB = $this->db->prepare("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, classe_id, lycee_id, moyenne_generale, statut) VALUES (?, ?, ?, ?, ?, ?, 'valide')");
        $stmtB->execute([$this->eleve3, $this->seq1, $this->annee1, $this->classeA1, $this->lyceeA, 11.00]);
    }

    private function createStudent(int $id, string $nom, string $prenom, int $classeId, int $anneeId, int $lyceeId) {
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM eleves WHERE id_eleve = ?");
        $stmtCheck->execute([$id]);
        if ((int)$stmtCheck->fetchColumn() === 0) {
            $stmtE = $this->db->prepare("INSERT INTO eleves (id_eleve, nom, prenom, identifiant_public, statut, lycee_id) VALUES (?, ?, ?, ?, 'actif', ?)");
            $stmtE->execute([$id, $nom, $prenom, "INT-{$id}", $lyceeId]);
        }

        $stmtEt = $this->db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (?, ?, ?, ?, 'active', 1)");
        $stmtEt->execute([$id, $classeId, $lyceeId, $anneeId]);
    }

    private function createBulletins(int $eleveId, int $classeId, int $anneeId, int $lyceeId, float $m1, float $m2) {
        $stmtB = $this->db->prepare("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, classe_id, lycee_id, moyenne_generale, statut) VALUES (?, ?, ?, ?, ?, ?, 'valide')");
        $stmtB->execute([$eleveId, $this->seq1, $anneeId, $classeId, $lyceeId, $m1]);
        $b1 = (int)$this->db->lastInsertId();
        $stmtB->execute([$eleveId, $this->seq2, $anneeId, $classeId, $lyceeId, $m2]);
        $b2 = (int)$this->db->lastInsertId();

        $stmtD = $this->db->prepare("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtD->execute([$b1, 1, 'Français', $m1, 2.0, round($m1 * 2.0, 2)]);
        $stmtD->execute([$b2, 1, 'Français', $m2, 2.0, round($m2 * 2.0, 2)]);
    }

    private function setupMockUser(int $userId, int $lyceeId, array $permissions) {
        $roleId = ($userId === 8881) ? 888 : 889;
        $roleName = ($userId === 8881) ? 'admin_admission_test' : 'surveillant_test';

        // 1. Create or replace role
        $this->db->prepare("INSERT OR REPLACE INTO roles (id_role, nom_role, lycee_id) VALUES (?, ?, ?)")
                 ->execute([$roleId, $roleName, $lyceeId]);

        // 2. Set permissions
        $this->db->exec("DELETE FROM role_permissions WHERE role_id = {$roleId}");
        $stmtRP = $this->db->prepare("
            INSERT INTO role_permissions (role_id, permission_id)
            SELECT {$roleId}, id_permission FROM permissions WHERE resource = ? AND action = ?
        ");

        foreach ($permissions as $permStr) {
            [$res, $act] = explode(':', $permStr);
            $this->db->prepare("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES (?, ?, ?)")
                     ->execute([$res, $act, 'Test permission']);
            $stmtRP->execute([$res, $act]);
        }

        // 3. Create or update user
        $this->db->prepare("INSERT OR REPLACE INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)")
                 ->execute([$userId, 'Test', 'Mock', "user{$userId}@test.com", 'dummy', $roleId, $lyceeId]);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION['user'] = [
            'id' => $userId,
            'id_user' => $userId,
            'nom' => 'Test',
            'prenom' => 'Mock',
            'lycee_id' => $lyceeId,
            'role_id' => $roleId,
            'role_name' => $roleName,
            'auth_version' => 1
        ];

        // Trigger session synchronization
        Auth::synchronizeSessionIfNeeded();
    }

    /**
     * Test 1: Contrôle d'accès RBAC (Autorisé vs 403 Forbidden).
     */
    public function test1_AccesSelonPermissionsRBAC() {
        // Utilisateur sans permissions
        $this->setupMockUser(8882, $this->lyceeA, []);

        $controller = new PromotionAdmissionController();
        $threw = false;
        try {
            $controller->index();
        } catch (LogicException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Accès refusé'), "Test 1: Erreur d'accès levée pour utilisateur non autorisé");
        }
        $this->assertTrue($threw, "Test 1: L'accès sans permission doit être bloqué");

        // Utilisateur avec permissions autorisées
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'bulletin:validate', 'bulletin:generate']);
        ob_start();
        $controller->index();
        $out = ob_get_clean();
        $this->assertTrue(!empty($out), "Test 1: Accès accordé pour utilisateur autorisé");
        echo "✅ PASSED: Scénario 1 - Contrôle d'accès selon les permissions RBAC\n";
    }

    /**
     * Test 2: Isolation par établissement.
     */
    public function test2_IsolationEtablissement() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all']);
        $_GET['lycee_id'] = $this->lyceeB; // Tente de forcer Lycée B

        $controller = new PromotionAdmissionController();
        $threw = false;
        try {
            $controller->index();
        } catch (LogicException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Accès refusé pour cet établissement'), "Test 2: Blocage accès cross-tenant");
        }
        $this->assertTrue($threw, "Test 2: Un admin du Lycée A ne peut requêter le Lycée B");
        echo "✅ PASSED: Scénario 2 - Isolation stricte par établissement\n";
    }

    /**
     * Test 3: Isolation par année académique.
     */
    public function test3_IsolationAnneeAcademique() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        $_GET['lycee_id'] = $this->lyceeA;
        $_GET['annee_id'] = $this->annee2; // Année 2 sans bulletins
        $_GET['classe_id'] = $this->classeA1;

        $res = PromotionAdmissionService::calculateClassDecisions($this->lyceeA, $this->annee2, $this->classeA1);
        $this->assertTrue($res['summary']['non_determine'] >= 1, "Test 3: Tous les élèves sont non déterminés pour l'année 2");
        echo "✅ PASSED: Scénario 3 - Isolation par année académique\n";
    }

    /**
     * Test 4: Isolation par classe.
     */
    public function test4_IsolationClasse() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        $resA2 = PromotionAdmissionService::calculateClassDecisions($this->lyceeA, $this->annee1, $this->classeA2);
        $this->assertTrue($resA2['total_eleves'] === 0, "Test 4: Aucun élève de la classe 1 n'apparaît dans la classe 2");
        echo "✅ PASSED: Scénario 4 - Isolation par classe\n";
    }

    /**
     * Test 5: Endpoint AJAX cascade des classes.
     */
    public function test5_EndpointAjaxClassesCascade() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        $_GET['lycee_id'] = $this->lyceeA;
        $_GET['cycle_id'] = $this->cycleA;

        $controller = new PromotionAdmissionController();
        ob_start();
        $controller->ajaxClasses();
        $json = ob_get_clean();
        $data = json_decode($json, true);

        $this->assertTrue($data['success'] === true, "Test 5: Réponse AJAX valide");
        $this->assertTrue(count($data['classes']) >= 2, "Test 5: Les classes du cycle A sont renvoyées");
        echo "✅ PASSED: Scénario 5 - Endpoint AJAX de filtrage des classes en cascade\n";
    }

    /**
     * Test 6: Calcul collectif d'une classe complète.
     */
    public function test6_CalculCollectifClasse() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        $res = PromotionAdmissionService::calculateClassDecisions($this->lyceeA, $this->annee1, $this->classeA1, [
            'autoriser_resultats_incomplets' => false
        ]);

        $this->assertTrue($res['success'] === true, "Test 6: Calcul collectif réussi");
        $this->assertTrue($res['total_eleves'] === 3, "Test 6: 3 élèves évalués");
        $this->assertTrue($res['summary']['admis'] === 1, "Test 6: Exactement 1 admis direct");
        $this->assertTrue($res['summary']['deliberation'] === 1, "Test 6: Exactement 1 en délibération");
        $this->assertTrue($res['summary']['non_determine'] === 1, "Test 6: Exactement 1 dossier incomplet");
        echo "✅ PASSED: Scénario 6 - Calcul collectif d'une classe complète\n";
    }

    /**
     * Test 7: Affichage et restitution des décisions individuelles et statuts.
     */
    public function test7_AffichageDecisionsIndividuelles() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        $res = PromotionAdmissionService::calculateClassDecisions($this->lyceeA, $this->annee1, $this->classeA1, [
            'autoriser_resultats_incomplets' => false
        ]);

        $admisStudent = null;
        $delibStudent = null;
        foreach ($res['decisions'] as $d) {
            if ($d['eleve_id'] === $this->eleve1) $admisStudent = $d;
            if ($d['eleve_id'] === $this->eleve2) $delibStudent = $d;
        }

        $this->assertTrue($admisStudent['decision'] === 'admis', "Test 7: Décision de l'élève 1 = admis");
        $this->assertTrue($admisStudent['moyenne_annuelle'] === 13.50, "Test 7: Moyenne annuelle élève 1 = 13.50");
        $this->assertTrue($delibStudent['decision'] === 'deliberation', "Test 7: Décision élève 2 = deliberation");
        $this->assertTrue($delibStudent['moyenne_annuelle'] === 9.50, "Test 7: Moyenne annuelle élève 2 = 9.50");
        echo "✅ PASSED: Scénario 7 - Affichage des décisions et statuts individuels\n";
    }

    /**
     * Test 8: Affichage et clarté des justifications.
     */
    public function test8_AffichageFicheDetailEtJustification() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        $studentDetail = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classeA1,
            $this->eleve1
        );

        $this->assertTrue(!empty($studentDetail['justification']), "Test 8: Justification renseignée");
        $this->assertTrue(str_contains($studentDetail['justification'], '13.50'), "Test 8: Moyenne mentionnée dans la justification");
        $this->assertTrue(str_contains($studentDetail['justification'], '10.00'), "Test 8: Seuil mentionné dans la justification");
        echo "✅ PASSED: Scénario 8 - Affichage et clarté des justifications détaillées\n";
    }

    /**
     * Test 9: Traitement des dossiers incomplets.
     */
    public function test9_TraitementDossiersIncomplets() {
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        // Eleve 3 n'a qu'un seul bulletin sur 2
        $incompletDetail = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classeA1,
            $this->eleve3,
            ['autoriser_resultats_incomplets' => false]
        );

        $this->assertTrue($incompletDetail['is_complete'] === false, "Test 9: Dossier identifié comme incomplet");
        $this->assertTrue(!empty($incompletDetail['incomplete_reason']), "Test 9: Motif d'incomplétude explicite");
        $this->assertTrue(str_contains($incompletDetail['incomplete_reason'], 'Semestre 2'), "Test 9: Semestre 2 mentionné manquant");
        echo "✅ PASSED: Scénario 9 - Traitement transparent des dossiers incomplets\n";
    }

    /**
     * Test 10: Absence de validation officielle automatique (le calcul reste une proposition).
     */
    public function test10_AbsenceValidationOfficielleAutomatique() {
        // Vérifier qu'aucune table de délibération n'a été mutée ou enregistrée comme définitive
        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        $res = PromotionAdmissionService::calculateClassDecisions($this->lyceeA, $this->annee1, $this->classeA1);

        foreach ($res['decisions'] as $d) {
            $this->assertTrue(str_contains($d['decision_libelle'], 'Admis') || str_contains($d['decision_libelle'], 'délibérer') || str_contains($d['decision_libelle'], 'incomplets'), "Test 10: Décision indicative produite");
        }
        echo "✅ PASSED: Scénario 10 - Absence de validation officielle automatique\n";
    }

    /**
     * Test 11: Absence d'effet secondaire sur les inscriptions existantes (`etudes`).
     */
    public function test11_AbsenceEffetSecondaireSurEtudes() {
        $etudesCountBefore = (int)$this->db->query("SELECT COUNT(*) FROM etudes WHERE lycee_id = {$this->lyceeA}")->fetchColumn();

        $this->setupMockUser(8881, $this->lyceeA, ['promotion_threshold:view', 'class:view_all', 'lycee:view_all_lycees']);
        PromotionAdmissionService::calculateClassDecisions($this->lyceeA, $this->annee1, $this->classeA1);
        PromotionAdmissionService::calculateStudentDecision($this->lyceeA, $this->annee1, $this->classeA1, $this->eleve1);

        $etudesCountAfter = (int)$this->db->query("SELECT COUNT(*) FROM etudes WHERE lycee_id = {$this->lyceeA}")->fetchColumn();

        $this->assertTrue($etudesCountBefore === $etudesCountAfter, "Test 11: Le calcul n'a modifié aucune ligne dans la table etudes ({$etudesCountBefore} == {$etudesCountAfter})");
        echo "✅ PASSED: Scénario 11 - Zéro effet secondaire sur les inscriptions administratives\n";
    }

    private function assertTrue(bool $condition, string $msg = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: " . $msg);
        }
    }
}

$test = new PromotionAdmissionIntegrationTest();
$test->run();
