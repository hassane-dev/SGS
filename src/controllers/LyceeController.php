<?php

require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/Validator.php';

class LyceeController {

    private function checkAccess() {
        if (!Auth::can('view_all_lycees', 'lycee')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/lycees/index.php';
    }

    public function create() {
        $this->checkAccess();
        require_once __DIR__ . '/../views/lycees/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            require_once __DIR__ . '/../services/SchoolInitializationService.php';
            require_once __DIR__ . '/../models/AnneeAcademique.php';

            // Normalize type_lycee
            if (isset($data['type_lycee']) && $data['type_lycee'] === 'semi-public') {
                $data['type_lycee'] = 'parapublic';
            }

            // Supply active year or default if missing
            $activeYear = AnneeAcademique::findActive();
            if (empty($data['annee_libelle'])) {
                $data['annee_libelle'] = $activeYear['libelle'] ?? (date('Y') . '-' . (date('Y') + 1));
            }
            if (empty($data['annee_date_debut'])) {
                $data['annee_date_debut'] = $activeYear['date_debut'] ?? date('Y-09-01');
            }
            if (empty($data['annee_date_fin'])) {
                $data['annee_date_fin'] = $activeYear['date_fin'] ?? (date('Y') + 1) . '-06-30';
            }

            // Default academic periods if not supplied
            if (empty($data['periods'])) {
                $seqType = $data['sequence_annuelle'] ?? 'Trimestrielle';
                $yStart = $data['annee_date_debut'];
                $yFin = $data['annee_date_fin'];
                $yNum = (int)substr($yStart, 0, 4);

                if ($seqType === 'Semestrielle') {
                    $data['periods'] = [
                        ['nom' => 'Semestre 1', 'date_debut' => $yStart, 'date_fin' => $yNum . '-01-31'],
                        ['nom' => 'Semestre 2', 'date_debut' => ($yNum + 1) . '-02-01', 'date_fin' => $yFin]
                    ];
                } else {
                    $data['periods'] = [
                        ['nom' => 'Trimestre 1', 'date_debut' => $yStart, 'date_fin' => $yNum . '-11-30'],
                        ['nom' => 'Trimestre 2', 'date_debut' => $yNum . '-12-01', 'date_fin' => ($yNum + 1) . '-02-28'],
                        ['nom' => 'Trimestre 3', 'date_debut' => ($yNum + 1) . '-03-01', 'date_fin' => $yFin]
                    ];
                }
            }

            try {
                SchoolInitializationService::initializeSchool($data);
                $_SESSION['success_message'] = _("L'établissement a été créé et initialisé avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = _("Erreur lors de l'initialisation de l'établissement: ") . $e->getMessage();
            }
        }
        header('Location: /lycees');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /lycees');
            exit();
        }
        $lycee = Lycee::findById($id);
        require_once __DIR__ . '/../views/lycees/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Lycee::save($data);
        }
        header('Location: /lycees');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Lycee::delete($id);
        }
        header('Location: /lycees');
        exit();
    }
}
?>