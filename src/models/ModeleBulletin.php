<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class ModeleBulletin {

    /**
     * Finds the report card template for the current lycee.
     * If one doesn't exist, it creates and returns a default template.
     *
     * @return array|false The template data.
     */
    public static function findByLyceeId() {
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) return false;

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM modele_bulletin WHERE lycee_id = :lycee_id LIMIT 1");
        $stmt->execute(['lycee_id' => $lycee_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            // No template exists, create a default one
            $default_layout = ['header', 'info_eleve', 'tableau_notes', 'resume_moyennes'];
            $default_data = [
                'lycee_id' => $lycee_id,
                'nom_modele' => 'Modèle par défaut',
                'layout_data' => json_encode($default_layout)
            ];
            self::createDefault($default_data);
            // Fetch the newly created template
            $stmt->execute(['lycee_id' => $lycee_id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Decode the layout data before returning
        if ($template && isset($template['layout_data'])) {
            $template['layout_data'] = json_decode($template['layout_data'], true);
        }

        return $template;
    }

    /**
     * Saves the new layout order for a given template.
     *
     * @param int $template_id The ID of the template to update.
     * @param array $layout_array The ordered array of block names.
     * @return bool True on success, false on failure.
     */
    public static function saveLayout($template_id, $layout_array) {
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) return false;

        $json_layout = json_encode($layout_array);

        $sql = "UPDATE modele_bulletin SET layout_data = :layout_data WHERE id = :id AND lycee_id = :lycee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'layout_data' => $json_layout,
                'id' => $template_id,
                'lycee_id' => $lycee_id
            ]);
        } catch (PDOException $e) {
            error_log("Error in ModeleBulletin::saveLayout: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new default template record.
     *
     * @param array $data The data for the new template.
     * @return bool True on success, false on failure.
     */
    private static function createDefault($data) {
        $sql = "INSERT INTO modele_bulletin (lycee_id, nom_modele, layout_data) VALUES (:lycee_id, :nom_modele, :layout_data)";
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Error in ModeleBulletin::createDefault: " . $e->getMessage());
            return false;
        }
    }
}
?>