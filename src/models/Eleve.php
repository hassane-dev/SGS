<?php

require_once __DIR__ . '/../config/database.php';

class Eleve {

    /**
     * Find all active students, with optional filtering.
     * @param array $filters
     * @return array
     */
    public static function findAll($filters = []) {
        $db = Database::getInstance();
        $sql = "SELECT e.*, c.niveau, c.serie, c.numero, cy.nom_cycle, l.nom_lycee
                FROM eleves e
                LEFT JOIN etudes et ON e.id_eleve = et.eleve_id
                    AND et.annee_academique_id = (SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1)
                LEFT JOIN classes c ON et.classe_id = c.id_classe
                LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
                LEFT JOIN param_lycee l ON e.lycee_id = l.id
                WHERE (e.statut = 'actif' OR e.statut = 'en_attente')";

        $params = [];

        if (!empty($filters['lycee_id'])) {
            $sql .= " AND e.lycee_id = :lycee_id";
            $params['lycee_id'] = $filters['lycee_id'];
        }

        if (!empty($filters['cycle_id'])) {
            $sql .= " AND c.cycle_id = :cycle_id";
            $params['cycle_id'] = $filters['cycle_id'];
        }

        if (!empty($filters['niveau'])) {
            $sql .= " AND c.niveau = :niveau";
            $params['niveau'] = $filters['niveau'];
        }

        if (!empty($filters['serie'])) {
            $sql .= " AND c.serie = :serie";
            $params['serie'] = $filters['serie'];
        }

        if (!empty($filters['numero'])) {
            $sql .= " AND c.numero = :numero";
            $params['numero'] = $filters['numero'];
        }

        $sql .= " ORDER BY l.nom_lycee ASC, cy.id_cycle ASC, c.niveau ASC, c.serie ASC, c.numero ASC, e.nom ASC, e.prenom ASC";

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

        if ($isUpdate) {
            $currentData = self::findById($data['id_eleve']);
            if (!$currentData) {
                throw new InvalidArgumentException("Élève non trouvé.");
            }
        }

        $params = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $params[$field] = $data[$field];
            } elseif ($isUpdate) {
                // Keep current value if not provided during update
                $params[$field] = $currentData[$field];
            } else {
                $params[$field] = null;
            }
        }

        if (!$isUpdate && empty($params['statut'])) {
            $params['statut'] = 'en_attente';
        }

        if (!empty($data['photo'])) {
            $fields[] = 'photo';
            $params['photo'] = $data['photo'];
        } elseif ($isUpdate) {
            // Keep current photo if not provided
            $fields[] = 'photo';
            $params['photo'] = $currentData['photo'];
        }

        if ($isUpdate) {
            $setClauses = array_map(fn($f) => "`$f` = :$f", $fields);
            $sql = "UPDATE eleves SET " . implode(', ', $setClauses) . " WHERE id_eleve = :id_eleve";
            $params['id_eleve'] = $data['id_eleve'];
        } else {
            $columns = implode(', ', array_map(fn($f) => "`$f`", $fields));
            $placeholders = ':' . implode(', :', $fields);
            $sql = "INSERT INTO eleves ($columns) VALUES ($placeholders)";
            $params = array_intersect_key($params, array_flip($fields));
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

        $sql = "SELECT e.*, c.niveau
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                JOIN classes c ON et.classe_id = c.id_classe
                JOIN annees_academiques aa ON et.annee_academique_id = aa.id
                WHERE et.classe_id IN ($placeholders)
                AND aa.est_active = 1
                AND e.statut = 'actif'
                ORDER BY c.niveau, e.nom, e.prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($class_ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Met à jour le statut d'un élève.
     */
    public static function updateStatus($id, $status) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE eleves SET statut = :statut WHERE id_eleve = :id");
        return $stmt->execute(['id' => $id, 'statut' => $status]);
    }

    public static function findByClass($classe_id) {
        $db = Database::getInstance();
        $sql = "SELECT e.*
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                WHERE et.classe_id = :classe_id AND et.actif = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['classe_id' => $classe_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
