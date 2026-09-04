<?php
// db/migrations/20240115_17_refactor_evaluations_dynamic.php

function migrate_17($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // Helper to add column if missing
    $addColumnIfMissing = function($table, $column, $definition) use ($db, $isSqlite) {
        if ($isSqlite) {
            $stmt = $db->query("PRAGMA table_info({$table})");
            $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $colNames = array_column($cols, 'name');
            if (!in_array($column, $colNames, true)) {
                $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name
                  AND COLUMN_NAME = :col_name
            ");
            $stmt->execute([
                'table_name' => $table,
                'col_name' => $column
            ]);
            if ((int)$stmt->fetchColumn() === 0) {
                $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        }
    };

    // 1. Create param_type_evaluation table if not exists
    $pkDef = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $sqlCreateParamType = "
        CREATE TABLE IF NOT EXISTS `param_type_evaluation` (
            `id` {$pkDef},
            `lycee_id` INT NOT NULL,
            `code` VARCHAR(50) NOT NULL,
            `libelle` VARCHAR(100) NOT NULL,
            `bareme_defaut` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
            `actif` TINYINT(1) NOT NULL DEFAULT 1,
            `ordre_affichage` INT DEFAULT 0,
            `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
        ) {$fk_ref}
    ";
    $db->exec($sqlCreateParamType);

    // Create unique index on param_type_evaluation (lycee_id, code) if missing
    try {
        if ($isSqlite) {
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS `uk_lycee_code` ON `param_type_evaluation` (`lycee_id`, `code`)");
        } else {
            $chk = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'param_type_evaluation' AND INDEX_NAME = 'uk_lycee_code'");
            $chk->execute();
            if ((int)$chk->fetchColumn() === 0) {
                $db->exec("ALTER TABLE `param_type_evaluation` ADD UNIQUE KEY `uk_lycee_code` (`lycee_id`, `code`)");
            }
        }
    } catch (Exception $e) {
        error_log("Note on uk_lycee_code: " . $e->getMessage());
    }

    // 2. Seed default evaluation types ('devoir', 'composition') per lycee reading historical param_devoir / param_composition if available
    try {
        $lycees = $db->query("SELECT id FROM param_lycee")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($lycees)) {
            $lycees = [1];
        }
        $insertKeyword = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";
        $stmtInsertType = $db->prepare("
            {$insertKeyword} INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, actif, ordre_affichage)
            VALUES (:lycee_id, :code, :libelle, :bareme, 1, :ordre)
        ");

        foreach ($lycees as $lyceeId) {
            $lyceeId = (int)$lyceeId;

            // Resolve historical max note for devoir
            $baremeDevoir = 20.00;
            try {
                $stDev = $db->prepare("SELECT note_maximale FROM param_devoir WHERE lycee_id = :l AND note_maximale IS NOT NULL LIMIT 1");
                $stDev->execute(['l' => $lyceeId]);
                $valDev = $stDev->fetchColumn();
                if ($valDev && (float)$valDev > 0) {
                    $baremeDevoir = (float)$valDev;
                }
            } catch (Exception $e) {
                // Table might be absent or empty
            }

            // Resolve historical max note for composition
            $baremeComposition = 20.00;
            try {
                $stComp = $db->prepare("SELECT note_maximale FROM param_composition WHERE lycee_id = :l AND note_maximale IS NOT NULL LIMIT 1");
                $stComp->execute(['l' => $lyceeId]);
                $valComp = $stComp->fetchColumn();
                if ($valComp && (float)$valComp > 0) {
                    $baremeComposition = (float)$valComp;
                }
            } catch (Exception $e) {
                // Table might be absent or empty
            }

            // Check 'devoir'
            $chkDev = $db->prepare("SELECT id FROM param_type_evaluation WHERE lycee_id = :l AND code = 'devoir'");
            $chkDev->execute(['l' => $lyceeId]);
            if (!$chkDev->fetchColumn()) {
                $stmtInsertType->execute([
                    'lycee_id' => $lyceeId,
                    'code' => 'devoir',
                    'libelle' => 'Devoir',
                    'bareme' => $baremeDevoir,
                    'ordre' => 1
                ]);
            }

            // Check 'composition'
            $chkComp = $db->prepare("SELECT id FROM param_type_evaluation WHERE lycee_id = :l AND code = 'composition'");
            $chkComp->execute(['l' => $lyceeId]);
            if (!$chkComp->fetchColumn()) {
                $stmtInsertType->execute([
                    'lycee_id' => $lyceeId,
                    'code' => 'composition',
                    'libelle' => 'Composition',
                    'bareme' => $baremeComposition,
                    'ordre' => 2
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Error seeding param_type_evaluation: " . $e->getMessage());
    }

    // 3. Extend evaluations table with new columns
    $addColumnIfMissing('evaluations', 'type_evaluation_id', "INT NULL");
    $addColumnIfMissing('evaluations', 'numero_evaluation', "INT NOT NULL DEFAULT 1");
    $addColumnIfMissing('evaluations', 'libelle_evaluation', "VARCHAR(100) NULL");
    $addColumnIfMissing('evaluations', 'bareme_snapshot', "DECIMAL(5,2) NOT NULL DEFAULT 20.00");

    // 4. Extend parametres_evaluations and deblocages_notes with type_evaluation_id
    $addColumnIfMissing('parametres_evaluations', 'type_evaluation_id', "INT NULL");
    $addColumnIfMissing('deblocages_notes', 'type_evaluation_id', "INT NULL");

    // 5. Backfill type_evaluation_id and bareme_snapshot in evaluations dynamically from param_type_evaluation
    try {
        $db->exec("
            UPDATE evaluations
            SET type_evaluation_id = (
                SELECT p.id FROM param_type_evaluation p
                WHERE p.lycee_id = evaluations.lycee_id AND p.code = evaluations.type
                LIMIT 1
            ),
            bareme_snapshot = COALESCE((
                SELECT p.bareme_defaut FROM param_type_evaluation p
                WHERE p.lycee_id = evaluations.lycee_id AND p.code = evaluations.type
                LIMIT 1
            ), 20.00)
            WHERE type_evaluation_id IS NULL
        ");

        // Fallback for any unmapped lycee_id
        $db->exec("
            UPDATE evaluations
            SET type_evaluation_id = (
                SELECT p.id FROM param_type_evaluation p
                WHERE p.code = evaluations.type
                LIMIT 1
            ),
            bareme_snapshot = COALESCE((
                SELECT p.bareme_defaut FROM param_type_evaluation p
                WHERE p.code = evaluations.type
                LIMIT 1
            ), 20.00)
            WHERE type_evaluation_id IS NULL
        ");
    } catch (Exception $e) {
        error_log("Error backfilling evaluations.type_evaluation_id: " . $e->getMessage());
    }

    // 6. Backfill type_evaluation_id in parametres_evaluations and deblocages_notes
    try {
        $db->exec("
            UPDATE parametres_evaluations
            SET type_evaluation_id = (
                SELECT p.id FROM param_type_evaluation p
                WHERE p.lycee_id = parametres_evaluations.lycee_id AND p.code = parametres_evaluations.type_evaluation
                LIMIT 1
            )
            WHERE type_evaluation_id IS NULL AND type_evaluation != 'tous'
        ");

        $db->exec("
            UPDATE deblocages_notes
            SET type_evaluation_id = (
                SELECT p.id FROM param_type_evaluation p
                WHERE p.lycee_id = deblocages_notes.lycee_id AND p.code = deblocages_notes.type_evaluation
                LIMIT 1
            )
            WHERE type_evaluation_id IS NULL AND type_evaluation != 'tous'
        ");
    } catch (Exception $e) {
        error_log("Error backfilling type_evaluation_id in settings/unlocks: " . $e->getMessage());
    }

    // 7. Update unique constraint on evaluations
    try {
        if ($isSqlite) {
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS `uk_eval_occ` ON `evaluations` (`eleve_id`, `matiere_id`, `sequence_id`, `annee_academique_id`, `type_evaluation_id`, `numero_evaluation`)");
        } else {
            // Drop old index if exists
            $chkOld = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluations' AND INDEX_NAME = 'unique_evaluation_note'");
            $chkOld->execute();
            if ((int)$chkOld->fetchColumn() > 0) {
                $db->exec("ALTER TABLE `evaluations` DROP INDEX `unique_evaluation_note`");
            }
            $chkNew = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluations' AND INDEX_NAME = 'uk_eval_occ'");
            $chkNew->execute();
            if ((int)$chkNew->fetchColumn() === 0) {
                $db->exec("ALTER TABLE `evaluations` ADD UNIQUE KEY `uk_eval_occ` (`eleve_id`, `matiere_id`, `sequence_id`, `annee_academique_id`, `type_evaluation_id`, `numero_evaluation`)");
            }
        }
    } catch (Exception $e) {
        error_log("Note on uk_eval_occ: " . $e->getMessage());
    }

    echo "Migration 17: Dynamic evaluation types and evaluations table extension OK.\n";
}
