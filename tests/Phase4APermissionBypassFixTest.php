<?php

// Test suite for Phase 4A: Elimination of Session Permission Array Bypass
define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';
require_once __DIR__ . '/../src/models/Budget.php';
require_once __DIR__ . '/../src/models/BudgetLigne.php';
require_once __DIR__ . '/../src/models/AchatCategorie.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/BudgetAdjustmentService.php';
require_once __DIR__ . '/../src/services/BudgetControlService.php';
require_once __DIR__ . '/../src/services/BudgetWorkflowService.php';
require_once __DIR__ . '/../src/services/DepenseWorkflowService.php';

function run_phase4a_tests() {
    echo "=====================================================\n";
    echo "Running Test Suite: Phase 4A Permission Bypass Fix\n";
    echo "=====================================================\n";

    $db = Database::getInstance();

    // Retrieve or seed required test permissions
    $permBudgetTransfer = $db->query("SELECT id_permission FROM permissions WHERE resource = 'budget' AND action = 'transfer'")->fetchColumn();
    $permBudgetAdjust   = $db->query("SELECT id_permission FROM permissions WHERE resource = 'budget' AND action = 'adjust'")->fetchColumn();
    $permBudgetOverride = $db->query("SELECT id_permission FROM permissions WHERE resource = 'budget' AND action = 'override'")->fetchColumn();
    $permBudgetActivate = $db->query("SELECT id_permission FROM permissions WHERE resource = 'budget' AND action = 'activate'")->fetchColumn();
    $permDepenseCreate  = $db->query("SELECT id_permission FROM permissions WHERE resource = 'depense' AND action = 'create'")->fetchColumn();

    if (!$permBudgetTransfer || !$permBudgetAdjust || !$permBudgetOverride || !$permBudgetActivate || !$permDepenseCreate) {
        $db->exec("INSERT OR IGNORE INTO permissions (id_permission, resource, action, description) VALUES
            (167, 'budget', 'transfer', 'Transfer budget credits'),
            (166, 'budget', 'adjust', 'Allocate extra budget'),
            (169, 'budget', 'override', 'Override budget limit'),
            (164, 'budget', 'activate', 'Activate budget'),
            (150, 'depense', 'create', 'Create expense')
        ");
        $permBudgetTransfer = 167;
        $permBudgetAdjust   = 166;
        $permBudgetOverride = 169;
        $permBudgetActivate = 164;
        $permDepenseCreate  = 150;
    }

    $db->beginTransaction();

    try {
        // Setup Roles
        Role::save(['nom_role' => 'Phase 4A Authorized Role']);
        $roleAuthId = (int)$db->lastInsertId();
        Role::setPermissions($roleAuthId, [$permBudgetTransfer, $permBudgetAdjust, $permBudgetOverride, $permBudgetActivate, $permDepenseCreate]);

        Role::save(['nom_role' => 'Phase 4A Restricted Role']);
        $roleRestrictedId = (int)$db->lastInsertId();
        Role::setPermissions($roleRestrictedId, []); // No permissions

        // Setup User
        $passHash = password_hash('pass123', PASSWORD_DEFAULT);
        $userId = User::save([
            'nom' => 'User4A', 'prenom' => 'BypassTest', 'email' => 'user4a@test.com',
            'mot_de_passe' => 'pass123', 'role_id' => $roleAuthId, 'actif' => 1
        ]);

        // ----------------------------------------------------
        // TEST 1: Authorized User executes operations
        // ----------------------------------------------------
        echo "  [Test 1] Authorized user permission checks...\n";
        Auth::logout();
        Auth::login('user4a@test.com', 'pass123');

        if (Auth::can('transfer', 'budget') && Auth::can('adjust', 'budget') && Auth::can('override', 'budget')) {
            echo "    PASS: Authorized user with valid DB permissions granted access.\n";
        } else {
            throw new Exception("FAIL: Authorized user was unexpectedly denied!");
        }

        // ----------------------------------------------------
        // TEST 2 & 3: Attempted Session Bypass when Auth::can() returns false
        // ----------------------------------------------------
        echo "  [Test 2 & 3] Verification that raw session permissions cannot bypass Auth::can() refusal...\n";

        // Downgrade user's role in DB to Restricted Role (no permissions)
        User::save([
            'id_user' => $userId,
            'nom' => 'User4A', 'prenom' => 'BypassTest', 'email' => 'user4a@test.com',
            'role_id' => $roleRestrictedId, 'actif' => 1
        ]);

        // Manually pollute session permissions array with fake 'budget' => ['transfer', 'adjust', 'override']
        $_SESSION['user']['permissions']['budget'] = ['transfer', 'adjust', 'override'];
        $_SESSION['user']['permissions']['depense'] = ['create'];

        // Auth::can() will perform session sync, see DB auth_version mismatch, reload fresh empty permissions, and return FALSE
        $canTransfer = Auth::can('transfer', 'budget');
        $canAdjust   = Auth::can('adjust', 'budget');
        $canOverride = Auth::can('override', 'budget');

        if (!$canTransfer && !$canAdjust && !$canOverride) {
            echo "    PASS: Auth::can() returned false; session pollution was cleanly overridden.\n";
        } else {
            throw new Exception("FAIL: Raw session array allowed bypass!");
        }

        // Test BudgetAdjustmentService::transferBudget refusal with session pollution
        $transferRefused = false;
        try {
            BudgetAdjustmentService::transferBudget(1, 2, 1000, $userId, 'Test Transfer');
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'FORBIDDEN')) {
                $transferRefused = true;
            }
        }

        // Test BudgetAdjustmentService::allocateExtra refusal with session pollution
        $allocateRefused = false;
        try {
            BudgetAdjustmentService::allocateExtra(1, 1000, $userId, 'Test Extra');
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'FORBIDDEN')) {
                $allocateRefused = true;
            }
        }

        if ($transferRefused && $allocateRefused) {
            echo "    PASS: BudgetAdjustmentService methods strictly rejected unauthorized request despite session pollution.\n";
        } else {
            throw new Exception("FAIL: BudgetAdjustmentService allowed session array bypass!");
        }

        // ----------------------------------------------------
        // TEST 4: BudgetControlService::authorizeSpending override refusal
        // ----------------------------------------------------
        echo "  [Test 4] BudgetControlService::authorizeSpending override refusal...\n";
        // Create Budget and Line with 0 balance
        $budgetId = Budget::create([
            'lycee_id' => 1, 'exercice_financier_id' => 1, 'libelle' => 'Test Budget 4A',
            'statut' => 'actif', 'cree_par' => $userId
        ]);

        $catId = AchatCategorie::create([
            'libelle' => 'Cat 4A', 'compte_comptable_charge' => '601000'
        ]);

        $ligneId = BudgetLigne::create([
            'budget_id' => $budgetId, 'categorie_id' => $catId, 'allocation_initiale' => 0
        ]);

        // Attempt spending exceeding available balance without 'override' permission (session polluted)
        $_SESSION['user']['permissions']['budget'] = ['override'];

        $authRes = BudgetControlService::authorizeSpending(1, 1, $catId, null, 100000, $userId);

        if (!$authRes['authorized'] && str_contains($authRes['message'], 'Fonds insuffisants')) {
            echo "    PASS: BudgetControlService::authorizeSpending refused override when Auth::can('override', 'budget') is false.\n";
        } else {
            throw new Exception("FAIL: BudgetControlService allowed override via session array bypass!");
        }

        // ----------------------------------------------------
        // TEST 5: BudgetWorkflowService and DepenseWorkflowService checkPermission
        // ----------------------------------------------------
        echo "  [Test 5] BudgetWorkflowService & DepenseWorkflowService refusal...\n";
        $_SESSION['user']['permissions']['budget'] = ['activate'];
        $_SESSION['user']['permissions']['depense'] = ['create'];

        $budgetWorkflowRefused = false;
        try {
            BudgetWorkflowService::activate($budgetId, $userId);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'FORBIDDEN')) {
                $budgetWorkflowRefused = true;
            }
        }

        $depenseWorkflowRefused = false;
        try {
            DepenseWorkflowService::submitForApproval(99999, $userId);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'FORBIDDEN')) {
                $depenseWorkflowRefused = true;
            }
        }

        if ($budgetWorkflowRefused && $depenseWorkflowRefused) {
            echo "    PASS: BudgetWorkflowService and DepenseWorkflowService strictly enforced Auth::can() refusals.\n";
        } else {
            throw new Exception("FAIL: Workflow services allowed session permission array bypass!");
        }

        echo "\n=====================================================\n";
        echo " ALL PHASE 4A TEST SCENARIOS PASSED SUCCESSFULLY!\n";
        echo "=====================================================\n";

    } finally {
        try {
            $db->exec("DELETE FROM utilisateurs WHERE email = 'user4a@test.com'");
            $db->exec("DELETE FROM roles WHERE nom_role LIKE 'Phase 4A%'");
            $db->exec("DELETE FROM budget_lignes WHERE budget_id IN (SELECT id FROM budgets WHERE libelle = 'Test Budget 4A')");
            $db->exec("DELETE FROM budgets WHERE libelle = 'Test Budget 4A'");
            $db->exec("DELETE FROM achat_categories WHERE libelle = 'Cat 4A'");
        } catch (Throwable $e) {}
        $db->rollBack();
        Auth::logout();
    }
}

run_phase4a_tests();
?>