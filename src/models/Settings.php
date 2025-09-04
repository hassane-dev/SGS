<?php

require_once __DIR__ . '/../config/database.php';

class Settings {

    /**
     * Get the general settings for a given Lycee.
     *
     * @param int $lycee_id The ID of the Lycee.
     * @return array|false The settings array or false if not found.
     */
    public static function getByLyceeId($lycee_id) {
        try {
            $db = Database::getInstance();
            // In a multi-lycee app, we'd get the lycee_id from the logged-in user.
            $stmt = $db->prepare("SELECT * FROM parametres_generaux WHERE lycee_id = :lycee_id LIMIT 1");
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Settings::getByLyceeId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or update the general settings for a given Lycee.
     *
     * @param int $lycee_id The ID of the Lycee.
     * @param array $data The data to save.
     * @return bool True on success, false on failure.
     */
    public static function save($lycee_id, $data) {
        $existing = self::getByLyceeId($lycee_id);

        $sql = "";
        if ($existing) {
            // Update
            $sql = "UPDATE parametres_generaux SET
                        nom_lycee = :nom_lycee,
                        type_lycee = :type_lycee,
                        annee_academique = :annee_academique,
                        nombre_devoirs_par_trimestre = :nombre_devoirs_par_trimestre,
                        modalite_paiement = :modalite_paiement,
                        multilingue_actif = :multilingue_actif,
                        biometrie_actif = :biometrie_actif,
                        confidentialite_nationale = :confidentialite_nationale
                    WHERE lycee_id = :lycee_id";
        } else {
            // Insert
            $sql = "INSERT INTO parametres_generaux (lycee_id, nom_lycee, type_lycee, annee_academique, nombre_devoirs_par_trimestre, modalite_paiement, multilingue_actif, biometrie_actif, confidentialite_nationale)
                    VALUES (:lycee_id, :nom_lycee, :type_lycee, :annee_academique, :nombre_devoirs_par_trimestre, :modalite_paiement, :multilingue_actif, :biometrie_actif, :confidentialite_nationale)";
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            return $stmt->execute([
                'lycee_id' => $lycee_id,
                'nom_lycee' => $data['nom_lycee'],
                'type_lycee' => $data['type_lycee'],
                'annee_academique' => $data['annee_academique'],
                'nombre_devoirs_par_trimestre' => $data['nombre_devoirs_par_trimestre'],
                'modalite_paiement' => $data['modalite_paiement'],
                // Handle checkboxes which may not be present in POST data
                'multilingue_actif' => isset($data['multilingue_actif']) ? 1 : 0,
                'biometrie_actif' => isset($data['biometrie_actif']) ? 1 : 0,
                'confidentialite_nationale' => isset($data['confidentialite_nationale']) ? 1 : 0,
            ]);
        } catch (PDOException $e) {
            error_log("Error in Settings::save: " . $e->getMessage());
            return false;
        }
    }
}
?>
