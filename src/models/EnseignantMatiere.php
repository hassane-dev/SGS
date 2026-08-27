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

    /**
     * Find distinct assigned teachers filtered by dynamic class hierarchy (cycle, niveau, serie, numero, classe, matiere).
     */
    public static function findTeachersByHierarchy(int $lyceeId, ?int $cycleId = null, ?string $niveau = null, ?string $serie = null, ?string $numero = null, ?int $classeId = null, ?int $matiereId = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT u.id_user, u.nom, u.prenom, u.identifiant_public
            FROM enseignant_matieres em
            JOIN utilisateurs u ON em.enseignant_id = u.id_user
            JOIN classes c ON em.classe_id = c.id_classe
            WHERE c.lycee_id = :lycee_id
        ";
        $params = ['lycee_id' => $lyceeId];

        if (!empty($cycleId)) {
            $sql .= " AND c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if (!empty($niveau)) {
            $sql .= " AND c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if (!empty($serie)) {
            $sql .= " AND c.serie = :serie";
            $params['serie'] = $serie;
        }
        if ($numero !== null && $numero !== '') {
            $sql .= " AND c.numero = :numero";
            $params['numero'] = $numero;
        }
        if (!empty($classeId)) {
            $sql .= " AND c.id_classe = :classe_id";
            $params['classe_id'] = $classeId;
        }
        if (!empty($matiereId)) {
            $sql .= " AND em.matiere_id = :matiere_id";
            $params['matiere_id'] = $matiereId;
        }

        $sql .= " ORDER BY u.nom ASC, u.prenom ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::findTeachersByHierarchy: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find subjects assigned to teachers for a specific class.
     */
    public static function findSubjectsForClass(int $classeId): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT m.id_matiere, m.nom_matiere
            FROM enseignant_matieres em
            JOIN matieres m ON em.matiere_id = m.id_matiere
            WHERE em.classe_id = :classe_id
            ORDER BY m.nom_matiere ASC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['classe_id' => $classeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::findSubjectsForClass: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find subjects taught by a specific teacher across all assigned classes.
     */
    public static function findSubjectsForTeacher(int $teacherId): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT m.id_matiere, m.nom_matiere
            FROM enseignant_matieres em
            JOIN matieres m ON em.matiere_id = m.id_matiere
            WHERE em.enseignant_id = :teacher_id
            ORDER BY m.nom_matiere ASC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['teacher_id' => $teacherId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::findSubjectsForTeacher: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find classes assigned to a teacher.
     */
    public static function findClassesForTeacher(int $teacherId, ?int $lyceeId = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT c.id_classe, c.niveau, c.serie, c.numero, cy.nom_cycle, cy.id_cycle
            FROM enseignant_matieres em
            JOIN classes c ON em.classe_id = c.id_classe
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE em.enseignant_id = :teacher_id
        ";
        $params = ['teacher_id' => $teacherId];

        if ($lyceeId !== null) {
            $sql .= " AND c.lycee_id = :lycee_id";
            $params['lycee_id'] = $lyceeId;
        }

        $sql .= " ORDER BY c.niveau ASC, c.serie ASC, c.numero ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::findClassesForTeacher: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find subjects taught by a teacher in a specific class.
     */
    public static function findSubjectsForTeacherInClass(int $teacherId, int $classeId): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT m.id_matiere, m.nom_matiere
            FROM enseignant_matieres em
            JOIN matieres m ON em.matiere_id = m.id_matiere
            WHERE em.enseignant_id = :teacher_id AND em.classe_id = :classe_id
            ORDER BY m.nom_matiere ASC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'teacher_id' => $teacherId,
                'classe_id' => $classeId
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in EnseignantMatiere::findSubjectsForTeacherInClass: " . $e->getMessage());
            return [];
        }
    }
}