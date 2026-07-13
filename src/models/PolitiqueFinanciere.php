<?php

require_once __DIR__ . '/../config/database.php';

class PolitiqueFinanciere {

    public static function findOrCreate($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM politiques_financieres WHERE lycee_id = :lycee_id");
        $stmt->execute(['lycee_id' => $lycee_id]);
        $policy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$policy) {
            $stmt = $db->prepare("INSERT INTO politiques_financieres (lycee_id, activation_seuil_type, activation_seuil_valeur, notes_seuil_mensualites, bulletin_seuil_complet, active) VALUES (:lycee_id, '100', NULL, 0, 1, 1)");
            $stmt->execute(['lycee_id' => $lycee_id]);

            $stmt = $db->prepare("SELECT * FROM politiques_financieres WHERE lycee_id = :lycee_id");
            $stmt->execute(['lycee_id' => $lycee_id]);
            $policy = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $policy;
    }

    public static function save($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE politiques_financieres SET
            activation_seuil_type = :activation_seuil_type,
            activation_seuil_valeur = :activation_seuil_valeur,
            notes_seuil_mensualites = :notes_seuil_mensualites,
            bulletin_seuil_complet = :bulletin_seuil_complet,
            active = :active
            WHERE lycee_id = :lycee_id");
        return $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'activation_seuil_type' => $data['activation_seuil_type'] ?? '100',
            'activation_seuil_valeur' => !empty($data['activation_seuil_valeur']) ? $data['activation_seuil_valeur'] : null,
            'notes_seuil_mensualites' => isset($data['notes_seuil_mensualites']) ? (int)$data['notes_seuil_mensualites'] : 0,
            'bulletin_seuil_complet' => isset($data['bulletin_seuil_complet']) ? (int)$data['bulletin_seuil_complet'] : 1,
            'active' => isset($data['active']) ? (int)$data['active'] : 1
        ]);
    }
}
