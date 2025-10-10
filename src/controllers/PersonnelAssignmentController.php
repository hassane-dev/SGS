<?php

require_once __DIR__ . '/../models/PersonnelAssignment.php';
require_once __DIR__ . '/../models/User.php';

class PersonnelAssignmentController {

    private function checkAccess() {
        // For now, only users with 'edit' permission on the 'user' resource can manage assignments.
        // This can be refined later if needed.
        if (!Auth::can('edit', 'user')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function store() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $personnel_id = $_POST['personnel_id'] ?? null;
            $assignment_type = $_POST['assignment_type'] ?? null;
            $target_id = $_POST['target_id'] ?? null;

            if ($personnel_id && $assignment_type && $target_id) {
                $user = User::findById($personnel_id);
                if ($user) {
                    PersonnelAssignment::add([
                        'personnel_id' => $personnel_id,
                        'assignment_type' => $assignment_type,
                        'target_id' => $target_id,
                        'lycee_id' => $user['lycee_id']
                    ]);
                }
            }
        }

        // Redirect back to the user's profile page
        header('Location: /users/view?id=' . $personnel_id);
        exit();
    }

    public function destroy() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $assignment_id = $_POST['id_assignment'] ?? null;
            $personnel_id = $_POST['personnel_id'] ?? null; // For redirect

            if ($assignment_id) {
                PersonnelAssignment::delete($assignment_id);
            }
        }

        // Redirect back to the user's profile page
        header('Location: /users/view?id=' . $personnel_id);
        exit();
    }
}
?>