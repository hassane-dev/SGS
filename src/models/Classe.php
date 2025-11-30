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
                    JOIN param_lycee l ON c.lycee_id = l.id";

            if ($lycee_id !== null) {
                $sql .= " WHERE c.lycee_id = :lycee_id";
            }

            $sql .= " ORDER BY l.nom_lycee, cy.nom_cycle, c.niveau ASC, c.serie ASC, c.numero ASC";

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

    public static function getFormattedName($classe) {
        $name = $classe['niveau'];
        if (!empty($classe['serie'])) {
            $name .= ' ' . $classe['serie'];
        }
        if (!empty($classe['numero'])) {
            $name .= ' ' . $classe['numero'];
        }
        return $name;
    }

    public static function save($data) {
        // --- Validation ---
        if (empty($data['niveau'])) {
            throw new InvalidArgumentException("Le niveau de la classe est obligatoire.");
        }
        if (empty($data['cycle_id'])) {
            throw new InvalidArgumentException("Le cycle est obligatoire.");
        }
        if (empty($data['lycee_id'])) {
            throw new InvalidArgumentException("L'identifiant de l'école est obligatoire.");
        }
        // --- End Validation ---

        $isUpdate = !empty($data['id_classe']);

        $sql = $isUpdate
            ? "UPDATE classes SET niveau = :niveau, serie = :serie, numero = :numero, categorie = :categorie, cycle_id = :cycle_id, lycee_id = :lycee_id WHERE id_classe = :id_classe"
            : "INSERT INTO classes (niveau, serie, numero, categorie, cycle_id, lycee_id) VALUES (:niveau, :serie, :numero, :categorie, :cycle_id, :lycee_id)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'niveau' => $data['niveau'],
                'serie' => $data['serie'] ?? null,
                'numero' => $data['numero'] ?? null,
                'categorie' => $data['categorie'] ?? null,
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

    public static function findDistinctNiveauxByCycle($cycle_id, $lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT DISTINCT niveau FROM classes WHERE cycle_id = :cycle_id AND lycee_id = :lycee_id ORDER BY niveau ASC");
        $stmt->execute(['cycle_id' => $cycle_id, 'lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findDistinctSeriesByNiveau($niveau, $lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT DISTINCT serie FROM classes WHERE niveau = :niveau AND lycee_id = :lycee_id AND serie IS NOT NULL ORDER BY serie ASC");
        $stmt->execute(['niveau' => $niveau, 'lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findAvailableNumeros($niveau, $serie, $lycee_id) {
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT numero FROM classes WHERE niveau = :niveau AND lycee_id = :lycee_id";
        $params = ['niveau' => $niveau, 'lycee_id' => $lycee_id];

        if ($serie) {
            $sql .= " AND serie = :serie";
            $params['serie'] = $serie;
        } else {
            $sql .= " AND (serie IS NULL OR serie = '')";
        }

        $sql .= " ORDER BY numero ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findIdByDetails($lycee_id, $niveau, $serie, $numero) {
        $db = Database::getInstance();
        $sql = "SELECT id_classe FROM classes WHERE lycee_id = :lycee_id AND niveau = :niveau AND numero = :numero";
        $params = ['lycee_id' => $lycee_id, 'niveau' => $niveau, 'numero' => $numero];

        if ($serie) {
            $sql .= " AND serie = :serie";
            $params['serie'] = $serie;
        } else {
            $sql .= " AND (serie IS NULL OR serie = '')";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id_classe'] : null;
    }

    public static function incrementerEffectifActuel($classe_id, $annee_academique_id) {
        if (empty($classe_id) || empty($annee_academique_id)) {
            throw new InvalidArgumentException("L'ID de la classe et l'ID de l'année académique sont requis.");
        }

        $sql = "INSERT INTO classe_parametres (classe_id, annee_academique_id, effectif_actuel)
                VALUES (:classe_id, :annee_academique_id, 1)
                ON DUPLICATE KEY UPDATE effectif_actuel = effectif_actuel + 1";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'classe_id' => $classe_id,
                'annee_academique_id' => $annee_academique_id
            ]);
        } catch (PDOException $e) {
            error_log("Database error in Classe::incrementerEffectifActuel: " . $e->getMessage());
            // Re-throw the exception to be handled by the controller's transaction management
            throw $e;
        }
    }
}
?>
