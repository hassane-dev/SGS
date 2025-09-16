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
                $navLinks[] = '<a href="/eleves" class="text-blue-500 hover:underline">Gérer les Élèves</a>';
                $navLinks[] = '<a href="/cycles" class="text-blue-500 hover:underline">Gérer les Cycles</a>';
                $navLinks[] = '<a href="/classes" class="text-blue-500 hover:underline">Gérer les Classes</a>';
                $navLinks[] = '<a href="/matieres" class="text-blue-500 hover:underline">Gérer les Matières</a>';
                $navLinks[] = '<a href="/boutique/articles" class="text-blue-500 hover:underline">Gérer la Boutique</a>';
            }

            if (Auth::can('manage_own_lycee_settings')) {
                $navLinks[] = '<a href="/modele-carte/edit" class="text-teal-500 hover:underline">Éditeur de Carte</a>';
            }

            if (Auth::can('manage_classes')) { // Reuse permission
                $navLinks[] = '<a href="/emploi-du-temps" class="text-orange-500 hover:underline">Emploi du Temps</a>';
            }

            if (Auth::get('role_name') === 'enseignant') {
                $navLinks[] = '<a href="/notes" class="text-blue-500 hover:underline">Saisir les Notes</a>';
                $navLinks[] = '<a href="/cahier-texte" class="text-green-500 hover:underline">Cahier de Texte</a>';
            }

            if (Auth::get('role') === 'super_admin_createur') {
                $navLinks[] = '<a href="/licences" class="text-red-500 hover:underline">Gérer les Licences</a>';
            }

            if (Auth::can('manage_roles')) {
                $navLinks[] = '<a href="/roles" class="text-purple-500 hover:underline">Gérer les Rôles</a>';
            }

            if (Auth::can('manage_users')) { // Reuse permission
                $navLinks[] = '<a href="/contrats" class="text-pink-500 hover:underline">Gérer les Contrats</a>';
            }

            if (Auth::can('manage_paiements')) { // Reuse permission
                $navLinks[] = '<a href="/salaires" class="text-red-500 hover:underline">Gérer les Salaires</a>';
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
