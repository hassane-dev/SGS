<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ClasseParametre.php';

class Classe {

    /**
     * Find all classes, joining with cycles and lycees tables.
     * If lycee_id is provided, it filters by that lycee.
     * @param int|null $lycee_id The ID of the lycee to filter by.
     * @return array An array of classe objects.
     */
    public static function findAll($lycee_id = null, ?array $allowedClassIds = null) {
        if ($allowedClassIds === []) {
            return [];
        }
        try {
            $db = Database::getInstance();
            $sql = "SELECT c.*, cy.nom_cycle, l.nom_lycee
                    FROM classes c
                    JOIN cycles cy ON c.cycle_id = cy.id_cycle
                    JOIN param_lycee l ON c.lycee_id = l.id";

            $where = [];
            $params = [];

            if ($lycee_id !== null) {
                $where[] = "c.lycee_id = :lycee_id";
                $params['lycee_id'] = $lycee_id;
            }

            if ($allowedClassIds !== null) {
                $inClause = implode(',', array_map('intval', $allowedClassIds));
                $where[] = "c.id_classe IN ($inClause)";
            }

            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }

            $sql .= " ORDER BY l.nom_lycee, cy.nom_cycle, c.niveau ASC, c.serie ASC, c.numero ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

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

            $res = $stmt->execute($params);

            if ($res && !empty($data['lycee_id'])) {
                try {
                    require_once __DIR__ . '/../services/SurveillantAssignmentService.php';
                    SurveillantAssignmentService::bumpConfigVersion((int)$data['lycee_id']);
                } catch (Throwable $e) {}
            }

            return $res;
        } catch (PDOException $e) {
            error_log("Error in Classe::save: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id, $lycee_id = null) {
        $db = Database::getInstance();
        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            require_once __DIR__ . '/../services/SurveillantAssignmentService.php';
            SurveillantAssignmentService::archiveClassAssignmentsAndGroups((int)$id);

            $sql = "DELETE FROM classes WHERE id_classe = :id";
            $params = ['id' => $id];

            if ($lycee_id !== null) {
                $sql .= " AND lycee_id = :lycee_id";
                $params['lycee_id'] = $lycee_id;
            }

            $stmt = $db->prepare($sql);
            $res = $stmt->execute($params);

            if ($lycee_id) {
                SurveillantAssignmentService::bumpConfigVersion((int)$lycee_id, $db);
            }

            if (!$inTx) {
                $db->commit();
            }
            return $res;
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

    public static function findDistinctNiveauxByCycle($cycle_id, $lycee_id, ?array $allowedClassIds = null) {
        if ($allowedClassIds === []) {
            return [];
        }
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT niveau FROM classes WHERE cycle_id = :cycle_id AND lycee_id = :lycee_id";
        $params = ['cycle_id' => $cycle_id, 'lycee_id' => $lycee_id];

        if ($allowedClassIds !== null) {
            $inClause = implode(',', array_map('intval', $allowedClassIds));
            $sql .= " AND id_classe IN ($inClause)";
        }

        $sql .= " ORDER BY niveau ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findDistinctSeriesByNiveau($niveau, $lycee_id, ?int $cycle_id = null, ?array $allowedClassIds = null) {
        if ($allowedClassIds === []) {
            return [];
        }
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT serie FROM classes WHERE niveau = :niveau AND lycee_id = :lycee_id AND serie IS NOT NULL AND serie != ''";
        $params = ['niveau' => $niveau, 'lycee_id' => $lycee_id];

        if (!empty($cycle_id)) {
            $sql .= " AND cycle_id = :cycle_id";
            $params['cycle_id'] = $cycle_id;
        }

        if ($allowedClassIds !== null) {
            $inClause = implode(',', array_map('intval', $allowedClassIds));
            $sql .= " AND id_classe IN ($inClause)";
        }

        $sql .= " ORDER BY serie ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findAvailableNumeros($niveau, $serie, $lycee_id, ?int $cycle_id = null, ?array $allowedClassIds = null) {
        if ($allowedClassIds === []) {
            return [];
        }
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT numero FROM classes WHERE niveau = :niveau AND lycee_id = :lycee_id";
        $params = ['niveau' => $niveau, 'lycee_id' => $lycee_id];

        if (!empty($cycle_id)) {
            $sql .= " AND cycle_id = :cycle_id";
            $params['cycle_id'] = $cycle_id;
        }

        if ($serie === 'none' || $serie === 'empty' || $serie === 'sans_serie') {
            $sql .= " AND (serie IS NULL OR serie = '')";
        } elseif (!empty($serie)) {
            $sql .= " AND serie = :serie";
            $params['serie'] = $serie;
        }

        if ($allowedClassIds !== null) {
            $inClause = implode(',', array_map('intval', $allowedClassIds));
            $sql .= " AND id_classe IN ($inClause)";
        }

        $sql .= " ORDER BY numero ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findIdByDetails($lycee_id, $niveau, $serie, $numero, ?int $cycle_id = null, ?array $allowedClassIds = null) {
        if ($allowedClassIds === []) {
            return null;
        }
        $db = Database::getInstance();
        $sql = "SELECT id_classe FROM classes WHERE lycee_id = :lycee_id AND niveau = :niveau AND numero = :numero";
        $params = ['lycee_id' => $lycee_id, 'niveau' => $niveau, 'numero' => $numero];

        if (!empty($cycle_id)) {
            $sql .= " AND cycle_id = :cycle_id";
            $params['cycle_id'] = $cycle_id;
        }

        if ($serie === 'none' || $serie === 'empty' || $serie === 'sans_serie') {
            $sql .= " AND (serie IS NULL OR serie = '')";
        } elseif (!empty($serie)) {
            $sql .= " AND serie = :serie";
            $params['serie'] = $serie;
        }

        if ($allowedClassIds !== null) {
            $inClause = implode(',', array_map('intval', $allowedClassIds));
            $sql .= " AND id_classe IN ($inClause)";
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

        try {
            $db = Database::getInstance();
            $existing = ClasseParametre::findByClasseAndAnnee($classe_id, $annee_academique_id);
            if ($existing) {
                $paramId = $existing['id'] ?? $existing['id_parametre'] ?? null;
                $stmt = $db->prepare("UPDATE classe_parametres SET effectif_actuel = effectif_actuel + 1 WHERE id = :id");
                $stmt->execute(['id' => $paramId]);
            } else {
                $stmt = $db->prepare("INSERT INTO classe_parametres (classe_id, annee_academique_id, effectif_actuel) VALUES (:classe_id, :annee_academique_id, 1)");
                $stmt->execute(['classe_id' => $classe_id, 'annee_academique_id' => $annee_academique_id]);
            }
        } catch (PDOException $e) {
            error_log("Database error in Classe::incrementerEffectifActuel: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getCanonicalLevelMap() {
        return [
            1 => '6e',
            2 => '5e',
            3 => '4e',
            4 => '3e',
            5 => '2nde',
            6 => '1ère',
            7 => 'Terminale'
        ];
    }

    public static function getLevelOrderMap() {
        // Defines the logical order of school levels.
        return [
            '6ème' => 1, '6eme' => 1, '6e' => 1,
            '5ème' => 2, '5eme' => 2, '5e' => 2,
            '4ème' => 3, '4eme' => 3, '4e' => 3,
            '3ème' => 4, '3eme' => 4, '3e' => 4,
            'Seconde' => 5, '2nde' => 5, '2nd' => 5,
            'Première' => 6, '1ère' => 6, '1ere' => 6,
            'Terminale' => 7, 'Tle' => 7,
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7
        ];
    }

    public static function resolveLevelOrder(?string $levelInput): ?int {
        if ($levelInput === null) {
            return null;
        }
        $trimmed = trim($levelInput);
        if ($trimmed === '') {
            return null;
        }

        $orderMap = self::getLevelOrderMap();
        if (isset($orderMap[$trimmed])) {
            return $orderMap[$trimmed];
        }

        $normalize = function ($str) {
            $lower = mb_strtolower((string)$str, 'UTF-8');
            return strtr($lower, [
                'à' => 'a', 'â' => 'a', 'ä' => 'a',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'î' => 'i', 'ï' => 'i',
                'ô' => 'o', 'ö' => 'o',
                'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                'ç' => 'c'
            ]);
        };

        $normalizedInput = $normalize($trimmed);
        foreach ($orderMap as $key => $order) {
            if ($normalize($key) === $normalizedInput) {
                return $order;
            }
        }

        return null;
    }

    public static function getDistinctNiveaux($lycee_id, ?array $allowedClassIds = null) {
        if ($allowedClassIds === []) {
            return [];
        }
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT niveau FROM classes WHERE lycee_id = :lycee_id";
        $params = ['lycee_id' => $lycee_id];

        if ($allowedClassIds !== null) {
            $inClause = implode(',', array_map('intval', $allowedClassIds));
            $sql .= " AND id_classe IN ($inClause)";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $niveaux = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $orderMap = self::getLevelOrderMap();

        usort($niveaux, function ($a, $b) use ($orderMap) {
            $orderA = $orderMap[$a] ?? 99;
            $orderB = $orderMap[$b] ?? 99;
            return $orderA <=> $orderB;
        });

        return $niveaux;
    }

    public static function getDistinctSeries($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT DISTINCT serie FROM classes WHERE lycee_id = :lycee_id AND serie IS NOT NULL AND serie != '' ORDER BY serie ASC");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
