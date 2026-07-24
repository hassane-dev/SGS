<?php

require_once __DIR__ . '/../config/database.php';

class SessionCaisse {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM sessions_caisse WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findActiveByCompte($compteId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM sessions_caisse
            WHERE compte_id = :compte_id AND statut IN ('ouverte', 'fermee_a_valider')
            LIMIT 1
        ");
        $stmt->execute(['compte_id' => $compteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findActiveByUser($userId, $lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM sessions_caisse
            WHERE user_id = :user_id AND lycee_id = :lycee_id AND statut = 'ouverte'
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId, 'lycee_id' => $lyceeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function ouvrir($data) {
        $db = Database::getInstance();

        // Safety verification: No active session must exist on the account
        $active = self::findActiveByCompte($data['compte_id']);
        if ($active) {
            throw new Exception("Une session de caisse est déjà active sur ce compte financier.");
        }

        $stmt = $db->prepare("
            INSERT INTO sessions_caisse (lycee_id, user_id, compte_id, date_ouverture, solde_ouverture, solde_theorique, statut)
            VALUES (:lycee_id, :user_id, :compte_id, :date_ouverture, :solde_ouverture, :solde_theorique, 'ouverte')
        ");

        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'user_id' => $data['user_id'],
            'compte_id' => $data['compte_id'],
            'date_ouverture' => date('Y-m-d H:i:s'),
            'solde_ouverture' => $data['solde_ouverture'] ?? 0.00,
            'solde_theorique' => $data['solde_ouverture'] ?? 0.00
        ]);

        return $db->lastInsertId();
    }

    public static function cloturer($id, $solde_reel, $justificatif = '') {
        $db = Database::getInstance();
        $session = self::findById($id);
        if (!$session) {
            throw new Exception("Session de caisse introuvable.");
        }

        $theorique = (float)$session['solde_theorique'];
        $ecart = (float)$solde_reel - $theorique;

        $stmt = $db->prepare("
            UPDATE sessions_caisse SET
                date_fermeture = :date_fermeture,
                solde_reel = :solde_reel,
                ecart = :ecart,
                justificatif_ecart = :justificatif,
                statut = 'fermee_a_valider'
            WHERE id = :id
        ");

        $stmt->execute([
            'date_fermeture' => date('Y-m-d H:i:s'),
            'solde_reel' => $solde_reel,
            'ecart' => $ecart,
            'justificatif' => $justificatif,
            'id' => $id
        ]);

        return $ecart;
    }
}
?>