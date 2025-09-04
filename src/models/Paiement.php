<?php

require_once __DIR__ . '/../config/database.php';

class Paiement {

    /**
     * Find all payments for a given student.
     * @param int $eleve_id
     * @return array
     */
    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM paiements
            WHERE eleve_id = :eleve_id
            ORDER BY date_paiement DESC
        ");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new payment record.
     * @param array $data
     * @return bool
     */
    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO paiements (eleve_id, type_paiement, montant, statut, date_paiement)
                VALUES (:eleve_id, :type_paiement, :montant, :statut, NOW())";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'type_paiement' => $data['type_paiement'],
            'montant' => $data['montant'],
            'statut' => $data['statut'],
        ]);
    }

    // A delete method could be added here if needed, but for financial records,
    // it's often better to mark them as void/cancelled instead of deleting.
    // We will omit it for this basic implementation.
}
?>
