<?php
// src/models/DepenseCentreCout.php

require_once __DIR__ . '/../config/database.php';

class DepenseCentreCout {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_centres_couts WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_centres_couts WHERE lycee_id = :lycee_id ORDER BY nom_centre ASC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActiveByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_centres_couts WHERE lycee_id = :lycee_id AND statut = 'actif' ORDER BY nom_centre ASC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO depense_centres_couts (lycee_id, nom_centre, statut, description)
            VALUES (:lycee_id, :nom_centre, :statut, :description)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'nom_centre' => $data['nom_centre'],
            'statut' => $data['statut'] ?? 'actif',
            'description' => $data['description'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Centre de coût introuvable.");
        }

        $stmt = $db->prepare("
            UPDATE depense_centres_couts
            SET nom_centre = :nom_centre,
                statut = :statut,
                description = :description
            WHERE id = :id
        ");
        return $stmt->execute([
            'nom_centre' => $data['nom_centre'] ?? $current['nom_centre'],
            'statut' => $data['statut'] ?? $current['statut'],
            'description' => $data['description'] ?? $current['description'],
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            return false;
        }

        // Soft-delete if used
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM depenses WHERE centre_cout_id = :id");
        $stmt_check->execute(['id' => $id]);
        if ($stmt_check->fetchColumn() > 0) {
            $stmt_toggle = $db->prepare("UPDATE depense_centres_couts SET statut = 'inactif' WHERE id = :id");
            return $stmt_toggle->execute(['id' => $id]);
        }

        $stmt = $db->prepare("DELETE FROM depense_centres_couts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>