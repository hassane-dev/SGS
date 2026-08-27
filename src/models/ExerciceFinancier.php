<?php

require_once __DIR__ . '/../config/database.php';

class ExerciceFinancier {

    public static function findActive($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM exercices_financiers
            WHERE lycee_id = :lycee_id AND est_actif = 1 AND (cloture = 0 OR cloture = '0' OR cloture IS NULL)
            LIMIT 1
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAll($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT e.*,
                   (SELECT COUNT(*) FROM comptabilite_periodes p WHERE p.exercice_financier_id = e.id) as nb_periodes
            FROM exercices_financiers e
            WHERE e.lycee_id = :lycee_id
            ORDER BY e.date_debut DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM exercices_financiers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create($data) {
        $db = Database::getInstance();

        if (self::hasOverlap($data['lycee_id'], $data['date_debut'], $data['date_fin'])) {
            throw new InvalidArgumentException(_("Un exercice financier chevauche ces dates pour cet établissement."));
        }

        $stmt = $db->prepare("
            INSERT INTO exercices_financiers (lycee_id, libelle, date_debut, date_fin, est_actif, cloture, type_exercice)
            VALUES (:lycee_id, :libelle, :date_debut, :date_fin, :est_actif, 0, :type_exercice)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'libelle' => $data['libelle'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'est_actif' => !empty($data['est_actif']) ? 1 : 0,
            'type_exercice' => $data['type_exercice'] ?? 'normal'
        ]);

        $id = $db->lastInsertId();

        if (!empty($data['est_actif'])) {
            self::setActive($data['lycee_id'], $id);
        }

        return $id;
    }

    public static function setActive($lyceeId, $exerciceId) {
        $db = Database::getInstance();
        $db->prepare("UPDATE exercices_financiers SET est_actif = 0 WHERE lycee_id = :lycee_id")->execute(['lycee_id' => $lyceeId]);
        $db->prepare("UPDATE exercices_financiers SET est_actif = 1 WHERE id = :id AND lycee_id = :lycee_id")->execute(['id' => $exerciceId, 'lycee_id' => $lyceeId]);
    }

    public static function close($exerciceId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE exercices_financiers SET cloture = 1, est_actif = 0 WHERE id = :id");
        return $stmt->execute(['id' => $exerciceId]);
    }

    public static function hasOverlap($lyceeId, $dateDebut, $dateFin, $excludeId = null) {
        $db = Database::getInstance();
        $sql = "
            SELECT COUNT(*) FROM exercices_financiers
            WHERE lycee_id = :lycee_id
              AND (
                (date_debut <= :date_fin AND date_fin >= :date_debut)
              )
        ";
        $params = [
            'lycee_id' => $lyceeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}
