<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class Evaluation {

    /**
     * Get the currently defined evaluation settings for a given class/subject combo.
     * This tells us which sequences are available for grading.
     */
    public static function getAvailableEvaluations($classe_id, $matiere_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return [];

        $sql = "
            SELECT p.*, s.nom as sequence_nom
            FROM parametres_evaluations p
            JOIN sequences s ON p.sequence_id = s.id
            WHERE p.classe_id = :classe_id
              AND p.matiere_id = :matiere_id
              AND p.annee_academique_id = :annee_id
              AND NOW() BETWEEN p.date_ouverture_saisie AND p.date_fermeture_saisie
            ORDER BY s.date_debut ASC
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_id' => $active_year['id']
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Evaluation::getAvailableEvaluations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get existing grades for a specific evaluation (class, subject, sequence).
     * Returns an array keyed by eleve_id for easy lookup.
     */
    public static function getGradesForEvaluation($classe_id, $matiere_id, $sequence_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return [];

        $sql = "SELECT * FROM evaluations
                WHERE classe_id = :classe_id
                  AND matiere_id = :matiere_id
                  AND sequence_id = :sequence_id
                  AND annee_academique_id = :annee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'sequence_id' => $sequence_id,
                'annee_id' => $active_year['id']
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $grades = [];
            foreach ($results as $result) {
                $grades[$result['eleve_id']] = $result;
            }
            return $grades;

        } catch (PDOException $e) {
            error_log("Error in Evaluation::getGradesForEvaluation: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save a batch of grades. This performs an "upsert" for each student's grade.
     */
    public static function saveGrades($data) {
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        if (!$lycee_id || !$active_year) {
            error_log("Cannot save grades: Missing lycee_id or active year.");
            return false;
        }

        $sql = "
            INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, note, coefficient, appreciation, date_saisie)
            VALUES (:lycee_id, :classe_id, :matiere_id, :enseignant_id, :eleve_id, :sequence_id, :annee_academique_id, :note, :coefficient, :appreciation, NOW())
            ON DUPLICATE KEY UPDATE note = VALUES(note), appreciation = VALUES(appreciation), date_saisie = NOW()
        ";

        // We need a unique key on (eleve_id, sequence_id, matiere_id, annee_academique_id) for ON DUPLICATE KEY to work correctly.
        // Let's assume this key exists.

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $db->beginTransaction();

            foreach ($data['grades'] as $eleve_id => $grade_data) {
                if (!is_numeric($grade_data['note']) || $grade_data['note'] === '') continue; // Skip if no grade is entered

                $stmt->execute([
                    'lycee_id' => $lycee_id,
                    'classe_id' => $data['classe_id'],
                    'matiere_id' => $data['matiere_id'],
                    'enseignant_id' => $data['enseignant_id'],
                    'eleve_id' => $eleve_id,
                    'sequence_id' => $data['sequence_id'],
                    'annee_academique_id' => $active_year['id'],
                    'note' => $grade_data['note'],
                    'coefficient' => $data['coefficient'],
                    'appreciation' => $grade_data['appreciation'] ?? null
                ]);
            }

            $db->commit();
            return true;

        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error in Evaluation::saveGrades: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Before saving grades, we must verify the grading window is open.
     */
    public static function isGradingWindowOpen($classe_id, $matiere_id, $sequence_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return false;

        $sql = "SELECT id FROM parametres_evaluations
                WHERE classe_id = :classe_id
                  AND matiere_id = :matiere_id
                  AND sequence_id = :sequence_id
                  AND annee_academique_id = :annee_id
                  AND NOW() BETWEEN date_ouverture_saisie AND date_fermeture_saisie";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'classe_id' => $classe_id,
            'matiere_id' => $matiere_id,
            'sequence_id' => $sequence_id,
            'annee_id' => $active_year['id']
        ]);

        return $stmt->fetchColumn() !== false;
    }
}
?>