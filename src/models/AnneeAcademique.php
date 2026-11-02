<?php

require_once __DIR__ . '/../config/database.php';

class AnneeAcademique {

    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM annees_academiques ORDER BY date_debut DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM annees_academiques WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findActive(?int $lycee_id = null): ?array {
        $db = Database::getInstance();

        // If an explicit establishment ID is passed, it must be strictly positive
        if ($lycee_id !== null) {
            if ($lycee_id <= 0) {
                throw new InvalidArgumentException(sprintf(_("Identifiant d'établissement invalide : #%d."), $lycee_id));
            }

            // Canonical linkage of active academic year to school is strictly established via sequences table
            $stmt = $db->prepare("
                SELECT DISTINCT aa.*
                FROM annees_academiques aa
                JOIN sequences s ON s.annee_academique_id = aa.id
                WHERE s.lycee_id = :lid AND aa.est_active = 1
            ");
            $stmt->execute(['lid' => $lycee_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) > 1) {
                throw new RuntimeException(sprintf(_("Incohérence temporelle détectée : Plusieurs années académiques sont marquées actives pour l'établissement #%d."), $lycee_id));
            }
            if (count($rows) === 1) {
                return $rows[0];
            }

            // An explicit lycee_id was requested but has no active academic year linked to it.
            // Never silently fall back to an active year belonging to another establishment.
            return null;
        }

        // Global context (when lycee_id is strictly null)
        $stmt = $db->query("SELECT * FROM annees_academiques WHERE est_active = 1 ORDER BY id ASC LIMIT 1");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function save($data) {
        $isUpdate = !empty($data['id']);
        $db = Database::getInstance();

        if ($isUpdate) {
            $sql = "UPDATE annees_academiques SET libelle = :libelle, date_debut = :date_debut, date_fin = :date_fin WHERE id = :id";
        } else {
            $sql = "INSERT INTO annees_academiques (libelle, date_debut, date_fin) VALUES (:libelle, :date_debut, :date_fin)";
        }

        $stmt = $db->prepare($sql);
        $params = [
            'libelle' => $data['libelle'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin']
        ];

        if ($isUpdate) {
            $params['id'] = $data['id'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM annees_academiques WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public static function setActive($id) {
        $db = Database::getInstance();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            // Deactivate all other years
            $db->query("UPDATE annees_academiques SET est_active = 0");
            // Activate the selected year
            $stmt = $db->prepare("UPDATE annees_academiques SET est_active = 1 WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Bump surveillant_config_version for all lycees
            try {
                $db->query("UPDATE param_lycee SET surveillant_config_version = surveillant_config_version + 1");
            } catch (Throwable $e) {}

            if (!$inTransaction) {
                $db->commit();
            }
            return true;
        } catch (Exception $e) {
            if (!$inTransaction) {
                $db->rollBack();
            }
            return false;
        }
    }
}
?>