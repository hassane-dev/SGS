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
     * Check if a user is logged in.
     *
     * @return bool True if logged in, false otherwise.
     */
    public static function check() {
        self::startSession();
        return isset($_SESSION['user']);
    }

    /**
     * Get the currently logged-in user's data.
     *
     * @return array|null The user data array or null if not logged in.
     */
    public static function user() {
        self::startSession();
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
        // Support both 'id' and 'id_user' interchangeably as aliases
        if ($key === 'id_user') {
            return $_SESSION['user']['id'] ?? null;
        }
        if ($key === 'id') {
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

        // Dynamic permission refreshing to keep sessions in sync with base updates real-time
        $role_id = self::get('role_id');
        if ($role_id) {
            try {
                $permissions = Role::getPermissions($role_id);
                $_SESSION['user']['permissions'] = $permissions;
            } catch (Exception $e) {
                $permissions = self::get('permissions');
            }
        } else {
            $permissions = self::get('permissions');
        }

        if (!is_array($permissions)) {
            $result = false;
            self::logDebug($action, $resource, $result, "No permissions loaded", $role_id);
            return false;
        }

        if (isset($permissions[$resource])) {
            // Wildcard '*' resolves all actions on the resource
            if (in_array('*', $permissions[$resource])) {
                $result = true;
                self::logDebug($action, $resource, $result, "Wildcard '*' matched", $role_id);
                return true;
            }

            // Direct action match
            if (in_array($action, $permissions[$resource])) {
                $result = true;
                self::logDebug($action, $resource, $result, "Direct action match", $role_id);
                return true;
            }

            // Hierarchy: 'manage' englobes standard CRUD 'view', 'create', 'edit', 'delete'
            $standard_crud = ['view', 'create', 'edit', 'delete', 'view_all', 'view_one'];
            if (in_array($action, $standard_crud) && in_array('manage', $permissions[$resource])) {
                $result = true;
                self::logDebug($action, $resource, $result, "Hierarchy 'manage' matched", $role_id);
                return true;
            }
        }

        $result = false;
        self::logDebug($action, $resource, $result, "No matching permission found", $role_id);
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
        return $_SESSION['user']['lycee_id'] ?? null;
    }

    public static function getUserId() {
        self::startSession();
        return $_SESSION['user']['id'] ?? null;
    }
}
?>
