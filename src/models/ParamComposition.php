<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class ParamComposition {

    /**
     * Finds the exam parameters for the current school and academic year.
     * If a record doesn't exist, it creates a default one.
     * @return array|false The parameter data.
     */
    public static function findByCurrentSchoolAndYear() {
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();
        if (!$lycee_id || !$activeYear) {
            return false;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM param_composition WHERE lycee_id = :lycee_id AND anneeId = :anneeId");
            $stmt->execute(['lycee_id' => $lycee_id, 'anneeId' => $activeYear['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // No record found, create a default one
                $stmt_create = $db->prepare("INSERT INTO param_composition (lycee_id, anneeId, creePar) VALUES (:lycee_id, :anneeId, :userId)");
                $stmt_create->execute([
                    'lycee_id' => $lycee_id,
                    'anneeId' => $activeYear['id'],
                    'userId' => Auth::get('id')
                ]);
                // Fetch the newly created record
                $stmt->execute(['lycee_id' => $lycee_id, 'anneeId' => $activeYear['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return $result;

        } catch (PDOException $e) {
            error_log("Error in ParamComposition::findByCurrentSchoolAndYear: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the exam parameters.
     * @param array $data The data to update.
     * @return bool True on success, false on failure.
     */
    public static function update($data) {
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();
        if (!$lycee_id || !$activeYear) {
            return false;
        }

        $sql = "UPDATE param_composition SET
                    nombreCompositionParSequence = :nombreCompositionParSequence,
                    noteMaximale = :noteMaximale,
                    dateDebutInsertion = :dateDebutInsertion,
                    dateFinInsertion = :dateFinInsertion,
                    deblocageUrgence = :deblocageUrgence
                WHERE lycee_id = :lycee_id AND anneeId = :anneeId";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'nombreCompositionParSequence' => $data['nombreCompositionParSequence'],
                'noteMaximale' => $data['noteMaximale'],
                'dateDebutInsertion' => $data['dateDebutInsertion'] ?: null,
                'dateFinInsertion' => $data['dateFinInsertion'] ?: null,
                'deblocageUrgence' => isset($data['deblocageUrgence']) ? 1 : 0,
                'lycee_id' => $lycee_id,
                'anneeId' => $activeYear['id']
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamComposition::update: " . $e->getMessage());
            return false;
        }
    }
}
?>