<?php

require_once __DIR__ . '/../config/database.php';

class ModePaiement {

    private static $columns = null;

    private static function getColumns() {
        if (self::$columns !== null) {
            return self::$columns;
        }

        $db = Database::getInstance();
        self::$columns = [];
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $stmt = $db->query("PRAGMA table_info(modes_paiement)");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $column) {
                self::$columns[strtolower($column['name'])] = true;
            }
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM `modes_paiement`");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $column) {
                self::$columns[strtolower($column['Field'])] = true;
            }
        }

        return self::$columns;
    }

    private static function normalize($mode) {
        if (!$mode) {
            return null;
        }

        $mode['lycee_id'] = $mode['lycee_id'] ?? null;
        $typeCanal = strtolower(trim((string)($mode['type_canal'] ?? '')));
        if ($typeCanal === '') {
            $legacyType = strtolower(trim((string)($mode['type_compte_defaut'] ?? '')));
            $typeCanal = [
                'caisse' => 'especes',
                'especes' => 'especes',
                'mobile_money' => 'mobile_money',
                'banque' => 'banque',
                'autre' => 'autre'
            ][$legacyType] ?? 'autre';
        }

        $mode['type_canal'] = $typeCanal;
        $mode['compte_financier_id'] = $mode['compte_financier_id'] ?? null;
        $mode['exige_session_caisse'] = $mode['exige_session_caisse'] ?? ($typeCanal === 'especes' ? 1 : 0);
        $mode['exige_reference_transaction'] = $mode['exige_reference_transaction'] ?? ($typeCanal === 'especes' ? 0 : 1);
        $mode['actif'] = $mode['actif'] ?? 1;

        return $mode;
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM modes_paiement WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return self::normalize($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
    }

    public static function findByCode($lyceeId, $code) {
        $db = Database::getInstance();
        $columns = self::getColumns();
        if (isset($columns['lycee_id'])) {
            $stmt = $db->prepare("SELECT * FROM modes_paiement
                WHERE (lycee_id = :lycee_id OR lycee_id IS NULL) AND UPPER(TRIM(code)) = :code
                ORDER BY CASE WHEN lycee_id = :priority_lycee_id THEN 0 ELSE 1 END, id DESC
                LIMIT 1");
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'priority_lycee_id' => $lyceeId,
                'code' => strtoupper(trim($code))
            ]);
        } else {
            // Legacy installations have global payment modes and no lycee_id column.
            $stmt = $db->prepare("SELECT * FROM modes_paiement WHERE UPPER(TRIM(code)) = :code LIMIT 1");
            $stmt->execute(['code' => strtoupper(trim($code))]);
        }

        return self::normalize($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
    }

    public static function findByLycee($lyceeId, $onlyActive = true) {
        $db = Database::getInstance();
        self::seedDefaultsForLycee($lyceeId);

        if (isset(self::getColumns()['lycee_id'])) {
            $sql = "SELECT * FROM modes_paiement WHERE (lycee_id = :lycee_id OR lycee_id IS NULL)";
        } else {
            // Legacy payment modes are shared by all establishments.
            $sql = "SELECT * FROM modes_paiement WHERE 1 = 1";
        }
        if ($onlyActive) {
            $sql .= " AND actif = 1";
        }
        $sql .= " ORDER BY libelle ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(isset(self::getColumns()['lycee_id']) ? ['lycee_id' => $lyceeId] : []);
        return array_map([self::class, 'normalize'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function seedDefaultsForLycee($lyceeId) {
        $db = Database::getInstance();
        if (!isset(self::getColumns()['lycee_id'])) {
            // This schema cannot store tenant-specific defaults; its global modes remain usable.
            return;
        }

        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM modes_paiement WHERE lycee_id = :lycee_id");
        $stmtCheck->execute(['lycee_id' => $lyceeId]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            return;
        }

        $defaults = [
            ['code' => 'ESPECES', 'libelle' => 'Espèces', 'type_canal' => 'especes', 'exige_session' => 1, 'exige_ref' => 0],
            ['code' => 'MOMO_ORANGE', 'libelle' => 'Orange Money', 'type_canal' => 'mobile_money', 'exige_session' => 0, 'exige_ref' => 1],
            ['code' => 'MOMO_MTN', 'libelle' => 'MTN Mobile Money', 'type_canal' => 'mobile_money', 'exige_session' => 0, 'exige_ref' => 1],
            ['code' => 'CHEQUE', 'libelle' => 'Chèque', 'type_canal' => 'banque', 'exige_session' => 0, 'exige_ref' => 1],
            ['code' => 'VIREMENT', 'libelle' => 'Virement bancaire', 'type_canal' => 'banque', 'exige_session' => 0, 'exige_ref' => 1]
        ];

        $legacyTypeColumn = isset(self::getColumns()['type_compte_defaut']);
        $insertColumns = 'lycee_id, code, libelle, type_canal, exige_session_caisse, exige_reference_transaction, actif';
        $insertValues = ':lycee_id, :code, :libelle, :type_canal, :exige_session, :exige_ref, 1';
        if ($legacyTypeColumn) {
            $insertColumns .= ', type_compte_defaut';
            $insertValues .= ', :type_compte_defaut';
        }

        $stmtIns = $db->prepare("INSERT INTO modes_paiement ({$insertColumns}) VALUES ({$insertValues})");

        foreach ($defaults as $d) {
            $params = [
                'lycee_id' => $lyceeId,
                'code' => $d['code'],
                'libelle' => $d['libelle'],
                'type_canal' => $d['type_canal'],
                'exige_session' => $d['exige_session'],
                'exige_ref' => $d['exige_ref']
            ];
            if ($legacyTypeColumn) {
                $params['type_compte_defaut'] = $d['type_canal'] === 'especes' ? 'caisse' : $d['type_canal'];
            }

            try {
                $stmtIns->execute($params);
            } catch (Exception $e) {
                // Ignore if duplicate
            }
        }
    }
}
