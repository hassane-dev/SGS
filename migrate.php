<?php
require_once __DIR__ . '/src/config/database.php';

$db = Database::getInstance();

function columnExists($db, $table, $column) {
    $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $stmt->fetch() !== false;
}

try {
    // 1. Update param_lycee
    if (!columnExists($db, 'param_lycee', 'header_primary')) {
        $db->exec("ALTER TABLE param_lycee ADD COLUMN header_primary TEXT");
    }
    if (!columnExists($db, 'param_lycee', 'header_secondary')) {
        $db->exec("ALTER TABLE param_lycee ADD COLUMN header_secondary TEXT");
    }
    if (!columnExists($db, 'param_lycee', 'signature_directeur')) {
        $db->exec("ALTER TABLE param_lycee ADD COLUMN signature_directeur TEXT");
    }
    if (!columnExists($db, 'param_lycee', 'tampon_ecole')) {
        $db->exec("ALTER TABLE param_lycee ADD COLUMN tampon_ecole TEXT");
    }

    // 2. Card Templates Migration
    $stmt = $db->query("SHOW TABLES LIKE 'modele_carte'");
    if ($stmt->fetch()) {
        $stmt2 = $db->query("SHOW TABLES LIKE 'carte_templates'");
        if (!$stmt2->fetch()) {
            $db->exec("RENAME TABLE modele_carte TO carte_templates;");

            if (!columnExists($db, 'carte_templates', 'orientation')) {
                 $db->exec("ALTER TABLE carte_templates ADD COLUMN orientation ENUM('landscape', 'portrait') DEFAULT 'landscape'");
            }
            if (!columnExists($db, 'carte_templates', 'width_mm')) {
                $db->exec("ALTER TABLE carte_templates ADD COLUMN width_mm DECIMAL(5,2) DEFAULT 85.60");
            }
            if (!columnExists($db, 'carte_templates', 'height_mm')) {
                $db->exec("ALTER TABLE carte_templates ADD COLUMN height_mm DECIMAL(5,2) DEFAULT 53.98");
            }
            if (columnExists($db, 'carte_templates', 'font_settings') && !columnExists($db, 'carte_templates', 'styles')) {
                 $db->exec("ALTER TABLE carte_templates CHANGE COLUMN font_settings styles JSON");
            }
            if (!columnExists($db, 'carte_templates', 'config_visuelle')) {
                $db->exec("ALTER TABLE carte_templates ADD COLUMN config_visuelle JSON");
            }
            if (!columnExists($db, 'carte_templates', 'version')) {
                $db->exec("ALTER TABLE carte_templates ADD COLUMN version VARCHAR(10) DEFAULT '3.0'");
            }
            echo "Table migration successful\n";
        }
    }

    // 3. Create carte_objects
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

    // 4. Update ENUMs
    $db->exec("ALTER TABLE eleves MODIFY COLUMN statut ENUM('en_attente', 'en_attente_paiement', 'actif', 'transféré', 'radié', 'diplômé', 'abandonné') NOT NULL DEFAULT 'en_attente'");
    $db->exec("ALTER TABLE etudes MODIFY COLUMN status ENUM('en_attente_paiement', 'active', 'inactive', 'suspended') DEFAULT 'en_attente_paiement'");

    // 5. Inscriptions updates
    if (!columnExists($db, 'inscriptions', 'recu_numero')) {
        $db->exec("ALTER TABLE inscriptions ADD COLUMN recu_numero VARCHAR(50)");
    }

    // 6. Mensualite Details updates (Critical for History)
    if (!columnExists($db, 'mensualite_details', 'user_id')) {
        $db->exec("ALTER TABLE mensualite_details ADD COLUMN user_id INT, ADD FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL");
    }
    if (!columnExists($db, 'mensualite_details', 'recu_numero')) {
        $db->exec("ALTER TABLE mensualite_details ADD COLUMN recu_numero VARCHAR(50)");
    }

    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
