<?php

require_once __DIR__ . '/../models/User.php';

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
                'ecoleId' => $user->ecoleId,
                'permissions' => $permissions,
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
        $permissions = self::get('permissions');

        if (!is_array($permissions)) {
            return false;
        }

        // Check for wildcard permission, e.g., 'manage_all'
        if (isset($permissions[$resource]) && in_array('*', $permissions[$resource])) {
            return true;
        }

        return isset($permissions[$resource]) && in_array($action, $permissions[$resource]);
    }

    public static function getEcoleId() {
        self::startSession();
        return $_SESSION['user']['ecoleId'] ?? null;
    }
}
?>
