<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class ParamGeneral {

    /**
     * Finds the general parameters for the currently authenticated user's school.
     * If a record doesn't exist, it creates a default one.
     * @return array|false The general parameter data.
     */
    public static function findByAuthenticatedUser() {
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM param_general WHERE lycee_id = :lycee_id");
            $stmt->execute(['lycee_id' => $lycee_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // No record found, create a default one
                $stmt_create = $db->prepare("INSERT INTO param_general (lycee_id) VALUES (:lycee_id)");
                $stmt_create->execute(['lycee_id' => $lycee_id]);
                // Fetch the newly created record
                $stmt->execute(['lycee_id' => $lycee_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return $result;

        } catch (PDOException $e) {
            error_log("Error in ParamGeneral::findByAuthenticatedUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the general parameters for the school.
     * @param array $data The data to update.
     * @return bool True on success, false on failure.
     */
    public static function update($data) {
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        $sql = "UPDATE param_general SET
                    devisePays = :devisePays,
                    monnaie = :monnaie,
                    modalitePaiement = :modalitePaiement,
                    nbLangue = :nbLangue,
                    langue_1 = :langue_1,
                    langue_2 = :langue_2,
                    sequenceAnnuelle = :sequenceAnnuelle
                WHERE lycee_id = :lycee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'devisePays' => $data['devisePays'],
                'monnaie' => $data['monnaie'],
                'modalitePaiement' => $data['modalitePaiement'],
                'nbLangue' => $data['nbLangue'],
                'langue_1' => $data['langue_1'],
                'langue_2' => $data['langue_2'] ?? null,
                'sequenceAnnuelle' => $data['sequenceAnnuelle'],
                'lycee_id' => $lycee_id
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamGeneral::update: " . $e->getMessage());
            return false;
        }
    }
     public static function save($data) {
        $db = Database::getInstance();

        // Check if a record already exists for the lycee_id
        $stmt_check = $db->prepare("SELECT id FROM param_general WHERE lycee_id = :lycee_id");
        $stmt_check->execute(['lycee_id' => $data['lycee_id']]);
        $exists = $stmt_check->fetchColumn();

        if ($exists) {
            // Update
            $sql = "UPDATE param_general SET
                        devisePays = :devisePays,
                        monnaie = :monnaie,
                        modalitePaiement = :modalitePaiement,
                        nbLangue = :nbLangue,
                        langue_1 = :langue_1,
                        langue_2 = :langue_2,
                        sequenceAnnuelle = :sequenceAnnuelle
                    WHERE lycee_id = :lycee_id";
        } else {
            // Create
            $sql = "INSERT INTO param_general (lycee_id, devisePays, monnaie, modalitePaiement, nbLangue, langue_1, langue_2, sequenceAnnuelle)
                    VALUES (:lycee_id, :devisePays, :monnaie, :modalitePaiement, :nbLangue, :langue_1, :langue_2, :sequenceAnnuelle)";
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'lycee_id' => $data['lycee_id'],
                'devisePays' => $data['devisePays'] ?? 'XAF',
                'monnaie' => $data['monnaie'] ?? 'FCFA',
                'modalitePaiement' => $data['modalitePaiement'] ?? 'Especes',
                'nbLangue' => $data['nbLangue'] ?? 1,
                'langue_1' => $data['langue_1'] ?? 'Francais',
                'langue_2' => $data['langue_2'] ?? null,
                'sequenceAnnuelle' => $data['sequenceAnnuelle'] ?? 'Trimestrielle'
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamGeneral::save: " . $e->getMessage());
            return false;
        }
    }
}
?>