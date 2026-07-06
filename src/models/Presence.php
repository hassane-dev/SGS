<?php

require_once __DIR__ . '/../config/database.php';

class Presence {

    /**
     * Enregistre une présence pour un élève.
     */
    public static function save($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO presences (eleve_id, classe_id, matiere_id, enseignant_id, annee_academique_id, lycee_id, date_presence, statut, commentaire)
            VALUES (:eleve_id, :classe_id, :matiere_id, :enseignant_id, :annee_academique_id, :lycee_id, :date_presence, :statut, :commentaire)
            ON DUPLICATE KEY UPDATE statut = VALUES(statut), commentaire = VALUES(commentaire)
        ");

        return $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'] ?? null,
            'enseignant_id' => $data['enseignant_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'lycee_id' => $data['lycee_id'],
            'date_presence' => $data['date_presence'],
            'statut' => $data['statut'],
            'commentaire' => $data['commentaire'] ?? null
        ]);
    }

    /**
     * Récupère les présences d'une classe pour une date donnée.
     */
    public static function findByClassAndDate($classeId, $date, $matiereId = null) {
        $db = Database::getInstance();
        $sql = "SELECT p.*, e.nom, e.prenom
                FROM presences p
                JOIN eleves e ON p.eleve_id = e.id_eleve
                WHERE p.classe_id = :classe_id AND p.date_presence = :date_presence";

        $params = ['classe_id' => $classeId, 'date_presence' => $date];

        if ($matiereId) {
            $sql .= " AND p.matiere_id = :matiere_id";
            $params['matiere_id'] = $matiereId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère l'historique des présences d'un élève.
     */
    public static function findByEleve($eleveId, $anneeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.*, c.niveau as nom_classe, m.nom_matiere
            FROM presences p
            JOIN classes c ON p.classe_id = c.id_classe
            LEFT JOIN matieres m ON p.matiere_id = m.id_matiere
            WHERE p.eleve_id = :eleve_id AND p.annee_academique_id = :annee_id
            ORDER BY p.date_presence DESC
        ");
        $stmt->execute(['eleve_id' => $eleveId, 'annee_id' => $anneeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Enregistre plusieurs présences en une seule fois.
     */
    public static function saveAll($data) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            foreach ($data['presences'] as $eleve_id => $presence_data) {
                self::save([
                    'eleve_id' => $eleve_id,
                    'classe_id' => $data['classe_id'],
                    'matiere_id' => $data['matiere_id'] ?? null,
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
            error_log("Error in Presence::saveAll: " . $e->getMessage());
            return false;
        }
    }
}
