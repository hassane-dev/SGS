<?php

class Migration2024011529CreateAffectationsCaissesAndExtendSessions {

    public static function up($db = null) {
        if (!$db) {
            require_once __DIR__ . '/../../src/config/database.php';
            $db = Database::getInstance();
        }
        $isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

        // 1. Table `affectations_caisses`
        if ($isSqlite) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS affectations_caisses (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    lycee_id INTEGER NOT NULL,
                    compte_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    type_affectation TEXT NOT NULL DEFAULT 'titulaire',
                    date_debut TEXT NOT NULL,
                    date_fin TEXT DEFAULT NULL,
                    statut TEXT NOT NULL DEFAULT 'actif',
                    attribue_par INTEGER NOT NULL,
                    motif_remplacement TEXT DEFAULT NULL,
                    cree_le TEXT DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                    FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
                    FOREIGN KEY (attribue_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
                );
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS affectations_caisses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lycee_id INT NOT NULL,
                    compte_id INT NOT NULL,
                    user_id INT NOT NULL,
                    type_affectation ENUM('titulaire', 'remplacant') NOT NULL DEFAULT 'titulaire',
                    date_debut DATE NOT NULL,
                    date_fin DATE DEFAULT NULL,
                    statut ENUM('actif', 'suspendu', 'termine') NOT NULL DEFAULT 'actif',
                    attribue_par INT NOT NULL,
                    motif_remplacement VARCHAR(255) DEFAULT NULL,
                    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                    FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
                    FOREIGN KEY (attribue_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }

        // 2. Extend `sessions_caisse` with handover proof columns
        $columnsToExtend = [
            'fonds_source_session_id' => $isSqlite ? 'INTEGER DEFAULT NULL' : 'INT DEFAULT NULL',
            'fonds_source_user_id' => $isSqlite ? 'INTEGER DEFAULT NULL' : 'INT DEFAULT NULL',
            'prise_en_charge_confirmee' => $isSqlite ? 'INTEGER NOT NULL DEFAULT 0' : 'BOOLEAN NOT NULL DEFAULT FALSE',
            'date_prise_en_charge' => $isSqlite ? 'TEXT DEFAULT NULL' : 'DATETIME DEFAULT NULL'
        ];

        foreach ($columnsToExtend as $col => $def) {
            try {
                if ($isSqlite) {
                    $stmt = $db->query("PRAGMA table_info(sessions_caisse)");
                    $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                    $colExists = false;
                    foreach ($cols as $c) {
                        if ($c['name'] === $col) {
                            $colExists = true;
                            break;
                        }
                    }
                    if (!$colExists) {
                        $db->exec("ALTER TABLE sessions_caisse ADD COLUMN {$col} {$def}");
                    }
                } else {
                    $stmt = $db->query("SHOW COLUMNS FROM `sessions_caisse` LIKE '{$col}'");
                    if (!$stmt || !$stmt->fetch()) {
                        $db->exec("ALTER TABLE sessions_caisse ADD COLUMN {$col} {$def}");
                    }
                }
            } catch (Exception $e) {
                // Column may already exist
            }
        }
    }
}

function migrate_29($db) {
    echo "Running Migration 29: Create affectations_caisses and extend sessions_caisse...\n";
    Migration2024011529CreateAffectationsCaissesAndExtendSessions::up($db);
    echo "Migration 29 completed successfully.\n";
}

// Execute migration directly if invoked as a CLI script
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/../../src/config/database.php';
    migrate_29(Database::getInstance());
}
