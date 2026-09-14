<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/DisciplineIncident.php';
require_once __DIR__ . '/../models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class DisciplineIncidentController {

    private function getTeacherScopeInfo($lyceeId) {
        $userId = Auth::getUserId();
        $canManage = Auth::can('manage_incident', 'discipline');

        if (!$canManage) {
            $assignments = User::getTeacherAssignments($userId);
            $allowedClassIds = array_unique(array_filter(array_column($assignments, 'id_classe')));
            return [
                'isTeacherScoped' => true,
                'userId' => $userId,
                'allowedClassIds' => array_values($allowedClassIds)
            ];
        }

        return [
            'isTeacherScoped' => false,
            'userId' => $userId,
            'allowedClassIds' => []
        ];
    }

    public function index() {
        Auth::requirePermission('discipline', 'view_incidents');

        $lyceeId = Auth::getLyceeId();
        if (!$lyceeId) {
            header('Location: /home?error=' . urlencode(_("Établissement non valide.")));
            exit;
        }

        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        $filters = [
            'annee_academique_id' => $_GET['annee_academique_id'] ?? null,
            'type_incident_id' => $_GET['type_incident_id'] ?? null,
            'statut' => $_GET['statut'] ?? null,
            'classe_id' => $_GET['classe_id'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null
        ];

        // Filter dropdown classes based on scope
        $allClasses = Classe::findAll($lyceeId);
        if ($scopeInfo['isTeacherScoped']) {
            $classes = array_filter($allClasses, function($cls) use ($scopeInfo) {
                return in_array((int)$cls['id_classe'], $scopeInfo['allowedClassIds'], true);
            });
        } else {
            $permittedCycles = AuthorizationScopeService::getPermittedCycles($lyceeId);
            $permittedCycleIds = array_column($permittedCycles, 'id_cycle');
            $classes = array_filter($allClasses, function($cls) use ($permittedCycleIds) {
                return empty($permittedCycleIds) || in_array((int)$cls['cycle_id'], $permittedCycleIds, true);
            });
        }

        $incidents = DisciplineIncident::search(
            $filters,
            $lyceeId,
            $scopeInfo['isTeacherScoped'],
            $scopeInfo['userId'],
            $scopeInfo['allowedClassIds']
        );
        $typesIncidents = DisciplineTypeIncident::findAll($lyceeId);

        View::render('discipline/incidents/index', [
            'incidents' => $incidents,
            'typesIncidents' => $typesIncidents,
            'classes' => $classes,
            'filters' => $filters
        ]);
    }

    public function create() {
        Auth::requirePermission('discipline', 'report_incident');

        $lyceeId = Auth::getLyceeId();
        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        $typesIncidents = DisciplineTypeIncident::findActive($lyceeId);
        $allClasses = Classe::findAll($lyceeId);

        if ($scopeInfo['isTeacherScoped']) {
            $classes = array_filter($allClasses, function($cls) use ($scopeInfo) {
                return in_array((int)$cls['id_classe'], $scopeInfo['allowedClassIds'], true);
            });
        } else {
            $permittedCycles = AuthorizationScopeService::getPermittedCycles($lyceeId);
            $permittedCycleIds = array_column($permittedCycles, 'id_cycle');
            $classes = array_filter($allClasses, function($cls) use ($permittedCycleIds) {
                return empty($permittedCycleIds) || in_array((int)$cls['cycle_id'], $permittedCycleIds, true);
            });
        }

        View::render('discipline/incidents/create', [
            'typesIncidents' => $typesIncidents,
            'classes' => $classes
        ]);
    }

    public function store() {
        Auth::requirePermission('discipline', 'report_incident');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/incidents');
            exit;
        }

        $lyceeId = Auth::getLyceeId();
        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        try {
            $elevesInput = $_POST['eleves'] ?? [];
            if (!is_array($elevesInput)) {
                $elevesInput = [];
            }

            $incidentId = DisciplineIncident::create(
                $_POST,
                $elevesInput,
                $scopeInfo['isTeacherScoped'],
                $scopeInfo['allowedClassIds']
            );

            $_SESSION['flash_success'] = _("Incident disciplinaire signalé avec succès.");
            header('Location: /discipline/incidents/show?id=' . $incidentId);
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /discipline/incidents/create');
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur serveur lors du signalement de l'incident.");
            error_log("DisciplineIncidentController::store error: " . $e->getMessage());
            header('Location: /discipline/incidents/create');
            exit;
        }
    }

    public function show() {
        Auth::requirePermission('discipline', 'view_incidents');

        $id = (int)($_GET['id'] ?? 0);
        $lyceeId = Auth::getLyceeId();
        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        $incident = DisciplineIncident::findById($id, $lyceeId);
        if (!$incident) {
            header('Location: /discipline/incidents?error=' . urlencode(_("Incident introuvable ou non autorisé.")));
            exit;
        }

        // Teacher Scope Consultation Check
        if ($scopeInfo['isTeacherScoped']) {
            $isAuthor = ((int)$incident['signale_par_user_id'] === (int)$scopeInfo['userId']);
            $hasStudentInClass = false;
            foreach ($incident['eleves'] as $e) {
                if (in_array((int)$e['classe_id'], $scopeInfo['allowedClassIds'], true)) {
                    $hasStudentInClass = true;
                    break;
                }
            }

            if (!$isAuthor && !$hasStudentInClass) {
                header('Location: /discipline/incidents?error=' . urlencode(_("Accès non autorisé à cet incident.")));
                exit;
            }
        }

        View::render('discipline/incidents/show', [
            'incident' => $incident
        ]);
    }

    public function edit() {
        Auth::requirePermission('discipline', 'report_incident');

        $id = (int)($_GET['id'] ?? 0);
        $lyceeId = Auth::getLyceeId();
        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        $incident = DisciplineIncident::findById($id, $lyceeId);
        if (!$incident) {
            header('Location: /discipline/incidents?error=' . urlencode(_("Incident introuvable.")));
            exit;
        }

        if ($incident['statut'] !== 'signale') {
            $_SESSION['flash_error'] = _("Cet incident est en cours d'instruction ou clôturé et ne peut plus être modifié.");
            header('Location: /discipline/incidents/show?id=' . $id);
            exit;
        }

        if ($scopeInfo['isTeacherScoped']) {
            if ((int)$incident['signale_par_user_id'] !== (int)$scopeInfo['userId']) {
                $_SESSION['flash_error'] = _("Vous ne pouvez modifier que les signalements dont vous êtes l'auteur.");
                header('Location: /discipline/incidents/show?id=' . $id);
                exit;
            }
        }

        $typesIncidents = DisciplineTypeIncident::findActive($lyceeId);
        $allClasses = Classe::findAll($lyceeId);

        if ($scopeInfo['isTeacherScoped']) {
            $classes = array_filter($allClasses, function($cls) use ($scopeInfo) {
                return in_array((int)$cls['id_classe'], $scopeInfo['allowedClassIds'], true);
            });
        } else {
            $classes = $allClasses;
        }

        View::render('discipline/incidents/edit', [
            'incident' => $incident,
            'typesIncidents' => $typesIncidents,
            'classes' => $classes
        ]);
    }

    public function update() {
        Auth::requirePermission('discipline', 'report_incident');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/incidents');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $lyceeId = Auth::getLyceeId();
        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        try {
            $elevesInput = $_POST['eleves'] ?? [];
            if (!is_array($elevesInput)) {
                $elevesInput = [];
            }

            DisciplineIncident::update(
                $id,
                $_POST,
                $elevesInput,
                $scopeInfo['isTeacherScoped'],
                $scopeInfo['userId'],
                $scopeInfo['allowedClassIds']
            );

            $_SESSION['flash_success'] = _("Incident disciplinaire mis à jour avec succès.");
            header('Location: /discipline/incidents/show?id=' . $id);
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /discipline/incidents/edit?id=' . $id);
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur lors de la mise à jour de l'incident.");
            error_log("DisciplineIncidentController::update error: " . $e->getMessage());
            header('Location: /discipline/incidents/edit?id=' . $id);
            exit;
        }
    }

    public function updateStatus() {
        Auth::requirePermission('discipline', 'manage_incident');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/incidents');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $newStatut = $_POST['statut'] ?? '';

        try {
            DisciplineIncident::updateStatus($id, $newStatut);
            $_SESSION['flash_success'] = _("Statut de l'incident mis à jour.");
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur lors de la mise à jour du statut.");
            error_log("DisciplineIncidentController::updateStatus error: " . $e->getMessage());
        }

        header('Location: /discipline/incidents/show?id=' . $id);
        exit;
    }

    public function searchElevesAjax() {
        Auth::requirePermission('discipline', 'report_incident');

        header('Content-Type: application/json');
        $lyceeId = Auth::getLyceeId();
        $classeId = (int)($_GET['classe_id'] ?? 0);

        if (!$lyceeId || !$classeId) {
            echo json_encode([]);
            exit;
        }

        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);
        if ($scopeInfo['isTeacherScoped']) {
            if (!in_array($classeId, $scopeInfo['allowedClassIds'], true)) {
                echo json_encode([]);
                exit;
            }
        }

        try {
            $db = Database::getInstance();
            $sql = "SELECT e.id_eleve, e.nom, e.prenom, COALESCE(e.identifiant_public, 'N/A') AS matricule
                    FROM eleves e
                    JOIN etudes et ON e.id_eleve = et.eleve_id
                    JOIN annees_academiques a ON et.annee_academique_id = a.id
                    WHERE e.lycee_id = :lycee_id
                      AND et.classe_id = :classe_id
                      AND a.est_active = 1
                    ORDER BY e.nom ASC, e.prenom ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lyceeId, 'classe_id' => $classeId]);
            $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($eleves);
            exit;
        } catch (Exception $e) {
            error_log("Error in DisciplineIncidentController::searchElevesAjax: " . $e->getMessage());
            echo json_encode([]);
            exit;
        }
    }
}
?>