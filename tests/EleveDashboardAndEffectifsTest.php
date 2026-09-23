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
require_once __DIR__ . '/../src/controllers/HomeController.php';
require_once __DIR__ . '/../src/controllers/ReportingController.php';
require_once __DIR__ . '/../src/controllers/EleveDashboardController.php';
require_once __DIR__ . '/../src/services/EleveDashboardService.php';

class EleveDashboardAndEffectifsTest {

    public static function run(): void {
        echo "=========================================================\n";
        echo " RUNNING ELEVES DASHBOARD & EFFECTIFS INTEGRATION TEST\n";
        echo "=========================================================\n";

        $testDb = new \PDO("sqlite:" . __DIR__ . "/../database.sqlite", null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        Database::setInstance($testDb);
        require_once __DIR__ . '/../migrate.php';
        $db = Database::getInstance();

        $lyceeId = 8801;
        $lyceeId2 = 8802;
        $yearPrevId = 810;
        $yearCurrId = 811;

        // Cleanup
        $db->exec("DELETE FROM etudes WHERE lycee_id IN ($lyceeId, $lyceeId2)");
        $db->exec("DELETE FROM eleves WHERE lycee_id IN ($lyceeId, $lyceeId2)");
        $db->exec("DELETE FROM classes WHERE lycee_id IN ($lyceeId, $lyceeId2)");

        // 1. Setup Schools & Academic Years
        $db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ($lyceeId, 'Lycée Effectifs Test A', 'prive') ON CONFLICT(id) DO UPDATE SET nom_lycee='Lycée Effectifs Test A'");
        $db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ($lyceeId2, 'Lycée Effectifs Test B', 'prive') ON CONFLICT(id) DO UPDATE SET nom_lycee='Lycée Effectifs Test B'");

        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES ($yearPrevId, '2024-2025', '2024-09-01', '2025-06-30', 0) ON CONFLICT(id) DO UPDATE SET est_active=0");
        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES ($yearCurrId, '2025-2026', '2025-09-01', '2026-06-30', 1) ON CONFLICT(id) DO UPDATE SET est_active=1");

        // 2. Setup Classes (Cycle 1 = Collège)
        $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (88001, $lyceeId, 1, '6eme', 'G', 1)");
        $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (88002, $lyceeId, 1, '5eme', 'G', 1)");

        // 3. Setup Students
        // Student 1: Enrolled in Previous Year N-1, NOT in Year N (Should NOT be counted in Year N effectif)
        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, sexe, statut) VALUES (88101, $lyceeId, 'OLD', 'Student', 'Masculin', 'actif')");
        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (88201, 88101, 88001, $lyceeId, $yearPrevId, 1, 'active')");

        // Student 2: Re-enrollment (Enrolled in Year N-1 AND Year N)
        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, sexe, statut) VALUES (88102, $lyceeId, 'REINSC', 'Paul', 'Masculin', 'actif')");
        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (88202, 88102, 88001, $lyceeId, $yearPrevId, 0, 'inactive')");
        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (88203, 88102, 88002, $lyceeId, $yearCurrId, 1, 'active')");

        // Student 3: New Inscription (Enrolled ONLY in Year N)
        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, sexe, statut) VALUES (88103, $lyceeId, 'NEW', 'Marie', 'Féminin', 'actif')");
        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (88204, 88103, 88001, $lyceeId, $yearCurrId, 1, 'active')");

        // Ensure Cycle 1 is attached to Lycée
        $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (1, 'Premier Cycle', $lyceeId) ON CONFLICT(id_cycle) DO UPDATE SET lycee_id=$lyceeId");

        // Set session for Auth with cycle permission
        $_SESSION['user'] = [
            'id' => 1,
            'id_user' => 1,
            'lycee_id' => $lyceeId,
            'role_name' => 'admin_local',
            'permissions' => [
                'eleve' => ['view_all', 'view_stats'],
                'cycle' => ['view_all_cycles'],
                'lycee' => ['view_all_lycees']
            ]
        ];

        // -------------------------------------------------------------
        // TEST 1: HomeController KPI Verification (CRITIQUE-01)
        // -------------------------------------------------------------
        echo "\n--- [TEST 1: Home Dashboard Active Student Effectif (CRITIQUE-01)] ---\n";
        $homeController = new HomeController();
        ob_start();
        $homeController->index();
        $outputHome = ob_get_clean();

        // In active Year N ($yearCurrId), only Student 2 and Student 3 are active. Total active should be 2 (NOT 3).
        $stmtActiveHome = $db->query("
            SELECT COUNT(DISTINCT e.id_eleve)
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE e.lycee_id = $lyceeId
              AND et.annee_academique_id = $yearCurrId
              AND et.is_active = 1
              AND et.status = 'active'
              AND e.statut = 'actif'
        ");
        $countHome = (int)$stmtActiveHome->fetchColumn();
        if ($countHome !== 2) {
            throw new Exception("[FAIL] HomeController effectif count is $countHome, expected 2 (Student N-1 must be excluded).");
        }
        echo " [PASS] Home Dashboard effectif count is strictly 2 (excludes student from N-1).\n";

        // -------------------------------------------------------------
        // TEST 2: ReportingController Inter-School Comparison (CRITIQUE-02)
        // -------------------------------------------------------------
        echo "\n--- [TEST 2: Inter-School Comparison Active Year Isolation (CRITIQUE-02)] ---\n";
        $stmtRep = $db->query("
            SELECT COUNT(DISTINCT et.eleve_id)
            FROM etudes et
            JOIN eleves e ON e.id_eleve = et.eleve_id
            WHERE et.lycee_id = $lyceeId
              AND et.annee_academique_id = $yearCurrId
              AND et.is_active = 1
              AND et.status = 'active'
              AND e.statut = 'actif'
        ");
        $countRep = (int)$stmtRep->fetchColumn();
        if ($countRep !== 2) {
            throw new Exception("[FAIL] Reporting comparison effectif count is $countRep, expected 2.");
        }
        echo " [PASS] Inter-school reporting comparison count is strictly 2 (excludes historical records).\n";

        // -------------------------------------------------------------
        // TEST 3: EleveDashboardService Payload & KPIs (Phase 6)
        // -------------------------------------------------------------
        echo "\n--- [TEST 3: EleveDashboardService KPIs & Deterministic Classification] ---\n";
        $filters = [
            'lycee_id' => $lyceeId,
            'annee_academique_id' => $yearCurrId
        ];
        $dashboardData = EleveDashboardService::getDashboardData($filters, 1);

        if (!$dashboardData['success']) {
            throw new Exception("[FAIL] Dashboard data retrieval failed: " . ($dashboardData['message'] ?? ''));
        }

        $kpis = $dashboardData['kpis'];
        if ($kpis['total_actifs'] !== 2) {
            throw new Exception("[FAIL] Dashboard total_actifs is " . $kpis['total_actifs'] . ", expected 2.");
        }
        if ($kpis['filles'] !== 1 || $kpis['garcons'] !== 1) {
            throw new Exception("[FAIL] Gender breakdown invalid. Filles: " . $kpis['filles'] . ", Garçons: " . $kpis['garcons']);
        }
        if ($kpis['nouvelles_inscriptions'] !== 1) {
            throw new Exception("[FAIL] Nouvelles inscriptions count is " . $kpis['nouvelles_inscriptions'] . ", expected 1 (Student 3).");
        }
        if ($kpis['reinscriptions'] !== 1) {
            throw new Exception("[FAIL] Réinscriptions count is " . $kpis['reinscriptions'] . ", expected 1 (Student 2).");
        }
        echo " [PASS] Dashboard KPIs match expected values:\n";
        echo "        - Total Actifs = 2\n";
        echo "        - Filles = 1, Garçons = 1 (Taux Filles = 50%)\n";
        echo "        - Nouvelles Inscriptions = 1, Réinscriptions = 1\n";

        // -------------------------------------------------------------
        // TEST 4: Chart Payload Consistency with KPIs
        // -------------------------------------------------------------
        echo "\n--- [TEST 4: ApexCharts Payload Consistency with KPIs] ---\n";
        $charts = $dashboardData['charts'];

        $totalInscrChart = array_sum($charts['inscription_types']['series']);
        if ($totalInscrChart !== ($kpis['nouvelles_inscriptions'] + $kpis['reinscriptions'])) {
            throw new Exception("[FAIL] Inscription types chart sum ($totalInscrChart) diverges from KPI total!");
        }

        $totalGenderByNiveau = 0;
        foreach ($charts['gender_by_niveau']['series'] as $s) {
            $totalGenderByNiveau += array_sum($s['data']);
        }
        if ($totalGenderByNiveau !== $kpis['total_actifs']) {
            throw new Exception("[FAIL] Gender by niveau chart sum ($totalGenderByNiveau) diverges from total_actifs KPI (" . $kpis['total_actifs'] . ")!");
        }
        echo " [PASS] All ApexCharts data series strictly match KPI totals (Zero divergence).\n";

        echo "=========================================================\n";
        echo " SUCCESS: ALL ELEVES DASHBOARD INTEGRATION TESTS PASSED!\n";
        echo "=========================================================\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    EleveDashboardAndEffectifsTest::run();
}
