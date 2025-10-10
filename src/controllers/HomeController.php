<?php

class HomeController {
    public function index() {
        // For now, just a simple welcome page.
        // In the future, this could be a dashboard.

        require_once __DIR__ . '/../views/layouts/header.php';
        echo "<h1>Welcome to the Dashboard</h1>";
        if (Auth::check()) {
            echo "<p>You are logged in as " . htmlspecialchars(Auth::get('email') ?? '') . " (" . htmlspecialchars(Auth::get('role_name') ?? 'N/A') . ").</p>";

            $navLinks = [];
            if (Auth::can('dashboard', 'view')) {
                if (Auth::can('setting', 'edit')) { $navLinks[] = '<a href="/settings" class="text-blue-500 hover:underline">Paramètres</a>'; }
                if (Auth::can('lycee', 'view_all')) { $navLinks[] = '<a href="/lycees" class="text-blue-500 hover:underline">Gérer les Lycées</a>'; }
                if (Auth::can('user', 'view_all')) { $navLinks[] = '<a href="/users" class="text-blue-500 hover:underline">Gérer le Personnel</a>'; }
                if (Auth::can('eleve', 'view_all')) { $navLinks[] = '<a href="/eleves" class="text-blue-500 hover:underline">Gérer les Élèves</a>'; }
                if (Auth::can('class', 'manage')) { $navLinks[] = '<a href="/classes" class="text-blue-500 hover:underline">Gérer les Classes</a>'; }
                if (Auth::can('matiere', 'manage')) { $navLinks[] = '<a href="/matieres" class="text-blue-500 hover:underline">Gérer les Matières</a>'; }
                if (Auth::can('role', 'view_all')) { $navLinks[] = '<a href="/roles" class="text-purple-500 hover:underline">Gérer les Rôles</a>'; }
                if (Auth::can('user', 'edit')) { $navLinks[] = '<a href="/contrats" class="text-pink-500 hover:underline">Gérer les Contrats</a>'; }
                if (Auth::can('salaire', 'manage')) { $navLinks[] = '<a href="/salaires" class="text-red-500 hover:underline">Gérer les Salaires</a>'; }
                if (Auth::can('note', 'manage')) { $navLinks[] = '<a href="/notes" class="text-blue-500 hover:underline">Saisir les Notes</a>'; }
                if (Auth::can('cahier_texte', 'create_own') || Auth::can('cahier_texte', 'view_all')) { $navLinks[] = '<a href="/cahier-texte" class="text-green-500 hover:underline">Cahier de Texte</a>'; }
                if (Auth::can('class', 'manage')) { $navLinks[] = '<a href="/emploi-du-temps" class="text-orange-500 hover:underline">Emploi du Temps</a>'; }
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
