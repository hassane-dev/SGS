<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class Deblocage {

    /**
     * Helper to strictly normalize datetime inputs from HTML5 inputs (e.g. Y-m-d\TH:i) to canonical Y-m-d H:i:s.
     * Throws InvalidArgumentException on invalid dates.
     */
    public static function normalizeDateTime(?string $dateStr): ?string {
        if (empty($dateStr)) {
            return null;
        }
        $cleanStr = str_replace('T', ' ', trim($dateStr));
        if (strlen($cleanStr) === 16 && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $cleanStr)) {
            $cleanStr .= ':00';
        }
        $timestamp = strtotime($cleanStr);
        if ($timestamp === false || date('Y-m-d H:i:s', $timestamp) === '1970-01-01 00:00:00') {
            throw new InvalidArgumentException("Format de date invalide: " . $dateStr);
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    public static function save($data) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        $typeEvalId = !empty($data['type_evaluation_id']) ? (int)$data['type_evaluation_id'] : null;
        $typeEvalCode = $data['type_evaluation'] ?? 'tous';

        // Check for existing record to avoid duplicates manually since NULLs in unique keys are tricky (with strictly unique named parameters)
        $sql_check = "SELECT id FROM deblocages_notes
                      WHERE lycee_id = :lycee_id
                      AND annee_academique_id = :annee_id
                      AND type = :type
                      AND (classe_id = :classe_id1 OR (classe_id IS NULL AND :classe_id2 IS NULL))
                      AND (matiere_id = :matiere_id1 OR (matiere_id IS NULL AND :matiere_id2 IS NULL))
                      AND (enseignant_id = :enseignant_id1 OR (enseignant_id IS NULL AND :enseignant_id2 IS NULL))
                      AND (sequence_id = :sequence_id1 OR (sequence_id IS NULL AND :sequence_id2 IS NULL))
                      AND (
                          (type_evaluation_id IS NOT NULL AND type_evaluation_id = :type_eval_id_chk)
                          OR (type_evaluation_id IS NULL AND :type_eval_id_chk2 IS NULL AND type_evaluation = :type_eval_str_chk)
                      )";

        $classe_id_val = $data['classe_id'] ?? null;
        $matiere_id_val = $data['matiere_id'] ?? null;
        $enseignant_id_val = $data['enseignant_id'] ?? null;
        $sequence_id_val = $data['sequence_id'] ?? null;

        $stmt_check = $db->prepare($sql_check);
        $stmt_check->execute([
            'lycee_id' => $lycee_id,
            'annee_id' => $active_year['id'],
            'type' => $data['type'],
            'classe_id1' => $classe_id_val,
            'classe_id2' => $classe_id_val,
            'matiere_id1' => $matiere_id_val,
            'matiere_id2' => $matiere_id_val,
            'enseignant_id1' => $enseignant_id_val,
            'enseignant_id2' => $enseignant_id_val,
            'sequence_id1' => $sequence_id_val,
            'sequence_id2' => $sequence_id_val,
            'type_eval_id_chk' => $typeEvalId,
            'type_eval_id_chk2' => $typeEvalId,
            'type_eval_str_chk' => $typeEvalCode
        ]);
        $existing_id = $stmt_check->fetchColumn();

        $date_debut_norm = self::normalizeDateTime($data['date_debut'] ?? null);
        $date_fin_norm = self::normalizeDateTime($data['date_fin'] ?? null);

        if (!$date_debut_norm || !$date_fin_norm) {
            throw new InvalidArgumentException("Les dates de début et de fin sont obligatoires.");
        }

        if ($existing_id) {
            $sql = "UPDATE deblocages_notes SET
                        type_evaluation_id = :type_eval_id,
                        type_evaluation = :type_eval,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        motif = :motif,
                        cree_par = :cree_par
                    WHERE id = :id";
            $params = [
                'type_eval_id' => $typeEvalId,
                'type_eval' => $typeEvalCode,
                'date_debut' => $date_debut_norm,
                'date_fin' => $date_fin_norm,
                'motif' => $data['motif'] ?? null,
                'cree_par' => Auth::getUserId(),
                'id' => $existing_id
            ];
        } else {
            $sql = "INSERT INTO deblocages_notes (
                        lycee_id, annee_academique_id, type, classe_id, matiere_id,
                        enseignant_id, sequence_id, type_evaluation, type_evaluation_id, date_debut, date_fin, motif, cree_par
                    ) VALUES (
                        :lycee_id, :annee_id, :type, :classe_id, :matiere_id,
                        :enseignant_id, :sequence_id, :type_evaluation, :type_evaluation_id, :date_debut, :date_fin, :motif, :cree_par
                    )";
            $params = [
                'lycee_id' => $lycee_id,
                'annee_id' => $active_year['id'],
                'type' => $data['type'],
                'classe_id' => $data['classe_id'] ?? null,
                'matiere_id' => $data['matiere_id'] ?? null,
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'sequence_id' => $data['sequence_id'] ?? null,
                'type_evaluation' => $typeEvalCode,
                'type_evaluation_id' => $typeEvalId,
                'date_debut' => $date_debut_norm,
                'date_fin' => $date_fin_norm,
                'motif' => $data['motif'] ?? null,
                'cree_par' => Auth::getUserId()
            ];
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Deblocage::save: " . $e->getMessage());
            return false;
        }
    }

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();

        $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $concatClasse = $isSqlite
            ? "(c.niveau || CASE WHEN c.serie IS NOT NULL AND c.serie != '' THEN ' ' || c.serie ELSE '' END || CASE WHEN c.numero IS NOT NULL AND c.numero != '' THEN ' ' || c.numero ELSE '' END) as nom_classe"
            : "CONCAT(c.niveau, IF(c.serie IS NOT NULL AND c.serie != '', CONCAT(' ', c.serie), ''), IF(c.numero IS NOT NULL AND c.numero != '', CONCAT(' ', c.numero), '')) as nom_classe";

        $sql = "SELECT d.*, {$concatClasse}, m.nom_matiere, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
                       s.nom as sequence_nom, creator.nom as creator_nom, creator.prenom as creator_prenom
                FROM deblocages_notes d
                LEFT JOIN classes c ON d.classe_id = c.id_classe
                LEFT JOIN matieres m ON d.matiere_id = m.id_matiere
                LEFT JOIN utilisateurs u ON d.enseignant_id = u.id_user
                LEFT JOIN sequences s ON d.sequence_id = s.id
                LEFT JOIN utilisateurs creator ON d.cree_par = creator.id_user
                WHERE d.lycee_id = :lycee_id
                ORDER BY d.cree_le DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Deblocage::findAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Checks if there is an active exceptional unlock for the given criteria.
     */
    public static function isUnlocked($classe_id, $matiere_id, $sequence_id, $enseignant_id, $type_evaluation = 'devoir') {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        if (!$active_year) return false;

        $sql = "SELECT id FROM deblocages_notes
                WHERE lycee_id = :lycee_id
                AND annee_academique_id = :annee_id
                AND (type_evaluation = :type_eval OR type_evaluation = 'tous')
                AND :now_time BETWEEN date_debut AND date_fin
                AND (
                    type = 'global'
                    OR (type = 'classe' AND classe_id = :classe_id)
                    OR (type = 'matiere' AND matiere_id = :matiere_id)
                    OR (type = 'classe_matiere' AND classe_id = :classe_id AND matiere_id = :matiere_id)
                    OR (type = 'enseignant' AND classe_id = :classe_id AND matiere_id = :matiere_id AND enseignant_id = :enseignant_id)
                )
                AND (sequence_id IS NULL OR sequence_id = :sequence_id)";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_id' => $active_year['id'],
                'type_eval' => $type_evaluation,
                'now_time' => date('Y-m-d H:i:s'),
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'enseignant_id' => $enseignant_id,
                'sequence_id' => $sequence_id
            ]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log("Error in Deblocage::isUnlocked: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $sql = "DELETE FROM deblocages_notes WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'lycee_id' => Auth::getLyceeId()
            ]);
        } catch (PDOException $e) {
            error_log("Error in Deblocage::delete: " . $e->getMessage());
            return false;
        }
    }
}
