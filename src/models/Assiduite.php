<?php

require_once __DIR__ . '/../config/database.php';

class Assiduite {

    /**
     * Save a batch of attendance records for a class.
     *
     * @param array $data Contains eleve_id, statut, etc.
     * @return bool
     */
    public static function saveBatch($data) {
        $db = Database::getInstance();

        // We assume the data is structured like:
        // $data = [
        //     'classe_id' => 1,
        //     'annee_academique_id' => 1,
        //     'enseignant_id' => 1,
        //     'date_cours' => '2025-11-09',
        //     'heure_debut' => '08:00',
        //     'heure_fin' => '09:00',
        //     'lycee_id' => 1,
        //     'presences' => [
        //         101 => 'Présent', // eleve_id => statut
        //         102 => 'Absent',
        //         103 => 'Retard'
        //     ]
        // ];

        $sql = "
            INSERT INTO assiduite (eleve_id, classe_id, annee_academique_id, enseignant_id, date_cours, heure_debut, heure_fin, statut, lycee_id)
            VALUES (:eleve_id, :classe_id, :annee_academique_id, :enseignant_id, :date_cours, :heure_debut, :heure_fin, :statut, :lycee_id)
            ON DUPLICATE KEY UPDATE statut = VALUES(statut) -- If teacher makes a mistake, they can re-submit
        ";

        try {
            $db->beginTransaction();
            $stmt = $db->prepare($sql);

            foreach ($data['presences'] as $eleve_id => $statut) {
                $stmt->execute([
                    'eleve_id' => $eleve_id,
                    'classe_id' => $data['classe_id'],
                    'annee_academique_id' => $data['annee_academique_id'],
                    'enseignant_id' => $data['enseignant_id'],
                    'date_cours' => $data['date_cours'],
                    'heure_debut' => $data['heure_debut'],
                    'heure_fin' => $data['heure_fin'],
                    'statut' => $statut,
                    'lycee_id' => $data['lycee_id']
                ]);
            }

            $db->commit();
            return true;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error in Assiduite::saveBatch: " . $e->getMessage());
            return false;
        }
    }

    public static function findAllForLycee($lycee_id, $filters = []) {
        $sql = "
            SELECT a.*, e.nom as eleve_nom, e.prenom as eleve_prenom, c.nom_classe
            FROM assiduite a
            JOIN eleves e ON a.eleve_id = e.id_eleve
            JOIN classes c ON a.classe_id = c.id_classe
            WHERE a.lycee_id = :lycee_id
        ";

        $params = ['lycee_id' => $lycee_id];

        if (!empty($filters['classe_id'])) {
            $sql .= " AND a.classe_id = :classe_id";
            $params['classe_id'] = $filters['classe_id'];
        }
        if (!empty($filters['date_cours'])) {
            $sql .= " AND a.date_cours = :date_cours";
            $params['date_cours'] = $filters['date_cours'];
        }
         if (!empty($filters['statut'])) {
            $sql .= " AND a.statut = :statut";
            $params['statut'] = $filters['statut'];
        }

        $sql .= " ORDER BY a.date_cours DESC, a.heure_debut DESC, c.nom_classe, e.nom, e.prenom";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Assiduite::findAllForLycee: " . $e->getMessage());
            return [];
        }
    }
}
