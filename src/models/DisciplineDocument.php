<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class DisciplineDocument {

    public static function findById($id, $lyceeId = null) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$id || !$lyceeId) {
            return false;
        }

        $sql = "SELECT d.*, u.nom as uploader_nom, u.prenom as uploader_prenom
                FROM discipline_documents d
                JOIN utilisateurs u ON d.uploaded_by_user_id = u.id_user
                WHERE d.id = :id AND d.lycee_id = :lycee_id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'lycee_id' => $lyceeId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineDocument::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function findByTarget($targetType, $targetId, $lyceeId = null) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$lyceeId || !$targetId) {
            return [];
        }

        $column = match($targetType) {
            'incident' => 'incident_id',
            'sanction' => 'sanction_id',
            'eleve' => 'eleve_id',
            default => null
        };

        if (!$column) {
            return [];
        }

        $sql = "SELECT d.*, u.nom as uploader_nom, u.prenom as uploader_prenom
                FROM discipline_documents d
                JOIN utilisateurs u ON d.uploaded_by_user_id = u.id_user
                WHERE d.lycee_id = :lycee_id AND d.{$column} = :target_id
                ORDER BY d.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lyceeId, 'target_id' => $targetId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineDocument::findByTarget: " . $e->getMessage());
            return [];
        }
    }

    public static function create($data) {
        $db = Database::getInstance();
        $lyceeId = Auth::getLyceeId();
        $userId = Auth::getUserId();

        if (!$lyceeId || !$userId) {
            throw new InvalidArgumentException("Session ou établissement non valide.");
        }

        if (empty($data['nom_original']) || empty($data['nom_stockage']) || empty($data['chemin_interne'])) {
            throw new InvalidArgumentException("Informations de fichier incomplètes.");
        }

        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO discipline_documents
                (lycee_id, incident_id, sanction_id, eleve_id, uploaded_by_user_id, nom_original, nom_stockage, chemin_interne, mime_type, taille, created_at)
                VALUES (:lycee_id, :incident_id, :sanction_id, :eleve_id, :user_id, :nom_orig, :nom_stock, :chemin, :mime, :taille, '$now')";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'incident_id' => $data['incident_id'] ?? null,
                'sanction_id' => $data['sanction_id'] ?? null,
                'eleve_id' => $data['eleve_id'] ?? null,
                'user_id' => $userId,
                'nom_orig' => trim($data['nom_original']),
                'nom_stock' => trim($data['nom_stockage']),
                'chemin' => trim($data['chemin_interne']),
                'mime' => trim($data['mime_type'] ?? 'application/octet-stream'),
                'taille' => (int)($data['taille'] ?? 0)
            ]);

            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in DisciplineDocument::create: " . $e->getMessage());
            throw $e;
        }
    }

    public static function delete($id, $lyceeId = null) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();

        $doc = self::findById($id, $lyceeId);
        if (!$doc) {
            return false;
        }

        if (file_exists($doc['chemin_interne'])) {
            @unlink($doc['chemin_interne']);
        }

        $sql = "DELETE FROM discipline_documents WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute(['id' => $id, 'lycee_id' => $lyceeId]);
        } catch (PDOException $e) {
            error_log("Error in DisciplineDocument::delete: " . $e->getMessage());
            return false;
        }
    }
}
?>