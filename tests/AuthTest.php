<?php

// Test for Auth class, specifically the can() method.

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';

function run_test() {
    echo "Running test: Auth::can() Authorization Logic...\n";

    // --- Test Case: User with 'user' -> 'edit' permission ---
    echo "  Case: Auth::can('user_edit') returns true for authorized user.\n";

    $dbFile = '/tmp/test_auth.sqlite';
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS param_lycee (id INTEGER PRIMARY KEY AUTOINCREMENT, nom_lycee VARCHAR(255), type_lycee VARCHAR(50))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (id_role INTEGER PRIMARY KEY AUTOINCREMENT, nom_role VARCHAR(100), lycee_id INTEGER)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (id_permission INTEGER PRIMARY KEY AUTOINCREMENT, resource VARCHAR(100), action VARCHAR(100), description TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (role_id INTEGER, permission_id INTEGER, PRIMARY KEY (role_id, permission_id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(100),
        prenom VARCHAR(100),
        sexe VARCHAR(10),
        date_naissance DATE,
        lieu_naissance VARCHAR(100),
        adresse VARCHAR(255),
        telephone VARCHAR(50),
        email VARCHAR(255) UNIQUE,
        mot_de_passe VARCHAR(255),
        fonction VARCHAR(100),
        role_id INTEGER,
        lycee_id INTEGER,
        contrat_id INTEGER,
        date_embauche DATE,
        actif BOOLEAN DEFAULT TRUE,
        photo VARCHAR(255),
        identifiant_public VARCHAR(50) UNIQUE
    )");

    Database::setInstance($pdo);
    $db = Database::getInstance();

    // Seed param_lycee so generatePublicId and other queries don't fail
    $db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Test Lycee', 'prive')");

    $db->beginTransaction();

    try {
        // 1. Setup: Create a role with the 'user' -> 'edit' permission
        Role::save(['nom_role' => 'Test Auth Role']);
        $role_id = $db->lastInsertId();

        // Seed permission
        $db->exec("INSERT INTO permissions (resource, action, description) VALUES ('user', 'edit', 'Edit users')");
        $perm_id = $db->lastInsertId();

        Role::setPermissions($role_id, [$perm_id]);

        // 2. Create a user with this role
        User::save([
            'nom' => 'AuthCan', 'prenom' => 'Test', 'email' => 'authcantest@example.com',
            'mot_de_passe' => 'password', 'role_id' => $role_id, 'actif' => 1
        ]);

        // 3. Log in as the user
        if (!Auth::login('authcantest@example.com', 'password')) {
            throw new Exception("Login failed unexpectedly.");
        }

        // 4. Check the permission
        $has_permission = Auth::can('edit', 'user');

        // 5. Assert the result
        if ($has_permission) {
            echo "    [PASS] Auth::can('edit', 'user') correctly returned true.\n";
        } else {
            echo "    [FAIL] Auth::can('edit', 'user') returned false for an authorized user.\n";
            exit(1);
        }

        echo "All assertions passed!\n";

    } catch (Exception $e) {
        echo "    [FAIL] Test failed with exception: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
        Auth::logout();
    }
}

run_test();
?>