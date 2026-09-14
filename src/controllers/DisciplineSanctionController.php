<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/DisciplineSanction.php';
require_once __DIR__ . '/../models/DisciplineTypeSanction.php';
require_once __DIR__ . '/../models/DisciplineIncident.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class DisciplineSanctionController {

    private function getTeacherScopeInfo($lyceeId) {
        $userId = Auth::getUserId();
        $canManage = Auth::can('manage_sanctions', 'discipline');

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
        Auth::requirePermission('discipline', 'view_sanctions');

        $lyceeId = Auth::getLyceeId();
        if (!$lyceeId) {
            header('Location: /home?error=' . urlencode(_("Établissement non valide.")));
            exit;
        }

        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        $filters = [
            'annee_academique_id' => $_GET['annee_academique_id'] ?? null,
            'type_sanction_id' => $_GET['type_sanction_id'] ?? null,
            'statut' => $_GET['statut'] ?? null,
            'classe_id' => $_GET['classe_id'] ?? null,
            'eleve_id' => $_GET['eleve_id'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null
        ];

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

        $sanctions = DisciplineSanction::search(
            $filters,
            $lyceeId,
            $scopeInfo['isTeacherScoped'],
            $scopeInfo['allowedClassIds']
        );

        $typesSanctions = DisciplineTypeSanction::findAll($lyceeId);

        View::render('discipline/sanctions/index', [
            'sanctions' => $sanctions,
            'typesSanctions' => $typesSanctions,
            'classes' => $classes,
            'filters' => $filters
        ]);
    }

    public function create() {
        Auth::requirePermission('discipline', 'manage_sanctions');

        $lyceeId = Auth::getLyceeId();
        $typesSanctions = DisciplineTypeSanction::findActive($lyceeId);
        $classes = Classe::findAll($lyceeId);

        $incidentId = !empty($_GET['incident_id']) ? (int)$_GET['incident_id'] : null;
        $incident = null;
        $incidentEleves = [];

        if ($incidentId) {
            $incident = DisciplineIncident::findById($incidentId, $lyceeId);
            if ($incident) {
                $incidentEleves = $incident['eleves'] ?? [];
            }
        }

        View::render('discipline/sanctions/create', [
            'typesSanctions' => $typesSanctions,
            'classes' => $classes,
            'incident' => $incident,
            'incidentEleves' => $incidentEleves
        ]);
    }

    public function store() {
        Auth::requirePermission('discipline', 'manage_sanctions');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/sanctions');
            exit;
        }

        try {
            $sanctionId = DisciplineSanction::create($_POST);
            $_SESSION['flash_success'] = _("Sanction disciplinaire enregistrée avec succès.");
            header('Location: /discipline/sanctions/show?id=' . $sanctionId);
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $redirectUrl = !empty($_POST['incident_id']) ? '/discipline/sanctions/create?incident_id=' . (int)$_POST['incident_id'] : '/discipline/sanctions/create';
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur serveur lors de la création de la sanction.");
            error_log("DisciplineSanctionController::store error: " . $e->getMessage());
            header('Location: /discipline/sanctions/create');
            exit;
        }
    }

    public function show() {
        Auth::requirePermission('discipline', 'view_sanctions');

        $id = (int)($_GET['id'] ?? 0);
        $lyceeId = Auth::getLyceeId();
        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);

        $sanction = DisciplineSanction::findById($id, $lyceeId);
        if (!$sanction) {
            header('Location: /discipline/sanctions?error=' . urlencode(_("Sanction introuvable ou non autorisée.")));
            exit;
        }

        if ($scopeInfo['isTeacherScoped']) {
            if (!in_array((int)$sanction['classe_id'], $scopeInfo['allowedClassIds'], true)) {
                header('Location: /discipline/sanctions?error=' . urlencode(_("Accès non autorisé à cette sanction.")));
                exit;
            }
        }

        View::render('discipline/sanctions/show', [
            'sanction' => $sanction
        ]);
    }

    public function updateStatus() {
        Auth::requirePermission('discipline', 'manage_sanctions');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/sanctions');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $newStatut = $_POST['statut'] ?? '';

        try {
            DisciplineSanction::updateStatus($id, $newStatut);
            $_SESSION['flash_success'] = _("Statut de la sanction mis à jour.");
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur lors de la mise à jour du statut.");
            error_log("DisciplineSanctionController::updateStatus error: " . $e->getMessage());
        }

        header('Location: /discipline/sanctions/show?id=' . $id);
        exit;
    }
}
?>