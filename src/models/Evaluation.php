<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Deblocage.php';
require_once __DIR__ . '/../models/ParamTypeEvaluation.php';

class Evaluation {

    /**
     * Get the currently defined evaluation settings for a given class/subject combo.
     * @param string|int $type The type code or type ID
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
     * Get existing grades for a specific evaluation (class, subject, sequence, type, numero).
     * Returns an array keyed by eleve_id for easy lookup.
     */
    public static function getGradesForEvaluation($classe_id, $matiere_id, $sequence_id, $type = 'devoir', $numero = 1) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return [];

        $lycee_id = Auth::getLyceeId() ?? $active_year['lycee_id'] ?? 1;

        // Resolve type
        $typeRec = null;
        if (is_numeric($type)) {
            $typeRec = ParamTypeEvaluation::findById((int)$type);
        } else {
            $typeRec = ParamTypeEvaluation::findByCode((string)$type, $lycee_id);
        }

        $typeCode = $typeRec['code'] ?? (string)$type;
        $typeId = $typeRec['id'] ?? null;

        $typeCond = ($typeId !== null)
            ? "(type_evaluation_id = :type_id OR type = :type_code)"
            : "type = :type_code";

        $sql = "SELECT * FROM evaluations
                WHERE classe_id = :classe_id
                  AND matiere_id = :matiere_id
                  AND sequence_id = :sequence_id
                  AND annee_academique_id = :annee_id
                  AND numero_evaluation = :numero
                  AND {$typeCond}";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $params = [
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'sequence_id' => $sequence_id,
                'annee_id' => $active_year['id'],
                'numero' => (int)$numero,
                'type_code' => $typeCode
            ];
            if ($typeId !== null) {
                $params['type_id'] = $typeId;
            }
            $stmt->execute($params);

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
     * Save a batch of grades. Performs upsert for each student's grade.
     */
    public static function saveGrades($data) {
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        if (!$lycee_id || !$active_year) {
            error_log("Cannot save grades: Missing lycee_id or active year.");
            return false;
        }

        $type = $data['type'] ?? 'devoir';
        $typeRec = is_numeric($type) ? ParamTypeEvaluation::findById((int)$type) : ParamTypeEvaluation::findByCode((string)$type, $lycee_id);

        $typeCode = $typeRec['code'] ?? (string)$type;
        $typeId = $typeRec['id'] ?? null;
        $baremeDefault = (!empty($typeRec['bareme_defaut']) && (float)$typeRec['bareme_defaut'] > 0) ? (float)$typeRec['bareme_defaut'] : 20.00;
        $baremeSnapshot = (!empty($data['bareme']) && (float)$data['bareme'] > 0) ? (float)$data['bareme'] : $baremeDefault;
        $numeroEval = (!empty($data['numero_evaluation']) && (int)$data['numero_evaluation'] > 0) ? (int)$data['numero_evaluation'] : 1;
        $libelleEval = !empty($data['libelle_evaluation']) ? trim($data['libelle_evaluation']) : null;

        $db = Database::getInstance();
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isSqlite = ($driver === 'sqlite');

        if ($isSqlite) {
            $sql = "
                INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, type_evaluation_id, numero_evaluation, libelle_evaluation, note, bareme_snapshot, coefficient, appreciation, date_saisie)
                VALUES (:lycee_id, :classe_id, :matiere_id, :enseignant_id, :eleve_id, :sequence_id, :annee_academique_id, :type, :type_evaluation_id, :numero_evaluation, :libelle_evaluation, :note, :bareme_snapshot, :coefficient, :appreciation, CURRENT_TIMESTAMP)
                ON CONFLICT(eleve_id, matiere_id, sequence_id, annee_academique_id, type_evaluation_id, numero_evaluation) DO UPDATE SET
                    note = excluded.note,
                    appreciation = excluded.appreciation,
                    bareme_snapshot = excluded.bareme_snapshot,
                    date_saisie = CURRENT_TIMESTAMP
            ";
        } else {
            $sql = "
                INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, type_evaluation_id, numero_evaluation, libelle_evaluation, note, bareme_snapshot, coefficient, appreciation, date_saisie)
                VALUES (:lycee_id, :classe_id, :matiere_id, :enseignant_id, :eleve_id, :sequence_id, :annee_academique_id, :type, :type_evaluation_id, :numero_evaluation, :libelle_evaluation, :note, :bareme_snapshot, :coefficient, :appreciation, NOW())
                ON DUPLICATE KEY UPDATE
                    note = VALUES(note),
                    appreciation = VALUES(appreciation),
                    bareme_snapshot = VALUES(bareme_snapshot),
                    date_saisie = NOW()
            ";
        }

        try {
            $stmt = $db->prepare($sql);
            $db->beginTransaction();

            foreach ($data['grades'] as $eleve_id => $grade_data) {
                if (!is_numeric($grade_data['note']) || $grade_data['note'] === '') continue;

                $stmt->execute([
                    'lycee_id' => $lycee_id,
                    'classe_id' => $data['classe_id'],
                    'matiere_id' => $data['matiere_id'],
                    'enseignant_id' => $data['enseignant_id'],
                    'eleve_id' => $eleve_id,
                    'sequence_id' => $data['sequence_id'],
                    'annee_academique_id' => $active_year['id'],
                    'type' => $typeCode,
                    'type_evaluation_id' => $typeId,
                    'numero_evaluation' => $numeroEval,
                    'libelle_evaluation' => $libelleEval,
                    'note' => $grade_data['note'],
                    'bareme_snapshot' => $baremeSnapshot,
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
     * Façade de compatibilité déléguant la vérification d'ouverture de la fenêtre de saisie
     * au service centralisé EvaluationSaisieService.
     */
    public static function isGradingWindowOpen($classe_id, $matiere_id, $sequence_id, $type = 'devoir', $simulatedNow = null) {
        require_once __DIR__ . '/../services/EvaluationSaisieService.php';
        return EvaluationSaisieService::isAllowed((int)$classe_id, (int)$matiere_id, (int)$sequence_id, $type, null, $simulatedNow);
    }
}
?>