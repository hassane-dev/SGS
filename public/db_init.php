<?php
require_once __DIR__ . '/../src/config/database.php';
try {
    $db = Database::getInstance();
    $db->exec("CREATE TABLE IF NOT EXISTS param_lycee (id INT PRIMARY KEY, nom_lycee VARCHAR(255), logo TEXT, header_primary TEXT, header_secondary TEXT, signature_directeur TEXT, tampon_ecole TEXT, type_lycee VARCHAR(50), boutique TINYINT)");
    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee, header_primary, header_secondary) VALUES (1, 'LYCÉE MODERNE', 'REP. DU TCHAD', 'MINISTERE EDUC.')");

    $db->exec("CREATE TABLE IF NOT EXISTS carte_templates (id INT AUTO_INCREMENT PRIMARY KEY, lycee_id INT, nom_modele VARCHAR(255), layout_data JSON, version VARCHAR(10))");

    echo "DB Ready";
} catch (Exception $e) { echo $e->getMessage(); }
