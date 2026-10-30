<?php

// Test suite for Role and Permission Session Synchronization (Phase 1)
define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';

function run_sync_tests() {
    echo "=====================================================\n";
    echo "Running Test Suite: Role & Permission Session Sync\n";
    echo "=====================================================\n";

    $db = Database::getInstance();

    // Ensure database contains permissions catalog
    $perm_id_user_create = $db->query("SELECT id_permission FROM permissions WHERE resource = 'user' AND action = 'create'")->fetchColumn();
    $perm_id_user_delete = $db->query("SELECT id_permission FROM permissions WHERE resource = 'user' AND action = 'delete'")->fetchColumn();
    $perm_id_eleve_create = $db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'create'")->fetchColumn();

    if (!$perm_id_user_create || !$perm_id_user_delete || !$perm_id_eleve_create) {
        $db->exec("INSERT OR IGNORE INTO permissions (id_permission, resource, action, description) VALUES
            (10, 'user', 'create', 'Can create users'),
            (14, 'user', 'delete', 'Can delete users'),
            (50, 'eleve', 'create', 'Can create students')
        ");
        $perm_id_user_create = 10;
        $perm_id_user_delete = 14;
        $perm_id_eleve_create = 50;
    }

    $db->beginTransaction();

    try {
        // Create Role A (user:create) and Role B (eleve:create)
        Role::save(['nom_role' => 'Sync Test Role A']);
        $roleA_id = (int)$db->lastInsertId();
        Role::setPermissions($roleA_id, [$perm_id_user_create]);

        Role::save(['nom_role' => 'Sync Test Role B']);
        $roleB_id = (int)$db->lastInsertId();
        Role::setPermissions($roleB_id, [$perm_id_eleve_create]);

        // Create User 1
        $u1_id = User::save([
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'mot_de_passe' => 'password123', 'role_id' => $roleA_id, 'actif' => 1
        ]);

        // Create User 2
        $u2_id = User::save([
            'nom' => 'SyncUser2', 'prenom' => 'Test', 'email' => 'syncuser2@test.com',
            'mot_de_passe' => 'password123', 'role_id' => $roleB_id, 'actif' => 1
        ]);

        // ----------------------------------------------------
        // TEST 1 — Modification du rôle en cours de session
        // ----------------------------------------------------
        echo "  [Test 1] Modification du rôle en cours de session...\n";
        Auth::logout();
        if (!Auth::login('syncuser1@test.com', 'password123')) {
            throw new Exception("Login failed for user 1");
        }

        if (!Auth::can('create', 'user') || Auth::can('create', 'eleve')) {
            throw new Exception("Initial Role A permissions check failed!");
        }

        // Change User 1 role from A to B
        User::save([
            'id_user' => $u1_id,
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'role_id' => $roleB_id, 'actif' => 1
        ]);

        // Session retains same active connection context; perform new request check
        $can_create_user = Auth::can('create', 'user');
        $can_create_eleve = Auth::can('create', 'eleve');

        if (!$can_create_user && $can_create_eleve && Auth::get('role_id') == $roleB_id) {
            echo "    PASS: Role updated from A to B dynamically. Old permissions revoked, new permissions granted.\n";
        } else {
            throw new Exception("FAIL: Role modification was not synchronized in session!");
        }

        // ----------------------------------------------------
        // TEST 2 — Révocation de privilèges
        // ----------------------------------------------------
        echo "  [Test 2] Révocation de privilèges...\n";
        // Role B currently has eleve:create. Re-add user:delete to Role B then revoke it.
        Role::setPermissions($roleB_id, [$perm_id_eleve_create, $perm_id_user_delete]);
        if (!Auth::can('delete', 'user')) {
            throw new Exception("Failed to grant user:delete to Role B!");
        }

        // Revoke user:delete from Role B
        Role::setPermissions($roleB_id, [$perm_id_eleve_create]);

        if (!Auth::can('delete', 'user') && Auth::can('create', 'eleve')) {
            echo "    PASS: Privilege revocation synchronized immediately across active session.\n";
        } else {
            throw new Exception("FAIL: Revoked privilege remained active in session!");
        }

        // ----------------------------------------------------
        // TEST 3 — Changement de rôle vers un rôle moins privilégié
        // ----------------------------------------------------
        echo "  [Test 3] Changement vers rôle moins privilégié (Downgrade)...\n";
        // Create Restricted Role C with no permissions
        Role::save(['nom_role' => 'Sync Restricted Role C']);
        $roleC_id = (int)$db->lastInsertId();
        Role::setPermissions($roleC_id, []);

        User::save([
            'id_user' => $u1_id,
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'role_id' => $roleC_id, 'actif' => 1
        ]);

        if (!Auth::can('create', 'eleve') && !Auth::can('create', 'user')) {
            echo "    PASS: Role downgrade revoked all former high privileges.\n";
        } else {
            throw new Exception("FAIL: High privileges persisted after role downgrade!");
        }

        // ----------------------------------------------------
        // TEST 4 — Changement de rôle vers un rôle plus privilégié
        // ----------------------------------------------------
        echo "  [Test 4] Changement vers rôle plus privilégié (Upgrade)...\n";
        User::save([
            'id_user' => $u1_id,
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'role_id' => $roleB_id, 'actif' => 1
        ]);

        if (Auth::can('create', 'eleve') && Auth::get('role_id') == $roleB_id) {
            echo "    PASS: Role upgrade granted new privileges without requiring re-login.\n";
        } else {
            throw new Exception("FAIL: Role upgrade failed to load new permissions!");
        }

        // ----------------------------------------------------
        // TEST 5 — Rôle supprimé ou invalide
        // ----------------------------------------------------
        echo "  [Test 5] Rôle supprimé ou invalide...\n";
        // Assign invalid role ID 999999 to user 1 in DB
        $db->prepare("UPDATE utilisateurs SET role_id = 999999, auth_version = auth_version + 1 WHERE id_user = :id")
           ->execute(['id' => $u1_id]);

        $can_do_anything = Auth::can('create', 'eleve');
        $is_checked = Auth::check();

        if (!$can_do_anything && !$is_checked) {
            echo "    PASS: Invalid or deleted role triggered fail-closed session revocation.\n";
        } else {
            throw new Exception("FAIL: Invalid role allowed access or kept session active!");
        }

        // ----------------------------------------------------
        // TEST 6 — Échec de synchronisation / Compte désactivé
        // ----------------------------------------------------
        echo "  [Test 6] Échec de synchronisation / Compte désactivé...\n";
        // Restore valid role B to user 1, log in, then deactivate user 1
        $db->prepare("UPDATE utilisateurs SET role_id = :rid, actif = 1, auth_version = auth_version + 1 WHERE id_user = :id")
           ->execute(['rid' => $roleB_id, 'id' => $u1_id]);

        Auth::logout();
        Auth::login('syncuser1@test.com', 'password123');
        if (!Auth::can('create', 'eleve')) {
            throw new Exception("Re-login for Test 6 failed.");
        }

        // Deactivate user in DB
        User::save([
            'id_user' => $u1_id,
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'role_id' => $roleB_id, 'actif' => 0
        ]);

        $can_access_after_deactivation = Auth::can('create', 'eleve');
        $check_after_deactivation = Auth::check();

        if (!$can_access_after_deactivation && !$check_after_deactivation) {
            echo "    PASS: Deactivated account failed closed immediately on next check.\n";
        } else {
            throw new Exception("FAIL: Deactivated account remained authorized!");
        }

        // ----------------------------------------------------
        // TEST 7 — Isolation entre deux sessions
        // ----------------------------------------------------
        echo "  [Test 7] Isolation entre deux sessions...\n";
        // Activate user 1 again with Role A
        User::save([
            'id_user' => $u1_id,
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'role_id' => $roleA_id, 'actif' => 1
        ]);

        // Session 1: User 1
        Auth::logout();
        Auth::login('syncuser1@test.com', 'password123');
        $sess1_user_id = Auth::getUserId();
        $sess1_role_id = Auth::get('role_id');

        // Session 2: User 2
        Auth::logout();
        Auth::login('syncuser2@test.com', 'password123');
        $sess2_user_id = Auth::getUserId();
        $sess2_role_id = Auth::get('role_id');

        // Modify User 1 role
        User::save([
            'id_user' => $u1_id,
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'role_id' => $roleC_id, 'actif' => 1
        ]);

        // Verify User 2 session is untouched
        if ($sess2_user_id == $u2_id && Auth::getUserId() == $u2_id && Auth::get('role_id') == $roleB_id && Auth::can('create', 'eleve')) {
            echo "    PASS: Session isolation maintained. Changes to User 1 did not contaminate User 2.\n";
        } else {
            throw new Exception("FAIL: Session cross-contamination detected!");
        }

        // ----------------------------------------------------
        // TEST 8 — Invalidation des cycles autorisés
        // ----------------------------------------------------
        echo "  [Test 8] Invalidation du cache des cycles autorisés...\n";
        Auth::logout();
        Auth::login('syncuser1@test.com', 'password123');
        $_SESSION['user']['authorized_cycles'] = [101, 102, 103]; // Mock cached cycles

        // Change User 1 role
        User::save([
            'id_user' => $u1_id,
            'nom' => 'SyncUser1', 'prenom' => 'Test', 'email' => 'syncuser1@test.com',
            'role_id' => $roleB_id, 'actif' => 1
        ]);

        // Run check to trigger sync
        Auth::can('create', 'eleve');

        if (!isset($_SESSION['user']['authorized_cycles']) || $_SESSION['user']['authorized_cycles'] !== [101, 102, 103]) {
            echo "    PASS: Stale authorized_cycles cache was invalidated on role change.\n";
        } else {
            throw new Exception("FAIL: Obsolete authorized_cycles cache survived role update!");
        }

        echo "\n=====================================================\n";
        echo " ALL 8 TEST SCENARIOS PASSED SUCCESSFULLY!\n";
        echo "=====================================================\n";

    } catch (Exception $e) {
        echo "\n    [FAIL] Test suite failed with exception: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        try {
            $db->exec("DELETE FROM utilisateurs WHERE email LIKE 'syncuser%' OR role_id = 999999");
            $db->exec("DELETE FROM roles WHERE nom_role LIKE 'Sync%'");
        } catch (Throwable $e) {}
        Auth::logout();
    }
}

run_sync_tests();
?>