<?php

class AuthController {

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process the form
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (Auth::login($email, $password)) {
                // Successful login, retrieve and set preferred user language
                require_once __DIR__ . '/../models/ParametreUtilisateur.php';
                $user_id = Auth::getUserId();
                if ($user_id) {
                    $user_settings = ParametreUtilisateur::findByUserId($user_id);
                    if ($user_settings && !empty($user_settings->langue_preferee)) {
                        $_SESSION['lang'] = $user_settings->langue_preferee;
                    }
                }

                // Redirect to home/dashboard
                header('Location: /');
                exit();
            } else {
                // Failed login, redirect back to login with an error
                header('Location: /login?error=1');
                exit();
            }
        } else {
            // Display the login form
            require_once __DIR__ . '/../views/auth/login.php';
        }
    }

    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit();
    }
}
?>
