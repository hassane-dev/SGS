<?php

require_once __DIR__ . '/../config/database.php';

class Mensualite {

    /**
     * Trouve les paiements mensuels pour un élève et une année académique.
     */
    public static function findByEleveId($eleveId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, aa.libelle as annee_academique
            FROM mensualites m
            JOIN annees_academiques aa ON m.annee_academique_id = aa.id
            WHERE m.eleve_id = :eleve_id
            ORDER BY m.date_paiement DESC
        ");
        $stmt->execute(['eleve_id' => $eleveId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByEleveAndAnnee($eleveId, $anneeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT mois_ou_sequence, SUM(montant_verse) as total_verse
             FROM mensualites
             WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id
             GROUP BY mois_ou_sequence"
        );
        $stmt->execute(['eleve_id' => $eleveId, 'annee_id' => $anneeId]);

        // Retourne un tableau associatif [mois => total_verse]
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $payments = [];
        foreach ($result as $row) {
            $payments[$row['mois_ou_sequence']] = $row['total_verse'];
        }
        return $payments;
    }

    /**
     * Enregistre un paiement mensuel.
     * Crée une nouvelle ligne pour chaque mois.
     */
    public static function save($data) {
        $db = Database::getInstance();

        // La spécification exige une ligne par mois, même si le paiement est groupé.
        $stmt = $db->prepare(
            "INSERT INTO mensualites (eleve_id, classe_id, lycee_id, annee_academique_id, mois_ou_sequence, montant_verse, user_id)
             VALUES (:eleve_id, :classe_id, :lycee_id, :annee_academique_id, :mois_ou_sequence, :montant_verse, :user_id)"
        );

        $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'classe_id' => $data['classe_id'],
            'lycee_id' => $data['lycee_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'mois_ou_sequence' => $data['mois_ou_sequence'],
            'montant_verse' => $data['montant_verse'],
            'user_id' => $data['user_id']
        ]);

        return $db->lastInsertId();
    }
}
