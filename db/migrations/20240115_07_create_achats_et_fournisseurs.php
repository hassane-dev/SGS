<?php
// db/migrations/20240115_07_create_achats_et_fournisseurs.php

function migrate_07($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

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

    // 1. fournisseurs
    $createTable($db, 'fournisseurs', "
        CREATE TABLE IF NOT EXISTS fournisseurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NULL,
            raison_sociale VARCHAR(255) NOT NULL,
            code_fournisseur VARCHAR(50) NOT NULL UNIQUE,
            nif VARCHAR(100) NULL,
            rccm VARCHAR(100) NULL,
            adresse TEXT NULL,
            telephone VARCHAR(50) NULL,
            email VARCHAR(255) NULL,
            contact_nom VARCHAR(150) NULL,
            compte_comptable_tiers VARCHAR(20) DEFAULT NULL,
            actif TINYINT(1) DEFAULT 1,
            cree_par INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE fournisseurs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NULL,
            raison_sociale VARCHAR(255) NOT NULL,
            code_fournisseur VARCHAR(50) NOT NULL UNIQUE,
            nif VARCHAR(100) NULL,
            rccm VARCHAR(100) NULL,
            adresse TEXT NULL,
            telephone VARCHAR(50) NULL,
            email VARCHAR(255) NULL,
            contact_nom VARCHAR(150) NULL,
            compte_comptable_tiers VARCHAR(20) DEFAULT NULL,
            actif TINYINT(1) DEFAULT 1,
            cree_par INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        );
    ");

    // 2. achat_categories
    $createTable($db, 'achat_categories', "
        CREATE TABLE IF NOT EXISTS achat_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            libelle VARCHAR(150) NOT NULL,
            compte_comptable_charge VARCHAR(20) NOT NULL,
            actif TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            libelle VARCHAR(150) NOT NULL,
            compte_comptable_charge VARCHAR(20) NOT NULL,
            actif TINYINT(1) DEFAULT 1
        );
    ");

    // 3. achat_articles
    $createTable($db, 'achat_articles', "
        CREATE TABLE IF NOT EXISTS achat_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categorie_id INT NOT NULL,
            libelle VARCHAR(255) NOT NULL,
            reference VARCHAR(100) NOT NULL UNIQUE,
            unite_mesure VARCHAR(50) NOT NULL,
            prix_unitaire_estime DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            is_service TINYINT(1) DEFAULT 0,
            actif TINYINT(1) DEFAULT 1,
            FOREIGN KEY (categorie_id) REFERENCES achat_categories(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categorie_id INTEGER NOT NULL,
            libelle VARCHAR(255) NOT NULL,
            reference VARCHAR(100) NOT NULL UNIQUE,
            unite_mesure VARCHAR(50) NOT NULL,
            prix_unitaire_estime DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            is_service TINYINT(1) DEFAULT 0,
            actif TINYINT(1) DEFAULT 1,
            FOREIGN KEY (categorie_id) REFERENCES achat_categories(id) ON DELETE RESTRICT
        );
    ");

    // 4. achat_demandes
    $createTable($db, 'achat_demandes', "
        CREATE TABLE IF NOT EXISTS achat_demandes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            demandeur_id INT NOT NULL,
            justification TEXT NOT NULL,
            date_demande DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'en_attente_approbation', 'approuvee', 'rejete', 'annule'
            approuve_par INT DEFAULT NULL,
            date_approbation DATETIME DEFAULT NULL,
            motif_statut TEXT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (demandeur_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (approuve_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_demandes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            demandeur_id INTEGER NOT NULL,
            justification TEXT NOT NULL,
            date_demande DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon',
            approuve_par INTEGER,
            date_approbation DATETIME,
            motif_statut TEXT,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (demandeur_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (approuve_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        );
    ");

    // 5. achat_demande_lignes
    $createTable($db, 'achat_demande_lignes', "
        CREATE TABLE IF NOT EXISTS achat_demande_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            demande_id INT NOT NULL,
            article_id INT NOT NULL,
            quantite_demandee DECIMAL(12,4) NOT NULL,
            prix_unitaire_estime DECIMAL(15,4) NOT NULL,
            budget_ligne_id INT DEFAULT NULL,
            FOREIGN KEY (demande_id) REFERENCES achat_demandes(id) ON DELETE CASCADE,
            FOREIGN KEY (article_id) REFERENCES achat_articles(id) ON DELETE RESTRICT,
            FOREIGN KEY (budget_ligne_id) REFERENCES budget_lignes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_demande_lignes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            demande_id INTEGER NOT NULL,
            article_id INTEGER NOT NULL,
            quantite_demandee DECIMAL(12,4) NOT NULL,
            prix_unitaire_estime DECIMAL(15,4) NOT NULL,
            budget_ligne_id INTEGER,
            FOREIGN KEY (demande_id) REFERENCES achat_demandes(id) ON DELETE CASCADE,
            FOREIGN KEY (article_id) REFERENCES achat_articles(id) ON DELETE RESTRICT,
            FOREIGN KEY (budget_ligne_id) REFERENCES budget_lignes(id) ON DELETE SET NULL
        );
    ");

    // 6. achat_commandes
    $createTable($db, 'achat_commandes', "
        CREATE TABLE IF NOT EXISTS achat_commandes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            demande_id INT DEFAULT NULL,
            fournisseur_id INT NOT NULL,
            numero_commande VARCHAR(100) NOT NULL,
            date_commande DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'emis', 'reception_partielle', 'executee', 'annulee'
            cree_par INT NOT NULL,
            valide_par INT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (demande_id) REFERENCES achat_demandes(id) ON DELETE SET NULL,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            UNIQUE (lycee_id, numero_commande)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_commandes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            demande_id INTEGER,
            fournisseur_id INTEGER NOT NULL,
            numero_commande VARCHAR(100) NOT NULL,
            date_commande DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon',
            cree_par INTEGER NOT NULL,
            valide_par INTEGER,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (demande_id) REFERENCES achat_demandes(id) ON DELETE SET NULL,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            UNIQUE (lycee_id, numero_commande)
        );
    ");

    // 7. achat_commande_lignes
    $createTable($db, 'achat_commande_lignes', "
        CREATE TABLE IF NOT EXISTS achat_commande_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commande_id INT NOT NULL,
            demande_ligne_id INT DEFAULT NULL,
            article_id INT NOT NULL,
            quantite_commandee DECIMAL(12,4) NOT NULL,
            prix_unitaire_negocie DECIMAL(15,4) NOT NULL,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE CASCADE,
            FOREIGN KEY (demande_ligne_id) REFERENCES achat_demande_lignes(id) ON DELETE SET NULL,
            FOREIGN KEY (article_id) REFERENCES achat_articles(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_commande_lignes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            commande_id INTEGER NOT NULL,
            demande_ligne_id INTEGER,
            article_id INTEGER NOT NULL,
            quantite_commandee DECIMAL(12,4) NOT NULL,
            prix_unitaire_negocie DECIMAL(15,4) NOT NULL,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE CASCADE,
            FOREIGN KEY (demande_ligne_id) REFERENCES achat_demande_lignes(id) ON DELETE SET NULL,
            FOREIGN KEY (article_id) REFERENCES achat_articles(id) ON DELETE RESTRICT
        );
    ");

    // 8. achat_receptions
    $createTable($db, 'achat_receptions', "
        CREATE TABLE IF NOT EXISTS achat_receptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            commande_id INT NOT NULL,
            numero_reception VARCHAR(100) NOT NULL,
            date_reception DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'valide'
            receptionne_par INT NOT NULL,
            details TEXT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE RESTRICT,
            FOREIGN KEY (receptionne_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            UNIQUE (lycee_id, numero_reception)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_receptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            commande_id INTEGER NOT NULL,
            numero_reception VARCHAR(100) NOT NULL,
            date_reception DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon',
            receptionne_par INTEGER NOT NULL,
            details TEXT,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE RESTRICT,
            FOREIGN KEY (receptionne_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            UNIQUE (lycee_id, numero_reception)
        );
    ");

    // 9. achat_reception_lignes
    $createTable($db, 'achat_reception_lignes', "
        CREATE TABLE IF NOT EXISTS achat_reception_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reception_id INT NOT NULL,
            commande_ligne_id INT NOT NULL,
            quantite_receptionnee DECIMAL(12,4) NOT NULL,
            quantite_refusee DECIMAL(12,4) DEFAULT 0.0000,
            motif_refus TEXT DEFAULT NULL,
            FOREIGN KEY (reception_id) REFERENCES achat_receptions(id) ON DELETE CASCADE,
            FOREIGN KEY (commande_ligne_id) REFERENCES achat_commande_lignes(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_reception_lignes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reception_id INTEGER NOT NULL,
            commande_ligne_id INTEGER NOT NULL,
            quantite_receptionnee DECIMAL(12,4) NOT NULL,
            quantite_refusee DECIMAL(12,4) DEFAULT 0.0000,
            motif_refus TEXT,
            FOREIGN KEY (reception_id) REFERENCES achat_receptions(id) ON DELETE CASCADE,
            FOREIGN KEY (commande_ligne_id) REFERENCES achat_commande_lignes(id) ON DELETE RESTRICT
        );
    ");

    // 10. achat_factures
    $createTable($db, 'achat_factures', "
        CREATE TABLE IF NOT EXISTS achat_factures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            fournisseur_id INT NOT NULL,
            commande_id INT DEFAULT NULL,
            reception_id INT DEFAULT NULL,
            piece_comptable_id INT DEFAULT NULL,
            reference_facture VARCHAR(150) NOT NULL,
            date_facture DATE NOT NULL,
            date_echeance DATE NOT NULL,
            montant_ht DECIMAL(15,2) NOT NULL,
            montant_ttc DECIMAL(15,2) NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'enregistree', -- 'enregistree', 'payee_partiellement', 'payee', 'annulee'
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE SET NULL,
            FOREIGN KEY (reception_id) REFERENCES achat_receptions(id) ON DELETE SET NULL,
            FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
            UNIQUE (lycee_id, fournisseur_id, reference_facture)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_factures (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            fournisseur_id INTEGER NOT NULL,
            commande_id INTEGER,
            reception_id INTEGER,
            piece_comptable_id INTEGER,
            reference_facture VARCHAR(150) NOT NULL,
            date_facture DATE NOT NULL,
            date_echeance DATE NOT NULL,
            montant_ht DECIMAL(15,2) NOT NULL,
            montant_ttc DECIMAL(15,2) NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'enregistree',
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE SET NULL,
            FOREIGN KEY (reception_id) REFERENCES achat_receptions(id) ON DELETE SET NULL,
            FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
            UNIQUE (lycee_id, fournisseur_id, reference_facture)
        );
    ");

    // 11. achat_facture_lignes
    $createTable($db, 'achat_facture_lignes', "
        CREATE TABLE IF NOT EXISTS achat_facture_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            facture_id INT NOT NULL,
            reception_ligne_id INT NOT NULL,
            quantite_facturee DECIMAL(12,4) NOT NULL,
            prix_unitaire_facture DECIMAL(15,4) NOT NULL,
            taux_tva_facture DECIMAL(5,4) DEFAULT 0.0000,
            montant_ht_ligne DECIMAL(15,2) NOT NULL,
            montant_ttc_ligne DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE CASCADE,
            FOREIGN KEY (reception_ligne_id) REFERENCES achat_reception_lignes(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_facture_lignes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            facture_id INTEGER NOT NULL,
            reception_ligne_id INTEGER NOT NULL,
            quantite_facturee DECIMAL(12,4) NOT NULL,
            prix_unitaire_facture DECIMAL(15,4) NOT NULL,
            taux_tva_facture DECIMAL(5,4) DEFAULT 0.0000,
            montant_ht_ligne DECIMAL(15,2) NOT NULL,
            montant_ttc_ligne DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE CASCADE,
            FOREIGN KEY (reception_ligne_id) REFERENCES achat_reception_lignes(id) ON DELETE RESTRICT
        );
    ");

    // 12. achat_facture_reglements
    $createTable($db, 'achat_facture_reglements', "
        CREATE TABLE IF NOT EXISTS achat_facture_reglements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            facture_id INT NOT NULL,
            mouvement_tresorerie_id INT NOT NULL,
            montant_alloue DECIMAL(15,2) NOT NULL,
            idempotency_key VARCHAR(100) NOT NULL UNIQUE,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE RESTRICT,
            FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_facture_reglements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            facture_id INTEGER NOT NULL,
            mouvement_tresorerie_id INTEGER NOT NULL,
            montant_alloue DECIMAL(15,2) NOT NULL,
            idempotency_key VARCHAR(100) NOT NULL UNIQUE,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE RESTRICT,
            FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE RESTRICT
        );
    ");

    // 13. achat_avoirs_fournisseurs
    $createTable($db, 'achat_avoirs_fournisseurs', "
        CREATE TABLE IF NOT EXISTS achat_avoirs_fournisseurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            fournisseur_id INT NOT NULL,
            facture_id INT NOT NULL,
            reference_avoir VARCHAR(150) NOT NULL,
            date_avoir DATE NOT NULL,
            montant_ht DECIMAL(15,2) NOT NULL,
            montant_ttc DECIMAL(15,2) NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'enregistre', -- 'enregistre', 'valide'
            piece_comptable_id INT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE RESTRICT,
            FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
            UNIQUE (lycee_id, fournisseur_id, reference_avoir)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_avoirs_fournisseurs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lycee_id INTEGER NOT NULL,
            fournisseur_id INTEGER NOT NULL,
            facture_id INTEGER NOT NULL,
            reference_avoir VARCHAR(150) NOT NULL,
            date_avoir DATE NOT NULL,
            montant_ht DECIMAL(15,2) NOT NULL,
            montant_ttc DECIMAL(15,2) NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'enregistre',
            piece_comptable_id INTEGER,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE RESTRICT,
            FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
            UNIQUE (lycee_id, fournisseur_id, reference_avoir)
        );
    ");

    // 14. achat_avoir_fournisseur_lignes
    $createTable($db, 'achat_avoir_fournisseur_lignes', "
        CREATE TABLE IF NOT EXISTS achat_avoir_fournisseur_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            avoir_id INT NOT NULL,
            facture_ligne_id INT NOT NULL,
            quantite_avoir DECIMAL(12,4) NOT NULL,
            prix_unitaire_avoir DECIMAL(15,4) NOT NULL,
            montant_ht_ligne DECIMAL(15,2) NOT NULL,
            montant_ttc_ligne DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (avoir_id) REFERENCES achat_avoirs_fournisseurs(id) ON DELETE CASCADE,
            FOREIGN KEY (facture_ligne_id) REFERENCES achat_facture_lignes(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ", "
        CREATE TABLE achat_avoir_fournisseur_lignes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            avoir_id INTEGER NOT NULL,
            facture_ligne_id INTEGER NOT NULL,
            quantite_avoir DECIMAL(12,4) NOT NULL,
            prix_unitaire_avoir DECIMAL(15,4) NOT NULL,
            montant_ht_ligne DECIMAL(15,2) NOT NULL,
            montant_ttc_ligne DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (avoir_id) REFERENCES achat_avoirs_fournisseurs(id) ON DELETE CASCADE,
            FOREIGN KEY (facture_ligne_id) REFERENCES achat_facture_lignes(id) ON DELETE RESTRICT
        );
    ");

    // DDL Alteration on budget_engagements to support polymorphic couplings (source_type, source_id)
    try {
        $cols = [];
        if ($isSqlite) {
            $stmt = $db->prepare("PRAGMA table_info(budget_engagements)");
            $stmt->execute();
            $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM budget_engagements");
            $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        }

        if (!in_array('source_type', $cols)) {
            $db->exec("ALTER TABLE budget_engagements ADD COLUMN source_type VARCHAR(100) DEFAULT NULL");
            echo "Added source_type column to budget_engagements\n";
        }
        if (!in_array('source_id', $cols)) {
            $db->exec("ALTER TABLE budget_engagements ADD COLUMN source_id INT DEFAULT NULL");
            echo "Added source_id column to budget_engagements\n";
        }
    } catch (Exception $e) {
        echo "Error altering budget_engagements table: " . $e->getMessage() . "\n";
    }

    // Seed Phase 7 permissions
    $phase7_perms = [
        ['fournisseur', 'view', 'Consulter la liste et fiches fournisseurs'],
        ['fournisseur', 'manage', 'Créer, modifier et gérer les fiches fournisseurs'],
        ['achat_categorie', 'manage', 'Gérer les catégories d\'achats et rattachements charges'],
        ['achat_article', 'view', 'Consulter le catalogue d\'articles et prestations'],
        ['achat_article', 'manage', 'Gérer le catalogue d\'articles et de prestations'],
        ['achat_demande', 'create', 'Créer une demande d\'achat (DA)'],
        ['achat_demande', 'view', 'Consulter ses demandes d\'achat'],
        ['achat_demande', 'approve', 'Approuver une demande d\'achat'],
        ['achat_demande', 'reject', 'Rejeter une demande d\'achat'],
        ['achat_demande', 'cancel', 'Annuler une demande d\'achat validée'],
        ['achat_commande', 'create', 'Émettre un bon de commande (BC)'],
        ['achat_commande', 'view', 'Consulter les bons de commande'],
        ['achat_commande', 'approve', 'Valider et signer un bon de commande émis'],
        ['achat_commande', 'cancel', 'Annuler un bon de commande'],
        ['achat_reception', 'create', 'Enregistrer un bon de réception (BR)'],
        ['achat_reception', 'validate', 'Valider définitivement les quantités livrées'],
        ['achat_facture', 'create', 'Enregistrer et rapprocher une facture fournisseur'],
        ['achat_facture', 'view', 'Consulter les factures et échéanciers'],
        ['achat_facture', 'validate', 'Valider définitivement une facture pour mise en paiement'],
        ['achat_facture', 'cancel', 'Annuler ou émettre un avoir sur une facture enregistrée'],
        ['achat_facture', 'pay', 'Procéder au règlement financier d\'une facture d\'achat'],
        ['achat_avoir', 'create', 'Enregistrer un avoir fournisseur'],
        ['achat_avoir', 'validate', 'Valider un avoir et générer la contrepassation']
    ];

    if ($isSqlite) {
        $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
    } else {
        $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
    }

    foreach ($phase7_perms as $perm) {
        $stmt_ins_perm->execute([
            'resource' => $perm[0],
            'action' => $perm[1],
            'description' => $perm[2]
        ]);
    }

    // Map Phase 7 permissions to roles dynamically based on role name (RBAC Compliance)
    $insert_ignore_keyword = $isSqlite ? 'INSERT OR IGNORE' : 'INSERT IGNORE';

    // Map permissions to Super Admin
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.nom_role IN ('super_admin_createur', 'super_admin_national')
        AND p.resource IN ('fournisseur', 'achat_categorie', 'achat_article', 'achat_demande', 'achat_commande', 'achat_reception', 'achat_facture', 'achat_avoir')
    ");

    // Map permissions to Local Admin
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.nom_role = 'admin_local'
        AND p.resource IN ('fournisseur', 'achat_categorie', 'achat_article', 'achat_demande', 'achat_commande', 'achat_reception', 'achat_facture', 'achat_avoir')
    ");

    // Map permissions to Chef Comptable
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.nom_role = 'chef_comptable'
        AND p.resource IN ('fournisseur', 'achat_categorie', 'achat_article', 'achat_demande', 'achat_commande', 'achat_reception', 'achat_facture', 'achat_avoir')
    ");

    // Map permissions to Comptable
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.nom_role = 'comptable'
        AND (
            (p.resource = 'fournisseur' AND p.action = 'view') OR
            (p.resource = 'achat_article' AND p.action = 'view') OR
            (p.resource = 'achat_demande' AND p.action IN ('create', 'view')) OR
            (p.resource = 'achat_commande' AND p.action IN ('create', 'view')) OR
            (p.resource = 'achat_reception' AND p.action IN ('create', 'view')) OR
            (p.resource = 'achat_facture' AND p.action IN ('create', 'view')) OR
            (p.resource = 'achat_avoir' AND p.action = 'create')
        )
    ");

    // Map permissions to Caissier
    $db->exec("
        {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.nom_role = 'caissier'
        AND (
            (p.resource = 'fournisseur' AND p.action = 'view') OR
            (p.resource = 'achat_facture' AND p.action IN ('view', 'pay'))
        )
    ");

    // Register dynamic schemas for Phase 7 Facturation & Règlements
    require_once __DIR__ . '/../../src/services/ComptabiliteService.php';
    try {
        $db->query("SELECT 1 FROM schemas_comptables LIMIT 1");
        $stmt_schema_ins = $isSqlite ?
            $db->prepare("INSERT INTO schemas_comptables (evenement, compte_debit_numero, compte_credit_numero, libelle_modele, journal_code) VALUES (:evenement, :compte_debit_numero, :compte_credit_numero, :libelle_modele, :journal_code) ON CONFLICT(evenement) DO UPDATE SET compte_debit_numero=excluded.compte_debit_numero, compte_credit_numero=excluded.compte_credit_numero, libelle_modele=excluded.libelle_modele") :
            $db->prepare("INSERT INTO schemas_comptables (evenement, compte_debit_numero, compte_credit_numero, libelle_modele, journal_code) VALUES (:evenement, :compte_debit_numero, :compte_credit_numero, :libelle_modele, :journal_code) ON DUPLICATE KEY UPDATE compte_debit_numero=VALUES(compte_debit_numero), compte_credit_numero=VALUES(compte_credit_numero), libelle_modele=VALUES(libelle_modele)");

        $stmt_schema_ins->execute([
            'evenement' => 'facture_fournisseur',
            'compte_debit_numero' => '',
            'compte_credit_numero' => '',
            'libelle_modele' => 'Achat fournisseur - Facture N°{ref}',
            'journal_code' => 'JA'
        ]);

        $stmt_schema_ins->execute([
            'evenement' => 'reglement_fournisseur',
            'compte_debit_numero' => '',
            'compte_credit_numero' => '',
            'libelle_modele' => 'Règlement fournisseur - Réf {ref}',
            'journal_code' => 'JO'
        ]);

        $stmt_schema_ins->execute([
            'evenement' => 'avoir_fournisseur',
            'compte_debit_numero' => '',
            'compte_credit_numero' => '',
            'libelle_modele' => 'Avoir reçu fournisseur - Réf {ref}',
            'journal_code' => 'JA'
        ]);
        echo "Phase 7 Comptabilité: Automated schemas seeded.\n";
    } catch (Exception $e) {
        echo "Error seeding schemas_comptables for Phase 7: " . $e->getMessage() . "\n";
    }
}

// Invoquer directement si executé à part
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../src/config/database.php';
    try {
        migrate_07(Database::getInstance());
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>