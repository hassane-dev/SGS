<?php
// src/models/DepenseBeneficiaire.php

require_once __DIR__ . '/../config/database.php';

class DepenseBeneficiaire {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_beneficiaires WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_beneficiaires WHERE lycee_id = :lycee_id ORDER BY nom_beneficiaire ASC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActiveByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_beneficiaires WHERE lycee_id = :lycee_id AND statut = 'actif' ORDER BY nom_beneficiaire ASC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO depense_beneficiaires (lycee_id, nom_beneficiaire, type, beneficiaire_utilisateur_id, statut, telephone, email)
            VALUES (:lycee_id, :nom_beneficiaire, :type, :beneficiaire_utilisateur_id, :statut, :telephone, :email)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'nom_beneficiaire' => $data['nom_beneficiaire'],
            'type' => $data['type'],
            'beneficiaire_utilisateur_id' => $data['beneficiaire_utilisateur_id'] ?? null,
            'statut' => $data['statut'] ?? 'actif',
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Bénéficiaire introuvable.");
        }

        $stmt = $db->prepare("
            UPDATE depense_beneficiaires
            SET nom_beneficiaire = :nom_beneficiaire,
                type = :type,
                beneficiaire_utilisateur_id = :beneficiaire_utilisateur_id,
                statut = :statut,
                telephone = :telephone,
                email = :email
            WHERE id = :id
        ");
        return $stmt->execute([
            'nom_beneficiaire' => $data['nom_beneficiaire'] ?? $current['nom_beneficiaire'],
            'type' => $data['type'] ?? $current['type'],
            'beneficiaire_utilisateur_id' => isset($data['beneficiaire_utilisateur_id']) ? $data['beneficiaire_utilisateur_id'] : $current['beneficiaire_utilisateur_id'],
            'statut' => $data['statut'] ?? $current['statut'],
            'telephone' => isset($data['telephone']) ? $data['telephone'] : $current['telephone'],
            'email' => isset($data['email']) ? $data['email'] : $current['email'],
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
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM depenses WHERE beneficiaire_id = :id");
        $stmt_check->execute(['id' => $id]);
        if ($stmt_check->fetchColumn() > 0) {
            $stmt_toggle = $db->prepare("UPDATE depense_beneficiaires SET statut = 'inactif' WHERE id = :id");
            return $stmt_toggle->execute(['id' => $id]);
        }

        $stmt = $db->prepare("DELETE FROM depense_beneficiaires WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>