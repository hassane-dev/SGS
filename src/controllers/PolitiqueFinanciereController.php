<?php

require_once __DIR__ . '/../models/PolitiqueFinanciere.php';
require_once __DIR__ . '/../models/EtatFinancierEleve.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class PolitiqueFinanciereController {

    private function checkAccess() {
        if (!Auth::can('edit', 'param_lycee')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function edit() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();

        $policy = PolitiqueFinanciere::findOrCreate($lycee_id);

        View::render('politiques_financieres/edit', [
            'title' => 'Politique financière du Lycée',
            'policy' => $policy
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /settings/politique-financiere');
            exit();
        }

        $data = Validator::sanitize($_POST);
        $lycee_id = Auth::getLyceeId();
        $data['lycee_id'] = $lycee_id;

        try {
            PolitiqueFinanciere::save($data);

            // Proactive update: Recalculate financial state for all students in the lycée
            $anneeActive = AnneeAcademique::findActive();
            if ($anneeActive) {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT eleve_id FROM etudes WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
                $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $anneeActive['id']]);
                $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($students as $studentId) {
                    EtatFinancierEleve::recalculateState($studentId);
                }
            }

            $_SESSION['success_message'] = "La politique financière de l'établissement a été mise à jour avec succès.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur lors de la mise à jour : " . $e->getMessage();
        }

        header('Location: /settings/politique-financiere');
        exit();
    }
}
