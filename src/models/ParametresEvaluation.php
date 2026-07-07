<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class ParametresEvaluation {

    public static function save($data) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        // Check for existing record to avoid duplicates manually
        $sql_check = "SELECT id FROM parametres_evaluations
                      WHERE lycee_id = :lycee_id
                      AND annee_academique_id = :annee_id
                      AND type = :type
                      AND (classe_id = :classe_id OR (classe_id IS NULL AND :classe_id IS NULL))
                      AND (matiere_id = :matiere_id OR (matiere_id IS NULL AND :matiere_id IS NULL))
                      AND (enseignant_id = :enseignant_id OR (enseignant_id IS NULL AND :enseignant_id IS NULL))
                      AND (sequence_id = :sequence_id OR (sequence_id IS NULL AND :sequence_id IS NULL))
                      AND type_evaluation = :type_eval";

        $stmt_check = $db->prepare($sql_check);
        $stmt_check->execute([
            'lycee_id' => $lycee_id,
            'annee_id' => $active_year['id'],
            'type' => $data['type'],
            'classe_id' => $data['classe_id'] ?? null,
            'matiere_id' => $data['matiere_id'] ?? null,
            'enseignant_id' => $data['enseignant_id'] ?? null,
            'sequence_id' => $data['sequence_id'] ?? null,
            'type_eval' => $data['type_evaluation'] ?? 'tous'
        ]);
        $existing_id = $stmt_check->fetchColumn();

        if ($existing_id) {
            $sql = "UPDATE parametres_evaluations SET
                        date_ouverture_saisie = :date_ouverture,
                        date_fermeture_saisie = :date_fermeture,
                        commentaire = :commentaire,
                        enseignant_id = :enseignant_id
                    WHERE id = :id";
            $params = [
                'date_ouverture' => $data['date_ouverture_saisie'],
                'date_fermeture' => $data['date_fermeture_saisie'],
                'commentaire' => $data['commentaire'] ?? null,
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'id' => $existing_id
            ];
        } else {
            $sql = "INSERT INTO parametres_evaluations (
                        lycee_id, annee_academique_id, type, classe_id, matiere_id,
                        enseignant_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie, commentaire
                    ) VALUES (
                        :lycee_id, :annee_id, :type, :classe_id, :matiere_id,
                        :enseignant_id, :sequence_id, :type_evaluation, :date_ouverture, :date_fermeture, :commentaire
                    )";
            $params = [
                'lycee_id' => $lycee_id,
                'annee_id' => $active_year['id'],
                'type' => $data['type'],
                'classe_id' => $data['classe_id'] ?? null,
                'matiere_id' => $data['matiere_id'] ?? null,
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'sequence_id' => $data['sequence_id'] ?? null,
                'type_evaluation' => $data['type_evaluation'] ?? 'tous',
                'date_ouverture' => $data['date_ouverture_saisie'],
                'date_fermeture' => $data['date_fermeture_saisie'],
                'commentaire' => $data['commentaire'] ?? null
            ];
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::save: " . $e->getMessage());
            return false;
        }
    }

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();

        $sql = "SELECT p.*, c.nom_classe, m.nom_matiere, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
                       s.nom as sequence_nom
                FROM parametres_evaluations p
                LEFT JOIN classes c ON p.classe_id = c.id_classe
                LEFT JOIN matieres m ON p.matiere_id = m.id_matiere
                LEFT JOIN utilisateurs u ON p.enseignant_id = u.id_user
                LEFT JOIN sequences s ON p.sequence_id = s.id
                WHERE p.lycee_id = :lycee_id
                ORDER BY p.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM parametres_evaluations WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'lycee_id' => Auth::getLyceeId()]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::findById: " . $e->getMessage());
            return false;
        }
    }

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
                AND annee_academique_id = :annee_id
                AND type = 'enseignant'";

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
                $settings[$result['sequence_id']][$result['type_evaluation']] = $result;
            }
            return $settings;
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::findByClassAndMatiere: " . $e->getMessage());
            return [];
        }
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $sql = "DELETE FROM parametres_evaluations WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'lycee_id' => Auth::getLyceeId()
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::delete: " . $e->getMessage());
            return false;
        }
    }
}
?>