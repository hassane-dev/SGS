<?php

require_once __DIR__ . '/../config/database.php';

class Presence {

    public static function findByClassAndDate($classe_id, $date) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.*, e.nom, e.prenom
            FROM presences p
            JOIN eleves e ON p.eleve_id = e.id_eleve
            WHERE p.classe_id = :classe_id AND p.date_presence = :date
        ");
        $stmt->execute(['classe_id' => $classe_id, 'date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function saveAll($data) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO presences (eleve_id, classe_id, enseignant_id, annee_academique_id, lycee_id, date_presence, statut, commentaire)
                VALUES (:eleve_id, :classe_id, :enseignant_id, :annee_academique_id, :lycee_id, :date_presence, :statut, :commentaire)
                ON DUPLICATE KEY UPDATE
                    statut = VALUES(statut),
                    commentaire = VALUES(commentaire),
                    enseignant_id = VALUES(enseignant_id)
            ");

            foreach ($data['presences'] as $eleve_id => $presence_data) {
                $stmt->execute([
                    'eleve_id' => $eleve_id,
                    'classe_id' => $data['classe_id'],
                    'enseignant_id' => $data['enseignant_id'],
                    'annee_academique_id' => $data['annee_academique_id'],
                    'lycee_id' => $data['lycee_id'],
                    'date_presence' => $data['date_presence'],
                    'statut' => $presence_data['statut'],
                    'commentaire' => $presence_data['commentaire'] ?? null
                ]);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
