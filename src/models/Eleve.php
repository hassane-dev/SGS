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
        $sql = "SELECT e.*, GROUP_CONCAT(c.nom_classe SEPARATOR ', ') as classes
                FROM eleves e
                LEFT JOIN etudes et ON e.id_eleve = et.eleve_id
                LEFT JOIN classes c ON et.classe_id = c.id_classe";

        $params = [];
        if ($lycee_id !== null) {
            $sql .= " WHERE e.lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }

        $sql .= " GROUP BY e.id_eleve ORDER BY e.nom, e.prenom ASC";

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

        // Base fields
        $fields = ['lycee_id', 'nom', 'prenom', 'date_naissance', 'lieu_naissance', 'nationalite', 'sexe', 'quartier', 'tel_parent', 'nom_pere', 'nom_mere', 'profession_pere', 'profession_mere', 'email', 'telephone', 'statut'];

        $params = [];
        foreach ($fields as $field) {
            $params[$field] = $data[$field] ?? null;
        }

        // Set default status for new students
        if (!$isUpdate) {
            $params['statut'] = 'en_attente';
        }

        // Handle photo upload only if a new photo is provided
        if (!empty($data['photo'])) {
            $fields[] = 'photo';
            $params['photo'] = $data['photo'];
        }

        if ($isUpdate) {
            $setClauses = [];
            foreach ($fields as $field) {
                $setClauses[] = "`$field` = :$field";
            }
            $sql = "UPDATE eleves SET " . implode(', ', $setClauses) . " WHERE id_eleve = :id_eleve";
            $params['id_eleve'] = $data['id_eleve'];
        } else {
            $columns = implode(', ', array_map(fn($f) => "`$f`", $fields));
            $placeholders = ':' . implode(', :', $fields);
            $sql = "INSERT INTO eleves ($columns) VALUES ($placeholders)";
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function findByStatus($statut, $lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM eleves WHERE statut = :statut AND lycee_id = :lycee_id ORDER BY nom, prenom ASC");
        $stmt->execute(['statut' => $statut, 'lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
