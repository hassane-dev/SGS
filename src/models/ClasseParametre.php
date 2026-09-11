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
     * Checks if a user is designated as the Main Teacher (Professeur Principal) for a given class and academic year.
     * @param int $user_id
     * @param int $classe_id
     * @param int $annee_id
     * @return bool
     */
    public static function isProfesseurPrincipal($user_id, $classe_id, $annee_id) {
        if (!$user_id || !$classe_id || !$annee_id) {
            return false;
        }
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM classe_parametres
                 WHERE classe_id = :classe_id
                   AND annee_academique_id = :annee_id
                   AND professeur_principal_id = :user_id"
            );
            $stmt->execute([
                'classe_id' => (int)$classe_id,
                'annee_id' => (int)$annee_id,
                'user_id' => (int)$user_id
            ]);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log("Error in ClasseParametre::isProfesseurPrincipal: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds all classes where a user is the Main Teacher for a specific academic year and school tenant.
     * @param int $user_id
     * @param int $annee_id
     * @param int $lycee_id
     * @return array
     */
    public static function findClassesByProfesseurPrincipal($user_id, $annee_id, $lycee_id) {
        if (!$user_id || !$annee_id || !$lycee_id) {
            return [];
        }
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT c.*, cp.professeur_principal_id
                 FROM classes c
                 JOIN classe_parametres cp ON c.id_classe = cp.classe_id
                 WHERE cp.professeur_principal_id = :user_id
                   AND cp.annee_academique_id = :annee_id
                   AND c.lycee_id = :lycee_id
                 ORDER BY c.niveau, c.serie, c.numero"
            );
            $stmt->execute([
                'user_id' => (int)$user_id,
                'annee_id' => (int)$annee_id,
                'lycee_id' => (int)$lycee_id
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ClasseParametre::findClassesByProfesseurPrincipal: " . $e->getMessage());
            return [];
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
