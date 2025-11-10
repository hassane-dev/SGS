<?php

require_once __DIR__ . '/../config/database.php';

class Eleve {

    /**
     * Find all active students, optionally filtered by lycee_id.
     * @param int|null $lycee_id
     * @return array
     */
    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT e.*, GROUP_CONCAT(c.nom_classe SEPARATOR ', ') as classes
                FROM eleves e
                LEFT JOIN etudes et ON e.id_eleve = et.eleve_id
                LEFT JOIN classes c ON et.classe_id = c.id_classe
                WHERE (e.statut = 'actif' OR e.statut = 'en_attente')";

        $params = [];
        if ($lycee_id !== null) {
            $sql .= " AND e.lycee_id = :lycee_id";
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
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['lycee_id'])) {
            throw new InvalidArgumentException("Les informations de base (nom, prénom, lycée) sont obligatoires.");
        }

        $db = Database::getInstance();
        $isUpdate = !empty($data['id_eleve']);

        $fields = ['lycee_id', 'nom', 'prenom', 'date_naissance', 'lieu_naissance', 'nationalite', 'sexe', 'quartier', 'tel_parent', 'nom_pere', 'nom_mere', 'profession_pere', 'profession_mere', 'email', 'telephone', 'statut'];

        $params = [];
        foreach ($fields as $field) {
            $params[$field] = $data[$field] ?? null;
        }

        if (!$isUpdate) {
            $params['statut'] = 'en_attente';
        }

        if (!empty($data['photo'])) {
            $fields[] = 'photo';
            $params['photo'] = $data['photo'];
        }

        if ($isUpdate) {
            $setClauses = array_map(fn($f) => "`$f` = :$f", $fields);
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

    /**
     * Changes the status of a student (soft delete/archive).
     * @param int $id The student ID.
     * @param string $status The new status.
     * @return bool
     */
    public static function changeStatus($id, $status) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE eleves SET statut = :statut WHERE id_eleve = :id");
        return $stmt->execute(['id' => $id, 'statut' => $status]);
    }

    /**
     * Find all archived students.
     * @param int|null $lycee_id
     * @return array
     */
    public static function findAllArchived($lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM eleves WHERE statut NOT IN ('actif', 'en_attente')";

        $params = [];
        if ($lycee_id !== null) {
            $sql .= " AND lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }

        $sql .= " ORDER BY nom, prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find all students belonging to a specific list of class IDs for the active academic year.
     * @param array $class_ids
     * @return array
     */
    public static function findAllByClassIds(array $class_ids) {
        if (empty($class_ids)) {
            return [];
        }

        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($class_ids), '?'));

        $sql = "SELECT e.*, c.nom_classe
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                JOIN classes c ON et.classe_id = c.id_classe
                JOIN annees_academiques aa ON et.annee_academique_id = aa.id
                WHERE et.classe_id IN ($placeholders)
                AND aa.est_active = 1
                AND e.statut = 'actif'
                ORDER BY c.nom_classe, e.nom, e.prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($class_ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
