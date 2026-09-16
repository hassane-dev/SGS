<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class DisciplineNotification {

    public static function findByConseilId(int $conseilId, ?int $lyceeId = null): array {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$lyceeId || !$conseilId) {
            return [];
        }

        $sql = "SELECT n.*, CONCAT(u.prenom, ' ', u.nom) AS emetteur_nom
                FROM discipline_notifications n
                LEFT JOIN utilisateurs u ON n.created_by_user_id = u.id_user
                WHERE n.lycee_id = :lycee_id AND n.conseil_id = :conseil_id
                ORDER BY n.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lyceeId, 'conseil_id' => $conseilId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineNotification::findByConseilId: " . $e->getMessage());
            return [];
        }
    }

    public static function log(array $data): bool {
        $db = Database::getInstance();
        $lyceeId = $data['lycee_id'] ?? Auth::getLyceeId();
        $userId = $data['user_id'] ?? Auth::getUserId();

        if (!$lyceeId) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO discipline_notifications
                (lycee_id, conseil_id, eleve_id, destinataire_nom, destinataire_contact, mode_notification, objet, message, statut, date_envoi, created_by_user_id, created_at)
                VALUES (:lycee_id, :conseil_id, :eleve_id, :dest_nom, :dest_contact, :mode, :objet, :message, :statut, '$now', :user_id, '$now')";

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':lycee_id' => $lyceeId,
                ':conseil_id' => $data['conseil_id'] ?? null,
                ':eleve_id' => $data['eleve_id'] ?? null,
                ':dest_nom' => $data['destinataire_type'] ?? 'Parent / Tuteur',
                ':dest_contact' => $data['destinataire_contact'] ?? null,
                ':mode' => $data['mode_notification'] ?? 'convocation_conseil',
                ':objet' => $data['objet'] ?? 'Notification Conseil',
                ':message' => $data['contenu'] ?? null,
                ':statut' => $data['statut'] ?? 'tracé',
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error in DisciplineNotification::log: " . $e->getMessage());
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

        $sql = "SELECT n.*, u.nom as user_nom, u.prenom as user_prenom
                FROM discipline_notifications n
                JOIN utilisateurs u ON n.created_by_user_id = u.id_user
                WHERE n.lycee_id = :lycee_id AND n.{$column} = :target_id
                ORDER BY n.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lyceeId, 'target_id' => $targetId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineNotification::findByTarget: " . $e->getMessage());
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

        $eleveId = (int)($data['eleve_id'] ?? 0);
        if ($eleveId <= 0) {
            throw new InvalidArgumentException("L'élève concerné est obligatoire.");
        }

        $destinataireNom = trim($data['destinataire_nom'] ?? '');
        if (empty($destinataireNom)) {
            throw new InvalidArgumentException("Le nom du destinataire (parent/tuteur) est obligatoire.");
        }

        $objet = trim($data['objet'] ?? '');
        if (empty($objet)) {
            throw new InvalidArgumentException("L'objet de la notification est obligatoire.");
        }

        $modeNotif = strtolower(trim($data['mode_notification'] ?? 'main_propre'));
        $modesValides = ['main_propre', 'courrier_decharge', 'appel_telephonique', 'email', 'sms'];
        if (!in_array($modeNotif, $modesValides, true)) {
            $modeNotif = 'main_propre';
        }

        $dateEnvoi = $data['date_envoi'] ?? date('Y-m-d H:i:s');

        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO discipline_notifications
                (lycee_id, eleve_id, incident_id, sanction_id, destinataire_nom, destinataire_contact, mode_notification, objet, message, statut, date_envoi, created_by_user_id, created_at)
                VALUES (:lycee_id, :eleve_id, :incident_id, :sanction_id, :dest_nom, :dest_contact, :mode, :objet, :message, :statut, :date_envoi, :user_id, '$now')";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'eleve_id' => $eleveId,
                'incident_id' => $data['incident_id'] ?? null,
                'sanction_id' => $data['sanction_id'] ?? null,
                'dest_nom' => $destinataireNom,
                'dest_contact' => !empty($data['destinataire_contact']) ? trim($data['destinataire_contact']) : null,
                'mode' => $modeNotif,
                'objet' => $objet,
                'message' => !empty($data['message']) ? trim($data['message']) : null,
                'statut' => !empty($data['statut']) ? trim($data['statut']) : 'transmise',
                'date_envoi' => $dateEnvoi,
                'user_id' => $userId
            ]);

            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in DisciplineNotification::create: " . $e->getMessage());
            throw $e;
        }
    }
}
?>