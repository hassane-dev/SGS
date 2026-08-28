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
                   c.niveau, c.serie, c.numero, c.lycee_id, c.cycle_id,
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
        if (!empty($filters['cycle_id'])) {
            $sql .= " AND c.cycle_id = :cycle_id";
            $params['cycle_id'] = $filters['cycle_id'];
        }
        if (!empty($filters['niveau'])) {
            $sql .= " AND c.niveau = :niveau";
            $params['niveau'] = $filters['niveau'];
        }
        if (isset($filters['serie']) && $filters['serie'] !== '') {
            if ($filters['serie'] === 'none' || $filters['serie'] === 'empty' || $filters['serie'] === 'sans_serie') {
                $sql .= " AND (c.serie IS NULL OR c.serie = '')";
            } else {
                $sql .= " AND c.serie = :serie";
                $params['serie'] = $filters['serie'];
            }
        }
        if (isset($filters['numero']) && $filters['numero'] !== '') {
            $sql .= " AND c.numero = :numero";
            $params['numero'] = $filters['numero'];
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


    /**
     * Find distinct assigned teachers filtered by dynamic class hierarchy.
     */
    public static function findTeachersByHierarchy(int $lyceeId, ?int $cycleId = null, ?string $niveau = null, ?string $serie = null, ?string $numero = null, ?int $classeId = null, ?int $matiereId = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT u.id_user, u.nom, u.prenom, u.identifiant_public
            FROM affectations_pedagogiques ap
            JOIN utilisateurs u ON ap.enseignant_id = u.id_user
            JOIN classes c ON ap.classe_id = c.id_classe
            WHERE c.lycee_id = :lycee_id AND ap.statut = 'actif'
        ";
        $params = ['lycee_id' => $lyceeId];

        if (!empty($cycleId)) {
            $sql .= " AND c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if (!empty($niveau)) {
            $sql .= " AND c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if (!empty($serie)) {
            $sql .= " AND c.serie = :serie";
            $params['serie'] = $serie;
        }
        if ($numero !== null && $numero !== '') {
            $sql .= " AND c.numero = :numero";
            $params['numero'] = $numero;
        }
        if (!empty($classeId)) {
            $sql .= " AND c.id_classe = :classe_id";
            $params['classe_id'] = $classeId;
        }
        if (!empty($matiereId)) {
            $sql .= " AND ap.matiere_id = :matiere_id";
            $params['matiere_id'] = $matiereId;
        }

        $sql .= " ORDER BY u.nom ASC, u.prenom ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findTeachersByHierarchy: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find subjects assigned to teachers for a specific class.
     */
    public static function findSubjectsForClass(int $classeId): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT m.id_matiere, m.nom_matiere
            FROM affectations_pedagogiques ap
            JOIN matieres m ON ap.matiere_id = m.id_matiere
            WHERE ap.classe_id = :classe_id AND ap.statut = 'actif'
            ORDER BY m.nom_matiere ASC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['classe_id' => $classeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findSubjectsForClass: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find subjects taught by a specific teacher across all assigned classes.
     */
    public static function findSubjectsForTeacher(int $teacherId): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT m.id_matiere, m.nom_matiere
            FROM affectations_pedagogiques ap
            JOIN matieres m ON ap.matiere_id = m.id_matiere
            WHERE ap.enseignant_id = :teacher_id AND ap.statut = 'actif'
            ORDER BY m.nom_matiere ASC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['teacher_id' => $teacherId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findSubjectsForTeacher: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find classes assigned to a teacher.
     */
    public static function findClassesForTeacher(int $teacherId, ?int $lyceeId = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT c.id_classe, c.niveau, c.serie, c.numero, cy.nom_cycle, cy.id_cycle
            FROM affectations_pedagogiques ap
            JOIN classes c ON ap.classe_id = c.id_classe
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE ap.enseignant_id = :teacher_id AND ap.statut = 'actif'
        ";
        $params = ['teacher_id' => $teacherId];

        if ($lyceeId !== null) {
            $sql .= " AND c.lycee_id = :lycee_id";
            $params['lycee_id'] = $lyceeId;
        }

        $sql .= " ORDER BY c.niveau ASC, c.serie ASC, c.numero ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findClassesForTeacher: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find subjects taught by a teacher in a specific class.
     */
    public static function findSubjectsForTeacherInClass(int $teacherId, int $classeId): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT m.id_matiere, m.nom_matiere
            FROM affectations_pedagogiques ap
            JOIN matieres m ON ap.matiere_id = m.id_matiere
            WHERE ap.enseignant_id = :teacher_id AND ap.classe_id = :classe_id AND ap.statut = 'actif'
            ORDER BY m.nom_matiere ASC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'teacher_id' => $teacherId,
                'classe_id' => $classeId
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findSubjectsForTeacherInClass: " . $e->getMessage());
            return [];
        }
    }



    /**
     * Find subjects in a class that are available for a new assignment
     * (i.e. not currently occupied by an 'actif' or 'suspendu' assignment).
     * Optionally exclude a specific assignment ID (e.g., when editing).
     * If $includeAll is true, returns all subjects of the class (for index searching).
     */
    public static function findAvailableSubjectsForClass(int $classeId, ?int $excludeAssignmentId = null, bool $includeAll = false): array {
        $db = Database::getInstance();
        $active_year = AnneeAcademique::findActive();
        if (!$active_year && !$includeAll) return [];

        if ($includeAll) {
            $sql = "
                SELECT DISTINCT m.id_matiere, m.nom_matiere, cm.coefficient
                FROM classe_matieres cm
                JOIN matieres m ON cm.matiere_id = m.id_matiere
                WHERE cm.classe_id = :classe_id
                ORDER BY m.nom_matiere ASC
            ";
            $params = ['classe_id' => $classeId];
        } else {
            $sql = "
                SELECT DISTINCT m.id_matiere, m.nom_matiere, cm.coefficient
                FROM classe_matieres cm
                JOIN matieres m ON cm.matiere_id = m.id_matiere
                WHERE cm.classe_id = :classe_id
                AND cm.matiere_id NOT IN (
                    SELECT ap.matiere_id
                    FROM affectations_pedagogiques ap
                    WHERE ap.classe_id = :classe_id2
                    AND ap.annee_academique_id = :annee_id
                    AND ap.statut IN ('actif', 'suspendu')
            ";
            $params = [
                'classe_id' => $classeId,
                'classe_id2' => $classeId,
                'annee_id' => $active_year['id']
            ];

            if ($excludeAssignmentId) {
                $sql .= " AND ap.id != :exclude_id";
                $params['exclude_id'] = $excludeAssignmentId;
            }

            $sql .= "
                )
                ORDER BY m.nom_matiere ASC
            ";
        }

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findAvailableSubjectsForClass: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find teachers eligible for a class/cycle assignment.
     */
    public static function findEligibleTeachers(int $lyceeId, ?int $cycleId = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT DISTINCT u.id_user, u.nom, u.prenom, u.identifiant_public, u.fonction
            FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id_role
            WHERE u.lycee_id = :lycee_id AND u.actif = 1
            AND (LOWER(r.nom_role) LIKE '%enseignant%' OR LOWER(u.fonction) LIKE '%enseignant%' OR r.nom_role IN ('proviseur', 'censeur', 'surveillant', 'directeur', 'admin_local', 'super_admin_createur'))
        ";
        $params = ['lycee_id' => $lyceeId];

        if ($cycleId) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM personnel_cycles_assignments pca
                WHERE pca.personnel_id = u.id_user AND pca.cycle_id = :cycle_id AND pca.actif = 1
            )";
            $params['cycle_id'] = $cycleId;
        }

        $sql .= " ORDER BY u.nom ASC, u.prenom ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in AffectationPedagogique::findEligibleTeachers: " . $e->getMessage());
            return [];
        }
    }

}
