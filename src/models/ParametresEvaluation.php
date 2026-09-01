<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class ParametresEvaluation {

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

    private static function ensureTypeColumn($db) {
        try {
            $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
            if ($isSqlite) {
                $stmt = $db->prepare("PRAGMA table_info(`parametres_evaluations`)");
                $stmt->execute();
                $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $hasType = false;
                foreach ($cols as $c) {
                    if ($c['name'] === 'type') { $hasType = true; break; }
                }
                if (!$hasType) {
                    $db->exec("ALTER TABLE parametres_evaluations ADD COLUMN type TEXT NOT NULL DEFAULT 'enseignant'");
                }
            } else {
                $stmt = $db->query("SHOW COLUMNS FROM `parametres_evaluations` LIKE 'type'");
                if (!$stmt->fetch()) {
                    $db->exec("ALTER TABLE `parametres_evaluations` ADD COLUMN `type` ENUM('global', 'classe', 'matiere', 'classe_matiere', 'enseignant') NOT NULL DEFAULT 'enseignant'");
                    $db->exec("ALTER TABLE `parametres_evaluations` MODIFY COLUMN `classe_id` INT DEFAULT NULL");
                    $db->exec("ALTER TABLE `parametres_evaluations` MODIFY COLUMN `matiere_id` INT DEFAULT NULL");
                    $db->exec("ALTER TABLE `parametres_evaluations` MODIFY COLUMN `sequence_id` INT DEFAULT NULL");
                }
            }
        } catch (Exception $e) {
            // Ignore if column check fails or exists
        }
    }

    public static function save($data) {
        $db = Database::getInstance();
        self::ensureTypeColumn($db);
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        // Check for existing record to avoid duplicates manually with strictly unique named parameters
        $sql_check = "SELECT id FROM parametres_evaluations
                      WHERE lycee_id = :lycee_id
                      AND annee_academique_id = :annee_id
                      AND type = :type
                      AND (classe_id = :classe_id1 OR (classe_id IS NULL AND :classe_id2 IS NULL))
                      AND (matiere_id = :matiere_id1 OR (matiere_id IS NULL AND :matiere_id2 IS NULL))
                      AND (enseignant_id = :enseignant_id1 OR (enseignant_id IS NULL AND :enseignant_id2 IS NULL))
                      AND (sequence_id = :sequence_id1 OR (sequence_id IS NULL AND :sequence_id2 IS NULL))
                      AND type_evaluation = :type_eval";

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
            'type_eval' => $data['type_evaluation'] ?? 'tous'
        ]);
        $existing_id = $stmt_check->fetchColumn();

        $date_ouverture_norm = self::normalizeDateTime($data['date_ouverture_saisie'] ?? null);
        $date_fermeture_norm = self::normalizeDateTime($data['date_fermeture_saisie'] ?? null);

        if (!$date_ouverture_norm || !$date_fermeture_norm) {
            throw new InvalidArgumentException("Les dates d'ouverture et de fermeture sont obligatoires.");
        }

        if ($existing_id) {
            $sql = "UPDATE parametres_evaluations SET
                        date_ouverture_saisie = :date_ouverture,
                        date_fermeture_saisie = :date_fermeture,
                        commentaire = :commentaire,
                        enseignant_id = :enseignant_id
                    WHERE id = :id";
            $params = [
                'date_ouverture' => $date_ouverture_norm,
                'date_fermeture' => $date_fermeture_norm,
                'commentaire' => $data['commentaire'] ?? null,
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'id' => $existing_id
            ];
        } else {
            $sql = "INSERT INTO parametres_evaluations (
                        lycee_id, annee_academique_id, type, classe_id, matiere_id,
                        enseignant_id, sequence_id, type_evaluation, date_ouverture_saisie, date_fermeture_saisie, commentaire
                    ) VALUES (
                        :lycee_id, :annee_id, :type, :classe_id, :matiere_id,
                        :enseignant_id, :sequence_id, :type_evaluation, :date_ouverture, :date_fermeture, :commentaire
                    )";
            $params = [
                'lycee_id' => $lycee_id,
                'annee_id' => $active_year['id'],
                'type' => $data['type'],
                'classe_id' => $data['classe_id'] ?? null,
                'matiere_id' => $data['matiere_id'] ?? null,
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'sequence_id' => $data['sequence_id'] ?? null,
                'type_evaluation' => $data['type_evaluation'] ?? 'tous',
                'date_ouverture' => $date_ouverture_norm,
                'date_fermeture' => $date_fermeture_norm,
                'commentaire' => $data['commentaire'] ?? null
            ];
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::save: " . $e->getMessage());
            return false;
        }
    }

    public static function update($id, $data) {
        $db = Database::getInstance();
        self::ensureTypeColumn($db);
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        // Verify the record exists and belongs to the current lycee
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        $type = $data['type'] ?? 'global';
        $type_evaluation = $data['type_evaluation'] ?? 'tous';
        $sequence_id = !empty($data['sequence_id']) ? (int)$data['sequence_id'] : null;

        // Clean obsolete target fields based on scope type
        $classe_id = null;
        $matiere_id = null;
        $enseignant_id = null;

        if ($type === 'classe') {
            $classe_id = !empty($data['classe_id']) ? (int)$data['classe_id'] : null;
        } elseif ($type === 'matiere') {
            $matiere_id = !empty($data['matiere_id']) ? (int)$data['matiere_id'] : null;
        } elseif ($type === 'classe_matiere') {
            $classe_id = !empty($data['classe_id']) ? (int)$data['classe_id'] : null;
            $matiere_id = !empty($data['matiere_id']) ? (int)$data['matiere_id'] : null;
        } elseif ($type === 'enseignant') {
            $classe_id = !empty($data['classe_id']) ? (int)$data['classe_id'] : null;
            $matiere_id = !empty($data['matiere_id']) ? (int)$data['matiere_id'] : null;
            $enseignant_id = !empty($data['enseignant_id']) ? (int)$data['enseignant_id'] : null;
        }

        $sql = "UPDATE parametres_evaluations SET
                    type = :type,
                    classe_id = :classe_id,
                    matiere_id = :matiere_id,
                    enseignant_id = :enseignant_id,
                    sequence_id = :sequence_id,
                    type_evaluation = :type_eval,
                    date_ouverture_saisie = :date_ouverture,
                    date_fermeture_saisie = :date_fermeture,
                    commentaire = :commentaire
                WHERE id = :id AND lycee_id = :lycee_id";

        $date_ouverture_raw = $data['date_ouverture_saisie'] ?? $data['date_ouverture'] ?? null;
        $date_fermeture_raw = $data['date_fermeture_saisie'] ?? $data['date_fermeture'] ?? null;

        $date_ouverture_norm = self::normalizeDateTime($date_ouverture_raw);
        $date_fermeture_norm = self::normalizeDateTime($date_fermeture_raw);

        if (!$date_ouverture_norm || !$date_fermeture_norm) {
            throw new InvalidArgumentException("Les dates d'ouverture et de fermeture sont obligatoires.");
        }

        $params = [
            'type' => $type,
            'classe_id' => $classe_id,
            'matiere_id' => $matiere_id,
            'enseignant_id' => $enseignant_id,
            'sequence_id' => $sequence_id,
            'type_eval' => $type_evaluation,
            'date_ouverture' => $date_ouverture_norm,
            'date_fermeture' => $date_fermeture_norm,
            'commentaire' => $data['commentaire'] ?? null,
            'id' => (int)$id,
            'lycee_id' => $lycee_id
        ];

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::update: " . $e->getMessage());
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

        $sql = "SELECT p.*, {$concatClasse}, m.nom_matiere, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
                       s.nom as sequence_nom
                FROM parametres_evaluations p
                LEFT JOIN classes c ON p.classe_id = c.id_classe
                LEFT JOIN matieres m ON p.matiere_id = m.id_matiere
                LEFT JOIN utilisateurs u ON p.enseignant_id = u.id_user
                LEFT JOIN sequences s ON p.sequence_id = s.id
                WHERE p.lycee_id = :lycee_id
                ORDER BY p.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM parametres_evaluations WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'lycee_id' => Auth::getLyceeId()]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::findById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find all settings for a given class and subject for the current academic year.
     * Returns an associative array keyed by sequence_id.
     */
    public static function findByClassAndMatiere($classe_id, $matiere_id) {
        $active_year = AnneeAcademique::findActive();
        $lycee_id = Auth::getLyceeId();
        if (!$active_year || !$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM parametres_evaluations
                WHERE lycee_id = :lycee_id
                AND classe_id = :classe_id
                AND matiere_id = :matiere_id
                AND annee_academique_id = :annee_id
                AND type = 'enseignant'";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_id' => $active_year['id']
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($results as $result) {
                $settings[$result['sequence_id']][$result['type_evaluation']] = $result;
            }
            return $settings;
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::findByClassAndMatiere: " . $e->getMessage());
            return [];
        }
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $sql = "DELETE FROM parametres_evaluations WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'lycee_id' => Auth::getLyceeId()
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParametresEvaluation::delete: " . $e->getMessage());
            return false;
        }
    }
}
?>