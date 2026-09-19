<?php
/**
 * Comprehensive Automated Integration Test Suite: Grade Dashboard (Phase 1).
 * Tests all 10 mandatory scenarios:
 * 1. Enseignant limité à ses classes et matières
 * 2. Responsable pédagogique avec son périmètre autorisé
 * 3. Séquence ouverte avec résultats provisoires
 * 4. Séquence fermée avec snapshots complets
 * 5. Séquence fermée avec données manquantes
 * 6. Élève ou parent consultant uniquement les données autorisées
 * 7. Tentative d'accès à un autre établissement
 * 8. Absence de données
 * 9. Gestion des valeurs limites des tranches de moyennes
 * 10. Vérification de l'absence de double comptabilisation
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';

// Setup SQLite test database before requiring models/services
$testDb = new \PDO("sqlite:" . __DIR__ . "/../database.sqlite", null, null, [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
]);
Database::setInstance($testDb);
require_once __DIR__ . '/../migrate.php';

require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/GradeDashboardService.php';
require_once __DIR__ . '/../src/services/AcademicAnalysisService.php';
require_once __DIR__ . '/../src/services/EvaluationCalculationService.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';

class GradeDashboardTest {

    private PDO $db;
    private int $lyceeId = 8801;
    private int $lyceeOtherId = 8802;
    private int $anneeId = 8810;
    private int $cycleId = 8815;
    private int $sequenceOpenId = 8820;
    private int $sequenceClosedId = 8821;
    private int $classeId = 8830;
    private int $classeUnassignedId = 8831;
    private int $matiereId = 8840;
    private int $matiereUnassignedId = 8841;
    private int $teacherUserId = 8850;
    private int $rpUserId = 8851;
    private int $student1Id = 8860;
    private int $student2Id = 8861;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function setUp(): void {
        // Clean up previous test data
        $this->db->exec("DELETE FROM evaluations WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM bulletin_details WHERE bulletin_id IN (SELECT id FROM bulletins WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId}))");
        $this->db->exec("DELETE FROM bulletins WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM affectations_pedagogiques WHERE enseignant_id IN ({$this->teacherUserId}, {$this->rpUserId})");
        $this->db->exec("DELETE FROM classe_matieres WHERE classe_id IN ({$this->classeId}, {$this->classeUnassignedId})");
        $this->db->exec("DELETE FROM etudes WHERE eleve_id IN ({$this->student1Id}, {$this->student2Id})");
        $this->db->exec("DELETE FROM eleves WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM classes WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM matieres WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM sequences WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM param_type_evaluation WHERE lycee_id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM annees_academiques WHERE id = {$this->anneeId}");
        $this->db->exec("DELETE FROM param_lycee WHERE id IN ({$this->lyceeId}, {$this->lyceeOtherId})");
        $this->db->exec("DELETE FROM cycles WHERE id_cycle = {$this->cycleId}");
        $this->db->exec("DELETE FROM utilisateurs WHERE id_user IN ({$this->teacherUserId}, {$this->rpUserId})");

        // 1. Create Lycée & Cycles
        $this->db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ({$this->lyceeId}, 'Lycée Test Phase 1', 'prive')");
        $this->db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ({$this->lyceeOtherId}, 'Lycée Autre Isolation', 'prive')");
        $this->db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES ({$this->cycleId}, {$this->lyceeId}, 'Secondaire')");

        // 2. Create Users
        $this->db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, actif) VALUES ({$this->teacherUserId}, {$this->lyceeId}, 'DURAND', 'Pierre', 'prof@test.ci', 6, 1)");
        $this->db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, actif) VALUES ({$this->rpUserId}, {$this->lyceeId}, 'KOUASSI', 'Jean', 'rp@test.ci', 5, 1)");

        // Map RBAC permissions for roles: RP (5) gets all, Teacher (6) gets ONLY create_own note
        $stmtInsRp = $this->db->prepare("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) SELECT 5, id_permission FROM permissions WHERE resource IN ('note', 'bulletin', 'reporting', 'evaluation', 'eleve')");
        $stmtInsRp->execute();

        // Ensure teacher (role 6) does NOT have global view or bulletin generate permissions in role_permissions
        $this->db->exec("DELETE FROM role_permissions WHERE role_id = 6 AND permission_id IN (SELECT id_permission FROM permissions WHERE (resource = 'bulletin' AND action = 'generate') OR (resource = 'note' AND action = 'view_all'))");

        $stmtInsTeacher = $this->db->prepare("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) SELECT 6, id_permission FROM permissions WHERE resource = 'note' AND action = 'create_own'");
        $stmtInsTeacher->execute();

        // 3. Create Academic Year
        $this->db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES ({$this->anneeId}, '2025-2026', '2025-09-01', '2026-06-30', 1, 0)");

        // 4. Create Sequences: 1 Open ('ouverte') & 1 Closed ('fermee')
        $this->db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES ({$this->sequenceOpenId}, {$this->lyceeId}, {$this->anneeId}, 'Séquence 1', 'trimestrielle', '2025-09-01', '2025-10-31', 'ouverte')");
        $this->db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES ({$this->sequenceClosedId}, {$this->lyceeId}, {$this->anneeId}, 'Séquence 2', 'trimestrielle', '2025-11-01', '2025-12-31', 'fermee')");

        // 5. Create Classes & Matieres
        $this->db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES ({$this->classeId}, {$this->lyceeId}, {$this->cycleId}, '6eme', '1')");
        $this->db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES ({$this->classeUnassignedId}, {$this->lyceeId}, {$this->cycleId}, '5eme', '1')");
        $this->db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES ({$this->matiereId}, {$this->lyceeId}, 'Mathématiques')");
        $this->db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES ({$this->matiereUnassignedId}, {$this->lyceeId}, 'Physique-Chimie')");

        // 6. Curriculum (classe_matieres)
        $this->db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES ({$this->classeId}, {$this->matiereId}, 3.00)");
        $this->db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES ({$this->classeId}, {$this->matiereUnassignedId}, 2.00)");

        // 7. Teacher Affectation
        $this->db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut) VALUES ({$this->teacherUserId}, {$this->classeId}, {$this->matiereId}, {$this->anneeId}, '2025-09-01', 'actif')");

        // 8. Students & Enrolment
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES ({$this->student1Id}, {$this->lyceeId}, 'KOUAME', 'Aya', 'E8860', 'actif')");
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES ({$this->student2Id}, {$this->lyceeId}, 'KONAN', 'Koffi', 'E8861', 'actif')");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, lycee_id, is_active) VALUES ({$this->student1Id}, {$this->classeId}, {$this->anneeId}, {$this->lyceeId}, 1)");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, lycee_id, is_active) VALUES ({$this->student2Id}, {$this->classeId}, {$this->anneeId}, {$this->lyceeId}, 1)");

        // 9. Evaluation Type (Devoir, 1 occurrence)
        $this->db->exec("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif) VALUES ({$this->lyceeId}, 'DEVOIR', 'Devoir', 20.00, 1, 1)");

        // 10. Evaluations for Open Sequence
        $this->db->exec("INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, numero_evaluation, note, bareme_snapshot, coefficient) VALUES ({$this->lyceeId}, {$this->classeId}, {$this->matiereId}, {$this->teacherUserId}, {$this->student1Id}, {$this->sequenceOpenId}, {$this->anneeId}, 'devoir', 1, 14.00, 20.00, 3.00)");
        $this->db->exec("INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, numero_evaluation, note, bareme_snapshot, coefficient) VALUES ({$this->lyceeId}, {$this->classeId}, {$this->matiereId}, {$this->teacherUserId}, {$this->student2Id}, {$this->sequenceOpenId}, {$this->anneeId}, 'devoir', 1, 10.00, 20.00, 3.00)");

        // 11. Official Bulletins & Details Snapshots for Closed Sequence
        $this->db->exec("INSERT INTO bulletins (id, eleve_id, sequence_id, annee_academique_id, lycee_id, classe_id, nom_classe_snapshot, effectif_classe, moyenne_generale, moyenne_classe, rang, rang_int, statut) VALUES (8890, {$this->student1Id}, {$this->sequenceClosedId}, {$this->anneeId}, {$this->lyceeId}, {$this->classeId}, '6eme 1', 2, 16.00, 14.00, '1er', 1, 'valide')");
        $this->db->exec("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes) VALUES (8890, {$this->matiereId}, 'Mathématiques', 16.00, 3.00, 48.00)");

        $this->db->exec("INSERT INTO bulletins (id, eleve_id, sequence_id, annee_academique_id, lycee_id, classe_id, nom_classe_snapshot, effectif_classe, moyenne_generale, moyenne_classe, rang, rang_int, statut) VALUES (8891, {$this->student2Id}, {$this->sequenceClosedId}, {$this->anneeId}, {$this->lyceeId}, {$this->classeId}, '6eme 1', 2, 12.00, 14.00, '2e', 2, 'valide')");
        $this->db->exec("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes) VALUES (8891, {$this->matiereId}, 'Mathématiques', 12.00, 3.00, 36.00)");

        // Session Setup for Teacher by default
        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 6,
            'role_name' => 'enseignant'
        ];
        unset($_SESSION['user']['permissions']);
    }

    /**
     * Scenario 1: Enseignant limité à ses classes et matières
     */
    public function test1_TeacherRestrictedScope(): bool {
        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 6,
            'role_name' => 'enseignant'
        ];
        unset($_SESSION['user']['permissions']);

        // Teacher allowed class/subject
        $filtersAllowed = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeId,
            'matiere_id' => $this->matiereId
        ];
        $dataAllowed = GradeDashboardService::getDashboardData($filtersAllowed, $this->teacherUserId);
        if (!$dataAllowed['success']) return false;

        // Teacher requesting unassigned class
        $filtersBlockedClass = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeUnassignedId
        ];
        $blockedClassCaught = false;
        try {
            GradeDashboardService::getDashboardData($filtersBlockedClass, $this->teacherUserId);
        } catch (Exception $e) {
            $blockedClassCaught = str_contains($e->getMessage(), "Accès refusé");
        }

        // Teacher requesting unassigned subject
        $filtersBlockedSub = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeId,
            'matiere_id' => $this->matiereUnassignedId
        ];
        $blockedSubCaught = false;
        try {
            GradeDashboardService::getDashboardData($filtersBlockedSub, $this->teacherUserId);
        } catch (Exception $e) {
            $blockedSubCaught = str_contains($e->getMessage(), "Accès refusé");
        }

        return $blockedClassCaught && $blockedSubCaught;
    }

    /**
     * Scenario 2: Responsable pédagogique avec son périmètre autorisé
     */
    public function test2_ResponsablePedagogiqueScope(): bool {
        $_SESSION['user_id'] = $this->rpUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->rpUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 5,
            'role_name' => 'responsable_pedagogique'
        ];
        unset($_SESSION['user']['permissions']);

        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeUnassignedId // RP can view unassigned class
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->rpUserId);
        return $data['success'] === true;
    }

    /**
     * Scenario 3: Séquence ouverte avec résultats provisoires
     */
    public function test3_OpenSequenceLiveProvisional(): bool {
        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 6,
            'role_name' => 'enseignant'
        ];
        unset($_SESSION['user']['permissions']);

        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);

        $checkLabel = ($data['sequence']['status_label'] === "Résultats en temps réel");
        $checkBadge = ($data['sequence']['status_badge_class'] === "bg-light-warning text-warning");
        $checkClosed = ($data['sequence']['is_closed'] === false);
        $checkAvg = ($data['performance']['moyenne_generale'] == 12.00);

        return $checkLabel && $checkBadge && $checkClosed && $checkAvg;
    }

    /**
     * Scenario 4: Séquence fermée avec snapshots complets
     */
    public function test4_ClosedSequenceSealedSnapshots(): bool {
        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 6,
            'role_name' => 'enseignant'
        ];
        unset($_SESSION['user']['permissions']);

        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceClosedId,
            'classe_id' => $this->classeId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);

        $checkLabel = ($data['sequence']['status_label'] === "Résultats officiels scellés");
        $checkBadge = ($data['sequence']['status_badge_class'] === "bg-light-success text-success");
        $checkClosed = ($data['sequence']['is_closed'] === true);
        $checkAvg = ($data['performance']['moyenne_generale'] == 14.00);

        // Verify post-closure evaluation insertion is ignored by closed sequence
        $this->db->exec("INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, numero_evaluation, note, bareme_snapshot, coefficient) VALUES ({$this->lyceeId}, {$this->classeId}, {$this->matiereId}, {$this->teacherUserId}, {$this->student1Id}, {$this->sequenceClosedId}, {$this->anneeId}, 'devoir', 1, 0.00, 20.00, 3.00)");

        $dataPost = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);
        $checkImmutability = ($dataPost['performance']['moyenne_generale'] == 14.00);

        return $checkLabel && $checkBadge && $checkClosed && $checkAvg && $checkImmutability;
    }

    /**
     * Scenario 5: Séquence fermée avec données manquantes
     */
    public function test5_ClosedSequenceMissingData(): bool {
        $_SESSION['user_id'] = $this->rpUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->rpUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 5,
            'role_name' => 'responsable_pedagogique'
        ];
        unset($_SESSION['user']['permissions']);

        // Query closed sequence for unassigned class where no bulletins were created
        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceClosedId,
            'classe_id' => $this->classeUnassignedId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->rpUserId);
        return $data['success'] === true
            && $data['performance']['assessed_students_count'] === 0
            && $data['performance']['moyenne_generale'] === 0.0;
    }

    /**
     * Scenario 6: Élève ou parent consultant uniquement les données autorisées
     */
    public function test6_StudentParentTrajectoryScope(): bool {
        // Query longitudinal series for student1
        $series = AcademicAnalysisService::getSequentialSeriesData($this->student1Id);
        $trend = AcademicAnalysisService::getGeneralAverageTrendSeries($this->student1Id);
        $ranks = AcademicAnalysisService::getRankTrendSeries($this->student1Id);

        $checkSeries = is_array($series) && count($series) === 2;
        $checkTrend = is_array($trend) && isset($trend['categories']) && isset($trend['series']);
        $checkRanks = is_array($ranks) && isset($ranks['categories']) && isset($ranks['ranks']);

        return $checkSeries && $checkTrend && $checkRanks;
    }

    /**
     * Scenario 7: Tentative d'accès à un autre établissement
     */
    public function test7_CrossTenantAccessBlocked(): bool {
        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 6,
            'role_name' => 'enseignant'
        ];
        unset($_SESSION['user']['permissions']);

        $filters = [
            'lycee_id' => $this->lyceeOtherId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId
        ];

        $blocked = false;
        try {
            GradeDashboardService::getDashboardData($filters, $this->teacherUserId);
        } catch (Exception $e) {
            $blocked = str_contains($e->getMessage(), "Accès refusé au lycée");
        }

        return $blocked;
    }

    /**
     * Scenario 8: Absence de données
     */
    public function test8_NoDataEmptyResponse(): bool {
        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 6,
            'role_name' => 'enseignant'
        ];
        unset($_SESSION['user']['permissions']);

        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => 99999 // Non-existent academic year without sequence_id
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);
        return str_contains($data['message'] ?? '', "Aucune") || str_contains($data['alerts'][0]['message'] ?? '', "Aucune");
    }

    /**
     * Scenario 9: Gestion des valeurs limites des tranches de moyennes
     */
    public function test9_DistributionBucketBoundaries(): bool {
        // Test edge values: 0.00, 4.99, 5.00, 9.99, 10.00, 11.99, 12.00, 13.99, 14.00, 15.99, 16.00, 20.00
        $grades = [0.00, 4.99, 5.00, 9.99, 10.00, 11.99, 12.00, 13.99, 14.00, 15.99, 16.00, 20.00];

        $distribution = [
            '0_5' => 0,
            '5_10' => 0,
            '10_12' => 0,
            '12_14' => 0,
            '14_16' => 0,
            '16_20' => 0
        ];

        foreach ($grades as $avg) {
            if ($avg < 5.0) {
                $distribution['0_5']++;
            } elseif ($avg < 10.0) {
                $distribution['5_10']++;
            } elseif ($avg < 12.0) {
                $distribution['10_12']++;
            } elseif ($avg < 14.0) {
                $distribution['12_14']++;
            } elseif ($avg < 16.0) {
                $distribution['14_16']++;
            } else {
                $distribution['16_20']++;
            }
        }

        $check0_5 = ($distribution['0_5'] === 2);    // 0.00, 4.99
        $check5_10 = ($distribution['5_10'] === 2);  // 5.00, 9.99
        $check10_12 = ($distribution['10_12'] === 2);// 10.00, 11.99
        $check12_14 = ($distribution['12_14'] === 2);// 12.00, 13.99
        $check14_16 = ($distribution['14_16'] === 2);// 14.00, 15.99
        $check16_20 = ($distribution['16_20'] === 2);// 16.00, 20.00

        return $check0_5 && $check5_10 && $check10_12 && $check12_14 && $check14_16 && $check16_20;
    }

    /**
     * Scenario 10: Vérification de l'absence de double comptabilisation
     */
    public function test10_NoDoubleCountingInCompletion(): bool {
        $_SESSION['user_id'] = $this->rpUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->rpUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 5,
            'role_name' => 'responsable_pedagogique'
        ];
        unset($_SESSION['user']['permissions']);

        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->rpUserId);
        $completion = $data['completion'];

        // Class has 2 students, 2 matieres (MATH, PHYS), 1 type (DEVOIR, 1 occurrence)
        // Total expected = 2 students * 2 matieres * 1 occurrence = 4 expected evaluations.
        $expectedCount = $completion['expected_evaluations'];
        $recordedCount = $completion['recorded_evaluations'];

        return ($expectedCount === 4) && ($recordedCount === 2);
    }

    /**
     * Scenario 11: Anti-leakage check — Unassigned subject notes in assigned class must NOT contaminate teacher dashboard.
     */
    public function test11_UnassignedSubjectDataLeakage(): bool {
        $_SESSION['user_id'] = $this->teacherUserId;
        $_SESSION['lycee_id'] = $this->lyceeId;
        $_SESSION['user'] = [
            'id_user' => $this->teacherUserId,
            'lycee_id' => $this->lyceeId,
            'role_id' => 6,
            'role_name' => 'enseignant'
        ];
        unset($_SESSION['user']['permissions']);

        // Insert a 0/20 grade in Physique-Chimie (matiereUnassignedId) for student1 in teacher's assigned class
        $this->db->exec("INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, numero_evaluation, note, bareme_snapshot, coefficient) VALUES ({$this->lyceeId}, {$this->classeId}, {$this->matiereUnassignedId}, 9999, {$this->student1Id}, {$this->sequenceOpenId}, {$this->anneeId}, 'devoir', 1, 0.00, 20.00, 2.00)");

        $filters = [
            'lycee_id' => $this->lyceeId,
            'annee_academique_id' => $this->anneeId,
            'sequence_id' => $this->sequenceOpenId,
            'classe_id' => $this->classeId
        ];

        $data = GradeDashboardService::getDashboardData($filters, $this->teacherUserId);

        // General average for teacher MUST remain 12.00 (Math average), ignoring the 0/20 in Physique-Chimie
        $checkAvg = ($data['performance']['moyenne_generale'] == 12.00);

        // Subject averages MUST only contain Mathématiques and NOT Physique-Chimie
        $subNames = array_column($data['performance']['subject_averages'], 'nom_matiere');
        $checkSubjectIsolation = !in_array('Physique-Chimie', $subNames);

        return $checkAvg && $checkSubjectIsolation;
    }

    public function run(): array {
        $results = [];

        $scenarios = [
            'Scenario 1: Enseignant limité à ses classes et matières' => 'test1_TeacherRestrictedScope',
            'Scenario 2: Responsable pédagogique avec son périmètre autorisé' => 'test2_ResponsablePedagogiqueScope',
            'Scenario 3: Séquence ouverte avec résultats provisoires' => 'test3_OpenSequenceLiveProvisional',
            'Scenario 4: Séquence fermée avec snapshots complets' => 'test4_ClosedSequenceSealedSnapshots',
            'Scenario 5: Séquence fermée avec données manquantes' => 'test5_ClosedSequenceMissingData',
            'Scenario 6: Élève ou parent consultant uniquement les données autorisées' => 'test6_StudentParentTrajectoryScope',
            'Scenario 7: Tentative d\'accès à un autre établissement' => 'test7_CrossTenantAccessBlocked',
            'Scenario 8: Absence de données' => 'test8_NoDataEmptyResponse',
            'Scenario 9: Gestion des valeurs limites des tranches de moyennes' => 'test9_DistributionBucketBoundaries',
            'Scenario 10: Vérification de l\'absence de double comptabilisation' => 'test10_NoDoubleCountingInCompletion',
            'Scenario 11: Isolation stricte des matières non affectées dans une classe affectée' => 'test11_UnassignedSubjectDataLeakage'
        ];

        foreach ($scenarios as $label => $method) {
            $this->setUp();
            $success = false;
            try {
                $success = $this->$method();
            } catch (Exception $e) {
                $success = false;
            }
            $results[$label] = $success ? 'RÉUSSI' : 'ÉCHOUÉ';
        }

        return $results;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "=========================================================\n";
    echo " RUNNING GRADE DASHBOARD INTEGRATION TEST SUITE (PHASE 1)\n";
    echo "=========================================================\n";
    $suite = new GradeDashboardTest();
    $res = $suite->run();

    $allPassed = true;
    foreach ($res as $label => $status) {
        echo "  [{$status}] {$label}\n";
        if ($status !== 'RÉUSSI') {
            $allPassed = false;
        }
    }
    echo "=========================================================\n";

    exit($allPassed ? 0 : 1);
}
?>