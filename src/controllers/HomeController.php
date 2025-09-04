<?php

class HomeController {
    public function index() {
        // For now, just a simple welcome page.
        // In the future, this could be a dashboard.

        require_once __DIR__ . '/../views/layouts/header.php';
        echo "<h1>Welcome to the Dashboard</h1>";
        if (Auth::check()) {
            echo "<p>You are logged in as " . htmlspecialchars(Auth::get('email')) . ".</p>";
            echo '<a href="/settings">Go to Settings</a> | <a href="/logout">Logout</a>';
        } else {
            echo '<p>Please <a href="/login">login</a> to continue.</p>';
        }
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
?>
