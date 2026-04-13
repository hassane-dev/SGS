<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/View.php';


class SettingsController {

    public function index() {
        if (!Auth::can('edit', 'param_lycee')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        $lycee_id = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $data['lycee_id'] = $lycee_id;

            // Save general settings
            ParamGeneral::save($data);

            // Save school identity settings if present in data
            if (isset($data['nom_lycee']) || isset($data['type_lycee'])) {
                $lycee_data = ParamLycee::findByLyceeId($lycee_id);
                if ($lycee_data) {
                    $update_data = array_merge($lycee_data, $data);
                    // Logo is handled specially in ParamLycee::update,
                    // but here we don't have file upload in the settings form,
                    // so we make sure current_logo is set to avoid it being lost
                    $update_data['current_logo'] = $lycee_data['logo'];
                    ParamLycee::update($update_data);
                }
            }

            // Also handle active year change
            if(isset($data['annee_academique_id'])) {
                AnneeAcademique::setActive($data['annee_academique_id']);
            }

            $_SESSION['success_message'] = 'Paramètres mis à jour avec succès.';
            header('Location: /settings');
            exit();
        }

        $gen_settings = ParamGeneral::findByLyceeId($lycee_id) ?: [];
        $lycee_settings = ParamLycee::findByLyceeId($lycee_id) ?: [];
        $settings = array_merge($gen_settings, $lycee_settings);
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
