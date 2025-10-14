<?php

require_once __DIR__ . '/../config/database.php';

class Inscription {

    /**
     * Create a new enrollment record along with the initial payment.
     * @param array $data
     * @return bool
     */
    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO inscriptions (eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, details_frais, user_id)
                VALUES (:eleve_id, :classe_id, :lycee_id, :annee_academique_id, :montant_total, :montant_verse, :reste_a_payer, :details_frais, :user_id)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'classe_id' => $data['classe_id'],
            'lycee_id' => $data['lycee_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'montant_total' => $data['montant_total'],
            'montant_verse' => $data['montant_verse'],
            'reste_a_payer' => $data['reste_a_payer'],
            'details_frais' => $data['details_frais'] ? json_encode($data['details_frais']) : null,
            'user_id' => $data['user_id']
        ]);
    }

    /**
     * Find all inscriptions for a given student.
     * @param int $eleve_id
     * @return array
     */
    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT i.*, c.nom_classe, a.libelle as annee_academique
            FROM inscriptions i
            JOIN classes c ON i.classe_id = c.id_classe
            JOIN annees_academiques a ON i.annee_academique_id = a.id
            WHERE i.eleve_id = :eleve_id
            ORDER BY i.date_inscription DESC
        ");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find inscriptions by lycee.
     * @param int $lycee_id
     * @return array
     */
    public static function findByLyceeId($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT i.*, e.nom, e.prenom, c.nom_classe
            FROM inscriptions i
            JOIN eleves e ON i.eleve_id = e.id_eleve
            JOIN classes c ON i.classe_id = c.id_classe
            WHERE i.lycee_id = :lycee_id
            ORDER BY i.date_inscription DESC
        ");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>