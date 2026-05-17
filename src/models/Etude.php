<?php

require_once __DIR__ . '/../config/database.php';

class Etude {

    /**
     * Find all enrollments for a given student.
     * @param int $eleve_id
     * @return array
     */
    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT et.*, c.serie, c.niveau, aa.libelle as annee_academique
            FROM etudes et
            JOIN classes c ON et.classe_id = c.id_classe
            JOIN annees_academiques aa ON et.annee_academique_id = aa.id
            WHERE et.eleve_id = :eleve_id
            ORDER BY aa.date_debut DESC
        ");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a student's current (active) enrollment for a given academic year.
     * @param int $eleve_id
     * @param int $annee_academique_id
     * @return array|false
     */
    public static function findActiveEnrollment($eleve_id, $annee_academique_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM etudes
            WHERE eleve_id = :eleve_id
            AND annee_academique_id = :annee_academique_id
            AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['eleve_id' => $eleve_id, 'annee_academique_id' => $annee_academique_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new enrollment record.
     * @param array $data
     * @return int lastInsertId
     */
    public static function create($data) {
        $db = Database::getInstance();
        // We need to get the lycee_id from the classe to ensure data integrity
        if (!isset($data['lycee_id'])) {
            $stmt_classe = $db->prepare("SELECT lycee_id FROM classes WHERE id_classe = :classe_id");
            $stmt_classe->execute(['classe_id' => $data['classe_id']]);
            $classe = $stmt_classe->fetch(PDO::FETCH_ASSOC);
            if (!$classe) {
                throw new Exception("Classe non trouvée.");
            }
            $data['lycee_id'] = $classe['lycee_id'];
        }

        $sql = "INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status)
                VALUES (:eleve_id, :classe_id, :lycee_id, :annee_academique_id, :is_active, :status)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'classe_id' => $data['classe_id'],
            'lycee_id' => $data['lycee_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'is_active' => $data['is_active'] ?? 0,
            'status' => $data['status'] ?? 'en_attente_paiement',
        ]);
        return $db->lastInsertId();
    }

    /**
     * Activate an enrollment.
     * @param int $id_etude
     * @param int $user_id The user who activates it
     * @return bool
     */
    public static function activate($id_etude, $user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE etudes
            SET is_active = 1, status = 'active', date_activation = NOW(), active_par = :user_id
            WHERE id_etude = :id_etude
        ");
        return $stmt->execute(['id_etude' => $id_etude, 'user_id' => $user_id]);
    }

    /**
     * Find a student's pending enrollment for a given academic year.
     * @param int $eleve_id
     * @param int $annee_academique_id
     * @return array|false
     */
    public static function findPendingEnrollment($eleve_id, $annee_academique_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT et.*, c.niveau, c.serie
            FROM etudes et
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE et.eleve_id = :eleve_id
            AND et.annee_academique_id = :annee_academique_id
            AND et.status = 'en_attente_paiement'
            LIMIT 1
        ");
        $stmt->execute(['eleve_id' => $eleve_id, 'annee_academique_id' => $annee_academique_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function isEnrolled(int $eleve_id, int $annee_academique_id, $lycee_id = null): bool {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM etudes WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_academique_id";
        $params = [
            'eleve_id' => $eleve_id,
            'annee_academique_id' => $annee_academique_id
        ];
        if ($lycee_id) {
            $sql .= " AND lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Trouve une étude par ID de l'élève et ID de l'année.
     */
    public static function findByEleveAndAnnee($eleveId, $anneeId, $lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM etudes WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id";
        $params = ['eleve_id' => $eleveId, 'annee_id' => $anneeId];
        if ($lycee_id) {
            $sql .= " AND lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM etudes WHERE id_etude = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
