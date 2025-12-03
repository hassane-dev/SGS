<?php

require_once __DIR__ . '/../config/database.php';

class Inscription {

    /**
     * Trouve une inscription par l'ID de l'élève et l'année académique.
     */
    public static function findByEleveAndAnnee($eleveId, $anneeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM inscriptions WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id");
        $stmt->execute(['eleve_id' => $eleveId, 'annee_id' => $anneeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
                    montant_verse = :montant_verse,
                    reste_a_payer = :reste_a_payer,
                    details_frais = :details_frais,
                    user_id = :user_id
                WHERE id_inscription = :id_inscription"
            );
            $stmt->execute([
                'montant_verse' => $data['montant_verse'],
                'reste_a_payer' => $data['reste_a_payer'],
                'details_frais' => $data['details_frais'],
                'user_id' => $data['user_id'],
                'id_inscription' => $data['id_inscription']
            ]);
        } else {
            // Création
            $stmt = $db->prepare(
                "INSERT INTO inscriptions (eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, details_frais, user_id)
                VALUES (:eleve_id, :classe_id, :lycee_id, :annee_academique_id, :montant_total, :montant_verse, :reste_a_payer, :details_frais, :user_id)"
            );
            $stmt->execute([
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
