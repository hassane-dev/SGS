<?php
require_once __DIR__ . '/src/config/database.php';
$db = Database::getInstance();

function addColumnIfNeeded($db, $table, $column, $definition) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if (!$stmt->fetch()) {
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

    // Rename modele_carte to carte_templates if it exists and carte_templates doesn't
    $stmt = $db->query("SHOW TABLES LIKE 'modele_carte'");
    if ($stmt->fetch()) {
        $stmt2 = $db->query("SHOW TABLES LIKE 'carte_templates'");
        if (!$stmt2->fetch()) {
            $db->exec("RENAME TABLE modele_carte TO carte_templates;");
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
        $stmt_col = $db->query("SHOW COLUMNS FROM carte_templates LIKE 'font_settings'");
        if ($stmt_col->fetch()) {
            $db->exec("ALTER TABLE carte_templates RENAME COLUMN font_settings TO styles;");
            echo "Renamed font_settings to styles on carte_templates\n";
        }
    } catch (Exception $e) {
        // Safe to ignore if already renamed or column doesn't exist
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

    // Update ENUM for eleves.statut and etudes.status
    $db->exec("ALTER TABLE eleves MODIFY COLUMN statut ENUM('en_attente', 'en_attente_paiement', 'actif', 'transféré', 'radié', 'diplômé', 'abandonné') NOT NULL DEFAULT 'en_attente'");
    $db->exec("ALTER TABLE etudes MODIFY COLUMN status ENUM('en_attente_paiement', 'active', 'inactive', 'suspended') DEFAULT 'en_attente_paiement'");

    // Add columns to other tables
    addColumnIfNeeded($db, 'inscriptions', 'recu_numero', 'VARCHAR(50)');
    addColumnIfNeeded($db, 'mensualites', 'reste_a_payer', 'DECIMAL(10, 2) DEFAULT 0.00');
    addColumnIfNeeded($db, 'inscriptions', 'statut', "ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide'");
    addColumnIfNeeded($db, 'mensualite_details', 'statut', "ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide'");
    addColumnIfNeeded($db, 'annees_academiques', 'cloturee', 'TINYINT(1) NOT NULL DEFAULT 0');

    // Create journal_comptable table
    $db->exec("CREATE TABLE IF NOT EXISTS journal_comptable (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create deblocages_notes table
    $db->exec("CREATE TABLE IF NOT EXISTS `deblocages_notes` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
    $eleves_without_id = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($eleves_without_id)) {
        $stmt_counter = $db->query("SELECT identifiant_public FROM eleves WHERE identifiant_public LIKE '%E' ORDER BY id_eleve DESC LIMIT 1");
        $last_student_public_id = $stmt_counter->fetchColumn();
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
    $users_without_id = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($users_without_id)) {
        $stmt_counter = $db->query("SELECT identifiant_public FROM utilisateurs WHERE identifiant_public IS NOT NULL ORDER BY id_user DESC LIMIT 1");
        $last_user_public_id = $stmt_counter->fetchColumn();
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
    $db->exec("CREATE TABLE IF NOT EXISTS parametres_utilisateurs (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // --- PHASE 1 FINANCIAL TABLES ---

    // Create exercices_financiers table
    $db->exec("CREATE TABLE IF NOT EXISTS exercices_financiers (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create comptes_financiers table
    $db->exec("CREATE TABLE IF NOT EXISTS comptes_financiers (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create sessions_caisse table with the stored generated column for active session uniqueness
    $db->exec("CREATE TABLE IF NOT EXISTS sessions_caisse (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create transferts_financiers table
    $db->exec("CREATE TABLE IF NOT EXISTS transferts_financiers (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create mouvements_tresorerie table with the composite unique index
    $db->exec("CREATE TABLE IF NOT EXISTS mouvements_tresorerie (
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
        evenement_type ENUM('encaissement', 'annulation', 'remboursement', 'correction') NOT NULL,
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create regularisations_ecarts table
    $db->exec("CREATE TABLE IF NOT EXISTS regularisations_ecarts (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed permissions into the database dynamically
    $new_perms = [
        ['comptes_financiers', 'view', 'Consulter la liste des comptes financiers et leurs soldes'],
        ['comptes_financiers', 'create', 'Créer de nouveaux comptes financiers'],
        ['comptes_financiers', 'edit', 'Modifier les propriétés d\'un compte financier'],
        ['comptes_financiers', 'manage', 'Suspendre ou réactiver un compte financier'],
        ['sessions_caisse', 'create', 'Ouvrir une session de caisse physique journalière'],
        ['sessions_caisse', 'edit', 'Fermer sa propre session de caisse'],
        ['sessions_caisse', 'validate', 'Valider la clôture d\'une session de caisse et régulariser les écarts'],
        ['sessions_caisse', 'reopen', 'Réouvrir exceptionnellement une caisse fermée (Permission sensible d\'audit)'],
        ['mouvements_tresorerie', 'view', 'Consulter le grand livre de trésorerie'],
        ['mouvements_tresorerie', 'create', 'Enregistrer un mouvement de trésorerie manuel'],
        ['transferts', 'create', 'Initier une demande de virement inter-comptes'],
        ['transferts', 'validate', 'Approuver et exécuter un transfert de fonds']
    ];

    $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
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

    // --- MAP PHASE 3 & 4 PERMISSIONS TO ROLES ---
    // Assign Phase 3 permissions to Super Admins (1, 2), Admin Local (3), Chef Comptable (9)
    $db->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.id_role IN (1, 2, 3, 9)
        AND p.resource = 'depense'
    ");

    // Assign view, export, and pay of depenses to Comptable (7)
    $db->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 7, p.id_permission
        FROM permissions p
        WHERE p.resource = 'depense' AND p.action IN ('view', 'export', 'pay')
    ");

    // Assign Phase 4 permissions to Super Admins (1, 2), Admin Local (3), Chef Comptable (9)
    $db->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.id_role IN (1, 2, 3, 9)
        AND p.resource = 'budget'
    ");

    // Assign view and report of budgets to Comptable (7)
    $db->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 7, p.id_permission
        FROM permissions p
        WHERE p.resource = 'budget' AND p.action IN ('view', 'report')
    ");

    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>