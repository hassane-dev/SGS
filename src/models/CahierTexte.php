<?php

require_once __DIR__ . '/../config/database.php';

class CahierTexte {

    public static function findAllByPersonnel($personnel_id, $lycee_id, $filters = []) {
        $db = Database::getInstance();
        $sql = "
            SELECT ct.*,
                   c.niveau, c.serie, c.numero, c.cycle_id,
                   cy.nom_cycle,
                   m.nom_matiere,
                   u.nom as nom_personnel, u.prenom as prenom_personnel, u.identifiant_public as matricule_personnel
            FROM cahier_texte ct
            LEFT JOIN classes c ON ct.classe_id = c.id_classe
            LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
            LEFT JOIN matieres m ON ct.matiere_id = m.id_matiere
            LEFT JOIN utilisateurs u ON ct.personnel_id = u.id_user
            WHERE ct.lycee_id = :lycee_id
        ";
        $params = ['lycee_id' => $lycee_id];

        if ($personnel_id !== null) {
            $sql .= " AND ct.personnel_id = :personnel_id";
            $params['personnel_id'] = $personnel_id;
        }

        // Dynamic hierarchical and attribute filters
        if (!empty($filters['personnel_id_filter'])) {
            $sql .= " AND ct.personnel_id = :personnel_id_filter";
            $params['personnel_id_filter'] = (int)$filters['personnel_id_filter'];
        }
        if (!empty($filters['cycle_id'])) {
            $sql .= " AND c.cycle_id = :cycle_id";
            $params['cycle_id'] = (int)$filters['cycle_id'];
        }
        if (!empty($filters['niveau'])) {
            $sql .= " AND c.niveau = :niveau";
            $params['niveau'] = $filters['niveau'];
        }
        if (isset($filters['serie']) && $filters['serie'] !== null && $filters['serie'] !== '') {
            $sql .= " AND c.serie = :serie";
            $params['serie'] = $filters['serie'];
        }
        if (isset($filters['numero']) && $filters['numero'] !== null && $filters['numero'] !== '') {
            $sql .= " AND c.numero = :numero";
            $params['numero'] = $filters['numero'];
        }
        if (!empty($filters['classe_id'])) {
            $sql .= " AND ct.classe_id = :classe_id";
            $params['classe_id'] = (int)$filters['classe_id'];
        }
        if (!empty($filters['matiere_id'])) {
            $sql .= " AND ct.matiere_id = :matiere_id";
            $params['matiere_id'] = (int)$filters['matiere_id'];
        }
        if (!empty($filters['date_filter'])) {
            $sql .= " AND ct.date_cours = :date_filter";
            $params['date_filter'] = $filters['date_filter'];
        }

        $sql .= " ORDER BY ct.date_cours DESC, ct.heure_debut DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($entries as &$entry) {
            self::normalizeTextFields($entry);
        }
        return $entries;
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM cahier_texte WHERE cahier_id = :id");
        $stmt->execute(['id' => $id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($entry) {
            self::normalizeTextFields($entry);
        }
        return $entry;
    }

    public static function findDetailsById($id, $lycee_id = null) {
        $db = Database::getInstance();
        $sql = "
            SELECT ct.*,
                   c.niveau, c.serie, c.numero, c.cycle_id,
                   cy.nom_cycle,
                   m.nom_matiere,
                   u.nom as nom_personnel, u.prenom as prenom_personnel, u.identifiant_public as matricule_personnel,
                   a.libelle as annee_academique_libelle
            FROM cahier_texte ct
            LEFT JOIN classes c ON ct.classe_id = c.id_classe
            LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
            LEFT JOIN matieres m ON ct.matiere_id = m.id_matiere
            LEFT JOIN utilisateurs u ON ct.personnel_id = u.id_user
            LEFT JOIN annees_academiques a ON ct.annee_id = a.id
            WHERE ct.cahier_id = :id
        ";
        $params = ['id' => $id];

        if ($lycee_id !== null) {
            $sql .= " AND ct.lycee_id = :lycee_id";
            $params['lycee_id'] = (int)$lycee_id;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            self::normalizeTextFields($result);
        }

        if ($result && !empty($result['heure_debut']) && !empty($result['heure_fin'])) {
            $ts1 = strtotime($result['heure_debut']);
            $ts2 = strtotime($result['heure_fin']);
            if ($ts2 > $ts1) {
                $diffSec = $ts2 - $ts1;
                $hours = floor($diffSec / 3600);
                $minutes = floor(($diffSec % 3600) / 60);
                $result['duree_minutes'] = floor($diffSec / 60);
                $result['duree_heures'] = round($diffSec / 3600, 2);
                $result['duree_formatee'] = ($hours > 0 ? "{$hours}h " : "") . sprintf("%02dmin", $minutes);
            } else {
                $result['duree_minutes'] = 0;
                $result['duree_heures'] = 0;
                $result['duree_formatee'] = "0 min";
            }
        } else {
            $result['duree_minutes'] = 0;
            $result['duree_heures'] = 0;
            $result['duree_formatee'] = "N/A";
        }

        return $result;
    }

    public static function save($data) {
        $isUpdate = !empty($data['cahier_id']);
        $db = Database::getInstance();

        if ($isUpdate) {
            $sql = "UPDATE cahier_texte SET
                        personnel_id = :personnel_id, classe_id = :classe_id, matiere_id = :matiere_id,
                        date_cours = :date_cours, heure_debut = :heure_debut, heure_fin = :heure_fin,
                        contenu_cours = :contenu_cours, travail_donne = :travail_donne,
                        observation = :observation, annee_id = :annee_id, lycee_id = :lycee_id
                    WHERE cahier_id = :cahier_id";
        } else {
            $sql = "INSERT INTO cahier_texte (
                        personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin,
                        contenu_cours, travail_donne, observation, annee_id, lycee_id
                    ) VALUES (
                        :personnel_id, :classe_id, :matiere_id, :date_cours, :heure_debut, :heure_fin,
                        :contenu_cours, :travail_donne, :observation, :annee_id, :lycee_id
                    )";
        }

        $stmt = $db->prepare($sql);

        $params = [
            'personnel_id' => $data['personnel_id'],
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'date_cours' => $data['date_cours'],
            'heure_debut' => !empty($data['heure_debut']) ? $data['heure_debut'] : null,
            'heure_fin' => !empty($data['heure_fin']) ? $data['heure_fin'] : null,
            'contenu_cours' => $data['contenu_cours'] ?? null,
            'travail_donne' => $data['travail_donne'] ?? null,
            'observation' => $data['observation'] ?? null,
            'annee_id' => $data['annee_id'] ?? null,
            'lycee_id' => $data['lycee_id'],
        ];

        if ($isUpdate) {
            $params['cahier_id'] = $data['cahier_id'];
        }

        return $stmt->execute($params);
    }

    public static function normalizeTextFields(&$row) {
        if (!is_array($row)) return $row;
        $fields = ['contenu_cours', 'travail_donne', 'observation'];
        foreach ($fields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = html_entity_decode($row[$field], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        return $row;
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM cahier_texte WHERE cahier_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>