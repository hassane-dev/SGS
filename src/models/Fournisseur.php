<?php
// src/models/Fournisseur.php

require_once __DIR__ . '/../config/database.php';

class Fournisseur {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM fournisseurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM fournisseurs
            WHERE lycee_id = :lycee_id OR lycee_id IS NULL
            ORDER BY raison_sociale ASC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActiveByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM fournisseurs
            WHERE (lycee_id = :lycee_id OR lycee_id IS NULL) AND actif = 1
            ORDER BY raison_sociale ASC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO fournisseurs (lycee_id, raison_sociale, code_fournisseur, nif, rccm, adresse, telephone, email, contact_nom, compte_comptable_tiers, actif, cree_par)
            VALUES (:lycee_id, :raison_sociale, :code_fournisseur, :nif, :rccm, :adresse, :telephone, :email, :contact_nom, :compte_comptable_tiers, :actif, :cree_par)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'] ?? null,
            'raison_sociale' => $data['raison_sociale'],
            'code_fournisseur' => $data['code_fournisseur'],
            'nif' => $data['nif'] ?? null,
            'rccm' => $data['rccm'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'contact_nom' => $data['contact_nom'] ?? null,
            'compte_comptable_tiers' => $data['compte_comptable_tiers'] ?? '401100',
            'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
            'cree_par' => $data['cree_par'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Fournisseur introuvable.");
        }

        $stmt = $db->prepare("
            UPDATE fournisseurs
            SET raison_sociale = :raison_sociale,
                nif = :nif,
                rccm = :rccm,
                adresse = :adresse,
                telephone = :telephone,
                email = :email,
                contact_nom = :contact_nom,
                compte_comptable_tiers = :compte_comptable_tiers,
                actif = :actif
            WHERE id = :id
        ");
        return $stmt->execute([
            'raison_sociale' => $data['raison_sociale'] ?? $current['raison_sociale'],
            'nif' => $data['nif'] ?? $current['nif'],
            'rccm' => $data['rccm'] ?? $current['rccm'],
            'adresse' => $data['adresse'] ?? $current['adresse'],
            'telephone' => $data['telephone'] ?? $current['telephone'],
            'email' => $data['email'] ?? $current['email'],
            'contact_nom' => $data['contact_nom'] ?? $current['contact_nom'],
            'compte_comptable_tiers' => $data['compte_comptable_tiers'] ?? $current['compte_comptable_tiers'],
            'actif' => isset($data['actif']) ? (int)$data['actif'] : $current['actif'],
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();

        // Enforce physical deletion block if there are invoices or orders associated
        $stmt_check_cmd = $db->prepare("SELECT COUNT(*) FROM achat_commandes WHERE fournisseur_id = :id");
        $stmt_check_cmd->execute(['id' => $id]);
        $has_orders = ($stmt_check_cmd->fetchColumn() > 0);

        $stmt_check_fact = $db->prepare("SELECT COUNT(*) FROM achat_factures WHERE fournisseur_id = :id");
        $stmt_check_fact->execute(['id' => $id]);
        $has_invoices = ($stmt_check_fact->fetchColumn() > 0);

        if ($has_orders || $has_invoices) {
            // Cannot physically delete; toggle actif to 0
            $stmt_toggle = $db->prepare("UPDATE fournisseurs SET actif = 0 WHERE id = :id");
            return $stmt_toggle->execute(['id' => $id]);
        }

        $stmt = $db->prepare("DELETE FROM fournisseurs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>