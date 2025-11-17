<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class ParamGeneral {

    public static function findByLyceeId($lycee_id) {
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
            error_log("Error in ParamGeneral::findByLyceeId: " . $e->getMessage());
            return false;
        }
    }

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
                    devise_pays = :devise_pays,
                    monnaie = :monnaie,
                    modalite_paiement = :modalite_paiement,
                    nb_langue = :nb_langue,
                    langue_1 = :langue_1,
                    langue_2 = :langue_2,
                    sequence_annuelle = :sequence_annuelle
                WHERE lycee_id = :lycee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'devise_pays' => $data['devise_pays'],
                'monnaie' => $data['monnaie'],
                'modalite_paiement' => $data['modalite_paiement'],
                'nb_langue' => $data['nb_langue'],
                'langue_1' => $data['langue_1'],
                'langue_2' => $data['langue_2'] ?? null,
                'sequence_annuelle' => $data['sequence_annuelle'],
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
                        devise_pays = :devise_pays,
                        monnaie = :monnaie,
                        modalite_paiement = :modalite_paiement,
                        nb_langue = :nb_langue,
                        langue_1 = :langue_1,
                        langue_2 = :langue_2,
                        sequence_annuelle = :sequence_annuelle
                    WHERE lycee_id = :lycee_id";
        } else {
            // Create
            $sql = "INSERT INTO param_general (lycee_id, devise_pays, monnaie, modalite_paiement, nb_langue, langue_1, langue_2, sequence_annuelle)
                    VALUES (:lycee_id, :devise_pays, :monnaie, :modalite_paiement, :nb_langue, :langue_1, :langue_2, :sequence_annuelle)";
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'lycee_id' => $data['lycee_id'],
                'devise_pays' => $data['devise_pays'] ?? 'XAF',
                'monnaie' => $data['monnaie'] ?? 'FCFA',
                'modalite_paiement' => $data['modalite_paiement'] ?? 'Especes',
                'nb_langue' => $data['nb_langue'] ?? 1,
                'langue_1' => $data['langue_1'] ?? 'Francais',
                'langue_2' => $data['langue_2'] ?? null,
                'sequence_annuelle' => $data['sequence_annuelle'] ?? 'Trimestrielle'
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamGeneral::save: " . $e->getMessage());
            return false;
        }
    }
}
?>