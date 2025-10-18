<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class ParametresEvaluation {

    /**
     * Find all settings for a given class and subject for the current academic year.
     * Returns an associative array keyed by sequence_id.
     */
    public static function findByClassAndMatiere($classe_id, $matiere_id) {
        $active_year = AnneeAcademique::findActive();
        $lycee_id = Auth::getLyceeId();
        if (!$active_year || !$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM parametres_evaluations
                WHERE lycee_id = :lycee_id
                AND classe_id = :classe_id
                AND matiere_id = :matiere_id
                AND annee_academique_id = :annee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_id' => $active_year['id']
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($results as $result) {
                $settings[$result['sequence_id']] = $result;
            }
            return $settings;
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::findByClassAndMatiere: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save (insert or update) evaluation settings for a specific sequence.
     */
    public static function save($data) {
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        if (!$lycee_id || !$active_year) {
            error_log("Cannot save settings: Missing lycee_id or active year.");
            return false;
        }

        // Check if a setting already exists for this combination
        $db = Database::getInstance();
        $stmt_check = $db->prepare("SELECT id FROM parametres_evaluations WHERE sequence_id = :sequence_id AND classe_id = :classe_id AND matiere_id = :matiere_id AND annee_academique_id = :annee_id");
        $stmt_check->execute([
            'sequence_id' => $data['sequence_id'],
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'annee_id' => $active_year['id']
        ]);
        $existing_id = $stmt_check->fetchColumn();

        $isUpdate = !empty($existing_id);

        $sql = $isUpdate
            ? "UPDATE parametres_evaluations SET enseignant_id = :enseignant_id, date_ouverture_saisie = :date_ouverture, date_fermeture_saisie = :date_fermeture, commentaire = :commentaire WHERE id = :id"
            : "INSERT INTO parametres_evaluations (lycee_id, classe_id, matiere_id, sequence_id, enseignant_id, annee_academique_id, date_ouverture_saisie, date_fermeture_saisie, commentaire)
               VALUES (:lycee_id, :classe_id, :matiere_id, :sequence_id, :enseignant_id, :annee_id, :date_ouverture, :date_fermeture, :commentaire)";

        try {
            $stmt = $db->prepare($sql);
            $params = [
                'enseignant_id' => $data['enseignant_id'], // The teacher assigned to the class/subject
                'date_ouverture' => $data['date_ouverture_saisie'],
                'date_fermeture' => $data['date_fermeture_saisie'],
                'commentaire' => $data['commentaire'] ?? null,
            ];

            if ($isUpdate) {
                $params['id'] = $existing_id;
            } else {
                $params['lycee_id'] = $lycee_id;
                $params['classe_id'] = $data['classe_id'];
                $params['matiere_id'] = $data['matiere_id'];
                $params['sequence_id'] = $data['sequence_id'];
                $params['annee_id'] = $active_year['id'];
            }

            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::save: " . $e->getMessage());
            return false;
        }
    }
}
?>