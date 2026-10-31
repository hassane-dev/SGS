<?php
/**
 * Migration 36: Harmonize and enforce Paie Lot 2 engine schema.
 * Ensures paie_regles_calcul, paie_baremes_tranches, paie_bulletins, paie_bulletin_lignes,
 * paie_bulletin_contrat_snapshot, paie_bulletin_financements, paie_bulletin_regles_snapshot,
 * paie_bulletin_regle_tranches_snapshot, paie_reglements, and paie_regularisation_integrations
 * have all expected Lot 2 canonical columns, indexes, and constraints.
 */

function migrate_36($db) {
    echo "Running Migration 36: Harmonize Paie Lot 2 schema...\n";
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // Helper function to check if table exists
    $tableExists = function($table) use ($db, $isSqlite) {
        try {
            if ($isSqlite) {
                $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name");
                $stmt->execute(['name' => $table]);
                $found = (bool)$stmt->fetch();
                $stmt->closeCursor();
                return $found;
            } else {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                $found = (bool)($stmt && $stmt->fetch());
                if ($stmt) $stmt->closeCursor();
                return $found;
            }
        } catch (Exception $e) {
            return false;
        }
    };

    // Helper function to check if column exists
    $hasColumn = function($table, $column) use ($db, $isSqlite) {
        try {
            if ($isSqlite) {
                $stmt = $db->query("PRAGMA table_info(`$table`)");
                if ($stmt) {
                    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (strtolower($col['name'] ?? '') === strtolower($column)) {
                            $stmt->closeCursor();
                            return true;
                        }
                    }
                    $stmt->closeCursor();
                }
            } else {
                $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                if ($stmt && $stmt->fetch()) {
                    $stmt->closeCursor();
                    return true;
                }
            }
        } catch (Exception $e) {
            // Ignore error
        }
        return false;
    };

    // Helper function to check if index exists
    $hasIndex = function($table, $indexName) use ($db, $isSqlite) {
        try {
            if ($isSqlite) {
                $stmt = $db->query("PRAGMA index_list(`$table`)");
                if ($stmt) {
                    while ($idx = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (strtolower($idx['name'] ?? '') === strtolower($indexName)) {
                            $stmt->closeCursor();
                            return true;
                        }
                    }
                    $stmt->closeCursor();
                }
            } else {
                $stmt = $db->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
                if ($stmt && $stmt->fetch()) {
                    $stmt->closeCursor();
                    return true;
                }
            }
        } catch (Exception $e) {
            // Ignore error
        }
        return false;
    };

    // 1. Ensure `paie_regles_calcul` exists
    if (!$tableExists('paie_regles_calcul')) {
        echo "Creating table paie_regles_calcul...\n";
        if ($isSqlite) {
            $db->exec("CREATE TABLE IF NOT EXISTS paie_regles_calcul (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                juridiction_code VARCHAR(20) NOT NULL DEFAULT 'DEFAULT',
                pays_code VARCHAR(10) NOT NULL DEFAULT 'RCA',
                code_regle VARCHAR(50) NOT NULL,
                libelle VARCHAR(150) NOT NULL,
                categorie VARCHAR(50) NOT NULL,
                mode_calcul VARCHAR(50) NOT NULL,
                base_calcul_type VARCHAR(50) NOT NULL DEFAULT 'brut_total',
                taux_par_defaut DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                montant_fixe_salarial DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                taux_patronal DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                montant_fixe_patronal DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                seuil_minimum DECIMAL(15,4) NULL,
                plafond_maximum DECIMAL(15,4) NULL,
                abattement_forfaitaire DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                abattement_pourcentage DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                ordre_application INT NOT NULL DEFAULT 100,
                type_contrat_id INT NULL,
                formule VARCHAR(255) NULL,
                est_systeme TINYINT NOT NULL DEFAULT 1,
                actif TINYINT NOT NULL DEFAULT 1,
                date_debut_validite DATE NOT NULL,
                date_fin_validite DATE NULL,
                created_at DATETIME NOT NULL,
                UNIQUE (juridiction_code, code_regle, date_debut_validite)
            );");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS paie_regles_calcul (
                id INT AUTO_INCREMENT PRIMARY KEY,
                juridiction_code VARCHAR(20) NOT NULL DEFAULT 'DEFAULT',
                pays_code VARCHAR(10) NOT NULL DEFAULT 'RCA',
                code_regle VARCHAR(50) NOT NULL,
                libelle VARCHAR(150) NOT NULL,
                categorie VARCHAR(50) NOT NULL,
                mode_calcul VARCHAR(50) NOT NULL,
                base_calcul_type VARCHAR(50) NOT NULL DEFAULT 'brut_total',
                taux_par_defaut DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                montant_fixe_salarial DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                taux_patronal DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                montant_fixe_patronal DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                seuil_minimum DECIMAL(15,4) NULL,
                plafond_maximum DECIMAL(15,4) NULL,
                abattement_forfaitaire DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                abattement_pourcentage DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                ordre_application INT NOT NULL DEFAULT 100,
                type_contrat_id INT NULL,
                formule VARCHAR(255) NULL,
                est_systeme TINYINT(1) NOT NULL DEFAULT 1,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                date_debut_validite DATE NOT NULL,
                date_fin_validite DATE NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uk_paie_regle_jur (juridiction_code, code_regle, date_debut_validite),
                INDEX idx_paie_reg_jur_act (juridiction_code, pays_code, actif)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    } else {
        // If paie_regles_calcul already exists, check and add missing extension columns
        $regleCols = [
            'pays_code' => "VARCHAR(10) NOT NULL DEFAULT 'RCA'",
            'base_calcul_type' => "VARCHAR(50) NOT NULL DEFAULT 'brut_total'",
            'montant_fixe_salarial' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'taux_patronal' => "DECIMAL(10,4) NOT NULL DEFAULT 0.0000",
            'montant_fixe_patronal' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'seuil_minimum' => "DECIMAL(15,4) NULL",
            'plafond_maximum' => "DECIMAL(15,4) NULL",
            'abattement_forfaitaire' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'abattement_pourcentage' => "DECIMAL(10,4) NOT NULL DEFAULT 0.0000",
            'ordre_application' => "INT NOT NULL DEFAULT 100",
            'type_contrat_id' => "INT NULL"
        ];
        foreach ($regleCols as $col => $def) {
            if (!$hasColumn('paie_regles_calcul', $col)) {
                $db->exec("ALTER TABLE paie_regles_calcul ADD COLUMN $col $def;");
            }
        }
    }

    // 2. Ensure `paie_baremes_tranches` has `regle_id` column and correct schema
    if ($tableExists('paie_baremes_tranches')) {
        if (!$hasColumn('paie_baremes_tranches', 'regle_id')) {
            echo "Adding column 'regle_id' to paie_baremes_tranches...\n";
            $db->exec($isSqlite
                ? "ALTER TABLE paie_baremes_tranches ADD COLUMN regle_id INTEGER NULL;"
                : "ALTER TABLE paie_baremes_tranches ADD COLUMN regle_id INT NULL AFTER id;"
            );
        }
        if (!$hasColumn('paie_baremes_tranches', 'tranche_numero')) {
            $db->exec("ALTER TABLE paie_baremes_tranches ADD COLUMN tranche_numero INT NOT NULL DEFAULT 1;");
        }
        if (!$hasColumn('paie_baremes_tranches', 'limite_inferieure')) {
            $db->exec("ALTER TABLE paie_baremes_tranches ADD COLUMN limite_inferieure DECIMAL(15,4) NOT NULL DEFAULT 0.0000;");
        }
        if (!$hasColumn('paie_baremes_tranches', 'limite_superieure')) {
            $db->exec("ALTER TABLE paie_baremes_tranches ADD COLUMN limite_superieure DECIMAL(15,4) NULL;");
        }
        if (!$hasColumn('paie_baremes_tranches', 'montant_fixe')) {
            $db->exec("ALTER TABLE paie_baremes_tranches ADD COLUMN montant_fixe DECIMAL(15,4) NOT NULL DEFAULT 0.0000;");
        }

        if (!$isSqlite && !$hasIndex('paie_baremes_tranches', 'uk_paie_bar_tranche')) {
            try {
                $db->exec("ALTER TABLE paie_baremes_tranches ADD UNIQUE KEY uk_paie_bar_tranche (regle_id, tranche_numero);");
            } catch (Exception $e) {
                $errMsg = $e->getMessage();
                if (strpos($errMsg, '1062') !== false || strpos($errMsg, 'Duplicate entry') !== false) {
                    throw new RuntimeException("Migration 36: Impossible d'ajouter la contrainte unique 'uk_paie_bar_tranche' sur paie_baremes_tranches en raison de doublons (Erreur 1062) : " . $errMsg, 0, $e);
                }
                if (!$hasIndex('paie_baremes_tranches', 'uk_paie_bar_tranche')) {
                    throw new RuntimeException("Migration 36: Échec d'ajout de la contrainte unique 'uk_paie_bar_tranche' sur paie_baremes_tranches : " . $errMsg, 0, $e);
                }
            }
        }
    }

    // 3. Ensure `paie_bulletins` has all Lot 2 engine columns
    if ($tableExists('paie_bulletins')) {
        $bulletinCols = [
            'version_num' => "INT NOT NULL DEFAULT 1",
            'est_version_active' => $isSqlite ? "TINYINT NOT NULL DEFAULT 1" : "TINYINT(1) NOT NULL DEFAULT 1",
            'entite_juridique_id' => $isSqlite ? "INTEGER NULL" : "INT NULL",
            'cycle_id' => $isSqlite ? "INTEGER NULL" : "INT NULL",
            'salaire_base' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'total_brut' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'total_cotisations_salariales' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'total_impots' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'total_retenues' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'net_imposable' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'total_cotisations_patronales' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'cout_total_employeur' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'devise' => "VARCHAR(10) NOT NULL DEFAULT 'XAF'",
            'taux_change' => "DECIMAL(12,6) NOT NULL DEFAULT 1.000000",
            'statut_bulletin' => "VARCHAR(20) NOT NULL DEFAULT 'brouillon'",
            'statut_comptabilisation' => "VARCHAR(20) NOT NULL DEFAULT 'non_comptabilise'",
            'statut_reglement' => "VARCHAR(20) NOT NULL DEFAULT 'non_paye'",
            'statut_cloture' => "VARCHAR(20) NOT NULL DEFAULT 'ouvert'",
            'est_reprise_legacy' => $isSqlite ? "TINYINT NOT NULL DEFAULT 0" : "TINYINT(1) NOT NULL DEFAULT 0",
            'idempotency_key' => "VARCHAR(100) NULL",
            'content_hash' => "VARCHAR(64) NULL",
            'created_at' => "DATETIME NULL",
            'updated_at' => "DATETIME NULL"
        ];

        foreach ($bulletinCols as $col => $def) {
            if (!$hasColumn('paie_bulletins', $col)) {
                echo "Adding column '$col' to paie_bulletins...\n";
                $db->exec("ALTER TABLE paie_bulletins ADD COLUMN $col $def;");
            }
        }

        // Backfill data if old columns existed
        if ($hasColumn('paie_bulletins', 'brut_total')) {
            try {
                $db->exec("UPDATE paie_bulletins SET total_brut = brut_total WHERE (total_brut = 0 OR total_brut IS NULL) AND brut_total > 0;");
            } catch (Exception $e) {}
        }
        if ($hasColumn('paie_bulletins', 'version')) {
            try {
                $db->exec("UPDATE paie_bulletins SET version_num = version WHERE (version_num = 1 OR version_num IS NULL) AND version > 1;");
            } catch (Exception $e) {}
        }
        if ($hasColumn('paie_bulletins', 'statut')) {
            try {
                $db->exec("UPDATE paie_bulletins SET statut_bulletin = statut WHERE statut_bulletin = 'brouillon' AND statut != 'brouillon' AND statut IS NOT NULL;");
            } catch (Exception $e) {}
        }
        if ($hasColumn('paie_bulletins', 'date_calcul')) {
            try {
                $db->exec("UPDATE paie_bulletins SET created_at = date_calcul WHERE created_at IS NULL AND date_calcul IS NOT NULL;");
                $db->exec("UPDATE paie_bulletins SET updated_at = date_calcul WHERE updated_at IS NULL AND date_calcul IS NOT NULL;");
            } catch (Exception $e) {}
        }

        // On MySQL, add virtual stored column active_scope_key and indexes if missing
        if (!$isSqlite) {
            // Helper to inspect MySQL/MariaDB structured PDO error info
            $getMysqlErrorCode = function(Exception $e) {
                if ($e instanceof PDOException && isset($e->errorInfo[1])) {
                    return (int)$e->errorInfo[1];
                }
                if (preg_match('/\b(1061|1062)\b/', $e->getMessage(), $m)) {
                    return (int)$m[1];
                }
                return 0;
            };

            // Helper to strictly normalize and verify a generated expression for active_scope_key
            $isExactActiveScopeExpr = function($rawExpr) {
                if (empty($rawExpr)) return false;
                // Unify quotes, strip spaces, backticks, parentheses, and lowercase
                $clean = str_replace(["\"", "'"], "'", $rawExpr);
                $clean = strtolower(preg_replace("/[\s\`\(\)]+/", "", $clean));

                $expectedCanonical = "casewhenest_version_active=1thenconcatpersonnel_id,':',entite_juridique_id,':',contrat_id,':',periode_idelsenullend";
                return ($clean === $expectedCanonical);
            };

            // Helper to verify compliance of active_scope_key generated column on MySQL via INFORMATION_SCHEMA / SHOW CREATE TABLE
            $isActiveScopeKeyCompliant = function() use ($db, $isExactActiveScopeExpr) {
                // 1. Check generation expression and storage mode in information_schema.COLUMNS
                $stmt = $db->prepare("
                    SELECT GENERATION_EXPRESSION, EXTRA
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'paie_bulletins'
                      AND COLUMN_NAME = 'active_scope_key'
                ");
                $stmt->execute();
                $colInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if (!$colInfo) return false;

                // Mode must strictly be STORED (never VIRTUAL)
                $extra = strtoupper($colInfo['EXTRA'] ?? '');
                if (strpos($extra, 'STORED') === false) {
                    return false;
                }

                // 2. Exact expression inspection
                $genExpr = $colInfo['GENERATION_EXPRESSION'] ?? '';
                if (!empty($genExpr)) {
                    return $isExactActiveScopeExpr($genExpr);
                }

                // Fallback to SHOW CREATE TABLE inspection
                $stmtCreate = $db->query("SHOW CREATE TABLE `paie_bulletins`");
                $createRow = $stmtCreate ? $stmtCreate->fetch(PDO::FETCH_NUM) : null;
                if ($stmtCreate) $stmtCreate->closeCursor();

                $createSql = $createRow[1] ?? '';
                if (!empty($createSql) && preg_match('/`active_scope_key`[^\n,]+/i', $createSql, $m)) {
                    $defLine = $m[0];
                    if (stripos($defLine, 'STORED') === false) {
                        return false;
                    }
                    if (preg_match('/GENERATED ALWAYS AS\s*\((.*)\)\s*STORED/i', $defLine, $matchesExpr)) {
                        return $isExactActiveScopeExpr($matchesExpr[1]);
                    }
                }

                return false;
            };

            // Helper to inspect MySQL index status: returns ['exists' => bool, 'compliant' => bool, 'reason' => string]
            // Strictly propagates PDO/SQL exceptions rather than swallowing them
            $inspectIndexCompliance = function($indexName, $expectedColumns, $shouldBeUnique = true) use ($db) {
                $stmt = $db->query("SHOW INDEX FROM `paie_bulletins` WHERE Key_name = '$indexName'");
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                if ($stmt) $stmt->closeCursor();

                if (empty($rows)) {
                    return ['exists' => false, 'compliant' => false, 'reason' => 'Index absent'];
                }

                // Sort rows by Seq_in_index ascending
                usort($rows, function($a, $b) {
                    return ((int)($a['Seq_in_index'] ?? 0)) <=> ((int)($b['Seq_in_index'] ?? 0));
                });

                // Verify uniqueness
                $firstRow = $rows[0];
                $isUnique = ((int)($firstRow['Non_unique'] ?? 1)) === 0;
                if ($shouldBeUnique && !$isUnique) {
                    return ['exists' => true, 'compliant' => false, 'reason' => "L'index '$indexName' existe mais n'est pas UNIQUE."];
                }

                // Verify column list and order
                $colNames = array_column($rows, 'Column_name');
                $expected = is_array($expectedColumns) ? $expectedColumns : [$expectedColumns];
                if ($colNames !== $expected) {
                    return ['exists' => true, 'compliant' => false, 'reason' => "L'index '$indexName' existe avec les colonnes (" . implode(', ', $colNames) . ") au lieu de (" . implode(', ', $expected) . ")."];
                }

                // Verify absence of unexpected sub-part prefixes
                foreach ($rows as $r) {
                    if (!empty($r['Sub_part'])) {
                        return ['exists' => true, 'compliant' => false, 'reason' => "L'index '$indexName' contient une longueur de préfixe Sub_part inattendue."];
                    }
                }

                return ['exists' => true, 'compliant' => true, 'reason' => 'Conforme'];
            };

            // 3.1 Ensure active_scope_key column exists and is strictly compliant
            if (!$isActiveScopeKeyCompliant()) {
                if ($hasColumn('paie_bulletins', 'active_scope_key')) {
                    throw new RuntimeException("Migration 36: La colonne 'active_scope_key' existe déjà sur paie_bulletins mais son expression de génération ou son stockage n'est pas conforme aux spécifications canoniques Lot 2.");
                }

                echo "Adding generated column 'active_scope_key' to paie_bulletins...\n";
                try {
                    $db->exec("ALTER TABLE paie_bulletins ADD COLUMN active_scope_key VARCHAR(255) GENERATED ALWAYS AS (
                        CASE WHEN est_version_active = 1 THEN CONCAT(personnel_id, ':', entite_juridique_id, ':', contrat_id, ':', periode_id) ELSE NULL END
                    ) STORED;");
                } catch (Exception $e) {
                    if (!$isActiveScopeKeyCompliant()) {
                        throw new RuntimeException("Migration 36: Échec critique de création de la colonne générée 'active_scope_key' sur paie_bulletins : " . $e->getMessage(), 0, $e);
                    }
                }

                if (!$isActiveScopeKeyCompliant()) {
                    throw new RuntimeException("Migration 36: La colonne générée 'active_scope_key' sur paie_bulletins n'est pas dans un état conforme après exécution.");
                }
            }

            // 3.2 Ensure uk_bulletin_version unique key exists and is strictly compliant
            $expectedVersionCols = ['personnel_id', 'entite_juridique_id', 'contrat_id', 'periode_id', 'version_num'];
            $chkVersion = $inspectIndexCompliance('uk_bulletin_version', $expectedVersionCols, true);
            if (!$chkVersion['compliant']) {
                if ($chkVersion['exists']) {
                    throw new RuntimeException("Migration 36: Index incompatible détecté sur paie_bulletins : " . $chkVersion['reason']);
                }

                try {
                    $db->exec("ALTER TABLE paie_bulletins ADD UNIQUE KEY uk_bulletin_version (personnel_id, entite_juridique_id, contrat_id, periode_id, version_num);");
                } catch (Exception $e) {
                    $mysqlErr = $getMysqlErrorCode($e);
                    if ($mysqlErr === 1062) {
                        throw new RuntimeException("Migration 36: Impossible d'ajouter la contrainte unique 'uk_bulletin_version' sur paie_bulletins en raison de doublons de versions existants (Erreur 1062) : " . $e->getMessage(), 0, $e);
                    }
                    if ($mysqlErr === 1061) {
                        $chkAfter = $inspectIndexCompliance('uk_bulletin_version', $expectedVersionCols, true);
                        if (!$chkAfter['compliant']) {
                            throw new RuntimeException("Migration 36: Index 'uk_bulletin_version' pré-existant non conforme (Erreur 1061) : " . $chkAfter['reason'], 0, $e);
                        }
                    } else {
                        $chkAfter = $inspectIndexCompliance('uk_bulletin_version', $expectedVersionCols, true);
                        if (!$chkAfter['compliant']) {
                            throw new RuntimeException("Migration 36: Échec d'ajout de la contrainte unique 'uk_bulletin_version' sur paie_bulletins : " . $e->getMessage(), 0, $e);
                        }
                    }
                }

                $finalChk = $inspectIndexCompliance('uk_bulletin_version', $expectedVersionCols, true);
                if (!$finalChk['compliant']) {
                    throw new RuntimeException("Migration 36: La contrainte unique 'uk_bulletin_version' sur paie_bulletins n'est pas conforme après exécution : " . $finalChk['reason']);
                }
            }

            // 3.3 Ensure uk_bulletin_active_scope unique key exists and is strictly compliant
            $chkActiveScope = $inspectIndexCompliance('uk_bulletin_active_scope', ['active_scope_key'], true);
            if (!$chkActiveScope['compliant']) {
                if ($chkActiveScope['exists']) {
                    throw new RuntimeException("Migration 36: Index incompatible détecté sur paie_bulletins : " . $chkActiveScope['reason']);
                }

                echo "Adding unique index 'uk_bulletin_active_scope' to paie_bulletins...\n";
                try {
                    $db->exec("ALTER TABLE paie_bulletins ADD UNIQUE KEY uk_bulletin_active_scope (active_scope_key);");
                } catch (Exception $e) {
                    $mysqlErr = $getMysqlErrorCode($e);
                    if ($mysqlErr === 1062) {
                        throw new RuntimeException("Migration 36: Impossible d'ajouter l'index d'unicité 'uk_bulletin_active_scope' sur paie_bulletins. Plusieurs bulletins actifs (est_version_active = 1) coexistent pour le même périmètre salarié/contrat/période (Erreur 1062) : " . $e->getMessage(), 0, $e);
                    }
                    if ($mysqlErr === 1061) {
                        $chkAfter = $inspectIndexCompliance('uk_bulletin_active_scope', ['active_scope_key'], true);
                        if (!$chkAfter['compliant']) {
                            throw new RuntimeException("Migration 36: Index 'uk_bulletin_active_scope' pré-existant non conforme (Erreur 1061) : " . $chkAfter['reason'], 0, $e);
                        }
                    } else {
                        $chkAfter = $inspectIndexCompliance('uk_bulletin_active_scope', ['active_scope_key'], true);
                        if (!$chkAfter['compliant']) {
                            throw new RuntimeException("Migration 36: Échec d'ajout de l'index d'unicité 'uk_bulletin_active_scope' sur paie_bulletins : " . $e->getMessage(), 0, $e);
                        }
                    }
                }

                $finalChkActive = $inspectIndexCompliance('uk_bulletin_active_scope', ['active_scope_key'], true);
                if (!$finalChkActive['compliant']) {
                    throw new RuntimeException("Migration 36: L'index d'unicité 'uk_bulletin_active_scope' sur paie_bulletins n'est pas conforme après exécution : " . $finalChkActive['reason']);
                }
            }

            // 3.4 Ensure idx_paie_bul_search index exists and is compliant
            $expectedSearchCols = ['personnel_id', 'periode_id', 'statut_bulletin'];
            $chkSearch = $inspectIndexCompliance('idx_paie_bul_search', $expectedSearchCols, false);
            if (!$chkSearch['compliant']) {
                if ($chkSearch['exists']) {
                    throw new RuntimeException("Migration 36: Index incompatible détecté sur paie_bulletins : " . $chkSearch['reason']);
                }

                try {
                    $db->exec("ALTER TABLE paie_bulletins ADD INDEX idx_paie_bul_search (personnel_id, periode_id, statut_bulletin);");
                } catch (Exception $e) {
                    $mysqlErr = $getMysqlErrorCode($e);
                    if ($mysqlErr === 1061) {
                        $chkAfter = $inspectIndexCompliance('idx_paie_bul_search', $expectedSearchCols, false);
                        if (!$chkAfter['compliant']) {
                            throw new RuntimeException("Migration 36: Index 'idx_paie_bul_search' pré-existant non conforme (Erreur 1061) : " . $chkAfter['reason'], 0, $e);
                        }
                    } else {
                        $chkAfter = $inspectIndexCompliance('idx_paie_bul_search', $expectedSearchCols, false);
                        if (!$chkAfter['compliant']) {
                            throw new RuntimeException("Migration 36: Échec d'ajout de l'index 'idx_paie_bul_search' sur paie_bulletins : " . $e->getMessage(), 0, $e);
                        }
                    }
                }

                $finalChkSearch = $inspectIndexCompliance('idx_paie_bul_search', $expectedSearchCols, false);
                if (!$finalChkSearch['compliant']) {
                    throw new RuntimeException("Migration 36: L'index 'idx_paie_bul_search' sur paie_bulletins n'est pas conforme après exécution : " . $finalChkSearch['reason']);
                }
            }
        }
    }

    // 4. Ensure `paie_bulletin_lignes` columns
    if ($tableExists('paie_bulletin_lignes')) {
        $ligneCols = [
            'categorie' => "VARCHAR(50) NOT NULL DEFAULT 'gain'",
            'taux' => "DECIMAL(10,4) NOT NULL DEFAULT 0.0000",
            'est_imposable' => $isSqlite ? "TINYINT NOT NULL DEFAULT 1" : "TINYINT(1) NOT NULL DEFAULT 1",
            'est_cotisable' => $isSqlite ? "TINYINT NOT NULL DEFAULT 1" : "TINYINT(1) NOT NULL DEFAULT 1"
        ];
        foreach ($ligneCols as $col => $def) {
            if (!$hasColumn('paie_bulletin_lignes', $col)) {
                echo "Adding column '$col' to paie_bulletin_lignes...\n";
                $db->exec("ALTER TABLE paie_bulletin_lignes ADD COLUMN $col $def;");
            }
        }
        if ($hasColumn('paie_bulletin_lignes', 'taux_salarial')) {
            try {
                $db->exec("UPDATE paie_bulletin_lignes SET taux = taux_salarial WHERE (taux = 0 OR taux IS NULL) AND taux_salarial > 0;");
            } catch (Exception $e) {}
        }
    }

    // 5. Ensure `paie_bulletin_contrat_snapshot` columns
    if ($tableExists('paie_bulletin_contrat_snapshot')) {
        $contratSnapCols = [
            'version_num' => "INT NOT NULL DEFAULT 1",
            'type_contrat' => "VARCHAR(50) NOT NULL DEFAULT 'CDI'",
            'mode_calcul_principal' => "VARCHAR(50) NOT NULL DEFAULT 'forfait_fixe'",
            'devise' => "VARCHAR(10) NOT NULL DEFAULT 'XAF'",
            'raw_json_snapshot' => $isSqlite ? "TEXT NULL" : "JSON NULL",
            'created_at' => "DATETIME NULL"
        ];
        foreach ($contratSnapCols as $col => $def) {
            if (!$hasColumn('paie_bulletin_contrat_snapshot', $col)) {
                echo "Adding column '$col' to paie_bulletin_contrat_snapshot...\n";
                $db->exec("ALTER TABLE paie_bulletin_contrat_snapshot ADD COLUMN $col $def;");
            }
        }
    }

    // 6. Ensure `paie_bulletin_financements` columns
    if ($tableExists('paie_bulletin_financements')) {
        $financementCols = [
            'financeur_nom' => "VARCHAR(150) NOT NULL DEFAULT 'Etablissement'",
            'type_financeur' => "VARCHAR(50) NOT NULL DEFAULT 'propre'",
            'pourcentage_prise_en_charge' => "DECIMAL(5,2) NOT NULL DEFAULT 100.00",
            'montant_prise_en_charge' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000"
        ];
        foreach ($financementCols as $col => $def) {
            if (!$hasColumn('paie_bulletin_financements', $col)) {
                echo "Adding column '$col' to paie_bulletin_financements...\n";
                $db->exec("ALTER TABLE paie_bulletin_financements ADD COLUMN $col $def;");
            }
        }
    }

    // 7. Ensure `paie_bulletin_regles_snapshot` columns
    if ($tableExists('paie_bulletin_regles_snapshot')) {
        $regleSnapCols = [
            'regle_id' => $isSqlite ? "INTEGER NULL" : "INT NULL",
            'code_regle' => "VARCHAR(50) NOT NULL DEFAULT ''",
            'categorie' => "VARCHAR(50) NOT NULL DEFAULT 'gain'",
            'mode_calcul' => "VARCHAR(50) NOT NULL DEFAULT 'fixe'",
            'taux_applique' => "DECIMAL(10,4) NOT NULL DEFAULT 0.0000",
            'raw_json_snapshot' => $isSqlite ? "TEXT NULL" : "JSON NULL"
        ];
        foreach ($regleSnapCols as $col => $def) {
            if (!$hasColumn('paie_bulletin_regles_snapshot', $col)) {
                echo "Adding column '$col' to paie_bulletin_regles_snapshot...\n";
                $db->exec("ALTER TABLE paie_bulletin_regles_snapshot ADD COLUMN $col $def;");
            }
        }
        if ($hasColumn('paie_bulletin_regles_snapshot', 'code_rubrique')) {
            try {
                $db->exec("UPDATE paie_bulletin_regles_snapshot SET code_regle = code_rubrique WHERE (code_regle = '' OR code_regle IS NULL) AND code_rubrique IS NOT NULL;");
            } catch (Exception $e) {}
        }
    }

    // 8. Ensure `paie_bulletin_regle_tranches_snapshot` columns
    if ($tableExists('paie_bulletin_regle_tranches_snapshot')) {
        $trancheSnapCols = [
            'tranche_numero' => "INT NOT NULL DEFAULT 1",
            'limite_inferieure' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'limite_superieure' => "DECIMAL(15,4) NULL",
            'base_imposable_tranche' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000",
            'montant_calcule' => "DECIMAL(15,4) NOT NULL DEFAULT 0.0000"
        ];
        foreach ($trancheSnapCols as $col => $def) {
            if (!$hasColumn('paie_bulletin_regle_tranches_snapshot', $col)) {
                echo "Adding column '$col' to paie_bulletin_regle_tranches_snapshot...\n";
                $db->exec("ALTER TABLE paie_bulletin_regle_tranches_snapshot ADD COLUMN $col $def;");
            }
        }
        if ($hasColumn('paie_bulletin_regle_tranches_snapshot', 'tranche_min')) {
            try {
                $db->exec("UPDATE paie_bulletin_regle_tranches_snapshot SET limite_inferieure = tranche_min WHERE (limite_inferieure = 0 OR limite_inferieure IS NULL) AND tranche_min IS NOT NULL;");
            } catch (Exception $e) {}
        }
        if ($hasColumn('paie_bulletin_regle_tranches_snapshot', 'tranche_max')) {
            try {
                $db->exec("UPDATE paie_bulletin_regle_tranches_snapshot SET limite_superieure = tranche_max WHERE limite_superieure IS NULL AND tranche_max IS NOT NULL;");
            } catch (Exception $e) {}
        }
    }

    // 9. Ensure `paie_reglements` columns
    if ($tableExists('paie_reglements')) {
        $reglementCols = [
            'compte_financier_id' => $isSqlite ? "INTEGER NULL" : "INT NULL",
            'mode_reglement' => "VARCHAR(50) NOT NULL DEFAULT 'virement'",
            'reference_transaction' => "VARCHAR(100) NULL",
            'cree_par' => $isSqlite ? "INTEGER NULL" : "INT NULL"
        ];
        foreach ($reglementCols as $col => $def) {
            if (!$hasColumn('paie_reglements', $col)) {
                echo "Adding column '$col' to paie_reglements...\n";
                $db->exec("ALTER TABLE paie_reglements ADD COLUMN $col $def;");
            }
        }
        if ($hasColumn('paie_reglements', 'compte_id')) {
            try {
                $db->exec("UPDATE paie_reglements SET compte_financier_id = compte_id WHERE compte_financier_id IS NULL AND compte_id IS NOT NULL;");
            } catch (Exception $e) {}
        }
        if ($hasColumn('paie_reglements', 'mode_paiement')) {
            try {
                $db->exec("UPDATE paie_reglements SET mode_reglement = mode_paiement WHERE (mode_reglement = '' OR mode_reglement IS NULL) AND mode_paiement IS NOT NULL;");
            } catch (Exception $e) {}
        }
        if ($hasColumn('paie_reglements', 'reference_paiement')) {
            try {
                $db->exec("UPDATE paie_reglements SET reference_transaction = reference_paiement WHERE reference_transaction IS NULL AND reference_paiement IS NOT NULL;");
            } catch (Exception $e) {}
        }
        if ($hasColumn('paie_reglements', 'execute_par')) {
            try {
                $db->exec("UPDATE paie_reglements SET cree_par = execute_par WHERE cree_par IS NULL AND execute_par IS NOT NULL;");
            } catch (Exception $e) {}
        }
    }

    // 10. Ensure `paie_regularisation_integrations` columns
    if ($tableExists('paie_regularisation_integrations')) {
        if (!$hasColumn('paie_regularisation_integrations', 'bulletin_id')) {
            echo "Adding column 'bulletin_id' to paie_regularisation_integrations...\n";
            $db->exec($isSqlite
                ? "ALTER TABLE paie_regularisation_integrations ADD COLUMN bulletin_id INTEGER NULL;"
                : "ALTER TABLE paie_regularisation_integrations ADD COLUMN bulletin_id BIGINT NULL AFTER regularisation_id;"
            );
            if ($hasColumn('paie_regularisation_integrations', 'bulletin_destination_id')) {
                try {
                    $db->exec("UPDATE paie_regularisation_integrations SET bulletin_id = bulletin_destination_id WHERE bulletin_id IS NULL AND bulletin_destination_id IS NOT NULL;");
                } catch (Exception $e) {}
            }
        }
    }

    // 11. Ensure `paie_audit_logs` exists
    if (!$tableExists('paie_audit_logs')) {
        echo "Creating table paie_audit_logs...\n";
        if ($isSqlite) {
            $db->exec("CREATE TABLE IF NOT EXISTS paie_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                entity_type VARCHAR(50) NOT NULL,
                entity_id BIGINT NOT NULL,
                action VARCHAR(50) NOT NULL,
                user_id INTEGER NOT NULL,
                payload_before TEXT NULL,
                payload_after TEXT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
            );");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS paie_audit_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                entity_type VARCHAR(50) NOT NULL,
                entity_id BIGINT NOT NULL,
                action VARCHAR(50) NOT NULL,
                user_id INT NOT NULL,
                payload_before JSON NULL,
                payload_after JSON NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
                INDEX idx_paie_aud_entity (entity_type, entity_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    }

    echo "Migration 36 completed successfully.\n";
}
