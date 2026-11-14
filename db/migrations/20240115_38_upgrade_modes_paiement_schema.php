<?php

/**
 * Upgrade the legacy global payment-mode table to the routing schema.
 * Existing rows keep their ids and remain shared (lycee_id = NULL) so old
 * payment references continue to work while tenant-specific rows are added.
 */
function migrate_38($db) {
    echo "Running Migration 38: Upgrade modes_paiement schema...\n";
    $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

    $hasColumn = function($table, $column) use ($db, $isSqlite) {
        if ($isSqlite) {
            $stmt = $db->query("PRAGMA table_info({$table})");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
                if (strtolower($row['name']) === strtolower($column)) {
                    return true;
                }
            }
            return false;
        }

        $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    };

    $hadTypeCanal = $hasColumn('modes_paiement', 'type_canal');
    $hadLegacyType = $hasColumn('modes_paiement', 'type_compte_defaut');
    $definitions = $isSqlite
        ? [
            'lycee_id' => 'INTEGER DEFAULT NULL',
            'type_canal' => "TEXT NOT NULL DEFAULT 'especes'",
            'compte_financier_id' => 'INTEGER DEFAULT NULL',
            'exige_session_caisse' => 'INTEGER NOT NULL DEFAULT 1',
            'exige_reference_transaction' => 'INTEGER NOT NULL DEFAULT 0',
            'updated_at' => 'TEXT DEFAULT NULL'
        ]
        : [
            'lycee_id' => 'INT DEFAULT NULL',
            'type_canal' => "ENUM('especes', 'mobile_money', 'banque', 'autre') NOT NULL DEFAULT 'especes'",
            'compte_financier_id' => 'INT DEFAULT NULL',
            'exige_session_caisse' => 'BOOLEAN NOT NULL DEFAULT TRUE',
            'exige_reference_transaction' => 'BOOLEAN NOT NULL DEFAULT FALSE',
            'updated_at' => 'TIMESTAMP NULL DEFAULT NULL'
        ];

    foreach ($definitions as $column => $definition) {
        if (!$hasColumn('modes_paiement', $column)) {
            $db->exec("ALTER TABLE modes_paiement ADD COLUMN {$column} {$definition}");
        }
    }

    if (!$hadTypeCanal && $hadLegacyType) {
        $db->exec("UPDATE modes_paiement SET type_canal = CASE LOWER(type_compte_defaut)
            WHEN 'caisse' THEN 'especes'
            WHEN 'especes' THEN 'especes'
            WHEN 'mobile_money' THEN 'mobile_money'
            WHEN 'banque' THEN 'banque'
            ELSE 'autre'
        END");
        $db->exec("UPDATE modes_paiement
            SET exige_session_caisse = CASE WHEN type_canal = 'especes' THEN 1 ELSE 0 END,
                exige_reference_transaction = CASE WHEN type_canal = 'especes' THEN 0 ELSE 1 END");
    }

    $db->exec("UPDATE modes_paiement SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL");

    // Upgrade the legacy ventilation table too; otherwise the next write after
    // resolving a mode would still target columns that only exist in migration 30.
    $hadSourceType = $hasColumn('paiement_ventilations', 'source_type');
    $hadSourceId = $hasColumn('paiement_ventilations', 'source_id');
    $hadCompteFinancierId = $hasColumn('paiement_ventilations', 'compte_financier_id');
    $hadReferenceTransaction = $hasColumn('paiement_ventilations', 'reference_transaction');
    $hadMouvementTreasorerieId = $hasColumn('paiement_ventilations', 'mouvement_tresorerie_id');
    $ventilationDefinitions = $isSqlite
        ? [
            'lycee_id' => 'INTEGER DEFAULT NULL',
            'source_type' => 'TEXT DEFAULT NULL',
            'source_id' => 'INTEGER DEFAULT NULL',
            'compte_financier_id' => 'INTEGER DEFAULT NULL',
            'session_caisse_id' => 'INTEGER DEFAULT NULL',
            'reference_transaction' => 'TEXT DEFAULT NULL',
            'mouvement_tresorerie_id' => 'INTEGER DEFAULT NULL',
            'updated_at' => 'TEXT DEFAULT NULL'
        ]
        : [
            'lycee_id' => 'INT DEFAULT NULL',
            'source_type' => 'VARCHAR(50) DEFAULT NULL',
            'source_id' => 'INT DEFAULT NULL',
            'compte_financier_id' => 'INT DEFAULT NULL',
            'session_caisse_id' => 'INT DEFAULT NULL',
            'reference_transaction' => 'VARCHAR(150) DEFAULT NULL',
            'mouvement_tresorerie_id' => 'INT DEFAULT NULL',
            'updated_at' => 'TIMESTAMP NULL DEFAULT NULL'
        ];

    foreach ($ventilationDefinitions as $column => $definition) {
        if (!$hasColumn('paiement_ventilations', $column)) {
            $db->exec("ALTER TABLE paiement_ventilations ADD COLUMN {$column} {$definition}");
        }
    }

    if (!$hadSourceType && $hasColumn('paiement_ventilations', 'paiement_type')) {
        $db->exec("UPDATE paiement_ventilations SET source_type = paiement_type WHERE source_type IS NULL");
    }
    if (!$hadSourceId && $hasColumn('paiement_ventilations', 'paiement_id')) {
        $db->exec("UPDATE paiement_ventilations SET source_id = paiement_id WHERE source_id IS NULL");
    }
    if (!$hadCompteFinancierId && $hasColumn('paiement_ventilations', 'compte_id')) {
        $db->exec("UPDATE paiement_ventilations SET compte_financier_id = compte_id WHERE compte_financier_id IS NULL");
    }
    if (!$hadReferenceTransaction && $hasColumn('paiement_ventilations', 'reference_externe')) {
        $db->exec("UPDATE paiement_ventilations SET reference_transaction = reference_externe WHERE reference_transaction IS NULL");
    }
    if (!$hadMouvementTreasorerieId && $hasColumn('paiement_ventilations', 'mouvement_id')) {
        $db->exec("UPDATE paiement_ventilations SET mouvement_tresorerie_id = mouvement_id WHERE mouvement_tresorerie_id IS NULL");
    }
    $db->exec("UPDATE paiement_ventilations SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL");

    echo "Migration 38 completed successfully.\n";
}
