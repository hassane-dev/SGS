<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';


class HomeController {
    public function index() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $navLinks = [];
        $navLinks[] = ['url' => '/settings', 'text' => _('Paramètres')];

        if (Auth::can('view_all_lycees', 'lycee')) {
            $navLinks[] = ['url' => '/lycees', 'text' => _('Gérer les Lycées')];
        }
        if (in_array(Auth::get('role_name'), ['admin_local', 'super_admin_national'])) {
            $navLinks[] = ['url' => '/users', 'text' => _('Gérer les Utilisateurs')];
            $navLinks[] = ['url' => '/eleves', 'text' => _('Gérer les Élèves')];
            $navLinks[] = ['url' => '/cycles', 'text' => _('Gérer les Cycles')];
            $navLinks[] = ['url' => '/classes', 'text' => _('Gérer les Classes')];
            $navLinks[] = ['url' => '/matieres', 'text' => _('Gérer les Matières')];
            $navLinks[] = ['url' => '/boutique/articles', 'text' => _('Gérer la Boutique')];
        }

        if (Auth::can('edit', 'param_lycee')) {
            $navLinks[] = ['url' => '/modele-carte/edit', 'text' => _('Éditeur de Carte')];
        }

        if (Auth::can('manage', 'class')) { // Reuse permission
            $navLinks[] = ['url' => '/emploi-du-temps', 'text' => _('Emploi du Temps')];
        }

        if (Auth::get('role_name') === 'enseignant') {
            $navLinks[] = ['url' => '/notes', 'text' => _('Saisir les Notes')];
            $navLinks[] = ['url' => '/cahier-texte', 'text' => _('Cahier de Texte')];
        }

        if (Auth::get('role_name') === 'super_admin_createur') {
            $navLinks[] = ['url' => '/licences', 'text' => _('Gérer les Licences')];
        }

        if (Auth::can('manage', 'role')) {
            $navLinks[] = ['url' => '/roles', 'text' => _('Gérer les Rôles')];
        }

        if (Auth::can('manage', 'user')) { // Reuse permission
            $navLinks[] = ['url' => '/contrats', 'text' => _('Gérer les Contrats')];
        }

        if (Auth::can('create', 'paiement')) { // Reuse permission
            $navLinks[] = ['url' => '/salaires', 'text' => _('Gérer les Salaires')];
        }

        $teacherSubjects = [];
        if (Auth::get('role_name') === 'enseignant') {
            $teacherSubjects = User::findSubjectsTaughtByTeacher(Auth::get('id'));
        }

        // Pass data to the view
        $data = [
            'navLinks' => $navLinks,
            'teacherSubjects' => $teacherSubjects
        ];

        // Load the view
        require_once __DIR__ . '/../views/home/index.php';
    }
}
?>
