<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Deblocage.php';

class Evaluation {

    /**
     * Get the currently defined evaluation settings for a given class/subject combo.
     * This tells us which sequences are available for grading.
     * @param string $type The type of evaluation ('devoir' or 'composition')
     */
    public static function getAvailableEvaluations($classe_id, $matiere_id, $type = 'devoir') {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return [];

        $sequences = Sequence::findAll();
        $filtered = [];

        foreach ($sequences as $seq) {
            if (self::isGradingWindowOpen($classe_id, $matiere_id, $seq['id'], $type)) {
                $seq['sequence_nom'] = $seq['nom'];
                $seq['sequence_id'] = $seq['id'];
                $filtered[] = $seq;
            }
        }

        return $filtered;
    }

    /**
     * Get existing grades for a specific evaluation (class, subject, sequence, type).
     * Returns an array keyed by eleve_id for easy lookup.
     */
    public static function getGradesForEvaluation($classe_id, $matiere_id, $sequence_id, $type = 'devoir') {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return [];

        $sql = "SELECT * FROM evaluations
                WHERE classe_id = :classe_id
                  AND matiere_id = :matiere_id
                  AND sequence_id = :sequence_id
                  AND annee_academique_id = :annee_id
                  AND type = :type";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'sequence_id' => $sequence_id,
                'annee_id' => $active_year['id'],
                'type' => $type
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
            INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, note, coefficient, appreciation, date_saisie)
            VALUES (:lycee_id, :classe_id, :matiere_id, :enseignant_id, :eleve_id, :sequence_id, :annee_academique_id, :type, :note, :coefficient, :appreciation, NOW())
            ON DUPLICATE KEY UPDATE note = VALUES(note), appreciation = VALUES(appreciation), date_saisie = NOW()
        ";

        // We need a unique key on (eleve_id, sequence_id, matiere_id, annee_academique_id, type) for ON DUPLICATE KEY to work correctly.

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
                    'type' => $data['type'] ?? 'devoir',
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
    public static function isGradingWindowOpen($classe_id, $matiere_id, $sequence_id, $type = 'devoir') {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return false;

        $lycee_id = Auth::getLyceeId();
        $db = Database::getInstance();

        // Find teacher for this class/subject
        require_once __DIR__ . '/../models/EnseignantMatiere.php';
        $assignments = EnseignantMatiere::findAssignmentsForClass($classe_id);
        $enseignant_id = $assignments[$matiere_id]['enseignant_id'] ?? null;

        // 1. Check standard evaluation settings (harmonized logic)
        $sql = "SELECT id FROM parametres_evaluations
                WHERE lycee_id = :lycee_id
                AND annee_academique_id = :annee_id
                AND (type_evaluation = :type_eval OR type_evaluation = 'tous')
                AND NOW() BETWEEN date_ouverture_saisie AND date_fermeture_saisie
                AND (sequence_id IS NULL OR sequence_id = :sequence_id)
                AND (
                    type = 'global'
                    OR (type = 'classe' AND classe_id = :classe_id)
                    OR (type = 'matiere' AND matiere_id = :matiere_id)
                    OR (type = 'classe_matiere' AND classe_id = :classe_id AND matiere_id = :matiere_id)
                    OR (type = 'enseignant' AND classe_id = :classe_id AND matiere_id = :matiere_id AND enseignant_id = :enseignant_id)
                )";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_id' => $active_year['id'],
                'type_eval' => $type,
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'enseignant_id' => $enseignant_id,
                'sequence_id' => $sequence_id
            ]);
            if ($stmt->fetchColumn() !== false) {
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error in Evaluation::isGradingWindowOpen: " . $e->getMessage());
        }

        // 2. Check exceptional unlocks (Deblocage)
        return Deblocage::isUnlocked($classe_id, $matiere_id, $sequence_id, $enseignant_id, $type);
    }
}
?>