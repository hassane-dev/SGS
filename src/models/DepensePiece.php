<?php
// src/models/DepensePiece.php

require_once __DIR__ . '/../config/database.php';

class DepensePiece {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depenses_pieces WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByDepense($depenseId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depenses_pieces WHERE depense_id = :depense_id ORDER BY id ASC");
        $stmt->execute(['depense_id' => $depenseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();

        // Validate attachment details
        self::validateAttachment($data);

        $stmt = $db->prepare("
            INSERT INTO depenses_pieces (depense_id, nom_fichier, chemin_fichier, type_mime, taille_octets, sha256_hash)
            VALUES (:depense_id, :nom_fichier, :chemin_fichier, :type_mime, :taille_octets, :sha256_hash)
        ");

        $stmt->execute([
            'depense_id' => $data['depense_id'],
            'nom_fichier' => $data['nom_fichier'],
            'chemin_fichier' => $data['chemin_fichier'],
            'type_mime' => $data['type_mime'],
            'taille_octets' => $data['taille_octets'],
            'sha256_hash' => $data['sha256_hash']
        ]);

        return $db->lastInsertId();
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM depenses_pieces WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Validates attachment size, mime-type, and SHA-256 hash
     */
    public static function validateAttachment($data) {
        // Enforce max size (e.g., 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if (isset($data['taille_octets']) && $data['taille_octets'] > $max_size) {
            throw new Exception("Le fichier dépasse la taille maximale autorisée de 5 Mo.");
        }

        // Validate SHA-256 format
        if (empty($data['sha256_hash']) || strlen($data['sha256_hash']) !== 64 || !ctype_xdigit($data['sha256_hash'])) {
            throw new Exception("Empreinte de hachage SHA-256 invalide.");
        }

        // Validate allowed mime types (pdf, png, jpg, jpeg, webp, doc, docx, xls, xlsx)
        $allowed_mimes = [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'image/webp',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];
        if (empty($data['type_mime']) || !in_array($data['type_mime'], $allowed_mimes)) {
            throw new Exception("Type de fichier non autorisé : " . ($data['type_mime'] ?? 'Inconnu'));
        }
    }
}
?>