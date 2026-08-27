<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ComptabiliteService.php';

class ComptabilitePeriode {

    public static function findAllForLycee($lyceeId, $exerciceId = null) {
        $db = Database::getInstance();
        $sql = "
            SELECT p.*, ef.libelle as exercice_libelle, u.nom as cloture_par_nom, u.prenom as cloture_par_prenom
            FROM comptabilite_periodes p
            JOIN exercices_financiers ef ON p.exercice_financier_id = ef.id
            LEFT JOIN utilisateurs u ON p.cloturee_par = u.id_user
            WHERE p.lycee_id = :lycee_id
        ";
        $params = ['lycee_id' => $lyceeId];

        if ($exerciceId) {
            $sql .= " AND p.exercice_financier_id = :exercice_id";
            $params['exercice_id'] = $exerciceId;
        }

        $sql .= " ORDER BY p.date_debut ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOpenForLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.*, ef.libelle as exercice_libelle
            FROM comptabilite_periodes p
            JOIN exercices_financiers ef ON p.exercice_financier_id = ef.id
            WHERE p.lycee_id = :lycee_id AND (p.est_cloturee = 0 OR p.est_cloturee IS NULL)
            ORDER BY p.date_debut ASC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.*, ef.libelle as exercice_libelle
            FROM comptabilite_periodes p
            JOIN exercices_financiers ef ON p.exercice_financier_id = ef.id
            WHERE p.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create($data) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $lyceeId = $data['lycee_id'];
            $exerciceId = $data['exercice_financier_id'];
            $dateDebut = $data['date_debut'];
            $dateFin = $data['date_fin'];

            if (strtotime($dateFin) < strtotime($dateDebut)) {
                throw new InvalidArgumentException(_("La date de fin doit être postérieure à la date de début."));
            }

            if (self::hasOverlap($lyceeId, $dateDebut, $dateFin)) {
                throw new InvalidArgumentException(_("Une période comptable existe déjà ou chevauche ces dates pour cet établissement."));
            }

            $stmt = $db->prepare("
                INSERT INTO comptabilite_periodes (lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee)
                VALUES (:lycee_id, :exercice_id, :date_debut, :date_fin, 0)
            ");
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'exercice_id' => $exerciceId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]);

            $id = $db->lastInsertId();
            $db->commit();
            return $id;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function generateMonthlyForExercice($lyceeId, $exerciceId) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmtEx = $db->prepare("SELECT * FROM exercices_financiers WHERE id = :id AND lycee_id = :lycee_id");
            $stmtEx->execute(['id' => $exerciceId, 'lycee_id' => $lyceeId]);
            $exercice = $stmtEx->fetch(PDO::FETCH_ASSOC);

            if (!$exercice) {
                throw new InvalidArgumentException(_("Exercice financier introuvable."));
            }

            $start = new DateTime($exercice['date_debut']);
            $end = new DateTime($exercice['date_fin']);

            $count = 0;
            $current = clone $start;
            // Set date to the first of the month to prevent DateTime overflow issues on months with 28-31 days
            $current->setDate((int)$current->format('Y'), (int)$current->format('m'), 1);

            while ($current <= $end) {
                $pStart = $current->format('Y-m-01');
                if ($pStart < $exercice['date_debut']) {
                    $pStart = $exercice['date_debut'];
                }

                $pEnd = $current->format('Y-m-t');
                if ($pEnd > $exercice['date_fin']) {
                    $pEnd = $exercice['date_fin'];
                }

                if (!self::hasOverlap($lyceeId, $pStart, $pEnd)) {
                    $stmtIns = $db->prepare("
                        INSERT INTO comptabilite_periodes (lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee)
                        VALUES (:lycee_id, :exercice_id, :date_debut, :date_fin, 0)
                    ");
                    $stmtIns->execute([
                        'lycee_id' => $lyceeId,
                        'exercice_id' => $exerciceId,
                        'date_debut' => $pStart,
                        'date_fin' => $pEnd
                    ]);
                    $count++;
                }

                $current->modify('+1 month');
            }

            $db->commit();
            return $count;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function hasOverlap($lyceeId, $dateDebut, $dateFin, $excludeId = null) {
        $db = Database::getInstance();
        $sql = "
            SELECT COUNT(*) FROM comptabilite_periodes
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
