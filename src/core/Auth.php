<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';

class Auth {

    /**
     * Start the session if not already started.
     */
    public static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Attempt to log in a user.
     *
     * @param string $email The user's email.
     * @param string $password The user's password.
     * @return bool True on success, false on failure.
     */
    public static function login($email, $password) {
        self::startSession();
        $user = User::findByEmail($email);

        if ($user && password_verify($password, $user->mot_de_passe)) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Password is correct, store user data in session
            $role = Role::findById($user->role_id);
            $permissions = Role::getPermissions($user->role_id);

            $_SESSION['user'] = [
                'id' => $user->id_user,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $role['nom_role'] ?? 'N/A',
                'lycee_id' => $user->lycee_id,
                'permissions' => $permissions,
                'photo' => $user->photo,
                'auth_version' => (int)($user->auth_version ?? 1),
            ];
            return true;
        }

        return false;
    }

    /**
     * Log the user out.
     */
    public static function logout() {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Synchronizes active session state (role_id, role_name, permissions, lycee_id, auth_version)
     * against the database.
     * Re-checks user existence, active status (actif = 1), valid role_id, and auth_version.
     * Fails closed (invalidates session and returns false) on inactive account, invalid role, or DB error.
     *
     * @return bool True if session is valid and synchronized; false otherwise.
     */
    public static function synchronizeSessionIfNeeded(): bool {
        self::startSession();
        $rawUserId = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? $_SESSION['user_id'] ?? null;
        if (!$rawUserId) {
            return false;
        }

        $userId = (int)$rawUserId;
        $_SESSION['user']['id'] = $userId;

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id_user, role_id, lycee_id, actif, COALESCE(auth_version, 1) AS auth_version FROM utilisateurs WHERE id_user = :id");
            $stmt->execute(['id' => $userId]);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fail closed if user was deleted or explicitly deactivated (actif === 0)
            if (!$dbUser) {
                self::revokeSession();
                return false;
            }

            $isInactive = ($dbUser['actif'] !== null && (int)$dbUser['actif'] === 0);
            if ($isInactive) {
                self::revokeSession();
                return false;
            }

            $dbVersion = (int)$dbUser['auth_version'];
            $sessVersion = $_SESSION['user']['auth_version'] ?? null;
            $currentRoleId = $_SESSION['user']['role_id'] ?? null;
            $dbRoleId = !empty($dbUser['role_id']) ? (int)$dbUser['role_id'] : null;

            if ($dbRoleId === null) {
                self::revokeSession();
                return false;
            }

            // Verify that role exists and is active
            $stmtRole = $db->prepare("SELECT id_role, nom_role FROM roles WHERE id_role = :role_id");
            $stmtRole->execute(['role_id' => $dbRoleId]);
            $role = $stmtRole->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                // Role deleted or missing -> fail closed
                self::revokeSession();
                return false;
            }

            $permissions = Role::getPermissions($dbRoleId);

            if ($sessVersion !== $dbVersion || $currentRoleId !== $dbRoleId) {
                // Reset cycle authorization cache for the updated role or version
                unset($_SESSION['user']['authorized_cycles']);
            }

            // Always synchronize role, permissions, lycee_id, and auth_version against DB
            $_SESSION['user']['role_id'] = $dbRoleId;
            $_SESSION['user']['role_name'] = $role['nom_role'] ?? 'N/A';
            $_SESSION['user']['lycee_id'] = $dbUser['lycee_id'];
            $_SESSION['user']['permissions'] = $permissions;
            $_SESSION['user']['auth_version'] = $dbVersion;

            return true;
        } catch (Throwable $e) {
            // DB errors fail closed to prevent stale privilege escalation
            self::revokeSession();
            return false;
        }
    }

    /**
     * Clear active user session on revocation or security failure.
     */
    private static function revokeSession(): void {
        unset($_SESSION['user']);
    }

    /**
     * Check if a user is logged in.
     *
     * @return bool True if logged in, false otherwise.
     */
    public static function check() {
        self::startSession();
        return self::synchronizeSessionIfNeeded();
    }

    /**
     * Get the currently logged-in user's data.
     *
     * @return array|null The user data array or null if not logged in.
     */
    public static function user() {
        self::startSession();
        if (!self::synchronizeSessionIfNeeded()) {
            return null;
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get a specific field from the logged-in user's data.
     *
     * @param string $key The key of the data to retrieve (e.g., 'id', 'role').
     * @return mixed|null The value or null if not found.
     */
    public static function get($key) {
        self::startSession();
        if (!self::synchronizeSessionIfNeeded()) {
            return null;
        }
        if ($key === 'id_user' || $key === 'id') {
            return $_SESSION['user']['id'] ?? null;
        }
        return $_SESSION['user'][$key] ?? null;
    }

    /**
     * Check if the logged-in user has a specific permission.
     * @param string $action The action to perform (e.g., 'create', 'view_all').
     * @param string $resource The resource the action applies to (e.g., 'user', 'eleve').
     * @return bool
     */
    public static function can(string $action, string $resource): bool {
        self::startSession();

        if (!self::synchronizeSessionIfNeeded()) {
            self::logDebug($action, $resource, false, "Session synchronization failed", null);
            return false;
        }

        $permissions = $_SESSION['user']['permissions'] ?? null;
        $role_id = $_SESSION['user']['role_id'] ?? null;

        if (!is_array($permissions)) {
            self::logDebug($action, $resource, false, "No permissions loaded", $role_id);
            return false;
        }

        if (isset($permissions[$resource])) {
            // Wildcard '*' resolves all actions on the resource
            if (in_array('*', $permissions[$resource], true)) {
                self::logDebug($action, $resource, true, "Wildcard '*' matched", $role_id);
                return true;
            }

            // Direct action match
            if (in_array($action, $permissions[$resource], true)) {
                self::logDebug($action, $resource, true, "Direct action match", $role_id);
                return true;
            }

            // Hierarchy: 'manage' englobes standard CRUD 'view', 'create', 'edit', 'delete'
            $standard_crud = ['view', 'create', 'edit', 'delete', 'view_all', 'view_one'];
            if (in_array($action, $standard_crud, true) && in_array('manage', $permissions[$resource], true)) {
                self::logDebug($action, $resource, true, "Hierarchy 'manage' matched", $role_id);
                return true;
            }
        }

        self::logDebug($action, $resource, false, "No matching permission found", $role_id);
        return false;
    }

    /**
     * Temporary instrumentation debug logger
     */
    private static function logDebug(string $action, string $resource, bool $result, string $reason, ?int $role_id) {
        if (defined('APP_ENV') && APP_ENV === 'development') {
            $res_str = $result ? 'ALLOWED' : 'DENIED';
            error_log(sprintf(
                "[RBAC DEBUG] Action: %s | Resource: %s | Result: %s | Reason: %s | Role ID: %s",
                $action, $resource, $res_str, $reason, ($role_id ?? 'N/A')
            ));
        }
    }

    public static function getLyceeId() {
        self::startSession();
        if (!self::synchronizeSessionIfNeeded()) {
            return null;
        }
        return $_SESSION['user']['lycee_id'] ?? null;
    }

    public static function getUserId() {
        self::startSession();
        if (!self::synchronizeSessionIfNeeded()) {
            return null;
        }
        return $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? $_SESSION['user_id'] ?? null;
    }

    public static function getAuthorizedCycleIds() {
        require_once __DIR__ . '/../services/AuthorizationScopeService.php';
        return AuthorizationScopeService::getAuthorizedCycleIds();
    }

    public static function assertAccessToObject($resource_lycee_id, $resource_cycle_id = null) {
        require_once __DIR__ . '/../services/AuthorizationScopeService.php';
        AuthorizationScopeService::assertAccessToObject($resource_lycee_id, $resource_cycle_id);
    }

    /**
     * Set session context for background testing or automated operations.
     */
    public static function setSessionContext(array $userData): void {
        self::startSession();
        $_SESSION['user'] = array_merge($_SESSION['user'] ?? [], $userData);
        if (isset($userData['id_user'])) {
            $_SESSION['user']['id'] = $userData['id_user'];
        }
    }

    /**
     * Enforce permission check. If user lacks permission, return 403 Forbidden.
     * @param string $resource
     * @param string $action
     */
    public static function requirePermission(string $resource, string $action): void {
        if (!self::can($action, $resource)) {
            http_response_code(403);
            require_once __DIR__ . '/View.php';
            View::render('errors/403');
            exit();
        }
    }
}
?>
