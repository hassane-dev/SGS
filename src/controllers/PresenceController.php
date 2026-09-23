<?php

require_once __DIR__ . '/../models/Presence.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class PresenceController {

    /**
     * Vérifie le droit d'accès RBAC, l'isolation multi-tenant, le scope de cycle et l'affectation pédagogique de l'enseignant.
     */
    private function verifyClassAccess(int $classe_id): array {
        // 1. Vérification RBAC basique sur la ressource 'presence'
        if (!Auth::can('manage', 'presence') && !Auth::can('view', 'presence')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        // 2. Recherche de la classe
        $classe = Classe::findById($classe_id);
        if (!$classe) {
            header('Location: /');
            exit();
        }

        // 3. Isolation Multi-Tenant
        $lyceeId = Auth::getLyceeId();
        if ((int)$classe['lycee_id'] !== (int)$lyceeId) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        // 4. Scope de cycle via AuthorizationScopeService
        AuthorizationScopeService::assertAccessToObject((int)$classe['lycee_id'], (int)($classe['cycle_id'] ?? 0));

        // 5. Contrôle de l'affectation pédagogique de l'enseignant
        $userId = Auth::getUserId();
        $activeYear = AnneeAcademique::findActive();
        $anneeId = $activeYear ? (int)$activeYear['id'] : null;

        $isGlobalAdmin = Auth::can('view_all_lycees', 'lycee') || Auth::can('manage', 'school');
        $teacherAssignments = User::getTeacherAssignments($userId, $anneeId, $lyceeId);

        // Si l'utilisateur possède des affectations pédagogiques (enseignant) et n'est pas un admin global
        if (!$isGlobalAdmin && !empty($teacherAssignments)) {
            $assignedClassIds = array_unique(array_filter(array_column($teacherAssignments, 'id_classe')));

            if (!in_array((int)$classe_id, array_map('intval', $assignedClassIds), true)) {
                http_response_code(403);
                View::render('errors/403');
                exit();
            }
        }

        return $classe;
    }

    public function gerer($classe_id) {
        $classe = $this->verifyClassAccess((int)$classe_id);
        $date = $_GET['date'] ?? date('Y-m-d');
        $active_year = AnneeAcademique::findActive();
        $annee_id = $active_year ? (int)$active_year['id'] : 0;
        $lycee_id = Auth::getLyceeId();

        $eleves = Eleve::findActiveRosterForClass((int)$classe_id, $annee_id, $lycee_id);
        $existing_presences = Presence::findByClassAndDate((int)$classe_id, $date, null, $annee_id, $lycee_id);

        // Index existing presences by eleve_id for easier lookup in view
        $presences_map = [];
        foreach ($existing_presences as $p) {
            $presences_map[$p['eleve_id']] = $p;
        }

        View::render('presences/gerer', [
            'classe' => $classe,
            'eleves' => $eleves,
            'date' => $date,
            'presences_map' => $presences_map,
            'title' => 'Gestion des Présences'
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = (int)$_POST['classe_id'];
            $classe = $this->verifyClassAccess($classe_id);

            $date = $_POST['date_presence'];
            $active_year = AnneeAcademique::findActive();
            $annee_id = $active_year ? (int)$active_year['id'] : 0;
            $lycee_id = Auth::getLyceeId();

            // Filtrage strict : les élèves enregistrés doivent être dans l'effectif actif de cette classe
            $activeRoster = Eleve::findActiveRosterForClass($classe_id, $annee_id, $lycee_id);
            $validEleveIds = array_column($activeRoster, 'id_eleve');

            $postedPresences = $_POST['presences'] ?? [];
            $filteredPresences = [];

            foreach ($postedPresences as $eleve_id => $presence_data) {
                if (in_array((int)$eleve_id, array_map('intval', $validEleveIds), true)) {
                    $filteredPresences[(int)$eleve_id] = $presence_data;
                }
            }

            $data = [
                'classe_id' => $classe_id,
                'date_presence' => $date,
                'enseignant_id' => Auth::getUserId(),
                'annee_academique_id' => $annee_id,
                'lycee_id' => $lycee_id,
                'presences' => $filteredPresences
            ];

            Presence::saveAll($data);

            header("Location: /presences/gerer/$classe_id?date=$date&success=1");
            exit();
        }
    }
}
