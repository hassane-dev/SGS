<?php

require_once __DIR__ . '/../config/database.php';

class Eleve {

    /**
     * Find all students, optionally filtered by lycee_id.
     * @param int|null $lycee_id
     * @return array
     */
    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM eleves";
        $params = [];

        if ($lycee_id !== null) {
            $sql .= " WHERE lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }
        $sql .= " ORDER BY nom, prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM eleves WHERE id_eleve = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save a student's data (create or update).
     * @param array $data
     * @return bool
     */
    public static function save($data) {
        $db = Database::getInstance();
        $isUpdate = !empty($data['id_eleve']);

        // List of all fields managed by this save method
        $params = [
            'lycee_id' => $data['lycee_id'],
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naissance' => $data['lieu_naissance'] ?? null,
            'nationalite' => $data['nationalite'] ?? null,
            'sexe' => $data['sexe'] ?? null,
            'quartier' => $data['quartier'] ?? null,
            'tel_parent' => $data['tel_parent'] ?? null,
            'nom_pere' => $data['nom_pere'] ?? null,
            'nom_mere' => $data['nom_mere'] ?? null,
            'profession_pere' => $data['profession_pere'] ?? null,
            'profession_mere' => $data['profession_mere'] ?? null,
            'email' => $data['email'] ?? null,
            'telephone' => $data['telephone'] ?? null,
        ];

        // Handle photo separately to avoid overwriting it with null on update
        if (!empty($data['photo'])) {
            $params['photo'] = $data['photo'];
        }

        if ($isUpdate) {
            $params['id_eleve'] = $data['id_eleve'];
            $setClauses = [];
            foreach ($params as $key => $value) {
                // Don't include the primary key in the SET clause
                if ($key !== 'id_eleve') {
                    $setClauses[] = "$key = :$key";
                }
            }
            $sql = "UPDATE eleves SET " . implode(', ', $setClauses) . " WHERE id_eleve = :id_eleve";
        } else {
            // For an insert, ensure photo is part of the params array, even if null
            if (!isset($params['photo'])) {
                $params['photo'] = null;
            }
            $columns = implode(', ', array_keys($params));
            $placeholders = ':' . implode(', :', array_keys($params));
            $sql = "INSERT INTO eleves ($columns) VALUES ($placeholders)";
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete($id) {
        // Before deleting, we might want to remove the photo file from the server
        $eleve = self::findById($id);
        if ($eleve && !empty($eleve['photo'])) {
            $photo_path = __DIR__ . '/../../public' . $eleve['photo'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM eleves WHERE id_eleve = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
