<?php
// db/migrations/20240115_11_create_drh_module.php

function migrate_11($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. Table personnel_details
    $sql_details = "CREATE TABLE IF NOT EXISTS personnel_details (
        id $pk,
        personnel_id INT NOT NULL UNIQUE,
        num_cnss VARCHAR(100) DEFAULT NULL,
        situation_matrimoniale VARCHAR(50) DEFAULT 'celibataire',
        nombre_enfants INT DEFAULT 0,
        statut_rh VARCHAR(50) DEFAULT 'en_activite',
        date_sortie DATE DEFAULT NULL,
        motif_sortie TEXT DEFAULT NULL,
        remarques TEXT DEFAULT NULL,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_details);

    // 2. Table personnel_fonctions
    $sql_fonctions = "CREATE TABLE IF NOT EXISTS personnel_fonctions (
        id $pk,
        lycee_id INT DEFAULT NULL,
        libelle VARCHAR(150) NOT NULL,
        departement VARCHAR(100) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        actif TINYINT(1) DEFAULT 1,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_fonctions);

    // Seed default HR Functions if empty
    $stmt_check = $db->query("SELECT COUNT(*) FROM personnel_fonctions");
    $hasFonctions = (int)$stmt_check->fetchColumn();
    $stmt_check->closeCursor();

    if ($hasFonctions === 0) {
        $default_fonctions = [
            ['Enseignant', 'Pédagogie', 'Membre du corps enseignant'],
            ['Surveillant Général', 'Vie Scolaire', 'Supervision générale de la discipline'],
            ['Censeur / Directeur des Études', 'Direction', 'Coordination des activités pédagogiques'],
            ['Comptable', 'Finance', 'Gestion de la comptabilité et de la trésorerie'],
            ['Chef Comptable', 'Finance', 'Supervision financière et bilan'],
            ['Caissier', 'Finance', 'Gestion des flux de caisse journaliers'],
            ['Secrétaire', 'Administration', 'Gestion du secrétariat et accueil'],
            ['Informaticien / Administrateur Système', 'Technique', 'Maintenance des équipements et systèmes']
        ];
        $stmt_f_ins = $db->prepare("INSERT INTO personnel_fonctions (libelle, departement, description) VALUES (?, ?, ?)");
        foreach ($default_fonctions as $df) {
            $stmt_f_ins->execute($df);
        }
    }

    // 3. Table personnel_contrats_historique
    $sql_contrats = "CREATE TABLE IF NOT EXISTS personnel_contrats_historique (
        id $pk,
        personnel_id INT NOT NULL,
        type_contrat_id INT NOT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE DEFAULT NULL,
        salaire_base DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        devise VARCHAR(10) DEFAULT NULL,
        volume_horaire_mensuel DECIMAL(6,2) DEFAULT NULL,
        statut_contrat VARCHAR(50) DEFAULT 'actif',
        commentaire TEXT DEFAULT NULL,
        cree_par INT DEFAULT NULL,
        cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
        FOREIGN KEY (type_contrat_id) REFERENCES type_contrat(id_contrat) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_contrats);

    // 4. Table personnel_documents
    $sql_documents = "CREATE TABLE IF NOT EXISTS personnel_documents (
        id $pk,
        personnel_id INT NOT NULL,
        type_document VARCHAR(100) NOT NULL,
        nom_fichier VARCHAR(255) NOT NULL,
        chemin_fichier TEXT NOT NULL,
        confidentiel TINYINT(1) DEFAULT 1,
        version INT DEFAULT 1,
        date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        uploaded_par INT DEFAULT NULL,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_documents);

    // 5. Table personnel_historique_mouvements
    $sql_mouvements = "CREATE TABLE IF NOT EXISTS personnel_historique_mouvements (
        id $pk,
        personnel_id INT NOT NULL,
        type_mouvement VARCHAR(100) NOT NULL,
        date_mouvement DATETIME NOT NULL,
        motif TEXT DEFAULT NULL,
        auteur_id INT NOT NULL,
        ancien_etat TEXT DEFAULT NULL,
        nouvel_etat TEXT DEFAULT NULL,
        lycee_id INT DEFAULT NULL,
        cycle_id INT DEFAULT NULL,
        cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_mouvements);

    // Add indexes for high-throughput queries
    try {
        if ($isSqlite) {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_pers_det_statut ON personnel_details (statut_rh);");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_pers_con_dates ON personnel_contrats_historique (personnel_id, statut_contrat, date_debut);");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_pers_mov_pers ON personnel_historique_mouvements (personnel_id, date_mouvement);");
        } else {
            $idx1 = $db->query("SHOW INDEX FROM personnel_details WHERE Key_name = 'idx_pers_det_statut'");
            if (!$idx1->fetch()) {
                $db->exec("ALTER TABLE personnel_details ADD INDEX idx_pers_det_statut (statut_rh);");
            }
            $idx2 = $db->query("SHOW INDEX FROM personnel_contrats_historique WHERE Key_name = 'idx_pers_con_dates'");
            if (!$idx2->fetch()) {
                $db->exec("ALTER TABLE personnel_contrats_historique ADD INDEX idx_pers_con_dates (personnel_id, statut_contrat, date_debut);");
            }
            $idx3 = $db->query("SHOW INDEX FROM personnel_historique_mouvements WHERE Key_name = 'idx_pers_mov_pers'");
            if (!$idx3->fetch()) {
                $db->exec("ALTER TABLE personnel_historique_mouvements ADD INDEX idx_pers_mov_pers (personnel_id, date_mouvement);");
            }
        }
    } catch (Exception $e) {
        // Log index creation exception gracefully
        error_log("Index creation warning in migrate_11: " . $e->getMessage());
    }

    echo "Migration 11: DRH Module tables and indexes OK.\n";
}
