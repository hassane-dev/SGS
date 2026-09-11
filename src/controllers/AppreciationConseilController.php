<?php

require_once __DIR__ . '/../models/Bulletin.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/ClasseParametre.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class AppreciationConseilController {

    private function checkAccess() {
        if (!Auth::can('edit_appreciation_conseil', 'bulletin')) {
            if (defined('TEST_MODE')) {
                throw new Exception("Accès Interdit (Permission bulletin:edit_appreciation_conseil requise).");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    /**
     * Dedicated Class Council Appreciation interface for Main Teachers (Professeurs Principaux).
     * Route: GET /appreciation-conseil
     */
    public function index() {
        $this->checkAccess();

        $active_year = AnneeAcademique::findActive();
        $lycee_id = Auth::getLyceeId();
        $user_id = Auth::getUserId();

        if (!$active_year || !$lycee_id || !$user_id) {
            View::render('appreciation_conseil/index', [
                'error' => _("Aucune année académique active ou établissement non défini."),
                'classes' => [],
                'sequences' => [],
                'selected_classe' => null,
                'selected_sequence' => null,
                'eleves' => [],
                'appreciations' => [],
                'title' => _("Appréciation du conseil de classe")
            ]);
            return;
        }

        $annee_id = (int)$active_year['id'];

        // Find classes where current user is Main Teacher (Professeur Principal) for current year and lycee
        $classes = ClasseParametre::findClassesByProfesseurPrincipal($user_id, $annee_id, $lycee_id);
        $sequences = Sequence::findAll();

        $classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : null;
        if (!$classe_id && !empty($classes)) {
            $classe_id = (int)$classes[0]['id_classe'];
        }

        // Default to active / open sequence if available
        $sequence_id = isset($_GET['sequence_id']) ? (int)$_GET['sequence_id'] : null;
        if (!$sequence_id && !empty($sequences)) {
            foreach ($sequences as $seq) {
                if ($seq['statut'] === 'ouverte') {
                    $sequence_id = (int)$seq['id'];
                    break;
                }
            }
            if (!$sequence_id && !empty($sequences)) {
                $sequence_id = (int)$sequences[0]['id'];
            }
        }

        $selected_classe = null;
        $selected_sequence = null;
        $eleves = [];
        $appreciations = [];

        if ($classe_id) {
            // Assert server-side PP assignment
            if (!ClasseParametre::isProfesseurPrincipal($user_id, $classe_id, $annee_id)) {
                if (defined('TEST_MODE')) {
                    throw new Exception("Accès Interdit (Vous n'êtes pas le professeur principal de cette classe).");
                }
                http_response_code(403);
                View::render('errors/403');
                exit();
            }

            $selected_classe = Classe::findById($classe_id);
            if ($selected_classe) {
                // Assert multi-tenant school access and cycle isolation
                AuthorizationScopeService::assertAccessToObject($selected_classe['lycee_id'], $selected_classe['cycle_id'] ?? null);
            }
        }

        if ($sequence_id) {
            $selected_sequence = Sequence::findById($sequence_id);
        }

        if ($selected_classe && $selected_sequence) {
            // Query enrolled students (ONLY id_eleve, nom, prenom, photo - NO grades, averages, or subjects)
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT e.id_eleve, e.nom, e.prenom, e.photo
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                WHERE et.classe_id = :classe_id
                  AND et.annee_academique_id = :annee_id
                  AND (et.is_active = 1 OR et.status = 'active')
                ORDER BY e.nom ASC, e.prenom ASC
            ");
            $stmt->execute(['classe_id' => $classe_id, 'annee_id' => $annee_id]);
            $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch existing appreciations
            $appreciations = Bulletin::findAppreciationsConseilByClasseAndSequence($classe_id, $sequence_id);
        }

        View::render('appreciation_conseil/index', [
            'classes' => $classes,
            'sequences' => $sequences,
            'selected_classe' => $selected_classe,
            'selected_sequence' => $selected_sequence,
            'eleves' => $eleves,
            'appreciations' => $appreciations,
            'title' => _("Appréciation du conseil de classe")
        ]);
    }

    /**
     * Bulk save Class Council Appreciations for a class.
     * Route: POST /appreciation-conseil/save
     */
    public function save() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!headers_sent()) {
                header('Location: /appreciation-conseil');
            }
            if (defined('TEST_MODE')) return;
            exit();
        }

        $active_year = AnneeAcademique::findActive();
        $lycee_id = Auth::getLyceeId();
        $user_id = Auth::getUserId();

        if (!$active_year || !$lycee_id || !$user_id) {
            $_SESSION['error_message'] = _("Erreur de session ou établissement non défini.");
            if (!headers_sent()) {
                header('Location: /appreciation-conseil');
            }
            if (defined('TEST_MODE')) return;
            exit();
        }

        $annee_id = (int)$active_year['id'];

        $classe_id = isset($_POST['classe_id']) ? (int)$_POST['classe_id'] : null;
        $sequence_id = isset($_POST['sequence_id']) ? (int)$_POST['sequence_id'] : null;

        if (!$classe_id || !$sequence_id) {
            $_SESSION['error_message'] = _("Classe ou Séquence invalide.");
            if (!headers_sent()) {
                header('Location: /appreciation-conseil');
            }
            if (defined('TEST_MODE')) return;
            exit();
        }

        // 1. Assert Server-side PP authorization
        if (!ClasseParametre::isProfesseurPrincipal($user_id, $classe_id, $annee_id)) {
            if (defined('TEST_MODE')) {
                throw new Exception("Accès Interdit (Vous n'êtes pas le professeur principal de cette classe).");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        // 2. Assert multi-tenant school tenant and cycle access
        $classe = Classe::findById($classe_id);
        if (!$classe) {
            $_SESSION['error_message'] = _("Classe introuvable.");
            if (!headers_sent()) {
                header('Location: /appreciation-conseil');
            }
            if (defined('TEST_MODE')) return;
            exit();
        }
        AuthorizationScopeService::assertAccessToObject($classe['lycee_id'], $classe['cycle_id'] ?? null);

        // 3. Verify sequence
        $sequence = Sequence::findById($sequence_id);
        if (!$sequence) {
            $_SESSION['error_message'] = _("Séquence introuvable.");
            if (!headers_sent()) {
                header('Location: /appreciation-conseil');
            }
            if (defined('TEST_MODE')) return;
            exit();
        }

        // 4. Verify list of active students enrolled in this class for the active year
        $db = Database::getInstance();
        $stmtStudents = $db->prepare("
            SELECT eleve_id FROM etudes
            WHERE classe_id = :classe_id
              AND annee_academique_id = :annee_id
              AND (is_active = 1 OR status = 'active')
        ");
        $stmtStudents->execute(['classe_id' => $classe_id, 'annee_id' => $annee_id]);
        $valid_student_ids = array_map('intval', $stmtStudents->fetchAll(PDO::FETCH_COLUMN));

        $rawAppreciations = $_POST['appreciations'] ?? [];
        if (!is_array($rawAppreciations)) {
            $rawAppreciations = [];
        }

        // Atomic Transaction: BEGIN -> Bulk Save -> COMMIT / ROLLBACK on error
        try {
            $db->beginTransaction();

            foreach ($rawAppreciations as $eleve_id_raw => $apprec_raw) {
                $eleve_id = (int)$eleve_id_raw;

                // Reject any eleve_id not enrolled in this class
                if (!in_array($eleve_id, $valid_student_ids, true)) {
                    throw new InvalidArgumentException(sprintf(_("L'élève ID %d n'appartient pas à la classe sélectionnée."), $eleve_id));
                }

                $apprec_text = is_string($apprec_raw) ? trim($apprec_raw) : null;
                if ($apprec_text === '') {
                    $apprec_text = null;
                }

                // Call Bulletin::saveAppreciationConseil (throws LogicException if status is 'valide' or 'publie')
                Bulletin::saveAppreciationConseil($eleve_id, $sequence_id, $annee_id, $lycee_id, $apprec_text);
            }

            $db->commit();
            $_SESSION['success_message'] = _("Les appréciations du conseil de classe ont été enregistrées avec succès.");

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error in AppreciationConseilController::save: " . $e->getMessage());
            $_SESSION['error_message'] = _("Erreur lors de l'enregistrement : ") . $e->getMessage();
        }

        if (!headers_sent()) {
            header('Location: /appreciation-conseil?classe_id=' . $classe_id . '&sequence_id=' . $sequence_id);
        }
        if (defined('TEST_MODE')) return;
        exit();
    }
}
?>