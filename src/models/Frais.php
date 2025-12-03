<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/Classe.php'; // Include Classe model for level sorting

class Frais {

    /**
     * Save a new fee configuration.
     * @param array $data
     * @return int|false The last inserted ID or false on failure.
     */
    public static function save($data) {
        // --- Validation ---
        if (empty($data['lycee_id']) || empty($data['annee_academique_id'])) {
            throw new InvalidArgumentException("Lycée and annee academique IDs are required.");
        }
        if (empty($data['frais_inscription']) || !is_numeric($data['frais_inscription'])) {
            throw new InvalidArgumentException("Les frais d'inscription sont requis et doivent être un nombre.");
        }
        if (empty($data['frais_mensuel']) || !is_numeric($data['frais_mensuel'])) {
            throw new InvalidArgumentException("La mensualité est requise et doit être un nombre.");
        }
        if (empty($data['type_config'])) {
             throw new InvalidArgumentException("Le type de configuration est requis.");
        }

        $db = Database::getInstance();

        // --- Prevent Duplicates ---
        $existing_check_sql = "SELECT id_frais FROM frais WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_academique_id";
        $check_params = [
            'lycee_id' => $data['lycee_id'],
            'annee_academique_id' => $data['annee_academique_id']
        ];

        if ($data['type_config'] === 'cycle') {
            $existing_check_sql .= " AND cycle = :cycle";
            $check_params['cycle'] = $data['cycle'];
        } else {
            $existing_check_sql .= " AND niveau_debut = :niveau_debut AND niveau_fin = :niveau_fin AND (serie = :serie OR (serie IS NULL AND :serie IS NULL))";
            $check_params['niveau_debut'] = $data['niveau_debut'];
            $check_params['niveau_fin'] = $data['niveau_fin'];
            $check_params['serie'] = !empty($data['serie']) ? $data['serie'] : null;
        }

        $stmt_check = $db->prepare($existing_check_sql);
        $stmt_check->execute($check_params);
        if ($stmt_check->fetch()) {
            throw new InvalidArgumentException("Une grille tarifaire identique ou conflictuelle existe déjà pour cette année académique.");
        }
        // --- End Prevent Duplicates ---
        $params = [
            ':lycee_id' => $data['lycee_id'],
            ':annee_academique_id' => $data['annee_academique_id'],
            ':frais_inscription' => $data['frais_inscription'],
            ':frais_mensuel' => $data['frais_mensuel'],
            ':autres_frais' => !empty($data['autres_frais']) ? json_encode($data['autres_frais']) : null,
            ':cycle' => null,
            ':niveau_debut' => null,
            ':niveau_fin' => null,
            ':serie' => null
        ];

        if ($data['type_config'] === 'cycle') {
            if (empty($data['cycle'])) {
                throw new InvalidArgumentException("Le champ Cycle est requis pour ce type de configuration.");
            }
            $params[':cycle'] = $data['cycle'];
        } elseif ($data['type_config'] === 'plage') {
            if (empty($data['niveau_debut']) || empty($data['niveau_fin'])) {
                throw new InvalidArgumentException("Niveau début et Niveau fin sont requis pour ce type de configuration.");
            }
            $params[':niveau_debut'] = $data['niveau_debut'];
            $params[':niveau_fin'] = $data['niveau_fin'];
            $params[':serie'] = !empty($data['serie']) ? $data['serie'] : null;
        } else {
            throw new InvalidArgumentException("Type de configuration non valide.");
        }

        // For now, we only handle creation (INSERT). Update logic can be added later.
        $sql = "INSERT INTO frais (lycee_id, annee_academique_id, frais_inscription, frais_mensuel, autres_frais, cycle, niveau_debut, niveau_fin, serie)
                VALUES (:lycee_id, :annee_academique_id, :frais_inscription, :frais_mensuel, :autres_frais, :cycle, :niveau_debut, :niveau_fin, :serie)";

        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            return $db->lastInsertId();
        }
        return false;
    }

    /**
     * Find all fee structures for a given lycee and active year.
     * @param int $lycee_id
     * @param int $annee_academique_id
     * @return array
     */
    public static function findByLyceeAndYear($lycee_id, $annee_academique_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM frais
                              WHERE lycee_id = :lycee_id
                              AND annee_academique_id = :annee_academique_id
                              ORDER BY cycle, niveau_debut");
        $stmt->execute([
            'lycee_id' => $lycee_id,
            'annee_academique_id' => $annee_academique_id
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the applicable fees for a specific class in a specific year.
     * @param int $classe_id
     * @param int $annee_academique_id
     * @return array|false
     */
    public static function findForClasse($classe, $annee_academique_id) {
        $db = Database::getInstance();

        // La classe contient déjà lycee_id, niveau, serie. Il faut juste le nom du cycle.
        $cycle = Cycle::findById($classe['cycle_id']);
        $nom_cycle = $cycle['nom_cycle'];

        $all_frais_sql = "SELECT * FROM frais
                          WHERE lycee_id = :lycee_id
                          AND annee_academique_id = :annee_academique_id";
        $frais_stmt = $db->prepare($all_frais_sql);
        $frais_stmt->execute([
            'lycee_id' => $classe['lycee_id'],
            'annee_academique_id' => $annee_academique_id
        ]);
        $all_frais = $frais_stmt->fetchAll(PDO::FETCH_ASSOC);

        $levelOrderMap = Classe::getLevelOrderMap();
        $class_level_order = $levelOrderMap[$classe['niveau']] ?? 99;

        foreach ($all_frais as $frais) {
            // Priorité 1: Correspondance par plage de niveau ET série
            if (!empty($frais['niveau_debut']) && !empty($frais['serie']) && $frais['serie'] === $classe['serie']) {
                 $start_level_order = $levelOrderMap[$frais['niveau_debut']] ?? 0;
                 $end_level_order = $levelOrderMap[$frais['niveau_fin']] ?? 100;
                 if ($class_level_order >= $start_level_order && $class_level_order <= $end_level_order) {
                     return $frais;
                 }
            }
            // Priorité 2: Correspondance par plage de niveau SANS série
            if (!empty($frais['niveau_debut']) && empty($frais['serie'])) {
                 $start_level_order = $levelOrderMap[$frais['niveau_debut']] ?? 0;
                 $end_level_order = $levelOrderMap[$frais['niveau_fin']] ?? 100;
                 if ($class_level_order >= $start_level_order && $class_level_order <= $end_level_order) {
                     return $frais;
                 }
            }
            // Priorité 3: Correspondance par cycle
            if (!empty($frais['cycle']) && $frais['cycle'] === $nom_cycle) {
                return $frais;
            }
        }

        return false; // Aucun frais applicable trouvé
    }
}
?>