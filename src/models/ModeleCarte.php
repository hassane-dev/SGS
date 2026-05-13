<?php
require_once __DIR__ . '/../config/database.php';

class ModeleCarte {

    public static function findByLyceeId($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM carte_templates WHERE lycee_id = :lycee_id LIMIT 1");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $db = Database::getInstance();
        $existing = self::findByLyceeId($data['lycee_id']);

        if ($existing) {
            $sql = "UPDATE carte_templates SET
                    nom_modele = :nom_modele,
                    orientation = :orientation,
                    background = :background,
                    styles = :styles,
                    layout_data = :layout_data,
                    config_visuelle = :config_visuelle,
                    version = :version
                    WHERE lycee_id = :lycee_id";
        } else {
            $sql = "INSERT INTO carte_templates
                    (lycee_id, nom_modele, orientation, background, styles, layout_data, config_visuelle, version)
                    VALUES
                    (:lycee_id, :nom_modele, :orientation, :background, :styles, :layout_data, :config_visuelle, :version)";
        }

        $stmt = $db->prepare($sql);
        $params = [
            'lycee_id' => $data['lycee_id'],
            'nom_modele' => $data['nom_modele'],
            'orientation' => $data['orientation'] ?? 'landscape',
            'background' => $data['background'] ?? null,
            'styles' => $data['styles'] ?? '{}',
            'layout_data' => $data['layout_data'] ?? '{}',
            'config_visuelle' => $data['config_visuelle'] ?? '{}',
            'version' => $data['version'] ?? '2.1'
        ];

        $stmt->execute($params);
        $template_id = $existing ? $existing['id'] : $db->lastInsertId();

        // If we have granular objects, save them too
        // Fabric.js elements are often passed in layout_data, let's parse them if objects are not directly provided
        $objects_to_save = $data['objects'] ?? null;
        if (!$objects_to_save && isset($data['layout_data'])) {
            $layout = json_decode($data['layout_data'], true);
            $objects_to_save = $layout['elements'] ?? null;
        }

        if (isset($objects_to_save) && is_array($objects_to_save)) {
            self::saveObjects($template_id, $objects_to_save);
        }

        return $template_id;
    }

    private static function saveObjects($template_id, $objects) {
        $db = Database::getInstance();
        // Clear existing objects for this template
        $stmt = $db->prepare("DELETE FROM carte_objects WHERE template_id = :template_id");
        $stmt->execute(['template_id' => $template_id]);

        $sql = "INSERT INTO carte_objects
                (template_id, type_objet, pos_x, pos_y, width, height, z_index, styles, placeholder)
                VALUES
                (:template_id, :type_objet, :pos_x, :pos_y, :width, :height, :z_index, :styles, :placeholder)";

        $stmt = $db->prepare($sql);
        foreach ($objects as $index => $obj) {
            // Map Fabric.js keys to Database columns
            $type = $obj['type'] ?? $obj['type_objet'] ?? 'text';
            $x = $obj['left'] ?? $obj['pos_x'] ?? 0;
            $y = $obj['top'] ?? $obj['pos_y'] ?? 0;
            $w = $obj['width'] ?? 0;
            $h = $obj['height'] ?? 0;

            $stmt->execute([
                'template_id' => $template_id,
                'type_objet' => $type,
                'pos_x' => $x,
                'pos_y' => $y,
                'width' => $w,
                'height' => $h,
                'z_index' => $obj['z_index'] ?? $index,
                'styles' => isset($obj['styles']) ? json_encode($obj['styles']) : json_encode($obj),
                'placeholder' => $obj['placeholder'] ?? null
            ]);
        }
    }

    public static function getObjects($template_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM carte_objects WHERE template_id = :template_id ORDER BY z_index ASC");
        $stmt->execute(['template_id' => $template_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
