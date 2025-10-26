<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Validator.php';


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
        $userCheck = User::findOneByRoleName('super_admin_createur');
         if ($userCheck) {
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

        require_once __DIR__ . '/../views/auth/login.php';
        exit();
    }

    private function setupMultiSchool($data) {
        $user_data = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'mot_de_passe' => $data['mot_de_passe'],
            'role_id' => 2,
            'lycee_id' => null,
            'actif' => 1
        ];
        User::save($user_data);
    }

    private function setupSingleSchool($data) {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // 1. Create the Lycee
            $lycee_id = Lycee::save([
                'nom_lycee' => $data['nom_lycee'],
                'typeLycee' => $data['type_lycee'],
                'sigle' => null,
                'tel' => null,
                'email' => null,
                'ville' => null,
                'quartier' => null,
                'ruelle' => null,
                'boitePostale' => null,
                'arrete' => null,
                'arrondissement' => null,
                'devise' => null,
                'logo' => null,
                'boutique' => 0
            ]);
            if (!$lycee_id) throw new Exception("Failed to create the lycee.");

            // 2. Seed the database with default roles and permissions
            $seed_sql = file_get_contents(__DIR__ . '/../../db/seeds.sql');
            if ($seed_sql) $db->exec($seed_sql);

            // 3. Create a specific admin role for this Lycee
            Role::save([
                'nom_role' => 'Admin - ' . $data['nom_lycee'],
                'lycee_id' => $lycee_id
            ]);
            $role_id = $db->lastInsertId();

            // 4. Assign permissions to this new role
            $template_permissions = Role::getPermissions(3); // admin_local template
            $perm_ids = [];
            if (is_array($template_permissions)) {
                foreach ($template_permissions as $resource => $actions) {
                    foreach ($actions as $action) {
                        $stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :r AND action = :a");
                        $stmt->execute(['r' => $resource, 'a' => $action]);
                        if ($p_id = $stmt->fetchColumn()) $perm_ids[] = $p_id;
                    }
                }
            }
            Role::setPermissions($role_id, $perm_ids);

            // 5. Create the admin user
            User::save([
                'nom' => $data['admin_nom'],
                'prenom' => $data['admin_prenom'],
                'email' => $data['admin_email'],
                'mot_de_passe' => $data['admin_pass'],
                'role_id' => $role_id,
                'lycee_id' => $lycee_id,
                'actif' => 1
            ]);

            // 6. Create and activate the first academic year
            AnneeAcademique::save([
                'libelle' => $data['annee_academique'],
                'date_debut' => date('Y-09-01'),
                'date_fin' => date('Y') + 1 . '-06-30'
            ]);
            $annee_id = $db->lastInsertId();
            AnneeAcademique::setActive($annee_id);

            // 7. Save general settings
            ParamGeneral::save([
                'lycee_id' => $lycee_id
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            die("Setup failed: " . $e->getMessage());
        }
    }
}
?>