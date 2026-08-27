<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/ExerciceFinancier.php';

class ExerciceFinancierController {

    public function index() {
        Auth::requirePermission('comptabilite', 'view');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $exercices = ExerciceFinancier::findAll($lyceeId);

        include __DIR__ . '/../views/comptabilite/exercices/index.php';
    }

    public function create() {
        Auth::requirePermission('comptabilite', 'create');
        include __DIR__ . '/../views/comptabilite/exercices/create.php';
    }

    public function store() {
        Auth::requirePermission('comptabilite', 'create');
        $lyceeId = Auth::getLyceeId() ?: 1;

        $libelle = trim($_POST['libelle'] ?? '');
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        $estActif = !empty($_POST['est_actif']);

        if (empty($libelle) || empty($dateDebut) || empty($dateFin)) {
            $_SESSION['error_message'] = _("Veuillez remplir tous les champs obligatoires.");
            header('Location: /comptabilite/exercices/create');
            exit();
        }

        if (strtotime($dateFin) < strtotime($dateDebut)) {
            $_SESSION['error_message'] = _("La date de fin doit être postérieure à la date de début.");
            header('Location: /comptabilite/exercices/create');
            exit();
        }

        try {
            $id = ExerciceFinancier::create([
                'lycee_id' => $lyceeId,
                'libelle' => $libelle,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'est_actif' => $estActif,
                'type_exercice' => $_POST['type_exercice'] ?? 'normal'
            ]);

            $_SESSION['success_message'] = _("Exercice financier créé avec succès.");
            header('Location: /comptabilite/exercices');
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /comptabilite/exercices/create');
            exit();
        }
    }

    public function activate($id = null) {
        Auth::requirePermission('comptabilite', 'edit');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $id = (int)($id ?: ($_GET['id'] ?? 0));

        if ($id) {
            ExerciceFinancier::setActive($lyceeId, $id);
            $_SESSION['success_message'] = _("Exercice financier activé.");
        }

        header('Location: /comptabilite/exercices');
        exit();
    }

    public function close($id = null) {
        Auth::requirePermission('comptabilite', 'close');
        $id = (int)($id ?: ($_GET['id'] ?? 0));

        if ($id) {
            ExerciceFinancier::close($id);
            $_SESSION['success_message'] = _("Exercice financier clôturé.");
        }

        header('Location: /comptabilite/exercices');
        exit();
    }
}
