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
            $stmt = $db->prepare("SELECT * FROM param_composition WHERE lycee_id = :lycee_id AND annee_id = :annee_id");
            $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // No record found, create a default one
                $stmt_create = $db->prepare("INSERT INTO param_composition (lycee_id, annee_id, cree_par) VALUES (:lycee_id, :annee_id, :userId)");
                $stmt_create->execute([
                    'lycee_id' => $lycee_id,
                    'annee_id' => $activeYear['id'],
                    'userId' => Auth::get('id_user')
                ]);
                // Fetch the newly created record
                $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
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
                    nombre_composition_par_sequence = :nombre_composition_par_sequence,
                    note_maximale = :note_maximale,
                    date_debut_insertion = :date_debut_insertion,
                    date_fin_insertion = :date_fin_insertion,
                    deblocage_urgence = :deblocage_urgence
                WHERE lycee_id = :lycee_id AND annee_id = :annee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'nombre_composition_par_sequence' => $data['nombre_composition_par_sequence'],
                'note_maximale' => $data['note_maximale'],
                'date_debut_insertion' => $data['date_debut_insertion'] ?: null,
                'date_fin_insertion' => $data['date_fin_insertion'] ?: null,
                'deblocage_urgence' => isset($data['deblocage_urgence']) ? 1 : 0,
                'lycee_id' => $lycee_id,
                'annee_id' => $activeYear['id']
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamComposition::update: " . $e->getMessage());
            return false;
        }
    }
}
?>