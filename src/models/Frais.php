<?php

require_once __DIR__ . '/../config/database.php';

class Frais {

    /**
     * Create or update a fee structure.
     * @param array $data
     * @return bool
     */
    public static function save($data) {
        $db = Database::getInstance();

        // Check if a fee structure already exists for this combination
        $sql_find = "SELECT id_frais FROM frais
                     WHERE lycee_id = :lycee_id AND niveau = :niveau AND serie = :serie AND annee_academique_id = :annee_academique_id";
        $stmt_find = $db->prepare($sql_find);
        $stmt_find->execute([
            'lycee_id' => $data['lycee_id'],
            'niveau' => $data['niveau'],
            'serie' => $data['serie'],
            'annee_academique_id' => $data['annee_academique_id']
        ]);
        $existing = $stmt_find->fetch();

        if ($existing) {
            // Update
            $sql = "UPDATE frais SET frais_inscription = :frais_inscription, frais_mensuel = :frais_mensuel, autres_frais = :autres_frais
                    WHERE id_frais = :id_frais";
            $params = [
                'frais_inscription' => $data['frais_inscription'],
                'frais_mensuel' => $data['frais_mensuel'],
                'autres_frais' => $data['autres_frais'] ? json_encode($data['autres_frais']) : null,
                'id_frais' => $existing['id_frais']
            ];
        } else {
            // Insert
            $sql = "INSERT INTO frais (lycee_id, niveau, serie, annee_academique_id, frais_inscription, frais_mensuel, autres_frais)
                    VALUES (:lycee_id, :niveau, :serie, :annee_academique_id, :frais_inscription, :frais_mensuel, :autres_frais)";
            $params = [
                 'lycee_id' => $data['lycee_id'],
                 'niveau' => $data['niveau'],
                 'serie' => $data['serie'],
                 'annee_academique_id' => $data['annee_academique_id'],
                 'frais_inscription' => $data['frais_inscription'],
                 'frais_mensuel' => $data['frais_mensuel'],
                 'autres_frais' => $data['autres_frais'] ? json_encode($data['autres_frais']) : null
            ];
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Find all fee structures for a given lycee.
     * @param int $lycee_id
     * @return array
     */
    public static function findByLyceeId($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM frais WHERE lycee_id = :lycee_id ORDER BY niveau, serie");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the applicable fees for a specific class in a specific year.
     * @param int $classe_id
     * @param int $annee_academique_id
     * @return array|false
     */
    public static function getForClasse($classe_id, $annee_academique_id) {
        $db = Database::getInstance();
        $sql = "SELECT f.* FROM frais f
                JOIN classes c ON f.lycee_id = c.lycee_id AND f.niveau = c.niveau AND f.serie = c.serie
                WHERE c.id_classe = :classe_id AND f.annee_academique_id = :annee_academique_id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'classe_id' => $classe_id,
            'annee_academique_id' => $annee_academique_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>