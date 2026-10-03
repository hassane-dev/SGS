<?php

require_once __DIR__ . '/../config/database.php';

class ModePaiement {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM modes_paiement WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByCode($lyceeId, $code) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM modes_paiement WHERE lycee_id = :lycee_id AND code = :code");
        $stmt->execute(['lycee_id' => $lyceeId, 'code' => strtoupper(trim($code))]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId, $onlyActive = true) {
        $db = Database::getInstance();
        self::seedDefaultsForLycee($lyceeId);

        $sql = "SELECT * FROM modes_paiement WHERE lycee_id = :lycee_id";
        if ($onlyActive) {
            $sql .= " AND actif = 1";
        }
        $sql .= " ORDER BY type_canal ASC, libelle ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function seedDefaultsForLycee($lyceeId) {
        $db = Database::getInstance();
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM modes_paiement WHERE lycee_id = :lycee_id");
        $stmtCheck->execute(['lycee_id' => $lyceeId]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            return;
        }

        $defaults = [
            ['code' => 'ESPECES', 'libelle' => 'Espèces', 'type_canal' => 'especes', 'exige_session' => 1, 'exige_ref' => 0],
            ['code' => 'MOMO_ORANGE', 'libelle' => 'Orange Money', 'type_canal' => 'mobile_money', 'exige_session' => 0, 'exige_ref' => 1],
            ['code' => 'MOMO_MTN', 'libelle' => 'MTN Mobile Money', 'type_canal' => 'mobile_money', 'exige_session' => 0, 'exige_ref' => 1],
            ['code' => 'CHEQUE', 'libelle' => 'Chèque', 'type_canal' => 'banque', 'exige_session' => 0, 'exige_ref' => 1],
            ['code' => 'VIREMENT', 'libelle' => 'Virement bancaire', 'type_canal' => 'banque', 'exige_session' => 0, 'exige_ref' => 1]
        ];

        $stmtIns = $db->prepare("
            INSERT INTO modes_paiement (
                lycee_id, code, libelle, type_canal, exige_session_caisse, exige_reference_transaction, actif
            ) VALUES (
                :lycee_id, :code, :libelle, :type_canal, :exige_session, :exige_ref, 1
            )
        ");

        foreach ($defaults as $d) {
            try {
                $stmtIns->execute([
                    'lycee_id' => $lyceeId,
                    'code' => $d['code'],
                    'libelle' => $d['libelle'],
                    'type_canal' => $d['type_canal'],
                    'exige_session' => $d['exige_session'],
                    'exige_ref' => $d['exige_ref']
                ]);
            } catch (Exception $e) {
                // Ignore if duplicate
            }
        }
    }
}
