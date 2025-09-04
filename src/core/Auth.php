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
            // Password is correct, store user data in session
            $_SESSION['user'] = [
                'id' => $user->id_user,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $user->role,
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
}
?>
