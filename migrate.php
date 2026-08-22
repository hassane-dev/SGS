<?php
require_once __DIR__ . '/src/config/database.php';
$db = Database::getInstance();

$isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

function addColumnIfNeeded($db, $table, $column, $definition) {
    try {
        $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $columnExists = false;

        if ($isSqlite) {
            $stmt = $db->prepare("PRAGMA table_info(`$table`)");
            $stmt->execute();
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                if ($col['name'] === $column) {
                    $columnExists = true;
                    break;
                }
            }
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->fetch()) {
                $columnExists = true;
            }
        }

        if (!$columnExists) {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Added column $column to table $table\n";
        }
    } catch (Exception $e) {
        echo "Error checking/adding column $column on $table: " . $e->getMessage() . "\n";
    }
}

try {
    // Update param_lycee
    addColumnIfNeeded($db, 'param_lycee', 'header_primary', 'TEXT');
    addColumnIfNeeded($db, 'param_lycee', 'header_secondary', 'TEXT');
    addColumnIfNeeded($db, 'param_lycee', 'signature_directeur', 'TEXT');
    addColumnIfNeeded($db, 'param_lycee', 'tampon_ecole', 'TEXT');

    // Update cycles and utilisateurs for Phase 10
    addColumnIfNeeded($db, 'cycles', 'lycee_id', 'INT DEFAULT NULL');
    addColumnIfNeeded($db, 'utilisateurs', 'auth_version', 'INT DEFAULT 1');

    $tableExists = function($db, $table) use ($isSqlite) {
        if ($isSqlite) {
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name");
            $stmt->execute(['name' => $table]);
            return (bool)$stmt->fetch();
        } else {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            return (bool)$stmt->fetch();
        }
    };

    // Rename modele_carte to carte_templates if it exists and carte_templates doesn't
    if ($tableExists($db, 'modele_carte')) {
        if (!$tableExists($db, 'carte_templates')) {
            if ($isSqlite) {
                $db->exec("ALTER TABLE modele_carte RENAME TO carte_templates;");
            } else {
                $db->exec("RENAME TABLE modele_carte TO carte_templates;");
            }
            echo "Renamed table modele_carte to carte_templates\n";
        }
    }

    // Add columns to carte_templates if needed
    addColumnIfNeeded($db, 'carte_templates', 'orientation', "ENUM('landscape', 'portrait') DEFAULT 'landscape'");
    addColumnIfNeeded($db, 'carte_templates', 'width_mm', "DECIMAL(5,2) DEFAULT 85.60");
    addColumnIfNeeded($db, 'carte_templates', 'height_mm', "DECIMAL(5,2) DEFAULT 53.98");
    addColumnIfNeeded($db, 'carte_templates', 'config_visuelle', "JSON");
    addColumnIfNeeded($db, 'carte_templates', 'version', "VARCHAR(10) DEFAULT '2.1'");

    // Rename font_settings to styles if needed
    try {
        $font_settings_exists = false;
        if ($isSqlite) {
            $stmt = $db->prepare("PRAGMA table_info(carte_templates)");
            $stmt->execute();
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                if ($col['name'] === 'font_settings') {
                    $font_settings_exists = true;
                    break;
                }
            }
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM carte_templates LIKE 'font_settings'");
            if ($stmt->fetch()) {
                $font_settings_exists = true;
            }
        }

        if ($font_settings_exists) {
            $db->exec("ALTER TABLE carte_templates RENAME COLUMN font_settings TO styles;");
            echo "Renamed font_settings to styles on carte_templates\n";
        }
    } catch (Exception $e) {
        // Safe to ignore if already renamed or column doesn't exist
    }

    // Create Table Helper Function to avoid syntax crashes on SQLite
    $createTable = function($db, $tableName, $mysqlSql, $sqliteSql) use ($isSqlite, $tableExists) {
        if ($tableExists($db, $tableName)) {
            return;
        }
        if ($isSqlite) {
            $db->exec($sqliteSql);
        } else {
            $db->exec($mysqlSql);
        }
        echo "Table '$tableName' created.\n";
    };

    // Create carte_objects if it doesn't exist
    $createTable($db, 'carte_objects', "
        CREATE TABLE IF NOT EXISTS carte_objects (
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
        );
    ", "
        CREATE TABLE carte_objects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            template_id INTEGER NOT NULL,
            type_objet VARCHAR(50) NOT NULL,
            pos_x INTEGER,
            pos_y INTEGER,
            width INTEGER,
            height INTEGER,
            z_index INTEGER DEFAULT 0,
            styles TEXT,
            placeholder VARCHAR(100),
            FOREIGN KEY (template_id) REFERENCES carte_templates(id) ON DELETE CASCADE
        );
    ");

    if (!$isSqlite) {
        // Update ENUM for eleves.statut and etudes.status (Skip on SQLite)
        $db->exec("ALTER TABLE eleves MODIFY COLUMN statut ENUM('en_attente', 'en_attente_paiement', 'actif', 'transféré', 'radié', 'diplômé', 'abandonné') NOT NULL DEFAULT 'en_attente'");
        $db->exec("ALTER TABLE etudes MODIFY COLUMN status ENUM('en_attente_paiement', 'active', 'inactive', 'suspended') DEFAULT 'en_attente_paiement'");
    }

    // Add columns to other tables
    addColumnIfNeeded($db, 'inscriptions', 'recu_numero', 'VARCHAR(50)');
    addColumnIfNeeded($db, 'mensualites', 'reste_a_payer', 'DECIMAL(10, 2) DEFAULT 0.00');
    addColumnIfNeeded($db, 'inscriptions', 'statut', "ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide'");
    addColumnIfNeeded($db, 'mensualite_details', 'statut', "ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide'");
    addColumnIfNeeded($db, 'annees_academiques', 'cloturee', 'TINYINT(1) NOT NULL DEFAULT 0');

    // Create journal_comptable table
    $createTable($db, 'journal_comptable', "
        CREATE TABLE IF NOT EXISTS journal_comptable (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            eleve_id INT DEFAULT NULL,
            user_id INT NOT NULL,
            annee_academique_id INT NOT NULL,
            operation VARCHAR(100) NOT NULL,
            montant DECIMAL(10, 2) NOT NULL,
            mode_paiement VARCHAR(50) DEFAULT NULL,
            recu_numero VARCHAR(50) DEFAULT NULL,
            reference_origine VARCHAR(100) DEFAULT NULL,
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
            FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE CASCADE,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE journal_comptable (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            eleve_id INTEGER DEFAULT NULL,
            user_id INTEGER NOT NULL,
            annee_academique_id INTEGER NOT NULL,
            operation VARCHAR(100) NOT NULL,
            montant DECIMAL(10, 2) NOT NULL,
            mode_paiement VARCHAR(50) DEFAULT NULL,
            recu_numero VARCHAR(50) DEFAULT NULL,
            reference_origine VARCHAR(100) DEFAULT NULL,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
            FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE CASCADE,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
        );
    ");

    // Create deblocages_notes table
    $createTable($db, 'deblocages_notes', "
        CREATE TABLE IF NOT EXISTS `deblocages_notes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `lycee_id` INT NOT NULL,
            `annee_academique_id` INT NOT NULL,
            `type` ENUM('global', 'classe', 'matiere', 'classe_matiere', 'enseignant') NOT NULL,
            `classe_id` INT DEFAULT NULL,
            `matiere_id` INT DEFAULT NULL,
            `enseignant_id` INT DEFAULT NULL,
            `sequence_id` INT DEFAULT NULL,
            `date_debut` DATETIME NOT NULL,
            `date_fin` DATETIME NOT NULL,
            `motif` TEXT,
            `cree_par` INT,
            `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
            FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
            FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
            FOREIGN KEY (`sequence_id`) REFERENCES `sequences`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`cree_par`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE deblocages_notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            annee_academique_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            classe_id INTEGER DEFAULT NULL,
            matiere_id INTEGER DEFAULT NULL,
            enseignant_id INTEGER DEFAULT NULL,
            sequence_id INTEGER DEFAULT NULL,
            date_debut DATETIME NOT NULL,
            date_fin DATETIME NOT NULL,
            motif TEXT,
            cree_par INTEGER,
            cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE CASCADE,
            FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE CASCADE,
            FOREIGN KEY (matiere_id) REFERENCES matieres(id_matiere) ON DELETE CASCADE,
            FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
            FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE,
            FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        );
    ");

    // Add identifiant_public columns
    addColumnIfNeeded($db, 'eleves', 'identifiant_public', 'VARCHAR(50) DEFAULT NULL');
    try {
        $db->exec("ALTER TABLE eleves ADD UNIQUE (identifiant_public);");
    } catch (Exception $e) {
        // Safe if unique key already exists
    }

    addColumnIfNeeded($db, 'utilisateurs', 'identifiant_public', 'VARCHAR(50) DEFAULT NULL');
    try {
        $db->exec("ALTER TABLE utilisateurs ADD UNIQUE (identifiant_public);");
    } catch (Exception $e) {
        // Safe if unique key already exists
    }

    // Retroactive generation for existing students (eleves)
    $stmt = $db->query("SELECT id_eleve, (SELECT date_activation FROM etudes WHERE eleve_id = id_eleve LIMIT 1) as date_activation FROM eleves WHERE identifiant_public IS NULL ORDER BY id_eleve ASC");
    $eleves_without_id = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($stmt) $stmt->closeCursor();

    if (!empty($eleves_without_id)) {
        $stmt_counter = $db->query("SELECT identifiant_public FROM eleves WHERE identifiant_public LIKE '%E' ORDER BY id_eleve DESC LIMIT 1");
        $last_student_public_id = $stmt_counter ? $stmt_counter->fetchColumn() : null;
        if ($stmt_counter) $stmt_counter->closeCursor();
        $student_counter = 1;
        if ($last_student_public_id && preg_match('/-(\d+)E$/', $last_student_public_id, $matches)) {
            $student_counter = (int)$matches[1] + 1;
        }

        $update_student_stmt = $db->prepare("UPDATE eleves SET identifiant_public = :identifiant_public WHERE id_eleve = :id_eleve");
        foreach ($eleves_without_id as $e) {
            $enrollDate = !empty($e['date_activation']) ? $e['date_activation'] : date('Y-m-d');
            $dateStr = date('dmY', strtotime($enrollDate));
            $paddedCounter = str_pad($student_counter, 4, '0', STR_PAD_LEFT);
            $identifiant = $dateStr . '-' . $paddedCounter . 'E';

            $update_student_stmt->execute([
                'identifiant_public' => $identifiant,
                'id_eleve' => $e['id_eleve']
            ]);
            $student_counter++;
        }
    }

    // Retroactive generation for existing staff (utilisateurs)
    $stmt = $db->query("SELECT id_user, role_id FROM utilisateurs WHERE identifiant_public IS NULL ORDER BY id_user ASC");
    $users_without_id = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($stmt) $stmt->closeCursor();

    if (!empty($users_without_id)) {
        $stmt_counter = $db->query("SELECT identifiant_public FROM utilisateurs WHERE identifiant_public IS NOT NULL ORDER BY id_user DESC LIMIT 1");
        $last_user_public_id = $stmt_counter ? $stmt_counter->fetchColumn() : null;
        if ($stmt_counter) $stmt_counter->closeCursor();
        $user_counter = 1;
        if ($last_user_public_id && preg_match('/^(\d+)/', $last_user_public_id, $matches)) {
            $user_counter = (int)$matches[1] + 1;
        }

        $update_user_stmt = $db->prepare("UPDATE utilisateurs SET identifiant_public = :identifiant_public WHERE id_user = :id_user");
        foreach ($users_without_id as $u) {
            $role_name = '';
            if (!empty($u['role_id'])) {
                $stmt_role = $db->prepare("SELECT nom_role FROM roles WHERE id_role = :id");
                $stmt_role->execute(['id' => $u['role_id']]);
                $role_name = $stmt_role->fetchColumn() ?: '';
            }
            $role_name = strtolower($role_name);

            if (strpos($role_name, 'enseignant') !== false) {
                $suffix = 'ENS';
            } elseif (strpos($role_name, 'comptable') !== false) {
                $suffix = 'COM';
            } elseif (strpos($role_name, 'surveillant') !== false) {
                $suffix = 'SUR';
            } elseif (strpos($role_name, 'proviseur') !== false || strpos($role_name, 'censeur') !== false || strpos($role_name, 'directeur') !== false) {
                $suffix = 'DIR';
            } else {
                $suffix = 'ADM';
            }

            $paddedCounter = str_pad($user_counter, 4, '0', STR_PAD_LEFT);
            $identifiant = $paddedCounter . $suffix;

            $update_user_stmt->execute([
                'identifiant_public' => $identifiant,
                'id_user' => $u['id_user']
            ]);
            $user_counter++;
        }
    }

    // Create parametres_utilisateurs table
    $createTable($db, 'parametres_utilisateurs', "
        CREATE TABLE IF NOT EXISTS parametres_utilisateurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            lycee_id INT DEFAULT NULL,
            signature TEXT DEFAULT NULL,
            cachet TEXT DEFAULT NULL,
            langue_preferee VARCHAR(10) DEFAULT 'fr_FR',
            theme_prefere VARCHAR(50) DEFAULT 'light',
            notifications_actives TINYINT(1) DEFAULT 1,
            date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE parametres_utilisateurs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            lycee_id INTEGER,
            signature TEXT,
            cachet TEXT,
            langue_preferee VARCHAR(10) DEFAULT 'fr_FR',
            theme_prefere VARCHAR(50) DEFAULT 'light',
            notifications_actives BOOLEAN DEFAULT 1,
            date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE SET NULL
        );
    ");

    // --- PHASE 1 FINANCIAL TABLES ---

    // Create exercices_financiers table
    $createTable($db, 'exercices_financiers', "
        CREATE TABLE IF NOT EXISTS exercices_financiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            libelle VARCHAR(100) NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            est_actif BOOLEAN NOT NULL DEFAULT FALSE,
            cloture BOOLEAN NOT NULL DEFAULT FALSE,
            type_exercice ENUM('normal', 'historique_transition') NOT NULL DEFAULT 'normal',
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            CONSTRAINT chk_dates_exercice CHECK (date_fin >= date_debut)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE exercices_financiers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            libelle VARCHAR(100) NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            est_actif BOOLEAN NOT NULL DEFAULT FALSE,
            cloture BOOLEAN NOT NULL DEFAULT FALSE,
            type_exercice VARCHAR(50) NOT NULL DEFAULT 'normal',
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            CONSTRAINT chk_dates_exercice CHECK (date_fin >= date_debut)
        );
    ");

    // Create comptes_financiers table
    $createTable($db, 'comptes_financiers', "
        CREATE TABLE IF NOT EXISTS comptes_financiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            nom_compte VARCHAR(150) NOT NULL,
            type_compte ENUM('caisse', 'banque', 'mobile_money', 'autre') NOT NULL,
            solde_courant DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
            responsable_id INT DEFAULT NULL,
            statut ENUM('actif', 'suspendu') NOT NULL DEFAULT 'actif',
            cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE comptes_financiers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            nom_compte VARCHAR(150) NOT NULL,
            type_compte VARCHAR(50) NOT NULL,
            solde_courant DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
            responsable_id INTEGER,
            statut VARCHAR(50) NOT NULL DEFAULT 'actif',
            cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        );
    ");

    // Create sessions_caisse table with the stored generated column for active session uniqueness
    $createTable($db, 'sessions_caisse', "
        CREATE TABLE IF NOT EXISTS sessions_caisse (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            user_id INT NOT NULL,
            compte_id INT NOT NULL,
            date_ouverture DATETIME NOT NULL,
            date_fermeture DATETIME DEFAULT NULL,
            solde_ouverture DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            solde_theorique DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            solde_reel DECIMAL(15, 2) DEFAULT NULL,
            ecart DECIMAL(15, 2) DEFAULT 0.00,
            justificatif_ecart TEXT DEFAULT NULL,
            statut ENUM('ouverte', 'fermee_a_valider', 'fermee_validee') NOT NULL DEFAULT 'ouverte',
            valide_par INT DEFAULT NULL,
            valide_le DATETIME DEFAULT NULL,
            is_active TINYINT GENERATED ALWAYS AS (
                CASE WHEN statut IN ('ouverte', 'fermee_a_valider') THEN 1 ELSE NULL END
            ) STORED,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            UNIQUE KEY unique_active_session_per_account (compte_id, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE sessions_caisse (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            compte_id INTEGER NOT NULL,
            date_ouverture DATETIME NOT NULL,
            date_fermeture DATETIME,
            solde_ouverture DECIMAL(15,2) DEFAULT 0.00,
            solde_theorique DECIMAL(15,2) DEFAULT 0.00,
            solde_reel DECIMAL(15,2),
            ecart DECIMAL(15,2) DEFAULT 0.00,
            justificatif_ecart TEXT,
            statut VARCHAR(50) DEFAULT 'ouverte',
            valide_par INTEGER,
            valide_le DATETIME,
            is_active TINYINT GENERATED ALWAYS AS (
                CASE WHEN statut IN ('ouverte', 'fermee_a_valider') THEN 1 ELSE NULL END
            ) STORED,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            UNIQUE (compte_id, is_active)
        );
    ");

    // Create transferts_financiers table
    $createTable($db, 'transferts_financiers', "
        CREATE TABLE IF NOT EXISTS transferts_financiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            compte_source_id INT NOT NULL,
            compte_destination_id INT NOT NULL,
            montant DECIMAL(15, 2) NOT NULL,
            motif VARCHAR(255) NOT NULL,
            statut ENUM('demande', 'autorise', 'complete', 'rejete') NOT NULL DEFAULT 'demande',
            demande_par INT NOT NULL,
            autorise_par INT DEFAULT NULL,
            date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_execution DATETIME DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (compte_source_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (compte_destination_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (demande_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (autorise_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            CONSTRAINT chk_montant_transfert CHECK (montant > 0.00),
            CONSTRAINT chk_different_accounts CHECK (compte_source_id <> compte_destination_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE transferts_financiers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            compte_source_id INTEGER NOT NULL,
            compte_destination_id INTEGER NOT NULL,
            montant DECIMAL(15,2),
            motif VARCHAR(255),
            statut VARCHAR(50) DEFAULT 'demande',
            demande_par INTEGER,
            autorise_par INTEGER,
            date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_execution DATETIME
        );
    ");

    // Create mouvements_tresorerie table with the composite unique index
    $createTable($db, 'mouvements_tresorerie', "
        CREATE TABLE IF NOT EXISTS mouvements_tresorerie (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            compte_id INT NOT NULL,
            session_caisse_id INT DEFAULT NULL,
            exercice_financier_id INT NOT NULL,
            transfert_id INT DEFAULT NULL,
            type_mouvement ENUM('entree', 'sortie') NOT NULL,
            montant DECIMAL(15, 2) NOT NULL,
            mode_paiement VARCHAR(50) NOT NULL,
            reference_transaction VARCHAR(150) DEFAULT NULL,
            source_type VARCHAR(100) NOT NULL,
            source_id INT NOT NULL,
            evenement_type ENUM('encaissement', 'annulation', 'remboursement', 'correction', 'remise_coffre_sortie', 'remise_coffre_entree', 'reglement_fournisseur') NOT NULL,
            motif VARCHAR(255) NOT NULL,
            date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INT NOT NULL,
            is_aggregate_data BOOLEAN NOT NULL DEFAULT FALSE,
            date_reconstruite BOOLEAN NOT NULL DEFAULT FALSE,
            is_historical_migration BOOLEAN NOT NULL DEFAULT FALSE,
            mode_paiement_reconstruit BOOLEAN NOT NULL DEFAULT FALSE,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (session_caisse_id) REFERENCES sessions_caisse(id) ON DELETE SET NULL,
            FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (transfert_id) REFERENCES transferts_financiers(id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            UNIQUE KEY unique_idempotence_flux (compte_id, source_type, source_id, evenement_type),
            CONSTRAINT chk_montant_positif CHECK (montant > 0.00)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE mouvements_tresorerie (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            compte_id INTEGER NOT NULL,
            session_caisse_id INTEGER,
            exercice_financier_id INTEGER,
            transfert_id INTEGER,
            type_mouvement VARCHAR(50),
            montant DECIMAL(15,2),
            mode_paiement VARCHAR(50),
            reference_transaction VARCHAR(150),
            source_type VARCHAR(100),
            source_id INTEGER,
            evenement_type VARCHAR(50),
            motif VARCHAR(255),
            date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INTEGER,
            is_aggregate_data BOOLEAN DEFAULT 0,
            date_reconstruite BOOLEAN DEFAULT 0,
            is_historical_migration BOOLEAN DEFAULT 0,
            mode_paiement_reconstruit BOOLEAN DEFAULT 0,
            UNIQUE (compte_id, source_type, source_id, evenement_type)
        );
    ");

    // Create regularisations_ecarts table
    $createTable($db, 'regularisations_ecarts', "
        CREATE TABLE IF NOT EXISTS regularisations_ecarts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            session_caisse_id INT NOT NULL,
            montant DECIMAL(15, 2) NOT NULL,
            type_ecart ENUM('negatif', 'positif') NOT NULL,
            motif TEXT NOT NULL,
            constate_par INT NOT NULL,
            approuve_par INT NOT NULL,
            date_constat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reference_audit VARCHAR(100) NOT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (session_caisse_id) REFERENCES sessions_caisse(id) ON DELETE RESTRICT,
            FOREIGN KEY (constate_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (approuve_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            UNIQUE KEY unique_audit_ref (reference_audit),
            CONSTRAINT chk_montant_ecart CHECK (montant > 0.00)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE regularisations_ecarts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            session_caisse_id INTEGER NOT NULL,
            montant DECIMAL(15,2),
            type_ecart VARCHAR(50),
            motif TEXT,
            constate_par INTEGER,
            approuve_par INTEGER,
            date_constat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reference_audit VARCHAR(100),
            UNIQUE (reference_audit)
        );
    ");

    // Provision core default roles if not present
    $default_roles = [
        [1, 'super_admin_createur'],
        [2, 'super_admin_national'],
        [3, 'admin_local'],
        [4, 'censeur'],
        [5, 'surveillant'],
        [6, 'enseignant'],
        [7, 'comptable'],
        [8, 'eleve'],
        [9, 'chef_comptable'],
        [10, 'caissier'],
        [11, 'drh'],
    ];

    foreach ($default_roles as $r_def) {
        $stmt_r = $db->prepare("SELECT id_role FROM roles WHERE id_role = :id");
        $stmt_r->execute(['id' => $r_def[0]]);
        if ($stmt_r->fetch()) {
            $stmt_up_r = $db->prepare("UPDATE roles SET nom_role = :nom WHERE id_role = :id");
            $stmt_up_r->execute(['id' => $r_def[0], 'nom' => $r_def[1]]);
        } else {
            $stmt_ins_r = $db->prepare("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (:id, :nom, NULL)");
            $stmt_ins_r->execute(['id' => $r_def[0], 'nom' => $r_def[1]]);
        }
    }

    // Seed permissions into the database dynamically
    $new_perms = [
        ['dashboard', 'view', 'Consulter le tableau de bord principal'],
        ['role', 'view_all', 'Consulter la liste des rôles'],
        ['comptabilite', 'view', 'Consulter les exercices financiers et périodes comptables'],
        ['comptabilite', 'create', 'Créer des exercices financiers et générer des périodes comptables'],
        ['comptabilite', 'edit', 'Modifier ou activer des exercices financiers et périodes comptables'],
        ['comptabilite', 'close', 'Clôturer un exercice financier ou une période comptable'],
        ['comptabilite', 'reopen', 'Réouvrir une période comptable clôturée'],
        ['comptes_financiers', 'view', 'Consulter la liste des comptes financiers et leurs soldes'],
        ['comptes_financiers', 'create', 'Créer de nouveaux comptes financiers'],
        ['comptes_financiers', 'edit', 'Modifier les propriétés d\'un compte financier'],
        ['comptes_financiers', 'manage', 'Suspendre ou réactiver un compte financier'],
        ['comptes_comptables', 'view', 'Consulter le plan de comptes comptables general'],
        ['comptes_comptables', 'create', 'Créer un compte comptable'],
        ['comptes_comptables', 'edit', 'Modifier un compte comptable'],
        ['comptes_comptables', 'delete', 'Supprimer ou désactiver un compte comptable'],
        ['sessions_caisse', 'view', 'Consulter la liste et le détail des sessions de caisse'],
        ['sessions_caisse', 'create', 'Ouvrir une session de caisse physique journalière'],
        ['sessions_caisse', 'edit', 'Fermer sa propre session de caisse'],
        ['sessions_caisse', 'modify', 'Modifier une session de caisse'],
        ['sessions_caisse', 'validate', 'Valider la clôture d\'une session de caisse et régulariser les écarts'],
        ['sessions_caisse', 'reopen', 'Réouvrir exceptionnellement une caisse fermée (Permission sensible d\'audit)'],
        ['mouvements_tresorerie', 'view', 'Consulter le grand livre de trésorerie'],
        ['mouvements_tresorerie', 'create', 'Enregistrer un mouvement de trésorerie manuel'],
        ['transferts', 'create', 'Initier une demande de virement inter-comptes'],
        ['transferts', 'validate', 'Approuver et exécuter un transfert de fonds'],
        ['finance', 'view_policy', 'Consulter la politique financière du lycée'],
        ['finance', 'edit_policy', 'Modifier la politique financière du lycée'],
        ['finance', 'view_control', 'Consulter le panneau de contrôle financier'],
        ['finance', 'view_reports', 'Consulter les rapports financiers'],
        ['journal', 'view', 'Consulter le journal comptable unique'],
        ['grand_livre', 'view', 'Consulter le grand livre comptable'],
        ['grand_livre', 'export', 'Exporter le grand livre comptable'],
        ['balance', 'view', 'Consulter la balance générale des comptes'],
        ['balance', 'export', 'Exporter la balance générale des comptes']
    ];

    if ($isSqlite) {
        $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
    } else {
        $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
    }

    foreach ($new_perms as $perm) {
        $stmt_ins_perm->execute([
            'resource' => $perm[0],
            'action' => $perm[1],
            'description' => $perm[2]
        ]);
    }

    // --- PHASE 3 EXPENSE MANAGEMENT ---
    require_once __DIR__ . '/db/migrations/20240115_01_create_referentiels_depenses.php';
    require_once __DIR__ . '/db/migrations/20240115_02_create_depenses_principale.php';
    require_once __DIR__ . '/db/migrations/20240115_03_create_historiques_et_logs_depenses.php';

    migrate_01($db);
    migrate_02($db);
    migrate_03($db);

    // Seed permissions for Phase 3
    $phase3_perms = [
        ['depense', 'create', 'Créer une demande d\'engagement de dépense (brouillon)'],
        ['depense', 'update', 'Modifier une demande de dépense non approuvée'],
        ['depense', 'validate', 'Voter pour ou approuver une demande de dépense'],
        ['depense', 'reject', 'Rejeter une demande de dépense'],
        ['depense', 'pay', 'Exécuter le règlement financier d\'une dépense approuvée'],
        ['depense', 'cancel', 'Annuler une dépense payée avec contre-passation'],
        ['depense', 'view', 'Consulter la liste et l\'historique d\'audit des dépenses'],
        ['depense', 'export', 'Exporter le registre des dépenses'],
        ['depense', 'manage', 'Gérer les catégories, centres de coûts et bénéficiaires de dépenses']
    ];

    foreach ($phase3_perms as $perm) {
        $stmt_ins_perm->execute([
            'resource' => $perm[0],
            'action' => $perm[1],
            'description' => $perm[2]
        ]);
    }

    // --- PHASE 4 BUDGET MANAGEMENT ---
    require_once __DIR__ . '/db/migrations/20240115_04_create_budget_tables.php';
    migrate_04($db);

    // Seed permissions for Phase 4
    $phase4_perms = [
        ['budget', 'view', 'Consulter la liste des budgets et lignes budgétaires'],
        ['budget', 'create', 'Configurer un nouveau budget annuel pour l\'établissement'],
        ['budget', 'update', 'Modifier ou ajouter des lignes de budget'],
        ['budget', 'delete', 'Supprimer un budget non approuvé'],
        ['budget', 'activate', 'Valider et activer officiellement un budget annuel'],
        ['budget', 'close', 'Clôturer un exercice budgétaire'],
        ['budget', 'adjust', 'Ajouter une allocation supplémentaire d\'urgence sur une ligne'],
        ['budget', 'transfer', 'Effectuer un virement de crédits entre deux lignes budgétaires'],
        ['budget', 'report', 'Consulter la synthèse visuelle et graphiques d\'exécution'],
        ['budget', 'override', 'Autoriser le dépassement budgétaire exceptionnel']
    ];

    foreach ($phase4_perms as $perm) {
        $stmt_ins_perm->execute([
            'resource' => $perm[0],
            'action' => $perm[1],
            'description' => $perm[2]
        ]);
    }

    // --- PHASE 5 COMPTABILITE GENERALE ---
    require_once __DIR__ . '/db/migrations/20240115_05_create_comptabilite_generale.php';
    migrate_05($db);

    require_once __DIR__ . '/src/services/ComptabiliteService.php';
    ComptabiliteService::seedDefaultChartOfAccounts();
    ComptabiliteService::seedDefaultSchemas();
    $stmt_lycees = $db->query("SELECT id FROM param_lycee");
    if ($stmt_lycees) {
        while ($row_lycee = $stmt_lycees->fetch(PDO::FETCH_ASSOC)) {
            ComptabiliteService::seedDefaultJournalsForLycee($row_lycee['id']);
        }
        $stmt_lycees->closeCursor();
    }

    $insert_ignore_keyword = $isSqlite ? 'INSERT OR IGNORE' : 'INSERT IGNORE';

    // --- MAP PHASE 3 & 4 PERMISSIONS TO ROLES ---
    // Assign Phase 3 permissions to Super Admins (1, 2), Admin Local (3), Chef Comptable (9)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.id_role IN (1, 2, 3, 9)
        AND p.resource = 'depense'
    ");

    // Assign view, export, and pay of depenses to Comptable (7)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT 7, p.id_permission
        FROM permissions p
        WHERE p.resource = 'depense' AND p.action IN ('view', 'export', 'pay')
    ");

    // Assign Phase 4 permissions to Super Admins (1, 2), Admin Local (3), Chef Comptable (9)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.id_role IN (1, 2, 3, 9)
        AND p.resource = 'budget'
    ");

    // Assign view and report of budgets to Comptable (7)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT 7, p.id_permission
        FROM permissions p
        WHERE p.resource = 'budget' AND p.action IN ('view', 'report')
    ");

    // Map Phase 1 & 2 (comptes_financiers, sessions_caisse, finance, journal, comptabilite) permissions
    // 1. All permissions to Super Admins (1, 2), Local Admin (3), Chef Comptable (9)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE (r.id_role IN (1, 2, 3, 9) OR r.nom_role IN ('super_admin_createur', 'super_admin_national', 'admin_local', 'chef_comptable'))
        AND p.resource IN ('comptes_financiers', 'comptes_comptables', 'sessions_caisse', 'finance', 'journal', 'grand_livre', 'balance', 'comptabilite')
    ");

    // 2. Specific permissions to Comptable (7) and Caissier (10)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE (r.id_role IN (7, 10) OR r.nom_role IN ('comptable', 'caissier'))
        AND (
            (p.resource = 'comptes_financiers' AND p.action = 'view') OR
            (p.resource = 'comptes_comptables' AND p.action IN ('view', 'create', 'edit')) OR
            (p.resource = 'sessions_caisse' AND p.action IN ('view', 'create', 'edit')) OR
            (p.resource = 'finance' AND p.action IN ('view_policy', 'view_control', 'view_reports')) OR
            (p.resource = 'journal' AND p.action = 'view') OR
            (p.resource IN ('grand_livre', 'balance') AND p.action = 'view') OR
            (p.resource = 'comptabilite' AND p.action IN ('view', 'create', 'edit') AND (r.id_role = 7 OR r.nom_role = 'comptable'))
        )
    ");

    // --- PHASE 6 REMISE AU COFFRE ---
    if (!$isSqlite) {
        $db->exec("ALTER TABLE mouvements_tresorerie MODIFY COLUMN evenement_type ENUM('encaissement', 'annulation', 'remboursement', 'correction', 'remise_coffre_sortie', 'remise_coffre_entree', 'reglement_fournisseur') NOT NULL");
    }
    addColumnIfNeeded($db, 'comptes_financiers', 'est_coffre', 'TINYINT DEFAULT 0');
    addColumnIfNeeded($db, 'comptes_financiers', 'compte_comptable_numero', 'VARCHAR(20) DEFAULT NULL');
    addColumnIfNeeded($db, 'comptes_financiers', 'compte_comptable_id', 'INTEGER DEFAULT NULL');

    // Populate compte_comptable_id on comptes_financiers where missing
    try {
        ComptabiliteService::seedDefaultChartOfAccounts();
        $stmt_cf = $db->query("SELECT id, compte_comptable_numero FROM comptes_financiers WHERE compte_comptable_id IS NULL AND compte_comptable_numero IS NOT NULL AND TRIM(compte_comptable_numero) != ''");
        if ($stmt_cf) {
            $cfRows = $stmt_cf->fetchAll(PDO::FETCH_ASSOC);
            $stmt_cf->closeCursor();

            foreach ($cfRows as $cf) {
                $num = trim($cf['compte_comptable_numero']);
                $stmt_cc = $db->prepare("SELECT id FROM comptes_comptables WHERE TRIM(numero) = :num");
                $stmt_cc->execute(['num' => $num]);
                $ccId = $stmt_cc->fetchColumn();
                if ($ccId) {
                    $stmt_up = $db->prepare("UPDATE comptes_financiers SET compte_comptable_id = :cc_id WHERE id = :id");
                    $stmt_up->execute(['cc_id' => $ccId, 'id' => $cf['id']]);
                } else {
                    echo "Notice: Compte financier #{$cf['id']} has unmapped compte_comptable_numero '{$num}'.\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "Notice mapping comptes_financiers: " . $e->getMessage() . "\n";
    }

    addColumnIfNeeded($db, 'sessions_caisse', 'montant_remis', 'DECIMAL(15,2) DEFAULT NULL');
    addColumnIfNeeded($db, 'sessions_caisse', 'fonds_caisse_conserve', 'DECIMAL(15,2) DEFAULT NULL');

    // Idempotent Index Creation
    try {
        if ($isSqlite) {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_comptes_coffre ON comptes_financiers (lycee_id, est_coffre);");
        } else {
            // Check if index already exists for MySQL
            $idx_stmt = $db->query("SHOW INDEX FROM comptes_financiers WHERE Key_name = 'idx_comptes_coffre'");
            if (!$idx_stmt->fetch()) {
                $db->exec("ALTER TABLE comptes_financiers ADD INDEX idx_comptes_coffre (lycee_id, est_coffre);");
            }
        }
        echo "Index idx_comptes_coffre checked/created.\n";
    } catch (Exception $e) {
        echo "Error checking/creating index: " . $e->getMessage() . "\n";
    }

    // --- PHASE 7 ACHATS ET FOURNISSEURS ---
    require_once __DIR__ . '/db/migrations/20240115_07_create_achats_et_fournisseurs.php';
    migrate_07($db);

    // --- PHASE 9 REPORTING DÉCISIONNEL ---
    require_once __DIR__ . '/db/migrations/20240115_09_create_reporting_decisionnel.php';
    migrate_09($db);

    // --- PHASE 10 ISOLATION PAR CYCLE & SCOPES ---
    require_once __DIR__ . '/db/migrations/20240115_10_create_personnel_cycles.php';
    migrate_10($db);

    // --- PHASE DRH - DIRECTION DES RESSOURCES HUMAINES ---
    require_once __DIR__ . '/db/migrations/20240115_11_create_drh_module.php';
    migrate_11($db);

    // --- PHASE DRH LOT 1 - CONSOLIDATION CONTRATS & INTERNATIONALISATION ---
    require_once __DIR__ . '/db/migrations/20240115_12_create_lot1_drh_contrats.php';
    migrate_12($db);

    // --- PHASE LOT 2.1 - INTERNATIONALIZED PAYROLL ENGINE ---
    require_once __DIR__ . '/db/migrations/20240115_13_create_lot2_paie_engine.php';
    migrate_13($db);

    // --- PHASE LOT 2.1 - REGULARISATIONS UPDATE & INTEGRATIONS ---
    require_once __DIR__ . '/db/migrations/20240115_14_update_paie_regularisations.php';
    migrate_14($db);

    // Seed permissions for DRH
    $drh_perms = [
        ['drh', 'view_all', 'Consulter le registre général du personnel RH'],
        ['drh', 'view_one', 'Consulter la fiche 360° d\'un membre du personnel'],
        ['drh', 'create', 'Créer un nouveau membre du personnel'],
        ['drh', 'edit', 'Modifier les informations du personnel'],
        ['drh', 'delete', 'Archiver ou réactiver un compte personnel'],
        ['drh', 'manage_affectations', 'Gérer les affectations par lycée et par cycle'],
        ['drh', 'manage_contrats', 'Gérer les contrats et éléments contractuels de rémunération'],
        ['drh', 'view_contracts', 'Consulter les contrats du personnel'],
        ['drh', 'create_contracts', 'Créer un contrat initial pour le personnel'],
        ['drh', 'create_amendments', 'Établir un avenant ou une nouvelle version contractuelle'],
        ['drh', 'view_contract_history', 'Consulter l\'historique des versions et avenants de contrat'],
        ['drh', 'view_contract_documents', 'Consulter les pièces jointes contractuelles'],
        ['drh', 'manage_statut', 'Gérer les statuts RH (congés, suspensions, départs)'],
        ['drh', 'manage_documents', 'Téléverser et gérer les pièces jointes du personnel'],
        ['drh', 'view_sensitive', 'Consulter les données RH confidentielles (CNSS, contrats, salaires)'],
        ['drh', 'export', 'Exporter le registre et les rapports du personnel'],
        ['drh', 'view_history', 'Consulter l\'historique d\'audit des mouvements RH']
    ];

    foreach ($drh_perms as $perm) {
        $stmt_ins_perm->execute([
            'resource' => $perm[0],
            'action' => $perm[1],
            'description' => $perm[2]
        ]);
    }

    // Assign all permissions in the system to Super Admins (1, 2)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.id_role IN (1, 2)
    ");

    // Assign full DRH permissions + role:view_all + dashboard:view to DRH role (11 / 'drh')
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.nom_role = 'drh'
        AND (
            p.resource = 'drh'
            OR (p.resource = 'role' AND p.action = 'view_all')
            OR (p.resource = 'dashboard' AND p.action = 'view')
        )
    ");

    // Assign basic operational DRH permissions to Admin Local (3) - strictly excluding sensitive actions
    $db->exec("
        DELETE FROM role_permissions
        WHERE role_id = 3 AND permission_id IN (
            SELECT id_permission FROM permissions
            WHERE resource = 'drh' AND action IN ('view_sensitive', 'manage_documents', 'manage_contrats', 'manage_statut', 'delete')
        )
    ");

    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT 3, p.id_permission
        FROM permissions p
        WHERE p.resource = 'drh' AND p.action IN ('view_all', 'view_one', 'create', 'edit', 'manage_affectations', 'export', 'view_history')
    ");

    // Assign view_all, view_one, view_history to Censeur (4)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT 4, p.id_permission
        FROM permissions p
        WHERE p.resource = 'drh' AND p.action IN ('view_all', 'view_one', 'view_history')
    ");

    // Assign view_all, view_one, view_history, view_sensitive, manage_contrats to Chef Comptable (9)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT 9, p.id_permission
        FROM permissions p
        WHERE p.resource = 'drh' AND p.action IN ('view_all', 'view_one', 'view_history', 'view_sensitive', 'manage_contrats')
    ");

    // Seed permissions for Lot 2.1 Paie Module
    $paie_perms = [
        ['paie', 'view', 'Consulter les périodes, bulletins et registres de paie'],
        ['paie', 'create', 'Créer une nouvelle période de paie ou importer les salaires'],
        ['paie', 'calculate', 'Lancer le calcul automatisé des bulletins de paie'],
        ['paie', 'validate', 'Valider les bulletins de paie et heures pédagogiques'],
        ['paie', 'redraw', 'Exécuter un re-tirage atomique de bulletin (V1 vers V2)'],
        ['paie', 'accounting', 'Comptabiliser les bulletins de paie au Grand Livre'],
        ['paie', 'settle', 'Payer et régler les bulletins de paie'],
        ['paie', 'regularize', 'Créer une régularisation de paie sur la période N+1'],
        ['paie', 'close', 'Clôturer définitivement une période de paie'],
        ['paie', 'audit', 'Consulter le journal d\'audit complet de la paie']
    ];

    foreach ($paie_perms as $perm) {
        $stmt_ins_perm->execute([
            'resource' => $perm[0],
            'action' => $perm[1],
            'description' => $perm[2]
        ]);
    }

    // Assign all paie permissions to Super Admins (1, 2), DRH (11), and Chef Comptable (9)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE (r.id_role IN (1, 2, 9) OR r.nom_role = 'drh')
        AND p.resource = 'paie'
    ");

    // Assign viewing and settlement permissions to standard Comptable (7)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT 7, p.id_permission
        FROM permissions p
        WHERE p.resource = 'paie' AND p.action IN ('view', 'settle')
    ");

    // Seed permissions for Phase 9
    $phase9_perms = [
        ['reporting', 'view', 'Consulter le tableau de bord décisionnel général'],
        ['reporting', 'dashboard', 'Accéder au cockpit principal de décision'],
        ['reporting', 'kpis', 'Consulter le catalogue détaillé des KPI et drill-down'],
        ['reporting', 'analyse', 'Accéder aux analyses d\'évolution temporelle'],
        ['reporting', 'comparaison', 'Accéder aux analyses comparatives multi-établissements'],
        ['reporting', 'previsions', 'Consulter les projections de flux et prévisions financières'],
        ['reporting', 'export', 'Exporter les données de reporting au format CSV/PDF'],
        ['reporting', 'threshold_manage', 'Paramétrer les seuils d\'alertes des KPI'],
        ['reporting', 'forecast_manage', 'Gérer les configurations et hypothèses de prévisions'],
        ['reporting', 'snapshot_manage', 'Gérer manuellement la génération de snapshots analytiques'],
        ['reporting', 'view_all_lycees', 'Permission spéciale d\'audit et d\'analyse transversale multi-établissements']
    ];

    foreach ($phase9_perms as $perm) {
        $stmt_ins_perm->execute([
            'resource' => $perm[0],
            'action' => $perm[1],
            'description' => $perm[2]
        ]);
    }

    // Assign all Phase 9 permissions to Super Admins (1, 2), Admin Local (3), and Chef Comptable (9)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.id_role IN (1, 2, 3, 9)
        AND p.resource = 'reporting'
    ");

    // Assign viewing/export permissions to standard Comptable (7)
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT 7, p.id_permission
        FROM permissions p
        WHERE p.resource = 'reporting' AND p.action IN ('view', 'dashboard', 'kpis', 'analyse', 'comparaison', 'previsions', 'export')
    ");

    // Explicitly restrict 'reporting.view_all_lycees' to global Super Admins (1, 2)
    // First clear it from roles 3, 7, 9 just in case to avoid security leaks
    $db->exec("
        DELETE FROM role_permissions
        WHERE role_id IN (3, 7, 9, 10)
        AND permission_id IN (
            SELECT id_permission FROM permissions WHERE resource = 'reporting' AND action = 'view_all_lycees'
        )
    ");

    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
