<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/ParametresGeneraux.php';
require_once __DIR__ . '/../models/Permission.php';


class SetupController {

    public function index() {
        // Step 0: Show the choice form
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /setup');
            exit();
        }

        $mode = $_POST['install_mode'] ?? 'single';

        if ($mode === 'multi') {
            $this->setupMultiSchool($_POST);
        } else {
            $this->setupSingleSchool($_POST);
        }

        // Redirect to login page after setup is complete
        header('Location: /login');
        exit();
    }

    private function setupMultiSchool($data) {
        // In multi-school mode, we just create the super_admin_national
        $user_data = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'mot_de_passe' => $data['mot_de_passe'],
            'role_id' => 2, // Assumes role_id 2 is super_admin_national from seeds
            'lycee_id' => null,
            'actif' => 1
        ];
        User::save($user_data);
    }

    private function setupSingleSchool($data) {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // 1. Create the Lycee and get its ID
            $lycee_data = [
                'nom_lycee' => $data['nom_lycee'],
                'type_lycee' => $data['type_lycee'],
            ];
            $lycee_id = Lycee::save($lycee_data);

            if (!$lycee_id) {
                throw new Exception("Failed to create the lycee.");
            }

            // 2. Create a specific admin role for this Lycee
            $role_data = [
                'nom_role' => 'Admin - ' . $data['nom_lycee'],
                'lycee_id' => $lycee_id
            ];
            Role::save($role_data);
            $role_id = $db->lastInsertId();

            // 3. Assign permissions to this new role (e.g., copy from template role 3)
            $template_permissions = Role::getPermissions(3); // Get perms from admin_local template
            $perm_ids = [];
            foreach($template_permissions as $p_name) {
                // This is inefficient, a better way would be a direct SQL copy or a findByName method
                // For now, this will work.
                $stmt = $db->prepare("SELECT id_permission FROM permissions WHERE nom_permission = :p_name");
                $stmt->execute(['p_name' => $p_name]);
                $p_id = $stmt->fetchColumn();
                if ($p_id) $perm_ids[] = $p_id;
            }
            Role::setPermissions($role_id, $perm_ids);

            // 4. Create the admin user for the Lycee
            $user_data = [
                'nom' => $data['admin_nom'],
                'prenom' => $data['admin_prenom'],
                'email' => $data['admin_email'],
                'mot_de_passe' => $data['admin_pass'],
                'role_id' => $role_id,
                'lycee_id' => $lycee_id,
                'actif' => 1
            ];
            User::save($user_data);

            // 3. Assign permissions to this new role (copy from template role 3)
            $template_permissions = Role::getPermissions(3); // Get perms from admin_local template
            $perm_ids = [];
            if (is_array($template_permissions)) {
                foreach ($template_permissions as $resource => $actions) {
                    foreach ($actions as $action) {
                        $stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :resource AND action = :action");
                        $stmt->execute(['resource' => $resource, 'action' => $action]);
                        $p_id = $stmt->fetchColumn();
                        if ($p_id) {
                            $perm_ids[] = $p_id;
                        }
                    }
                }
            }
            Role::setPermissions($role_id, $perm_ids);

            // 4. Create the admin user for the Lycee
            $user_data = [
                'nom' => $data['admin_nom'],
                'prenom' => $data['admin_prenom'],
                'email' => $data['admin_email'],
                'mot_de_passe' => $data['admin_pass'],
                'role_id' => $role_id,
                'lycee_id' => $lycee_id,
                'actif' => 1
            ];
            User::save($user_data);

            // 5. Create and activate the first academic year
            require_once __DIR__ . '/../models/AnneeAcademique.php';
            $annee_data = [
                'libelle' => $data['annee_academique'],
                'date_debut' => date('Y-m-d', strtotime('first day of September this year')),
                'date_fin' => date('Y-m-d', strtotime('last day of June next year'))
            ];
            AnneeAcademique::save($annee_data);
            $annee_id = $db->lastInsertId();
            AnneeAcademique::setActive($annee_id);

            // 6. Save general settings
            $settings_data = [
                'lycee_id' => $lycee_id,
                'nom_lycee' => $data['nom_lycee'],
                'type_lycee' => $data['type_lycee'],
                'annee_academique_id' => $annee_id,
                'modalite_paiement' => 'avant_inscription', // Default
            ];
            ParametresGeneraux::save($settings_data);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            // In a real app, show a proper error page
            die("Setup failed: " . $e->getMessage());
        }
    }
}
?>
