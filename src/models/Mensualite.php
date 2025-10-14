<?php

require_once __DIR__ . '/../config/database.php';

class Mensualite {

    /**
     * Create a new monthly payment record.
     * @param array $data
     * @return bool
     */
    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO mensualites (eleve_id, lycee_id, annee_academique_id, mois_ou_sequence, montant_verse, user_id)
                VALUES (:eleve_id, :lycee_id, :annee_academique_id, :mois_ou_sequence, :montant_verse, :user_id)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'lycee_id' => $data['lycee_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'mois_ou_sequence' => $data['mois_ou_sequence'],
            'montant_verse' => $data['montant_verse'],
            'user_id' => $data['user_id']
        ]);
    }

    /**
     * Find all monthly payments for a given student.
     * @param int $eleve_id
     * @return array
     */
    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, a.libelle as annee_academique
            FROM mensualites m
            JOIN annees_academiques a ON m.annee_academique_id = a.id
            WHERE m.eleve_id = :eleve_id
            ORDER BY m.date_paiement DESC
        ");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find monthly payments by lycee.
     * @param int $lycee_id
     * @return array
     */
    public static function findByLyceeId($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, e.nom, e.prenom
            FROM mensualites m
            JOIN eleves e ON m.eleve_id = e.id_eleve
            WHERE m.lycee_id = :lycee_id
            ORDER BY m.date_paiement DESC
        ");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>