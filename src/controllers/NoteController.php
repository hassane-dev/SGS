<?php

require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';

class NoteController {

    private function checkTeacher() {
        // This module is primarily for teachers
        if (!Auth::check() || Auth::get('role') !== 'enseignant') {
            http_response_code(403);
            echo "Accès Interdit. Vous devez être un enseignant.";
            exit();
        }
    }

    // Show the list of classes/subjects for the logged-in teacher
    public function index() {
        $this->checkTeacher();
        $teacher_id = Auth::get('id');
        $assignments = User::getTeacherAssignments($teacher_id);
        require_once __DIR__ . '/../views/notes/teacher_classes.php';
    }

    // Show the grade entry form
    public function enter() {
        $this->checkTeacher();
        $class_id = $_GET['class_id'] ?? null;
        $matiere_id = $_GET['matiere_id'] ?? null;
        $type = $_GET['type'] ?? 'devoir'; // 'devoir' or 'composition'

        if (!$class_id || !$matiere_id) {
            header('Location: /notes');
            exit();
        }

        // Security check: Make sure this teacher is actually assigned to this class/subject
        // (This is a simplified check)
        $assignments = User::getTeacherAssignments(Auth::get('id'));
        $is_assigned = false;
        foreach ($assignments as $assignment) {
            if ($assignment['id_classe'] == $class_id && $assignment['id_matiere'] == $matiere_id) {
                $is_assigned = true;
                break;
            }
        }
        if (!$is_assigned) {
            http_response_code(403);
            echo "Vous n'êtes pas assigné à cette classe/matière.";
            exit();
        }

        $classe = Classe::findById($class_id);
        $matiere = Matiere::findById($matiere_id);
        $students = Note::getStudentsForGrading($class_id, $matiere_id, $type);

        require_once __DIR__ . '/../views/notes/grade_entry.php';
    }

    // Save the grades from the form
    public function save() {
        $this->checkTeacher();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $class_id = $_POST['class_id'] ?? null;
            $matiere_id = $_POST['matiere_id'] ?? null;
            $type = $_POST['type'] ?? null;
            $notes = $_POST['notes'] ?? [];

            if ($class_id && $matiere_id && $type) {
                Note::saveBatch($class_id, $matiere_id, $type, $notes);
            }
        }
        // Redirect back to the teacher's class list
        header('Location: /notes');
        exit();
    }
}
?>
