<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class ParamDevoir {

    /**
     * Finds the homework parameters for the current school and academic year.
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
            $stmt = $db->prepare("SELECT * FROM param_devoir WHERE lycee_id = :lycee_id AND annee_id = :annee_id");
            $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // No record found, create a default one
                $stmt_create = $db->prepare("INSERT INTO param_devoir (lycee_id, annee_id, cree_par) VALUES (:lycee_id, :annee_id, :userId)");
                $stmt_create->execute([
                    'lycee_id' => $lycee_id,
                    'annee_id' => $activeYear['id'],
                    'userId' => Auth::getUserId()
                ]);
                // Fetch the newly created record
                $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return $result;

        } catch (PDOException $e) {
            error_log("Error in ParamDevoir::findByCurrentSchoolAndYear: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the homework parameters.
     * @param array $data The data to update.
     * @return bool True on success, false on failure.
     */
    public static function update($data) {
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();
        if (!$lycee_id || !$activeYear) {
            return false;
        }

        $sql = "UPDATE param_devoir SET
                    nombre_devoir_par_sequence = :nombre_devoir_par_sequence,
                    note_maximale = :note_maximale
                WHERE lycee_id = :lycee_id AND annee_id = :annee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'nombre_devoir_par_sequence' => $data['nombre_devoir_par_sequence'],
                'note_maximale' => $data['note_maximale'],
                'lycee_id' => $lycee_id,
                'annee_id' => $activeYear['id']
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamDevoir::update: " . $e->getMessage());
            return false;
        }
    }
}
?>