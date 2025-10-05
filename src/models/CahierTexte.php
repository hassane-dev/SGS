<?php

require_once __DIR__ . '/../config/database.php';

class CahierTexte {

    public static function findAllByPersonnel($personnel_id, $ecole_id, $filters = []) {
        $db = Database::getInstance();
        $sql = "
            SELECT ct.*, c.nom_classe, c.serie, m.nom_matiere, u.nom as nom_personnel, u.prenom as prenom_personnel
            FROM cahier_texte ct
            LEFT JOIN classes c ON ct.classe_id = c.id_classe
            LEFT JOIN matieres m ON ct.matiere_id = m.id_matiere
            LEFT JOIN utilisateurs u ON ct.personnel_id = u.id_user
            WHERE ct.ecole_id = :ecole_id
        ";
        $params = ['ecole_id' => $ecole_id];

        if ($personnel_id !== null) {
            $sql .= " AND ct.personnel_id = :personnel_id";
            $params['personnel_id'] = $personnel_id;
        }

        // Admin filters
        if (!empty($filters['personnel_id_filter'])) {
            $sql .= " AND ct.personnel_id = :personnel_id_filter";
            $params['personnel_id_filter'] = $filters['personnel_id_filter'];
        }
        if (!empty($filters['classe_id_filter'])) {
            $sql .= " AND ct.classe_id = :classe_id_filter";
            $params['classe_id_filter'] = $filters['classe_id_filter'];
        }
        if (!empty($filters['date_filter'])) {
            $sql .= " AND ct.date_cours = :date_filter";
            $params['date_filter'] = $filters['date_filter'];
        }

        $sql .= " ORDER BY ct.date_cours DESC, ct.heure_debut DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM cahier_texte WHERE cahier_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['cahier_id']);
        $db = Database::getInstance();

        if ($isUpdate) {
            $sql = "UPDATE cahier_texte SET
                        personnel_id = :personnel_id, classe_id = :classe_id, matiere_id = :matiere_id,
                        date_cours = :date_cours, heure_debut = :heure_debut, heure_fin = :heure_fin,
                        contenu_cours = :contenu_cours, travail_donne = :travail_donne,
                        observation = :observation, annee_id = :annee_id, ecole_id = :ecole_id
                    WHERE cahier_id = :cahier_id";
        } else {
            $sql = "INSERT INTO cahier_texte (
                        personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin,
                        contenu_cours, travail_donne, observation, annee_id, ecole_id
                    ) VALUES (
                        :personnel_id, :classe_id, :matiere_id, :date_cours, :heure_debut, :heure_fin,
                        :contenu_cours, :travail_donne, :observation, :annee_id, :ecole_id
                    )";
        }

        $stmt = $db->prepare($sql);

        $params = [
            'personnel_id' => $data['personnel_id'],
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'date_cours' => $data['date_cours'],
            'heure_debut' => $data['heure_debut'] ?: null,
            'heure_fin' => $data['heure_fin'] ?: null,
            'contenu_cours' => $data['contenu_cours'] ?? null,
            'travail_donne' => $data['travail_donne'] ?? null,
            'observation' => $data['observation'] ?? null,
            'annee_id' => $data['annee_id'] ?? null,
            'ecole_id' => $data['ecole_id'],
        ];

        if ($isUpdate) {
            $params['cahier_id'] = $data['cahier_id'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM cahier_texte WHERE cahier_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>