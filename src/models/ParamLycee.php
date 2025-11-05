<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class ParamLycee {

    /**
     * Finds the record for the currently authenticated user's school.
     * @return array|false The school's parameter data.
     */
    public static function findByAuthenticatedUser() {
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM param_lycee WHERE lycee_id = :lycee_id");
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParamLycee::findByAuthenticatedUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the school's parameters.
     * @param array $data The data to update.
     * @return bool True on success, false on failure.
     */
    public static function update($data) {
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = uniqid() . '-' . basename($_FILES['logo']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFilePath)) {
                $data['logo'] = '/uploads/logos/' . $fileName;
            } else {
                // Handle upload error if necessary
                $data['logo'] = $data['current_logo']; // Keep old logo
            }
        } else {
            $data['logo'] = $data['current_logo']; // Keep old logo if no new one is uploaded
        }


        $sql = "UPDATE param_lycee SET
                    nom_lycee = :nom_lycee,
                    sigle = :sigle,
                    tel = :tel,
                    email = :email,
                    ville = :ville,
                    quartier = :quartier,
                    ruelle = :ruelle,
                    boite_postale = :boite_postale,
                    arrete = :arrete,
                    arrondissement = :arrondissement,
                    devise = :devise,
                    logo = :logo,
                    type_lycee = :type_lycee,
                    boutique = :boutique
                WHERE lycee_id = :lycee_id";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'nom_lycee' => $data['nom_lycee'],
                'sigle' => $data['sigle'],
                'tel' => $data['tel'],
                'email' => $data['email'],
                'ville' => $data['ville'],
                'quartier' => $data['quartier'],
                'ruelle' => $data['ruelle'],
                'boite_postale' => $data['boite_postale'],
                'arrete' => $data['arrete'],
                'arrondissement' => $data['arrondissement'],
                'devise' => $data['devise'],
                'logo' => $data['logo'],
                'type_lycee' => $data['type_lycee'],
                'boutique' => isset($data['boutique']) ? 1 : 0,
                'lycee_id' => $lycee_id
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamLycee::update: " . $e->getMessage());
            return false;
        }
    }

    // Note: The `findAll` and `save` (for creation) methods are removed
    // as they are typically only used by a super_admin_createur, which is outside
    // the scope of a single school's admin.
}
?>