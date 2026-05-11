<?php

require_once __DIR__ . '/../config/database.php';

class Inscription {

    /**
     * Trouve une inscription par l'ID de l'étude.
     */
    public static function findByEtudeId($etudeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM inscriptions WHERE etude_id = :etude_id");
        $stmt->execute(['etude_id' => $etudeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve une inscription par l'ID de l'élève et l'année académique.
     */
    public static function findByEleveAndAnnee($eleveId, $anneeId, $lyceeId = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM inscriptions WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id";
        $params = ['eleve_id' => $eleveId, 'annee_id' => $anneeId];
        if ($lyceeId) {
            $sql .= " AND lycee_id = :lycee_id";
            $params['lycee_id'] = $lyceeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Liste les inscriptions d'un élève.
     */
    public static function findByEleveId($eleveId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT i.*, aa.libelle as annee_academique, c.niveau as nom_classe
            FROM inscriptions i
            JOIN annees_academiques aa ON i.annee_academique_id = aa.id
            JOIN classes c ON i.classe_id = c.id_classe
            WHERE i.eleve_id = :eleve_id
            ORDER BY aa.date_debut DESC
        ");
        $stmt->execute(['eleve_id' => $eleveId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée ou met à jour une inscription.
     */
    public static function save($data) {
        $db = Database::getInstance();

        if (isset($data['id_inscription']) && !empty($data['id_inscription'])) {
            // Mise à jour
            $stmt = $db->prepare(
                "UPDATE inscriptions SET
                    etude_id = :etude_id,
                    montant_verse = :montant_verse,
                    reste_a_payer = :reste_a_payer,
                    details_frais = :details_frais,
                    user_id = :user_id
                WHERE id_inscription = :id_inscription"
            );
            $stmt->execute([
                'etude_id' => $data['etude_id'] ?? null,
                'montant_verse' => $data['montant_verse'],
                'reste_a_payer' => $data['reste_a_payer'],
                'details_frais' => $data['details_frais'],
                'user_id' => $data['user_id'],
                'id_inscription' => $data['id_inscription']
            ]);
        } else {
            // Création
            $stmt = $db->prepare(
                "INSERT INTO inscriptions (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, details_frais, user_id)
                VALUES (:etude_id, :eleve_id, :classe_id, :lycee_id, :annee_academique_id, :montant_total, :montant_verse, :reste_a_payer, :details_frais, :user_id)"
            );
            $stmt->execute([
                'etude_id' => $data['etude_id'] ?? null,
                'eleve_id' => $data['eleve_id'],
                'classe_id' => $data['classe_id'],
                'lycee_id' => $data['lycee_id'],
                'annee_academique_id' => $data['annee_academique_id'],
                'montant_total' => $data['montant_total'],
                'montant_verse' => $data['montant_verse'],
                'reste_a_payer' => $data['reste_a_payer'],
                'details_frais' => $data['details_frais'],
                'user_id' => $data['user_id']
            ]);
            return $db->lastInsertId();
        }
        return true;
    }
}
