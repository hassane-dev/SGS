<?php

// Force file recognition
require_once __DIR__ . '/../config/database.php';

class ModeleCarte {

    /**
     * Get the card template for a given lycee.
     * Since each lycee has only one, we find by lycee_id.
     * @param int $lycee_id
     * @return array|false
     */
    public static function findByLyceeId($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM modele_carte WHERE lycee_id = :lycee_id LIMIT 1");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create or update the card template for a given lycee.
     * @param array $data
     * @return bool
     */
    public static function save($data) {
        $existing = self::findByLyceeId($data['lycee_id']);

        $sql = $existing
            ? "UPDATE modele_carte SET nom_modele = :nom_modele, background = :background, font_settings = :font_settings, layout_data = :layout_data, qr_code_settings = :qr_code_settings WHERE lycee_id = :lycee_id"
            : "INSERT INTO modele_carte (lycee_id, nom_modele, background, font_settings, layout_data, qr_code_settings) VALUES (:lycee_id, :nom_modele, :background, :font_settings, :layout_data, :qr_code_settings)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'lycee_id' => $data['lycee_id'],
            'nom_modele' => $data['nom_modele'],
            'background' => $data['background'] ?? null,
            'font_settings' => $data['font_settings'] ?? '{}',
            'layout_data' => $data['layout_data'] ?? '{}',
            'qr_code_settings' => $data['qr_code_settings'] ?? '{}',
        ];

        return $stmt->execute($params);
    }
}
?>
