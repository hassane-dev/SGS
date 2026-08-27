<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AnneeAcademique.php';

class AffectationPedagogique {

    /**
     * Find all active assignments for a given class for the current academic year.
     * Returns an associative array mapping matiere_id to teacher's assignment data.
     */
    public static function findAssignmentsForClass($classe_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            return [];
        }

        $sql = "
            SELECT
                ap.id,
                ap.matiere_id,
                ap.volume_horaire_hebdo,
                ap.date_debut,
                ap.date_fin,
                ap.statut,
                u.id_user as enseignant_id,
                CONCAT(u.prenom, ' ', u.nom) as enseignant_nom
            FROM affectations_pedagogiques ap
            JOIN utilisateurs u ON ap.enseignant_id = u.id_user
            WHERE ap.classe_id = :classe_id
            AND ap.annee_academique_id = :annee_academique_id
            AND ap.statut = 'actif'
            ORDER BY ap.id DESC
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'classe_id' => $classe_id,
                'annee_academique_id' => $active_year['id']
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Re-key the array by matiere_id for easy lookup in the view
            $assignments = [];
            foreach ($results as $result) {
                $assignments[$result['matiere_id']] = $result;
            }
            return $assignments;

        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findAssignmentsForClass: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find assignment details by ID.
     */
    public static function findById($id) {
        $db = Database::getInstance();
        $sql = "
            SELECT ap.*,
                   u.nom as enseignant_nom_famille, u.prenom as enseignant_prenom, u.identifiant_public as enseignant_matricule,
                   c.niveau, c.serie, c.numero, c.lycee_id, c.cycle_id,
                   m.nom_matiere,
                   aa.libelle as annee_libelle
            FROM affectations_pedagogiques ap
            JOIN utilisateurs u ON ap.enseignant_id = u.id_user
            JOIN classes c ON ap.classe_id = c.id_classe
            JOIN matieres m ON ap.matiere_id = m.id_matiere
            JOIN annees_academiques aa ON ap.annee_academique_id = aa.id
            WHERE ap.id = :id
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find all assignments with optional filters.
     */
    public static function findAll($filters = []) {
        $db = Database::getInstance();
        $sql = "
            SELECT ap.*,
                   CONCAT(u.prenom, ' ', u.nom) as enseignant_nom, u.identifiant_public as enseignant_matricule,
                   c.niveau, c.serie, c.numero, c.lycee_id,
                   m.nom_matiere,
                   aa.libelle as annee_libelle
            FROM affectations_pedagogiques ap
            JOIN utilisateurs u ON ap.enseignant_id = u.id_user
            JOIN classes c ON ap.classe_id = c.id_classe
            JOIN matieres m ON ap.matiere_id = m.id_matiere
            JOIN annees_academiques aa ON ap.annee_academique_id = aa.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['lycee_id'])) {
            $sql .= " AND c.lycee_id = :lycee_id";
            $params['lycee_id'] = $filters['lycee_id'];
        }
        if (!empty($filters['annee_id'])) {
            $sql .= " AND ap.annee_academique_id = :annee_id";
            $params['annee_id'] = $filters['annee_id'];
        }
        if (!empty($filters['classe_id'])) {
            $sql .= " AND ap.classe_id = :classe_id";
            $params['classe_id'] = $filters['classe_id'];
        }
        if (!empty($filters['enseignant_id'])) {
            $sql .= " AND ap.enseignant_id = :enseignant_id";
            $params['enseignant_id'] = $filters['enseignant_id'];
        }
        if (!empty($filters['matiere_id'])) {
            $sql .= " AND ap.matiere_id = :matiere_id";
            $params['matiere_id'] = $filters['matiere_id'];
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND ap.statut = :statut";
            $params['statut'] = $filters['statut'];
        }

        $sql .= " ORDER BY c.niveau, c.serie, c.numero, m.nom_matiere, ap.date_debut DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
