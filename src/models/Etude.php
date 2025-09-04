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
            SELECT et.*, c.nom_classe, c.serie, c.niveau
            FROM etudes et
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE et.eleve_id = :eleve_id
            ORDER BY et.annee_academique DESC
        ");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a student's current (active) enrollment for a given academic year.
     * @param int $eleve_id
     * @param string $annee_academique
     * @return array|false
     */
    public static function findActiveEnrollment($eleve_id, $annee_academique) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM etudes
            WHERE eleve_id = :eleve_id
            AND annee_academique = :annee_academique
            AND actif = 1
            LIMIT 1
        ");
        $stmt->execute(['eleve_id' => $eleve_id, 'annee_academique' => $annee_academique]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new enrollment record.
     * @param array $data
     * @return bool
     */
    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO etudes (eleve_id, classe_id, annee_academique, actif)
                VALUES (:eleve_id, :classe_id, :annee_academique, :actif)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'classe_id' => $data['classe_id'],
            'annee_academique' => $data['annee_academique'],
            'actif' => $data['actif'] ?? 0, // Default to inactive
        ]);
    }

    /**
     * Activate an enrollment.
     * @param int $id_etude
     * @return bool
     */
    public static function activate($id_etude) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE etudes SET actif = 1 WHERE id_etude = :id_etude");
        return $stmt->execute(['id_etude' => $id_etude]);
    }
}
?>
