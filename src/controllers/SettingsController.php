<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../core/Validator.php';


class SettingsController {

    public function index() {
        if (!Auth::can('manage', 'settings')) {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit();
        }

        $lycee_id = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $data['lycee_id'] = $lycee_id;

            // In the old controller, this was ParamGeneral::update.
            // Let's assume a general save/update method is better.
            ParamGeneral::save($data);

            // Also handle active year change
            if(isset($data['annee_academique_id'])) {
                AnneeAcademique::setActive($data['annee_academique_id']);
            }

            $_SESSION['success_message'] = 'Paramètres mis à jour avec succès.';
            header('Location: /settings');
            exit();
        }

        $settings = ParamGeneral::findByLyceeId($lycee_id);
        $annees_academiques = AnneeAcademique::findAll();

        $data = [
            'settings' => $settings,
            'annees_academiques' => $annees_academiques,
            'title' => 'Paramètres'
        ];

        if (isset($_SESSION['success_message'])) {
            $data['message'] = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        require_once __DIR__ . '/../views/settings/index.php';
    }

    public function changeLanguage() {
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    public function changeLycee() {
        if (isset($_GET['id']) && Auth::can('view_all_lycees', 'lycee')) {
            $lycee_id = (int)$_GET['id'];
            $_SESSION['user']['lycee_id'] = $lycee_id;
        }
        header('Location: /');
        exit();
    }

    public function changeYear() {
        if (isset($_GET['id'])) {
            require_once __DIR__ . '/../models/AnneeAcademique.php';
            $year_id = (int)$_GET['id'];
            AnneeAcademique::setActive($year_id);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
