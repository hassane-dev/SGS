<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';

// Setup SQLite test database for test suite
$dbFile = __DIR__ . "/../database.sqlite";
$testDb = new \PDO("sqlite:" . $dbFile, null, null, [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
]);
Database::setInstance($testDb);

// Run migrations and seeds
require_once __DIR__ . '/../migrate.php';

require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/services/GlobalDashboardService.php';
require_once __DIR__ . '/../src/controllers/GlobalDashboardController.php';
require_once __DIR__ . '/../src/controllers/HomeController.php';

class GlobalDashboardPhase8Test {

    private static $testPassedCount = 0;

    public static function runAllTests() {
        echo "=== DÉBUT DES TESTS D'INTÉGRATION PHASE 8 : DASHBOARD GLOBAL SGS ===\n\n";

        self::testSummaryKpisDomainMethods();
        self::testGlobalDashboardServiceAggregator();
        self::testRbacWidgetIsolation();
        self::testTenantIsolation();
        self::testRouteAndControllerRendering();
        self::testSidebarNavigationAndHomeDispatcher();
        self::testNonRegressionSpecializedHubs();
        self::testPerformanceMetrics();

        echo "\n=== TOUS LES TESTS PHASE 8 SE SONT ÉCOUTÉS AVEC SUCCÈS (" . self::$testPassedCount . " assertions validées) ===\n";
    }

    private static function assert($condition, $message) {
        if (!$condition) {
            echo "❌ ECHEC : $message\n";
            throw new Exception("Test échoué : $message");
        }
        echo "  [OK] $message\n";
        self::$testPassedCount++;
    }

    private static function setupMockUser($roleName = 'admin_local', $lyceeId = 1, $userId = 1) {
        $_SESSION = [];
        $_SESSION['user'] = [
            'id_user' => $userId,
            'id' => $userId,
            'email' => 'admin.test@sgs.com',
            'prenom' => 'Admin',
            'nom' => 'Test',
            'role_name' => $roleName,
            'role_id' => 1,
            'lycee_id' => $lyceeId
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.resource, p.action
            FROM permissions p
            JOIN role_permissions rp ON p.id_permission = rp.permission_id
            JOIN roles r ON r.id_role = rp.role_id
            WHERE r.nom_role = :role
        ");
        $stmt->execute(['role' => $roleName]);
        $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $_SESSION['permissions'] = [];
        foreach ($perms as $p) {
            $_SESSION['permissions'][$p['resource']][$p['action']] = true;
        }

        if ($roleName === 'admin_local') {
            // Seed presence & eleve permissions in DB for role 1 if not present
            $db = Database::getInstance();
            $needed = [
                ['resource' => 'presence', 'action' => 'view_all'],
                ['resource' => 'presence', 'action' => 'manage'],
                ['resource' => 'presence', 'action' => 'view'],
                ['resource' => 'eleve', 'action' => 'view_all'],
                ['resource' => 'eleve', 'action' => 'view_stats'],
                ['resource' => 'paiement', 'action' => 'view']
            ];
            foreach ($needed as $item) {
                $res = $item['resource'];
                $act = $item['action'];
                $stmtP = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :res AND action = :act");
                $stmtP->execute(['res' => $res, 'act' => $act]);
                $pId = $stmtP->fetchColumn();
                if (!$pId) {
                    $db->prepare("INSERT INTO permissions (resource, action) VALUES (:res, :act)")->execute(['res' => $res, 'act' => $act]);
                    $pId = $db->lastInsertId();
                }
                $stmtRp = $db->prepare("SELECT 1 FROM role_permissions WHERE role_id = 1 AND permission_id = :pid");
                $stmtRp->execute(['pid' => $pId]);
                if (!$stmtRp->fetchColumn()) {
                    $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, :pid)")->execute(['pid' => $pId]);
                }
            }
        }

    }

    /**
     * Test 1: Verify each domain service provides a valid getSummaryKpis() payload
     */
    public static function testSummaryKpisDomainMethods() {
        echo "--- Test 1 : Validation des méthodes Summary KPI des 6 services de domaine ---\n";

        self::setupMockUser('admin_local', 1, 1);
        $lyceeId = 1;
        $activeYear = AnneeAcademique::findActive();
        $anneeId = $activeYear ? (int)$activeYear['id'] : 1;

        // 1. Grade Summary
        $gradeSummary = GradeDashboardService::getSummaryKpis($lyceeId, $anneeId);
        self::assert(is_array($gradeSummary), "GradeDashboardService::getSummaryKpis retourne un tableau");
        self::assert(array_key_exists('average_grade', $gradeSummary), "GradeSummary contient average_grade");
        self::assert(array_key_exists('completion_rate', $gradeSummary), "GradeSummary contient completion_rate");

        // 2. Presence Summary
        $presenceSummary = PresenceDashboardService::getSummaryKpis($lyceeId, $anneeId, 1);
        self::assert(is_array($presenceSummary), "PresenceDashboardService::getSummaryKpis retourne un tableau");
        self::assert(array_key_exists('presence_rate', $presenceSummary), "PresenceSummary contient presence_rate");

        // 3. Discipline Summary
        $disciplineSummary = DisciplineSearchService::getSummaryKpis($lyceeId, $anneeId, 1, 'admin_local', true);
        self::assert(is_array($disciplineSummary), "DisciplineSearchService::getSummaryKpis retourne un tableau");
        self::assert(array_key_exists('total_incidents', $disciplineSummary), "DisciplineSummary contient total_incidents");

        // 4. Treasury Summary
        $treasurySummary = TreasuryDashboardService::getSummaryKpis($lyceeId, $anneeId);
        self::assert(is_array($treasurySummary), "TreasuryDashboardService::getSummaryKpis retourne un tableau");
        self::assert(array_key_exists('solde_total_liquidites', $treasurySummary), "TreasurySummary contient solde_total_liquidites");

        // 5. Paie Summary
        $paieSummary = PaieDashboardService::getSummaryKpis($lyceeId);
        self::assert(is_array($paieSummary), "PaieDashboardService::getSummaryKpis retourne un tableau");
        self::assert(array_key_exists('effectif_rh_actif', $paieSummary), "PaieSummary contient effectif_rh_actif");

        // 6. Eleve Summary
        $eleveSummary = EleveDashboardService::getSummaryKpis($lyceeId, $anneeId);
        self::assert(is_array($eleveSummary), "EleveDashboardService::getSummaryKpis retourne un tableau");
        self::assert(array_key_exists('total_actifs', $eleveSummary), "EleveSummary contient total_actifs");
    }

    /**
     * Test 2: Verify GlobalDashboardService aggregates payload according to permissions
     */
    public static function testGlobalDashboardServiceAggregator() {
        echo "--- Test 2 : Aggégateur GlobalDashboardService ---\n";

        self::setupMockUser('admin_local', 1, 1);

        $allPerms = [
            'canSeeAcademic' => true,
            'canSeePresence' => true,
            'canSeeDiscipline' => true,
            'canSeeFinance' => true,
            'canSeeRh' => true,
            'canSeeEffectifs' => true
        ];

        $payload = GlobalDashboardService::getDashboardData(1, $allPerms);
        self::assert(is_array($payload), "GlobalDashboardService retourne un tableau");
        self::assert(isset($payload['kpis']['academic']), "Payload contient le pilier Academic");
        self::assert(isset($payload['kpis']['presence']), "Payload contient le pilier Presence");
        self::assert(isset($payload['kpis']['discipline']), "Payload contient le pilier Discipline");
        self::assert(isset($payload['kpis']['finance']), "Payload contient le pilier Finance");
        self::assert(isset($payload['kpis']['rh']), "Payload contient le pilier RH");
        self::assert(isset($payload['kpis']['effectifs']), "Payload contient le pilier Effectifs");
    }

    /**
     * Test 3: Verify RBAC widget isolation (unauthorized pillars are omitted)
     */
    public static function testRbacWidgetIsolation() {
        echo "--- Test 3 : Isolation RBAC granulaire par widget ---\n";

        self::setupMockUser('enseignant', 1, 10);

        // Teacher permission set: only academic & presence, no finance/rh
        $teacherPerms = [
            'canSeeAcademic' => true,
            'canSeePresence' => true,
            'canSeeDiscipline' => false,
            'canSeeFinance' => false,
            'canSeeRh' => false,
            'canSeeEffectifs' => false
        ];

        $payload = GlobalDashboardService::getDashboardData(1, $teacherPerms);
        self::assert(isset($payload['kpis']['academic']), "Enseignant reçoit le widget Academic");
        self::assert(isset($payload['kpis']['presence']), "Enseignant reçoit le widget Presence");
        self::assert(!isset($payload['kpis']['finance']), "Enseignant NE REÇOIT PAS le widget Finance");
        self::assert(!isset($payload['kpis']['rh']), "Enseignant NE REÇOIT PAS le widget RH");
    }

    /**
     * Test 4: Verify Multi-tenant Lycée ID isolation
     */
    public static function testTenantIsolation() {
        echo "--- Test 4 : Isolation multi-tenant lycee_id ---\n";

        self::setupMockUser('admin_local', 2, 20); // Lycée 2

        $allPerms = ['canSeeEffectifs' => true];
        $payloadLycee2 = GlobalDashboardService::getDashboardData(2, $allPerms);
        self::assert($payloadLycee2['lycee_id'] === 2, "GlobalDashboardService utilise strictly lycee_id = 2");
    }

    /**
     * Test 5: Verify Route /dashboard & Controller rendering
     */
    public static function testRouteAndControllerRendering() {
        echo "--- Test 5 : Route /dashboard et rendu du GlobalDashboardController ---\n";

        self::setupMockUser('admin_local', 1, 1);

        ob_start();
        $controller = new GlobalDashboardController();
        $controller->index();
        $output = ob_get_clean();

        self::assert(strpos($output, 'Dashboard Global') !== false, "La vue /dashboard est rendue avec le titre 'Dashboard Global'");
        self::assert(strpos($output, 'Vision globale transversale 360°') !== false, "La vue contient le sous-titre de pilotage");
    }

    /**
     * Test 6: Verify Sidebar navigation points to /dashboard and / retains historical dispatcher
     */
    public static function testSidebarNavigationAndHomeDispatcher() {
        echo "--- Test 6 : Navigation sidebar et dispatcher HomeController ---\n";

        self::setupMockUser('admin_local', 1, 1);

        // Sidebar check
        ob_start();
        require __DIR__ . '/../src/views/layouts/sidebar_able.php';
        $sidebarOutput = ob_get_clean();

        self::assert(strpos($sidebarOutput, 'href="/dashboard"') !== false, "sidebar_able.php pointe vers /dashboard");

        // HomeController check
        ob_start();
        $homeController = new HomeController();
        $homeController->index();
        $homeOutput = ob_get_clean();

        self::assert(strpos($homeOutput, 'Dashboard Global 360°') !== false || strpos($homeOutput, '/dashboard') !== false, "HomeController::index() intègre un lien vers /dashboard");
    }

    /**
     * Test 7: Verify non-regression of all 8 specialized operational hubs
     */
    public static function testNonRegressionSpecializedHubs() {
        echo "--- Test 7 : Non-régression des 8 hubs opérationnels spécialisés ---\n";

        self::setupMockUser('admin_local', 1, 1);

        // 1. Grade Dashboard
        require_once __DIR__ . '/../src/controllers/GradeDashboardController.php';
        ob_start();
        (new GradeDashboardController())->index();
        $outGrade = ob_get_clean();
        self::assert(strlen($outGrade) > 100, "/evaluations/dashboard fonctionne normalement");

        // 2. Treasury Dashboard
        require_once __DIR__ . '/../src/controllers/TreasuryDashboardController.php';
        ob_start();
        (new TreasuryDashboardController())->index();
        $outTreso = ob_get_clean();
        self::assert(strlen($outTreso) > 100, "/treasury/dashboard fonctionne normalement");

        // 3. Presence Dashboard
        require_once __DIR__ . '/../src/controllers/PresenceDashboardController.php';
        ob_start();
        (new PresenceDashboardController())->index();
        $outPres = ob_get_clean();
        self::assert(strlen($outPres) > 100, "/presences/dashboard fonctionne normalement");

        // 4. Discipline Dashboard
        require_once __DIR__ . '/../src/controllers/DisciplineDashboardController.php';
        ob_start();
        (new DisciplineDashboardController())->index();
        $outDisc = ob_get_clean();
        self::assert(strlen($outDisc) > 100, "/discipline/dashboard fonctionne normalement");

        // 5. DRH Dashboard
        require_once __DIR__ . '/../src/controllers/PaieDashboardController.php';
        ob_start();
        (new PaieDashboardController())->index();
        $outDrh = ob_get_clean();
        self::assert(strlen($outDrh) > 100, "/drh/dashboard fonctionne normalement");

        // 6. Eleve Dashboard
        require_once __DIR__ . '/../src/controllers/EleveDashboardController.php';
        ob_start();
        (new EleveDashboardController())->index();
        $outEleve = ob_get_clean();
        self::assert(strlen($outEleve) > 100, "/eleves/dashboard fonctionne normalement");

        // 7. Paiements Dashboard
        require_once __DIR__ . '/../src/controllers/PaiementController.php';
        ob_start();
        (new PaiementController())->index();
        $outPay = ob_get_clean();
        self::assert(strlen($outPay) > 100, "/paiements fonctionne normalement");

        // 8. Reporting Cockpit
        require_once __DIR__ . '/../src/controllers/ReportingController.php';
        ob_start();
        (new ReportingController())->dashboard();
        $outRep = ob_get_clean();
        self::assert(strlen($outRep) > 100, "/reporting fonctionne normalement");
    }

    /**
     * Test 8: Measure performance (query count and execution time)
     */
    public static function testPerformanceMetrics() {
        echo "--- Test 8 : Mesure des performances du Dashboard Global ---\n";

        self::setupMockUser('admin_local', 1, 1);

        $startTime = microtime(true);

        $allPerms = [
            'canSeeAcademic' => true,
            'canSeePresence' => true,
            'canSeeDiscipline' => true,
            'canSeeFinance' => true,
            'canSeeRh' => true,
            'canSeeEffectifs' => true
        ];

        $payload = GlobalDashboardService::getDashboardData(1, $allPerms);

        $endTime = microtime(true);
        $durationMs = round(($endTime - $startTime) * 1000, 2);

        echo "  [PERF] Temps d'exécution de GlobalDashboardService : {$durationMs} ms\n";
        self::assert($durationMs < 200, "Le Dashboard Global s'exécute en moins de 200 ms (mesuré : {$durationMs} ms)");
    }
}

// Run test suite
GlobalDashboardPhase8Test::runAllTests();
