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

        if (Auth::can('manage', 'annee_academique') || Auth::can('view_all_lycees', 'lycee')) {
            $navLinks[] = ['url' => '/annees-academiques', 'text' => _('Gérer les Années Académiques')];
        }

        if (Auth::can('edit', 'param_lycee')) {
            $navLinks[] = ['url' => '/modele-carte/edit', 'text' => _('Éditeur de Carte')];
        }

        if (Auth::can('manage', 'timetable')) {
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

        if (Auth::can('manage', 'user')) {
            $navLinks[] = ['url' => '/contrats', 'text' => _('Gérer les Contrats')];
        }

        if (Auth::can('manage', 'salaire')) {
            $navLinks[] = ['url' => '/salaires', 'text' => _('Gérer les Salaires')];
        }

        $teacherSubjects = [];
        if (Auth::get('role_name') === 'enseignant') {
            $teacherSubjects = User::findSubjectsTaughtByTeacher(Auth::get('id'));
        }

        // If the user is a comptable, we redirect them to the accounting dashboard directly
        // as it is their primary workstation.
        if (Auth::get('role_name') === 'comptable') {
            header('Location: /paiements');
            exit();
        }

        $stats = [];
        if (Auth::get('role_name') === 'admin_local' || Auth::get('role_name') === 'super_admin_national') {
            $lycee_id = Auth::getLyceeId();
            $activeYear = AnneeAcademique::findActive();
            if ($activeYear) {
                $db = Database::getInstance();

                // Student counts
                $stmt = $db->prepare("SELECT COUNT(*) FROM eleves WHERE lycee_id = :lycee_id AND statut = 'actif'");
                $stmt->execute(['lycee_id' => $lycee_id]);
                $stats['total_eleves'] = $stmt->fetchColumn();

                $stmt = $db->prepare("SELECT COUNT(*) FROM eleves WHERE lycee_id = :lycee_id AND statut = 'en_attente_paiement'");
                $stmt->execute(['lycee_id' => $lycee_id]);
                $stats['en_attente_paiement'] = $stmt->fetchColumn();

                // Staff counts
                $stmt = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE lycee_id = :lycee_id AND actif = 1");
                $stmt->execute(['lycee_id' => $lycee_id]);
                $stats['total_personnel'] = $stmt->fetchColumn();

                // Class counts
                $stmt = $db->prepare("SELECT COUNT(*) FROM classes WHERE lycee_id = :lycee_id");
                $stmt->execute(['lycee_id' => $lycee_id]);
                $stats['total_classes'] = $stmt->fetchColumn();

                // Financial Overview (Simplified for dashboard)
                $stmt = $db->prepare("SELECT SUM(montant_verse) FROM inscriptions WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
                $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
                $totalInscriptions = $stmt->fetchColumn() ?: 0;

                $stmt = $db->prepare("SELECT SUM(montant) FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :lycee_id AND m.annee_academique_id = :annee_id");
                $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
                $totalMensualites = $stmt->fetchColumn() ?: 0;

                $stats['total_recettes'] = $totalInscriptions + $totalMensualites;
            }
        }

        // Pass data to the view
        $data = [
            'navLinks' => $navLinks,
            'teacherSubjects' => $teacherSubjects,
            'stats' => $stats
        ];

        // Load the view
        require_once __DIR__ . '/../views/home/index.php';
    }
}
?>
