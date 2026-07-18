<?php

require_once __DIR__ . '/../config/database.php';

class ParametreUtilisateur {
    public $id;
    public $user_id;
    public $lycee_id;
    public $signature;
    public $cachet;
    public $langue_preferee;
    public $theme_prefere;
    public $notifications_actives;
    public $date_modification;

    public function __construct($data = []) {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->user_id = $data['user_id'] ?? null;
            $this->lycee_id = $data['lycee_id'] ?? null;
            $this->signature = $data['signature'] ?? null;
            $this->cachet = $data['cachet'] ?? null;
            $this->langue_preferee = $data['langue_preferee'] ?? 'fr_FR';
            $this->theme_prefere = $data['theme_prefere'] ?? 'light';
            $this->notifications_actives = isset($data['notifications_actives']) ? (bool)$data['notifications_actives'] : true;
            $this->date_modification = $data['date_modification'] ?? null;
        }
    }

    /**
     * Finds settings for a user. If none exist in the database, returns a new instance with default values.
     *
     * @param int $user_id
     * @return ParametreUtilisateur
     */
    public static function findByUserId($user_id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM parametres_utilisateurs WHERE user_id = :user_id LIMIT 1");
            $stmt->execute(['user_id' => $user_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self($data);
            }
        } catch (PDOException $e) {
            error_log("Error in ParametreUtilisateur::findByUserId: " . $e->getMessage());
        }

        // Return default settings for user if none exist yet
        return new self([
            'user_id' => $user_id,
            'langue_preferee' => 'fr_FR',
            'theme_prefere' => 'light',
            'notifications_actives' => true
        ]);
    }

    /**
     * Saves or updates the user settings (UPSERT).
     *
     * @return bool
     */
    public function save() {
        try {
            $db = Database::getInstance();

            // Check if record already exists to handle both MySQL (ON DUPLICATE KEY UPDATE) and SQLite compatibility
            $stmt_check = $db->prepare("SELECT id FROM parametres_utilisateurs WHERE user_id = :user_id");
            $stmt_check->execute(['user_id' => $this->user_id]);
            $exists = $stmt_check->fetchColumn();

            if ($exists) {
                // UPDATE
                $sql = "UPDATE parametres_utilisateurs SET
                            lycee_id = :lycee_id,
                            signature = :signature,
                            cachet = :cachet,
                            langue_preferee = :langue_preferee,
                            theme_prefere = :theme_prefere,
                            notifications_actives = :notifications_actives
                        WHERE user_id = :user_id";
            } else {
                // INSERT
                $sql = "INSERT INTO parametres_utilisateurs
                            (user_id, lycee_id, signature, cachet, langue_preferee, theme_prefere, notifications_actives)
                        VALUES
                            (:user_id, :lycee_id, :signature, :cachet, :langue_preferee, :theme_prefere, :notifications_actives)";
            }

            $stmt = $db->prepare($sql);
            $params = [
                'user_id' => $this->user_id,
                'lycee_id' => $this->lycee_id,
                'signature' => $this->signature,
                'cachet' => $this->cachet,
                'langue_preferee' => $this->langue_preferee ?: 'fr_FR',
                'theme_prefere' => $this->theme_prefere ?: 'light',
                'notifications_actives' => $this->notifications_actives ? 1 : 0
            ];

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in ParametreUtilisateur::save: " . $e->getMessage());
            return false;
        }
    }
}
