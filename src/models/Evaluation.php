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
     * Enforces strict 4-level hierarchy:
     * 1. Sequence status check: If sequence is closed ('fermee'), normal grading is blocked (unless unlocked by exception).
     * 2. Exceptional unlock (deblocage_notes): Overrides closed sequences or expired settings.
     * 3. Explicit evaluation settings (parametres_evaluations): If explicit rules exist for the target, their active dates determine authorization.
     * 4. Default fallback: If sequence is open ('ouverte') and no explicit rules exist for the target, grading is allowed by default.
     */
    public static function isGradingWindowOpen($classe_id, $matiere_id, $sequence_id, $type = 'devoir') {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return false;

        require_once __DIR__ . '/Sequence.php';
        $sequence = Sequence::findById($sequence_id);
        if (!$sequence) return false;

        $is_sequence_open = (isset($sequence['statut']) && $sequence['statut'] === 'ouverte');

        $lycee_id = Auth::getLyceeId();
        $db = Database::getInstance();

        // Find teacher for this class/subject
        require_once __DIR__ . '/../models/AffectationPedagogique.php';
        $assignments = AffectationPedagogique::findAssignmentsForClass($classe_id);
        $enseignant_id = $assignments[$matiere_id]['enseignant_id'] ?? null;

        // Level 2: Check exceptional unlocks (Deblocage) - overrides closed sequence or expired parameters
        if (Deblocage::isUnlocked($classe_id, $matiere_id, $sequence_id, $enseignant_id, $type)) {
            return true;
        }

        // Level 1 Enforcement: If sequence is closed and no exceptional unlock exists -> BLOCKED
        if (!$is_sequence_open) {
            return false;
        }

        // Level 3: Check explicit evaluation settings (parametres_evaluations)
        $teacherCondition = ($enseignant_id !== null)
            ? "(type = 'enseignant' AND classe_id = :classe_id5 AND matiere_id = :matiere_id5 AND enseignant_id = :enseignant_id)"
            : "(1 = 0)";

        $sql = "SELECT id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie,
                       (CASE
                           WHEN type = 'enseignant' THEN 5
                           WHEN type = 'classe_matiere' THEN 4
                           WHEN type = 'classe' THEN 3
                           WHEN type = 'matiere' THEN 2
                           ELSE 1
                       END) as specificity
                FROM parametres_evaluations
                WHERE lycee_id = :lycee_id
                AND annee_academique_id = :annee_id
                AND (sequence_id IS NULL OR sequence_id = :sequence_id)
                AND (
                    type = 'global'
                    OR (type = 'classe' AND classe_id = :classe_id3)
                    OR (type = 'matiere' AND matiere_id = :matiere_id2)
                    OR (type = 'classe_matiere' AND classe_id = :classe_id4 AND matiere_id = :matiere_id4)
                    OR {$teacherCondition}
                )
                ORDER BY specificity DESC";

        try {
            $stmt = $db->prepare($sql);
            $params = [
                'lycee_id' => $lycee_id,
                'annee_id' => $active_year['id'],
                'sequence_id' => $sequence_id,
                'classe_id3' => $classe_id,
                'matiere_id2' => $matiere_id,
                'classe_id4' => $classe_id,
                'matiere_id4' => $matiere_id
            ];
            if ($enseignant_id !== null) {
                $params['classe_id5'] = $classe_id;
                $params['matiere_id5'] = $matiere_id;
                $params['enseignant_id'] = $enseignant_id;
            }
            $stmt->execute($params);
            $matching_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($matching_rules)) {
                // Explicit rules exist for this target. Filter by highest specificity level present.
                $max_specificity = (int)$matching_rules[0]['specificity'];
                $target_rules = array_filter($matching_rules, function($r) use ($max_specificity) {
                    return (int)$r['specificity'] === $max_specificity;
                });

                $now = date('Y-m-d H:i:s');
                foreach ($target_rules as $rule) {
                    $rule_type = $rule['type_evaluation'] ?? 'tous';
                    if ($rule_type === 'tous' || $rule_type === $type) {
                        if ($now >= $rule['date_ouverture_saisie'] && $now <= $rule['date_fermeture_saisie']) {
                            return true;
                        }
                    }
                }
                // Explicit rules exist for this target at the highest specificity level,
                // but none cover the requested type OR those that do are not active at NOW -> EXPLICITLY BLOCKED
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error in Evaluation::isGradingWindowOpen: " . $e->getMessage());
        }

        // Level 4: Fallback by default - If sequence is open and NO explicit rules exist for this target -> ALLOWED BY DEFAULT
        return true;
    }
}
?>