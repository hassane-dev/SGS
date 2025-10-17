<?php
require_once __DIR__ . '/../core/View.php';

class AuthController {

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process the form
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (Auth::login($email, $password)) {
                // Successful login, redirect to home/dashboard
                header('Location: /');
                exit();
            } else {
                // Failed login, redirect back to login with an error
                header('Location: /login?error=1');
                exit();
            }
        } else {
            // Display the login form using the centered layout
            View::renderCentered('auth/login', ['title' => 'Connexion']);
        }
    }

    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit();
    }
}