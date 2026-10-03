<?php

require_once __DIR__ . '/../config/database.php';

class PaiementVentilation {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paiement_ventilations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findBySource($sourceType, $sourceId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT v.*, m.code as mode_code, m.libelle as mode_libelle, m.type_canal
            FROM paiement_ventilations v
            JOIN modes_paiement m ON v.mode_paiement_id = m.id
            WHERE v.source_type = :source_type AND v.source_id = :source_id
            ORDER BY v.id ASC
        ");
        $stmt->execute([
            'source_type' => $sourceType,
            'source_id' => $sourceId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();

        $stmt = $db->prepare("
            INSERT INTO paiement_ventilations (
                lycee_id, source_type, source_id, mode_paiement_id, compte_financier_id,
                session_caisse_id, montant, reference_transaction, mouvement_tresorerie_id
            ) VALUES (
                :lycee_id, :source_type, :source_id, :mode_paiement_id, :compte_financier_id,
                :session_caisse_id, :montant, :reference_transaction, :mouvement_tresorerie_id
            )
        ");

        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'mode_paiement_id' => $data['mode_paiement_id'],
            'compte_financier_id' => $data['compte_financier_id'],
            'session_caisse_id' => $data['session_caisse_id'] ?? null,
            'montant' => (float)$data['montant'],
            'reference_transaction' => $data['reference_transaction'] ?? null,
            'mouvement_tresorerie_id' => $data['mouvement_tresorerie_id'] ?? null
        ]);

        return $db->lastInsertId();
    }

    public static function updateMouvementId($ventilationId, $mouvementId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE paiement_ventilations SET mouvement_tresorerie_id = :mvt_id WHERE id = :id");
        $stmt->execute(['mvt_id' => $mouvementId, 'id' => $ventilationId]);
    }
}
