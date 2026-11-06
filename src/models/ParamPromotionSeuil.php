<?php

require_once __DIR__ . '/../config/database.php';

class ParamPromotionSeuil {

    /**
     * Find a threshold record by ID.
     */
    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM param_promotion_seuils WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find all threshold records for a school tenant.
     */
    public static function findByLycee(int $lyceeId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ps.*, cy.nom_cycle
            FROM param_promotion_seuils ps
            LEFT JOIN cycles cy ON ps.cycle_id = cy.id_cycle
            WHERE ps.lycee_id = :lycee_id
            ORDER BY
                CASE ps.portee_type
                    WHEN 'etablissement' THEN 1
                    WHEN 'cycle' THEN 2
                    WHEN 'niveau' THEN 3
                    WHEN 'niveau_serie' THEN 4
                END ASC,
                cy.nom_cycle ASC,
                ps.niveau ASC,
                ps.serie ASC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find specific rule by exact scope.
     */
    public static function findExactScope(int $lyceeId, string $porteeType, ?int $cycleId = null, ?string $niveau = null, ?string $serie = null): ?array {
        $db = Database::getInstance();
        $targetCycleId = $cycleId ?? 0;
        $targetNiveau = $niveau ?? '';
        $targetSerie = $serie ?? '';

        $stmt = $db->prepare("
            SELECT * FROM param_promotion_seuils
            WHERE lycee_id = :lycee_id
              AND portee_type = :portee_type
              AND target_cycle_id = :target_cycle_id
              AND target_niveau = :target_niveau
              AND target_serie = :target_serie
            LIMIT 1
        ");
        $stmt->execute([
            'lycee_id' => $lyceeId,
            'portee_type' => $porteeType,
            'target_cycle_id' => $targetCycleId,
            'target_niveau' => $targetNiveau,
            'target_serie' => $targetSerie
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Insert or update a threshold record.
     */
    public static function save(array $data): int {
        $db = Database::getInstance();

        $lyceeId = (int)$data['lycee_id'];
        $cycleId = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null;
        $niveau = !empty($data['niveau']) ? trim($data['niveau']) : null;
        $serie = !empty($data['serie']) ? trim($data['serie']) : null;
        $porteeType = trim($data['portee_type']);

        $admission = (float)$data['seuil_admission_directe'];
        $delibMin = (float)$data['seuil_deliberation_min'];
        $delibMax = (float)$data['seuil_deliberation_max'];
        $redoublMax = (float)$data['seuil_redoublement_max'];
        $exclusionMax = (isset($data['seuil_exclusion_max']) && $data['seuil_exclusion_max'] !== '' && $data['seuil_exclusion_max'] !== null)
            ? (float)$data['seuil_exclusion_max']
            : null;
        $creePar = !empty($data['cree_par']) ? (int)$data['cree_par'] : null;

        if (!empty($data['id'])) {
            // Update
            $id = (int)$data['id'];
            $stmt = $db->prepare("
                UPDATE param_promotion_seuils SET
                    seuil_admission_directe = :admission,
                    seuil_deliberation_min = :delib_min,
                    seuil_deliberation_max = :delib_max,
                    seuil_redoublement_max = :redoubl_max,
                    seuil_exclusion_max = :exclusion_max,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND lycee_id = :lycee_id
            ");
            $stmt->execute([
                'admission' => $admission,
                'delib_min' => $delibMin,
                'delib_max' => $delibMax,
                'redoubl_max' => $redoublMax,
                'exclusion_max' => $exclusionMax,
                'id' => $id,
                'lycee_id' => $lyceeId
            ]);
            return $id;
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO param_promotion_seuils (
                    lycee_id, cycle_id, niveau, serie, portee_type,
                    seuil_admission_directe, seuil_deliberation_min, seuil_deliberation_max,
                    seuil_redoublement_max, seuil_exclusion_max, cree_par
                ) VALUES (
                    :lycee_id, :cycle_id, :niveau, :serie, :portee_type,
                    :admission, :delib_min, :delib_max,
                    :redoubl_max, :exclusion_max, :cree_par
                )
            ");
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'cycle_id' => $cycleId,
                'niveau' => $niveau,
                'serie' => $serie,
                'portee_type' => $porteeType,
                'admission' => $admission,
                'delib_min' => $delibMin,
                'delib_max' => $delibMax,
                'redoubl_max' => $redoublMax,
                'exclusion_max' => $exclusionMax,
                'cree_par' => $creePar
            ]);
            return (int)$db->lastInsertId();
        }
    }

    /**
     * Delete a threshold record.
     */
    public static function delete(int $id, int $lyceeId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM param_promotion_seuils WHERE id = :id AND lycee_id = :lycee_id");
        return $stmt->execute(['id' => $id, 'lycee_id' => $lyceeId]);
    }
}
