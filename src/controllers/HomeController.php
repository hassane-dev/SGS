<?php

class HomeController {
    public function index() {
        // For now, just a simple welcome page.
        // In the future, this could be a dashboard.

        require_once __DIR__ . '/../views/layouts/header.php';
        echo "<h1>Welcome to the Dashboard</h1>";
        if (Auth::check()) {
            echo "<p>You are logged in as " . htmlspecialchars(Auth::get('email')) . " (" . htmlspecialchars(Auth::get('role')) . ").</p>";

            $navLinks = [];
            $navLinks[] = '<a href="/settings" class="text-blue-500 hover:underline">Paramètres</a>';

            if (Auth::get('role') === 'super_admin_national') {
                $navLinks[] = '<a href="/lycees" class="text-blue-500 hover:underline">Gérer les Lycées</a>';
            }
            if (in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])) {
                $navLinks[] = '<a href="/users" class="text-blue-500 hover:underline">Gérer les Utilisateurs</a>';
                $navLinks[] = '<a href="/cycles" class="text-blue-500 hover:underline">Gérer les Cycles</a>';
                $navLinks[] = '<a href="/classes" class="text-blue-500 hover:underline">Gérer les Classes</a>';
                $navLinks[] = '<a href="/matieres" class="text-blue-500 hover:underline">Gérer les Matières</a>';
            }

            $navLinks[] = '<a href="/logout" class="text-blue-500 hover:underline">Déconnexion</a>';

            echo implode(' | ', $navLinks);

        } else {
            echo '<p>Please <a href="/login">login</a> to continue.</p>';
        }
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
?>
