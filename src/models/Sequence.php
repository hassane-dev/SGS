<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class Sequence {

    public static function findAll() {
        try {
            $db = Database::getInstance();
            $lycee_id = Auth::getLyceeId();
            $active_year = AnneeAcademique::findActive();

            if (!$lycee_id || !$active_year) {
                return [];
            }

            $stmt = $db->prepare("
                SELECT * FROM sequences
                WHERE lycee_id = :lycee_id
                AND annee_academique_id = :annee_academique_id
                ORDER BY date_debut ASC
            ");
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_academique_id' => $active_year['id']
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Sequence::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        try {
            $db = Database::getInstance();
            $lycee_id = Auth::getLyceeId();
            if (!$lycee_id) {
                return false;
            }
            $stmt = $db->prepare("SELECT * FROM sequences WHERE id = :id AND lycee_id = :lycee_id");
            $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Sequence::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        $isUpdate = !empty($data['id']);
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();

        if (!$lycee_id || !$active_year) {
            error_log("Error in Sequence::save: Missing lycee_id or active year.");
            return false;
        }

        $sql = $isUpdate
            ? "UPDATE sequences SET nom = :nom, type = :type, date_debut = :date_debut, date_fin = :date_fin, statut = :statut WHERE id = :id AND lycee_id = :lycee_id"
            : "INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (:lycee_id, :annee_academique_id, :nom, :type, :date_debut, :date_fin, :statut)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'nom' => $data['nom'],
                'type' => $data['type'],
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'statut' => $data['statut'],
                'lycee_id' => $lycee_id,
            ];

            if ($isUpdate) {
                $params['id'] = $data['id'];
            } else {
                $params['annee_academique_id'] = $active_year['id'];
            }

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Sequence::save: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $lycee_id = Auth::getLyceeId();
            if (!$lycee_id) {
                return false;
            }
            $stmt = $db->prepare("DELETE FROM sequences WHERE id = :id AND lycee_id = :lycee_id");
            return $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
        } catch (PDOException $e) {
            error_log("Error in Sequence::delete: " . $e->getMessage());
            if ($e->getCode() == '23000') {
                return false; // In use
            }
            return false;
        }
    }
}
?>