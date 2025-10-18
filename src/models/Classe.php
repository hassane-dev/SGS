<?php

require_once __DIR__ . '/../config/database.php';

class Classe {

    /**
     * Find all classes, joining with cycles and lycees tables.
     * If lycee_id is provided, it filters by that lycee.
     * @param int|null $lycee_id The ID of the lycee to filter by.
     * @return array An array of classe objects.
     */
    public static function findAll($lycee_id = null) {
        try {
            $db = Database::getInstance();
            $sql = "SELECT c.*, cy.nom_cycle, l.nom_lycee
                    FROM classes c
                    JOIN cycles cy ON c.cycle_id = cy.id_cycle
                    JOIN lycees l ON c.lycee_id = l.id_lycee";

            if ($lycee_id !== null) {
                $sql .= " WHERE c.lycee_id = :lycee_id";
            }

            $sql .= " ORDER BY l.nom_lycee, cy.nom_cycle, c.nom_classe ASC";

            $stmt = $db->prepare($sql);
            if ($lycee_id !== null) {
                $stmt->execute(['lycee_id' => $lycee_id]);
            } else {
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Classe::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM classes WHERE id_classe = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Classe::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_classe']);

        $sql = $isUpdate
            ? "UPDATE classes SET nom_classe = :nom_classe, niveau = :niveau, serie = :serie, numero_classe = :numero_classe, cycle_id = :cycle_id, lycee_id = :lycee_id WHERE id_classe = :id_classe"
            : "INSERT INTO classes (nom_classe, niveau, serie, numero_classe, cycle_id, lycee_id) VALUES (:nom_classe, :niveau, :serie, :numero_classe, :cycle_id, :lycee_id)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'nom_classe' => $data['nom_classe'],
                'niveau' => $data['niveau'] ?? null,
                'serie' => $data['serie'] ?? null,
                'numero_classe' => $data['numero_classe'] ?? null,
                'cycle_id' => $data['cycle_id'],
                'lycee_id' => $data['lycee_id'],
            ];

            if ($isUpdate) {
                $params['id_classe'] = $data['id_classe'];
            }

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Classe::save: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id, $lycee_id = null) {
        try {
            $db = Database::getInstance();
            $sql = "DELETE FROM classes WHERE id_classe = :id";
            $params = ['id' => $id];

            if ($lycee_id !== null) {
                $sql .= " AND lycee_id = :lycee_id";
                $params['lycee_id'] = $lycee_id;
            }

            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Classe::delete: " . $e->getMessage());
            return false;
        }
    }

    public static function findMatiereDetails($classe_id, $matiere_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM classe_matieres WHERE classe_id = :classe_id AND matiere_id = :matiere_id");
        $stmt->execute(['classe_id' => $classe_id, 'matiere_id' => $matiere_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
