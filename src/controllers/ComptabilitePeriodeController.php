<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/ComptabilitePeriode.php';
require_once __DIR__ . '/../models/ExerciceFinancier.php';
require_once __DIR__ . '/../services/ComptabiliteService.php';

class ComptabilitePeriodeController {

    public function index() {
        Auth::requirePermission('comptabilite', 'view');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $exerciceId = isset($_GET['exercice_id']) ? (int)$_GET['exercice_id'] : null;

        $exercices = ExerciceFinancier::findAll($lyceeId);
        $periodes = ComptabilitePeriode::findAllForLycee($lyceeId, $exerciceId);

        include __DIR__ . '/../views/comptabilite/periodes/index.php';
    }

    public function create() {
        Auth::requirePermission('comptabilite', 'create');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $exercices = ExerciceFinancier::findAll($lyceeId);

        include __DIR__ . '/../views/comptabilite/periodes/create.php';
    }

    public function store() {
        Auth::requirePermission('comptabilite', 'create');
        $lyceeId = Auth::getLyceeId() ?: 1;

        $exerciceId = (int)($_POST['exercice_financier_id'] ?? 0);
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';

        if (!$exerciceId || empty($dateDebut) || empty($dateFin)) {
            $_SESSION['error_message'] = _("Veuillez remplir tous les champs obligatoires.");
            header('Location: /comptabilite/periodes/create');
            exit();
        }

        try {
            ComptabilitePeriode::create([
                'lycee_id' => $lyceeId,
                'exercice_financier_id' => $exerciceId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]);

            $_SESSION['success_message'] = _("Période comptable créée avec succès.");
            header('Location: /comptabilite/periodes?exercice_id=' . $exerciceId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /comptabilite/periodes/create');
            exit();
        }
    }

    public function generate() {
        Auth::requirePermission('comptabilite', 'create');
        $lyceeId = Auth::getLyceeId() ?: 1;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $exerciceId = (int)($_POST['exercice_financier_id'] ?? 0);
            if (!$exerciceId) {
                $_SESSION['error_message'] = _("Veuillez sélectionner un exercice financier.");
                header('Location: /comptabilite/periodes/generate');
                exit();
            }

            try {
                $count = ComptabilitePeriode::generateMonthlyForExercice($lyceeId, $exerciceId);
                $_SESSION['success_message'] = sprintf(_("%d périodes comptables mensuelles ont été générées avec succès."), $count);
                header('Location: /comptabilite/periodes?exercice_id=' . $exerciceId);
                exit();
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /comptabilite/periodes/generate');
                exit();
            }
        }

        $exercices = ExerciceFinancier::findAll($lyceeId);
        include __DIR__ . '/../views/comptabilite/periodes/generate.php';
    }

    public function close($id = null) {
        Auth::requirePermission('comptabilite', 'close');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $userId = Auth::getUserId();
        $id = (int)($id ?: ($_GET['id'] ?? 0));

        if ($id) {
            ComptabiliteService::validerCloturePeriode($lyceeId, $id, $userId);
            $_SESSION['success_message'] = _("Période comptable clôturée.");
        }

        header('Location: /comptabilite/periodes');
        exit();
    }

    public function reopen($id = null) {
        Auth::requirePermission('comptabilite', 'reopen');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $userId = Auth::getUserId();
        $id = (int)($id ?: ($_GET['id'] ?? 0));

        if ($id) {
            ComptabiliteService::reouvrirPeriode($lyceeId, $id, $userId);
            $_SESSION['success_message'] = _("Période comptable réouverte.");
        }

        header('Location: /comptabilite/periodes');
        exit();
    }
}
