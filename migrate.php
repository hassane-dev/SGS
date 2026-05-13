<?php
require_once __DIR__ . '/src/config/database.php';
$db = Database::getInstance();
try {
    // Update param_lycee
    $db->exec("ALTER TABLE param_lycee
        ADD COLUMN IF NOT EXISTS header_primary TEXT,
        ADD COLUMN IF NOT EXISTS header_secondary TEXT,
        ADD COLUMN IF NOT EXISTS signature_directeur TEXT,
        ADD COLUMN IF NOT EXISTS tampon_ecole TEXT;");

    // Rename modele_carte to carte_templates if it exists and carte_templates doesn't
    $stmt = $db->query("SHOW TABLES LIKE 'modele_carte'");
    if ($stmt->fetch()) {
        $stmt2 = $db->query("SHOW TABLES LIKE 'carte_templates'");
        if (!$stmt2->fetch()) {
            $db->exec("RENAME TABLE modele_carte TO carte_templates;");
            $db->exec("ALTER TABLE carte_templates
                ADD COLUMN IF NOT EXISTS orientation ENUM('landscape', 'portrait') DEFAULT 'landscape',
                ADD COLUMN IF NOT EXISTS width_mm DECIMAL(5,2) DEFAULT 85.60,
                ADD COLUMN IF NOT EXISTS height_mm DECIMAL(5,2) DEFAULT 53.98,
                RENAME COLUMN font_settings TO styles,
                ADD COLUMN IF NOT EXISTS config_visuelle JSON,
                ADD COLUMN IF NOT EXISTS version VARCHAR(10) DEFAULT '2.1';");
            echo "Table migration successful\n";
        }
    }

    // Create carte_objects if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS carte_objects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_id INT NOT NULL,
        type_objet VARCHAR(50) NOT NULL,
        pos_x INT,
        pos_y INT,
        width INT,
        height INT,
        z_index INT DEFAULT 0,
        styles JSON,
        placeholder VARCHAR(100),
        FOREIGN KEY (template_id) REFERENCES carte_templates(id) ON DELETE CASCADE
    );");

    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
