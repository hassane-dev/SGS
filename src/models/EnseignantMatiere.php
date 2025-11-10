<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class EnseignantMatiere {

    /**
     * Find all assignments for a given class for the current academic year.
     * Returns an associative array mapping matiere_id to teacher's data.
     */
    public static function findAssignmentsForClass($classe_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            return [];
        }

        $sql = "
            SELECT
                em.id,
                em.matiere_id,
                u.id_user as enseignant_id,
                CONCAT(u.prenom, ' ', u.nom) as enseignant_nom
            FROM enseignant_matieres em
            JOIN utilisateurs u ON em.enseignant_id = u.id_user
            WHERE em.classe_id = :classe_id
            AND em.annee_academique_id = :annee_academique_id
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'classe_id' => $classe_id,
                'annee_academique_id' => $active_year['id']
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Re-key the array by matiere_id for easy lookup in the view
            $assignments = [];
            foreach ($results as $result) {
                $assignments[$result['matiere_id']] = $result;
            }
            return $assignments;

        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::findAssignmentsForClass: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign a teacher to a subject in a class for the current academic year.
     * It performs an "upsert" (update or insert).
     */
    public static function assign($enseignant_id, $classe_id, $matiere_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            error_log("Cannot assign teacher: No active academic year.");
            return false;
        }
        $annee_id = $active_year['id'];

        $sql = "
            INSERT INTO enseignant_matieres (enseignant_id, classe_id, matiere_id, annee_academique_id)
            VALUES (:enseignant_id, :classe_id, :matiere_id, :annee_academique_id)
            ON DUPLICATE KEY UPDATE enseignant_id = VALUES(enseignant_id)
        ";

        // The unique key is on (enseignant_id, classe_id, matiere_id, annee_academique_id)
        // A better approach for upserting only the teacher for a class/subject/year combo
        // is to first DELETE any existing entry for that combo, then INSERT the new one.

        $delete_sql = "DELETE FROM enseignant_matieres WHERE classe_id = :classe_id AND matiere_id = :matiere_id AND annee_academique_id = :annee_academique_id";
        $insert_sql = "INSERT INTO enseignant_matieres (enseignant_id, classe_id, matiere_id, annee_academique_id) VALUES (:enseignant_id, :classe_id, :matiere_id, :annee_academique_id)";

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $stmt_delete = $db->prepare($delete_sql);
            $stmt_delete->execute([
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_academique_id' => $annee_id
            ]);

            $stmt_insert = $db->prepare($insert_sql);
            $stmt_insert->execute([
                'enseignant_id' => $enseignant_id,
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_academique_id' => $annee_id
            ]);

            $db->commit();
            return true;

        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error in EnseignantMatiere::assign: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unassign a teacher using the assignment ID.
     */
    public static function unassign($assignment_id) {
        $sql = "DELETE FROM enseignant_matieres WHERE id = :id";
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute(['id' => $assignment_id]);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::unassign: " . $e->getMessage());
            return false;
        }
    }
}