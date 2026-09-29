<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../services/SchoolInitializationService.php';

class SetupController {

    public function index() {
        require_once __DIR__ . '/../views/setup/step0_choice.php';
    }

    public function processChoice() {
        $mode = $_POST['install_mode'] ?? 'single';
        if ($mode === 'multi') {
            require_once __DIR__ . '/../views/setup/step1_multi.php';
        } else {
            require_once __DIR__ . '/../views/setup/step1_single.php';
        }
    }

    public function finish() {
        // Prevent re-installation if Super Admin or Lycee already exists
        $hasLycee = !empty(Lycee::findAll());
        $hasSuperAdmin = (User::findOneByRoleName('super_admin_createur') || User::findOneByRoleName('super_admin_national'));

        if ($hasLycee || $hasSuperAdmin) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /setup');
            exit();
        }

        $data = Validator::sanitize($_POST);
        $mode = $data['install_mode'] ?? 'single';

        if ($mode === 'multi') {
            $this->setupMultiSchool($data);
        } else {
            $this->setupSingleSchool($data);
        }

        if (!empty($_SESSION['error_message'])) {
            header('Location: /setup');
            exit();
        }

        $_SESSION['success_message'] = _("L'installation s'est déroulée avec succès. Vous pouvez maintenant vous connecter.");
        header('Location: /login');
        exit();
    }

    private function setupMultiSchool($data) {
        try {
            $email = trim($data['email'] ?? '');
            $pass = $data['mot_de_passe'] ?? '';
            $nom = trim($data['nom'] ?? '');
            $prenom = trim($data['prenom'] ?? '');

            if (empty($email) || empty($pass) || empty($nom) || empty($prenom)) {
                throw new InvalidArgumentException(_("Tous les champs sont obligatoires pour créer le Super Admin National."));
            }

            if (strlen($pass) < 4) {
                throw new InvalidArgumentException(_("Le mot de passe doit comporter au moins 4 caractères."));
            }

            $user_data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'mot_de_passe' => password_hash($pass, PASSWORD_DEFAULT),
                'role_id' => 2, // super_admin_national
                'lycee_id' => null,
                'actif' => 1
            ];

            User::save($user_data);
        } catch (Exception $e) {
            error_log("Multi setup failed: " . $e->getMessage());
            $_SESSION['error_message'] = _("L'installation Multi-écoles a échoué : ") . $e->getMessage();
        }
    }

    private function setupSingleSchool($data) {
        try {
            // Map single setup POST keys to SchoolInitializationService expected keys
            $initData = [
                'nom_lycee' => $data['nom_lycee'] ?? '',
                'type_lycee' => $data['type_lycee'] ?? 'prive',
                'sigle' => $data['sigle'] ?? null,
                'tel' => $data['tel'] ?? null,
                'email' => $data['lycee_email'] ?? null,
                'ville' => $data['ville'] ?? null,
                'quartier' => $data['quartier'] ?? null,

                'admin_nom' => $data['admin_nom'] ?? '',
                'admin_prenom' => $data['admin_prenom'] ?? '',
                'admin_email' => $data['admin_email'] ?? '',
                'admin_pass' => $data['admin_pass'] ?? '',

                'annee_libelle' => $data['annee_libelle'] ?? '',
                'annee_date_debut' => $data['annee_date_debut'] ?? '',
                'annee_date_fin' => $data['annee_date_fin'] ?? '',

                'sequence_annuelle' => $data['sequence_annuelle'] ?? 'Trimestrielle',
                'devise_pays' => $data['devise_pays'] ?? 'FCFA',
                'monnaie' => $data['monnaie'] ?? 'FCFA',
                'mode_cycle' => $data['mode_cycle'] ?? 'separe_ceg_lycee',
                'nb_langue' => $data['nb_langue'] ?? 1,
                'langue_1' => $data['langue_1'] ?? 'Francais',
                'langue_2' => $data['langue_2'] ?? null,

                'periods' => $data['periods'] ?? []
            ];

            SchoolInitializationService::initializeSchool($initData);
        } catch (Exception $e) {
            error_log("Single setup failed: " . $e->getMessage());
            $_SESSION['error_message'] = _("L'installation a échoué : ") . $e->getMessage();
        }
    }
}
?>