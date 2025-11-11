<?php

require_once __DIR__ . '/../config/database.php';

class ClasseParametre {

    /**
     * Find parameters for a specific class and academic year.
     * @param int $classe_id
     * @param int $annee_id
     * @return array|false
     */
    public static function findByClasseAndAnnee($classe_id, $annee_id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM classe_parametres WHERE classe_id = :classe_id AND annee_academique_id = :annee_id");
            $stmt->execute(['classe_id' => $classe_id, 'annee_id' => $annee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ClasseParametre::findByClasseAndAnnee: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save or update class parameters for a given year.
     * @param array $data
     * @return bool
     */
    public static function save($data) {
        // Basic validation
        if (empty($data['classe_id']) || empty($data['annee_academique_id'])) {
            throw new InvalidArgumentException("Class ID and Academic Year ID are required.");
        }

        $existing = self::findByClasseAndAnnee($data['classe_id'], $data['annee_academique_id']);
        $isUpdate = !empty($existing['id']);

        $sql = $isUpdate
            ? "UPDATE classe_parametres SET nombre_places = :nombre_places, professeur_principal_id = :professeur_principal_id, commentaire = :commentaire WHERE id = :id"
            : "INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, professeur_principal_id, commentaire) VALUES (:classe_id, :annee_academique_id, :nombre_places, :professeur_principal_id, :commentaire)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'nombre_places' => $data['nombre_places'] ?? null,
                'professeur_principal_id' => $data['professeur_principal_id'] ?? null,
                'commentaire' => $data['commentaire'] ?? null,
            ];

            if ($isUpdate) {
                $params['id'] = $existing['id'];
            } else {
                $params['classe_id'] = $data['classe_id'];
                $params['annee_academique_id'] = $data['annee_academique_id'];
            }

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in ClasseParametre::save: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the student count for a class in a specific year.
     * @param int $classe_id
     * @param int $annee_id
     * @return bool
     */
    public static function updateEffectif($classe_id, $annee_id) {
        try {
            $db = Database::getInstance();
            // First, count the number of active students in the 'etudes' table
            $count_stmt = $db->prepare(
                "SELECT COUNT(*) as effectif FROM etudes
                 WHERE classe_id = :classe_id AND annee_academique_id = :annee_id AND actif = 1"
            );
            $count_stmt->execute(['classe_id' => $classe_id, 'annee_id' => $annee_id]);
            $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $effectif = $result['effectif'] ?? 0;

            // Now, update the 'classe_parametres' table
            $update_stmt = $db->prepare(
                "UPDATE classe_parametres SET effectif_actuel = :effectif
                 WHERE classe_id = :classe_id AND annee_academique_id = :annee_id"
            );
            return $update_stmt->execute(['effectif' => $effectif, 'classe_id' => $classe_id, 'annee_id' => $annee_id]);
        } catch (PDOException $e) {
            error_log("Error in ClasseParametre::updateEffectif: " . $e->getMessage());
            return false;
        }
    }
}
?>
