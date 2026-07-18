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

    // Update ENUM for eleves.statut and etudes.status
    // This is a bit tricky with raw SQL but we try to be safe.
    $db->exec("ALTER TABLE eleves MODIFY COLUMN statut ENUM('en_attente', 'en_attente_paiement', 'actif', 'transféré', 'radié', 'diplômé', 'abandonné') NOT NULL DEFAULT 'en_attente'");
    $db->exec("ALTER TABLE etudes MODIFY COLUMN status ENUM('en_attente_paiement', 'active', 'inactive', 'suspended') DEFAULT 'en_attente_paiement'");

    // Add recu_numero to inscriptions
    $db->exec("ALTER TABLE inscriptions ADD COLUMN IF NOT EXISTS recu_numero VARCHAR(50);");

    // Add reste_a_payer to mensualites
    $db->exec("ALTER TABLE mensualites ADD COLUMN IF NOT EXISTS reste_a_payer DECIMAL(10, 2) DEFAULT 0.00;");

    // Add statut to inscriptions & mensualite_details
    $db->exec("ALTER TABLE inscriptions ADD COLUMN IF NOT EXISTS statut ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide';");
    $db->exec("ALTER TABLE mensualite_details ADD COLUMN IF NOT EXISTS statut ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide';");

    // Add cloturee to annees_academiques
    $db->exec("ALTER TABLE annees_academiques ADD COLUMN IF NOT EXISTS cloturee TINYINT(1) NOT NULL DEFAULT 0;");

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

    // Add identifiant_public to eleves table
    $db->exec("ALTER TABLE eleves ADD COLUMN IF NOT EXISTS identifiant_public VARCHAR(50) DEFAULT NULL;");
    try {
        $db->exec("ALTER TABLE eleves ADD UNIQUE (identifiant_public);");
    } catch (Exception $e) {
        // Safe if unique key already exists
    }

    // Add identifiant_public to utilisateurs table
    $db->exec("ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS identifiant_public VARCHAR(50) DEFAULT NULL;");
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

    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
