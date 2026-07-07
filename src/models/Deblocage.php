<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class Deblocage {

    public static function save($data) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        // Check for existing record to avoid duplicates manually since NULLs in unique keys are tricky
        $sql_check = "SELECT id FROM deblocages_notes
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
            $sql = "UPDATE deblocages_notes SET
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        motif = :motif,
                        cree_par = :cree_par
                    WHERE id = :id";
            $params = [
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'motif' => $data['motif'] ?? null,
                'cree_par' => Auth::getUserId(),
                'id' => $existing_id
            ];
        } else {
            $sql = "INSERT INTO deblocages_notes (
                        lycee_id, annee_academique_id, type, classe_id, matiere_id,
                        enseignant_id, sequence_id, type_evaluation, date_debut, date_fin, motif, cree_par
                    ) VALUES (
                        :lycee_id, :annee_id, :type, :classe_id, :matiere_id,
                        :enseignant_id, :sequence_id, :type_evaluation, :date_debut, :date_fin, :motif, :cree_par
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
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'motif' => $data['motif'] ?? null,
                'cree_par' => Auth::getUserId()
            ];
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Deblocage::save: " . $e->getMessage());
            return false;
        }
    }

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();

        $sql = "SELECT d.*, c.nom_classe, m.nom_matiere, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
                       s.nom as sequence_nom, creator.nom as creator_nom, creator.prenom as creator_prenom
                FROM deblocages_notes d
                LEFT JOIN classes c ON d.classe_id = c.id_classe
                LEFT JOIN matieres m ON d.matiere_id = m.id_matiere
                LEFT JOIN utilisateurs u ON d.enseignant_id = u.id_user
                LEFT JOIN sequences s ON d.sequence_id = s.id
                LEFT JOIN utilisateurs creator ON d.cree_par = creator.id_user
                WHERE d.lycee_id = :lycee_id
                ORDER BY d.cree_le DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Deblocage::findAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Checks if there is an active exceptional unlock for the given criteria.
     */
    public static function isUnlocked($classe_id, $matiere_id, $sequence_id, $enseignant_id, $type_evaluation = 'devoir') {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        if (!$active_year) return false;

        $sql = "SELECT id FROM deblocages_notes
                WHERE lycee_id = :lycee_id
                AND annee_academique_id = :annee_id
                AND (type_evaluation = :type_eval OR type_evaluation = 'tous')
                AND NOW() BETWEEN date_debut AND date_fin
                AND (
                    type = 'global'
                    OR (type = 'classe' AND classe_id = :classe_id)
                    OR (type = 'matiere' AND matiere_id = :matiere_id)
                    OR (type = 'classe_matiere' AND classe_id = :classe_id AND matiere_id = :matiere_id)
                    OR (type = 'enseignant' AND classe_id = :classe_id AND matiere_id = :matiere_id AND enseignant_id = :enseignant_id)
                )";

        // We also want to check sequence_id if it was specified in the unlock,
        // but the requirements say "all subjects of this class", "all classes of this subject", etc.
        // If sequence_id is NULL in deblocages_notes, it means all sequences are unlocked.

        $sql .= " AND (sequence_id IS NULL OR sequence_id = :sequence_id)";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_id' => $active_year['id'],
                'type_eval' => $type_evaluation,
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'enseignant_id' => $enseignant_id,
                'sequence_id' => $sequence_id
            ]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log("Error in Deblocage::isUnlocked: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $sql = "DELETE FROM deblocages_notes WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'lycee_id' => Auth::getLyceeId()
            ]);
        } catch (PDOException $e) {
            error_log("Error in Deblocage::delete: " . $e->getMessage());
            return false;
        }
    }
}
