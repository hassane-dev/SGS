<?php

require_once __DIR__ . '/../../src/config/database.php';

class Migration2024011530CreateModesPaiementAndVentilations {

    public static function up() {
        $db = Database::getInstance();
        $isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

        // 1. Table `modes_paiement`
        if ($isSqlite) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS modes_paiement (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    lycee_id INTEGER NOT NULL,
                    code TEXT NOT NULL,
                    libelle TEXT NOT NULL,
                    type_canal TEXT NOT NULL DEFAULT 'especes',
                    compte_financier_id INTEGER DEFAULT NULL,
                    exige_session_caisse INTEGER NOT NULL DEFAULT 1,
                    exige_reference_transaction INTEGER NOT NULL DEFAULT 0,
                    actif INTEGER NOT NULL DEFAULT 1,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                    FOREIGN KEY (compte_financier_id) REFERENCES comptes_financiers(id) ON DELETE SET NULL,
                    UNIQUE(lycee_id, code)
                );
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS modes_paiement (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lycee_id INT NOT NULL,
                    code VARCHAR(30) NOT NULL,
                    libelle VARCHAR(100) NOT NULL,
                    type_canal ENUM('especes', 'mobile_money', 'banque', 'autre') NOT NULL DEFAULT 'especes',
                    compte_financier_id INT DEFAULT NULL,
                    exige_session_caisse BOOLEAN NOT NULL DEFAULT TRUE,
                    exige_reference_transaction BOOLEAN NOT NULL DEFAULT FALSE,
                    actif BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                    FOREIGN KEY (compte_financier_id) REFERENCES comptes_financiers(id) ON DELETE SET NULL,
                    UNIQUE KEY uk_lycee_mode_code (lycee_id, code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }

        // 2. Table `paiement_ventilations`
        if ($isSqlite) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS paiement_ventilations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    lycee_id INTEGER NOT NULL,
                    source_type TEXT NOT NULL,
                    source_id INTEGER NOT NULL,
                    mode_paiement_id INTEGER NOT NULL,
                    compte_financier_id INTEGER NOT NULL,
                    session_caisse_id INTEGER DEFAULT NULL,
                    montant REAL NOT NULL,
                    reference_transaction TEXT DEFAULT NULL,
                    mouvement_tresorerie_id INTEGER DEFAULT NULL,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                    FOREIGN KEY (mode_paiement_id) REFERENCES modes_paiement(id) ON DELETE RESTRICT,
                    FOREIGN KEY (compte_financier_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
                    FOREIGN KEY (session_caisse_id) REFERENCES sessions_caisse(id) ON DELETE SET NULL,
                    FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE SET NULL
                );
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS paiement_ventilations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lycee_id INT NOT NULL,
                    source_type VARCHAR(50) NOT NULL,
                    source_id INT NOT NULL,
                    mode_paiement_id INT NOT NULL,
                    compte_financier_id INT NOT NULL,
                    session_caisse_id INT DEFAULT NULL,
                    montant DECIMAL(15, 2) NOT NULL,
                    reference_transaction VARCHAR(150) DEFAULT NULL,
                    mouvement_tresorerie_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                    FOREIGN KEY (mode_paiement_id) REFERENCES modes_paiement(id) ON DELETE RESTRICT,
                    FOREIGN KEY (compte_financier_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
                    FOREIGN KEY (session_caisse_id) REFERENCES sessions_caisse(id) ON DELETE SET NULL,
                    FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE SET NULL,
                    CONSTRAINT chk_ventilation_montant_positif CHECK (montant > 0.00)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }
    }
}

// Auto-run if invoked directly or required
if (class_exists('Database')) {
    try {
        Migration2024011530CreateModesPaiementAndVentilations::up();
    } catch (Exception $e) {
        // Ignore if database connection is not established during static loading
    }
}
