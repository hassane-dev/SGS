<?php

class EmploiDuTemps {

    /**
     * Find a timetable entry by its ID.
     * @param int $id The entry ID.
     * @return array|false The entry data or false if not found.
     */
    public static function findById($id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM emploi_du_temps WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Find all timetable entries for a given class and academic year.
     * @param int $classe_id The class ID.
     * @param string $annee_academique The academic year.
     * @return array An array of timetable entries.
     */
    public static function findByClass($classe_id, $annee_academique) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT edt.*, m.nom_matiere, u.prenom, u.nom AS nom_professeur, s.nom_salle
            FROM emploi_du_temps edt
            JOIN matieres m ON edt.matiere_id = m.id_matiere
            JOIN utilisateurs u ON edt.professeur_id = u.id_user
            JOIN salles s ON edt.salle_id = s.id_salle
            WHERE edt.classe_id = :classe_id AND edt.annee_academique = :annee_academique
            ORDER BY jour, heure_debut
        ");
        $stmt->execute(['classe_id' => $classe_id, 'annee_academique' => $annee_academique]);
        return $stmt->fetchAll();
    }

    /**
     * Save a timetable entry (create or update).
     * @param array $data The entry data.
     * @return bool True on success, false on failure.
     */
    public static function save($data) {
        $pdo = Database::getInstance();
        $sql = "";
        if (isset($data['id']) && !empty($data['id'])) {
            // Update
            $sql = "UPDATE emploi_du_temps SET classe_id = :classe_id, matiere_id = :matiere_id, professeur_id = :professeur_id, jour = :jour, heure_debut = :heure_debut, heure_fin = :heure_fin, salle_id = :salle_id, annee_academique = :annee_academique, modifiable = :modifiable WHERE id = :id";
        } else {
            // Create
            $sql = "INSERT INTO emploi_du_temps (classe_id, matiere_id, professeur_id, jour, heure_debut, heure_fin, salle_id, annee_academique, modifiable) VALUES (:classe_id, :matiere_id, :professeur_id, :jour, :heure_debut, :heure_fin, :salle_id, :annee_academique, :modifiable)";
        }

        $stmt = $pdo->prepare($sql);

        $params = [
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'professeur_id' => $data['professeur_id'],
            'jour' => $data['jour'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'salle_id' => $data['salle_id'],
            'annee_academique' => $data['annee_academique'],
            'modifiable' => $data['modifiable'] ?? 1
        ];

        if (isset($data['id']) && !empty($data['id'])) {
            $params['id'] = $data['id'];
        }

        return $stmt->execute($params);
    }

    /**
     * Delete a timetable entry.
     * @param int $id The entry ID.
     * @return bool True on success, false on failure.
     */
    public static function delete($id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM emploi_du_temps WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Check for conflicts before saving a timetable entry.
     * @param array $data The entry data.
     * @return array An array of conflict messages, empty if no conflicts.
     */
    public static function checkConflicts($data) {
        $conflicts = [];
        $pdo = Database::getInstance();

        $id_to_exclude = $data['id'] ?? 0;

        // Check if the teacher is already booked at that time
        $stmt = $pdo->prepare("
            SELECT * FROM emploi_du_temps
            WHERE professeur_id = :professeur_id
            AND jour = :jour
            AND (
                (:heure_debut >= heure_debut AND :heure_debut < heure_fin) OR
                (:heure_fin > heure_debut AND :heure_fin <= heure_fin)
            )
            AND id != :id
        ");
        $stmt->execute([
            'professeur_id' => $data['professeur_id'],
            'jour' => $data['jour'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'id' => $id_to_exclude
        ]);
        if ($stmt->fetch()) {
            $conflicts[] = "Le professeur est déjà occupé à ce créneau.";
        }

        // Check if the room is already booked at that time
        $stmt = $pdo->prepare("
            SELECT * FROM emploi_du_temps
            WHERE salle_id = :salle_id
            AND jour = :jour
            AND (
                (:heure_debut >= heure_debut AND :heure_debut < heure_fin) OR
                (:heure_fin > heure_debut AND :heure_fin <= heure_fin)
            )
            AND id != :id
        ");
        $stmt->execute([
            'salle_id' => $data['salle_id'],
            'jour' => $data['jour'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'id' => $id_to_exclude
        ]);
        if ($stmt->fetch()) {
            $conflicts[] = "La salle est déjà occupée à ce créneau.";
        }

        // Check if the class is already booked at that time
        $stmt = $pdo->prepare("
            SELECT * FROM emploi_du_temps
            WHERE classe_id = :classe_id
            AND jour = :jour
            AND (
                (:heure_debut >= heure_debut AND :heure_debut < heure_fin) OR
                (:heure_fin > heure_debut AND :heure_fin <= heure_fin)
            )
            AND id != :id
        ");
        $stmt->execute([
            'classe_id' => $data['classe_id'],
            'jour' => $data['jour'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'id' => $id_to_exclude
        ]);
        if ($stmt->fetch()) {
            $conflicts[] = "La classe a déjà un cours à ce créneau.";
        }

        return $conflicts;
    }
}
