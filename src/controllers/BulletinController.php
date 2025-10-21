<?php

require_once __DIR__ . '/../models/Bulletin.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/ModeleBulletin.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class BulletinController {

    private function checkAccess($permission = 'bulletin:generate') {
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    // Step 1: Show form to select class and sequence
    public function index() {
        $this->checkAccess();

        $classes = Classe::findAll(Auth::getLyceeId());
        $sequences = Sequence::findAll();

        View::render('bulletins/index', [
            'classes' => $classes,
            'sequences' => $sequences,
            'title' => 'Génération des Bulletins'
        ]);
    }

    // Step 2: Show the list of students with their average for the selected class/sequence
    public function showClassResults() {
        $this->checkAccess();

        $classe_id = $_POST['classe_id'] ?? null;
        $sequence_id = $_POST['sequence_id'] ?? null;

        if (!$classe_id || !$sequence_id) {
            header('Location: /bulletins');
            exit();
        }

        $classe = Classe::findById($classe_id);
        $sequence = Sequence::findById($sequence_id);
        $results = Bulletin::generateForClass($classe_id, $sequence_id);

        View::render('bulletins/class_results', [
            'classe' => $classe,
            'sequence' => $sequence,
            'results' => $results,
            'title' => 'Résultats de la Classe'
        ]);
    }

    // Step 3: Show the detailed report card for a single student
    public function showStudentBulletin() {
        $this->checkAccess(); // Or a more specific permission like 'bulletin:view'

        $eleve_id = $_GET['eleve_id'] ?? null;
        $sequence_id = $_GET['sequence_id'] ?? null;

        if (!$eleve_id || !$sequence_id) {
            header('Location: /bulletins');
            exit();
        }

        $bulletin_data = Bulletin::generateForStudent($eleve_id, $sequence_id);

        if (!$bulletin_data) {
            View::render('errors/404', ['message' => 'Aucune donnée de bulletin trouvée pour cet élève et cette séquence.']);
            exit();
        }

        $template = ModeleBulletin::findByLyceeId();

        View::render('bulletins/show', [
            'bulletin' => $bulletin_data,
            'layout' => $template['layout_data'],
            'title' => 'Bulletin de ' . $bulletin_data['eleve']['prenom'] . ' ' . $bulletin_data['eleve']['nom']
        ]);
    }

    public function saveAppreciation() {
        $this->checkAccess('bulletin:validate'); // A more specific permission for this action

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Bulletin::saveAppreciation($data);
            header('Location: /bulletins/student?eleve_id=' . $data['eleve_id'] . '&sequence_id=' . $data['sequence_id']);
            exit();
        }

        // Redirect if not a POST request
        header('Location: /bulletins');
        exit();
    }
}
?>