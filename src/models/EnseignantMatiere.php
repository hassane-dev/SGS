<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';

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
                em.statut,
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
     * This creates an assignment with 'en_attente' status.
     * It now includes a validation check for cycle compatibility.
     */
    public static function assign($enseignant_id, $classe_id, $matiere_id, $lycee_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            throw new Exception("Impossible d'assigner l'enseignant : Aucune année académique active.");
        }
        $annee_id = $active_year['id'];

        $db = Database::getInstance();

        // --- Cycle Validation ---
        $val_sql = "
            SELECT cl.cycle_id, cy.nom_cycle, m.cycle_concerne
            FROM classes cl
            JOIN cycles cy ON cl.cycle_id = cy.id_cycle
            JOIN matieres m ON m.id_matiere = :matiere_id
            WHERE cl.id_classe = :classe_id
        ";
        $stmt_val = $db->prepare($val_sql);
        $stmt_val->execute(['classe_id' => $classe_id, 'matiere_id' => $matiere_id]);
        $data = $stmt_val->fetch(PDO::FETCH_ASSOC);

        if ($data && !empty($data['cycle_concerne']) && strcasecmp($data['nom_cycle'], $data['cycle_concerne']) != 0) {
             throw new Exception("Incompatibilité de cycle : La matière est prévue pour le cycle '{$data['cycle_concerne']}' mais la classe appartient au cycle '{$data['nom_cycle']}'.");
        }
        // --- End Validation ---

        $delete_sql = "DELETE FROM enseignant_matieres WHERE classe_id = :classe_id AND matiere_id = :matiere_id AND annee_academique_id = :annee_academique_id AND lycee_id = :lycee_id";
        $insert_sql = "INSERT INTO enseignant_matieres (enseignant_id, classe_id, matiere_id, annee_academique_id, lycee_id, statut) VALUES (:enseignant_id, :classe_id, :matiere_id, :annee_academique_id, :lycee_id, 'en_attente')";

        try {
            $db->beginTransaction();

            $stmt_delete = $db->prepare($delete_sql);
            $stmt_delete->execute([
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_academique_id' => $annee_id,
                'lycee_id' => $lycee_id
            ]);

            $stmt_insert = $db->prepare($insert_sql);
            $stmt_insert->execute([
                'enseignant_id' => $enseignant_id,
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_academique_id' => $annee_id,
                'lycee_id' => $lycee_id
            ]);

            $db->commit();
            return true;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error in EnseignantMatiere::assign: " . $e->getMessage());
            throw new Exception("Une erreur de base de données est survenue lors de l'assignation.");
        }
    }

    /**
     * Validate an assignment.
     */
    public static function validate($assignment_id) {
        $sql = "UPDATE enseignant_matieres SET statut = 'valide', validateur_id = :validateur_id, date_validation = NOW() WHERE id = :id";
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'id' => $assignment_id,
                'validateur_id' => Auth::getUserId()
            ]);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::validate: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject an assignment.
     */
    public static function reject($assignment_id) {
        $sql = "UPDATE enseignant_matieres SET statut = 'refuse', validateur_id = :validateur_id, date_validation = NOW() WHERE id = :id";
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'id' => $assignment_id,
                'validateur_id' => Auth::getUserId()
            ]);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::reject: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unassign a teacher using the assignment ID (full deletion).
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
