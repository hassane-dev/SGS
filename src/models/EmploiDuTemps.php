<?php

require_once __DIR__ . '/../config/database.php';

class EmploiDuTemps {

    /**
     * Get all timetable entries for a given context (class, teacher, or room).
     * @param string $annee_academique
     * @param int|null $classe_id
     * @param int|null $professeur_id
     * @return array
     */
    public static function getByContext($annee_academique, $classe_id = null, $professeur_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT edt.*, c.nom_classe, m.nom_matiere, u.nom as prof_nom, u.prenom as prof_prenom, s.nom_salle
                FROM emploi_du_temps edt
                JOIN classes c ON edt.classe_id = c.id_classe
                JOIN matieres m ON edt.matiere_id = m.id_matiere
                JOIN utilisateurs u ON edt.professeur_id = u.id_user
                JOIN salles s ON edt.salle_id = s.id_salle
                WHERE edt.annee_academique = :annee_academique";

        $params = ['annee_academique' => $annee_academique];

        if ($classe_id) {
            $sql .= " AND edt.classe_id = :classe_id";
            $params['classe_id'] = $classe_id;
        }
        if ($professeur_id) {
            $sql .= " AND edt.professeur_id = :professeur_id";
            $params['professeur_id'] = $professeur_id;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check for conflicts before saving a new entry.
     * A conflict exists if the same teacher or same class is booked at the same time.
     * @param array $data
     * @param int|null $exclude_id The ID of the current entry to exclude from the check (for updates).
     * @return string|false False if no conflict, or a string describing the conflict.
     */
    private static function checkConflict($data, $exclude_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM emploi_du_temps WHERE
                annee_academique = :annee_academique AND
                jour = :jour AND
                (heure_debut < :heure_fin AND heure_fin > :heure_debut) AND
                (professeur_id = :professeur_id OR classe_id = :classe_id)";

        if ($exclude_id) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $db->prepare($sql);
        $params = [
            'annee_academique' => $data['annee_academique'],
            'jour' => $data['jour'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'professeur_id' => $data['professeur_id'],
            'classe_id' => $data['classe_id'],
        ];
        if ($exclude_id) {
            $params['exclude_id'] = $exclude_id;
        }
        $stmt->execute($params);

        $conflict = $stmt->fetch();
        if ($conflict) {
            if ($conflict['professeur_id'] == $data['professeur_id']) {
                return "Conflict: The teacher is already booked at this time.";
            }
            if ($conflict['classe_id'] == $data['classe_id']) {
                return "Conflict: The class is already booked at this time.";
            }
        }
        return false;
    }

    public static function save($data) {
        $isUpdate = !empty($data['id']);
        $exclude_id = $isUpdate ? $data['id'] : null;

        if (self::checkConflict($data, $exclude_id) !== false) {
            return false; // Conflict detected
        }

        $sql = $isUpdate
            ? "UPDATE emploi_du_temps SET classe_id = :classe_id, matiere_id = :matiere_id, professeur_id = :professeur_id, jour = :jour, heure_debut = :heure_debut, heure_fin = :heure_fin, salle_id = :salle_id, annee_academique = :annee_academique WHERE id = :id"
            : "INSERT INTO emploi_du_temps (classe_id, matiere_id, professeur_id, jour, heure_debut, heure_fin, salle_id, annee_academique) VALUES (:classe_id, :matiere_id, :professeur_id, :jour, :heure_debut, :heure_fin, :salle_id, :annee_academique)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'professeur_id' => $data['professeur_id'],
            'jour' => $data['jour'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'salle_id' => $data['salle_id'],
            'annee_academique' => $data['annee_academique'],
        ];

        if ($isUpdate) {
            $params['id'] = $data['id'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM emploi_du_temps WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
