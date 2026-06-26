<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class ParamLycee {

    /**
     * Finds the record for a specific lycee by its ID.
     * @param int $lycee_id The ID of the school.
     * @return array|false The school's parameter data.
     */
    public static function findByLyceeId($lycee_id) {
        if (!$lycee_id) {
            return false;
        }
        try {
            $db = Database::getInstance();
            // Corrected to query by id
            $stmt = $db->prepare("SELECT * FROM param_lycee WHERE id = :lycee_id");
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParamLycee::findByLyceeId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds the record for the currently authenticated user's school.
     * @return array|false The school's parameter data.
     */
    public static function findByAuthenticatedUser() {
        $lycee_id = Auth::getLyceeId();
        return self::findByLyceeId($lycee_id);
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
            $uploadDir = UPLOAD_BASE_DIR . '/logos/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create logos upload directory: " . $uploadDir);
                }
            }
            $fileName = uniqid() . '-' . basename($_FILES['logo']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFilePath)) {
                // Delete old logo if it exists
                if (!empty($data['current_logo'])) {
                    $oldLogoPath = __DIR__ . '/../../public' . $data['current_logo'];
                    if (file_exists($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }
                $data['logo'] = UPLOAD_PUBLIC_PATH . '/logos/' . $fileName;
            } else {
                error_log("Failed to move uploaded logo to: " . $targetFilePath);
                $data['logo'] = $data['current_logo'] ?? null;
            }
        } else {
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] != UPLOAD_ERR_NO_FILE) {
                error_log("Logo upload error: " . $_FILES['logo']['error']);
            }
            $data['logo'] = $data['current_logo'] ?? null;
        }

        // Handle Signature upload
        if (isset($_FILES['signature_directeur']) && $_FILES['signature_directeur']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_BASE_DIR . '/signatures/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create signatures upload directory: " . $uploadDir);
                }
            }
            $fileName = uniqid() . '-' . basename($_FILES['signature_directeur']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['signature_directeur']['tmp_name'], $targetFilePath)) {
                if (!empty($data['current_signature_directeur'])) {
                    $oldPath = __DIR__ . '/../../public' . $data['current_signature_directeur'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $data['signature_directeur'] = UPLOAD_PUBLIC_PATH . '/signatures/' . $fileName;
            } else {
                error_log("Failed to move uploaded signature to: " . $targetFilePath);
                $data['signature_directeur'] = $data['current_signature_directeur'] ?? null;
            }
        } else {
            if (isset($_FILES['signature_directeur']) && $_FILES['signature_directeur']['error'] != UPLOAD_ERR_NO_FILE) {
                error_log("Signature upload error: " . $_FILES['signature_directeur']['error']);
            }
            $data['signature_directeur'] = $data['current_signature_directeur'] ?? null;
        }

        // Handle Tampon upload
        if (isset($_FILES['tampon_ecole']) && $_FILES['tampon_ecole']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_BASE_DIR . '/tampons/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create tampons upload directory: " . $uploadDir);
                }
            }
            $fileName = uniqid() . '-' . basename($_FILES['tampon_ecole']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['tampon_ecole']['tmp_name'], $targetFilePath)) {
                if (!empty($data['current_tampon_ecole'])) {
                    $oldPath = __DIR__ . '/../../public' . $data['current_tampon_ecole'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $data['tampon_ecole'] = UPLOAD_PUBLIC_PATH . '/tampons/' . $fileName;
            } else {
                error_log("Failed to move uploaded tampon to: " . $targetFilePath);
                $data['tampon_ecole'] = $data['current_tampon_ecole'] ?? null;
            }
        } else {
            if (isset($_FILES['tampon_ecole']) && $_FILES['tampon_ecole']['error'] != UPLOAD_ERR_NO_FILE) {
                error_log("Tampon upload error: " . $_FILES['tampon_ecole']['error']);
            }
            $data['tampon_ecole'] = $data['current_tampon_ecole'] ?? null;
        }


        // Normalize type_lycee
        $type_lycee = $data['type_lycee'];
        if ($type_lycee === 'semi-public') {
            $type_lycee = 'parapublic';
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
                    boutique = :boutique,
                    header_primary = :header_primary,
                    header_secondary = :header_secondary,
                    signature_directeur = :signature_directeur,
                    tampon_ecole = :tampon_ecole
                WHERE id = :lycee_id";

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
                'type_lycee' => $type_lycee,
                'boutique' => isset($data['boutique']) ? 1 : 0,
                'header_primary' => $data['header_primary'] ?? null,
                'header_secondary' => $data['header_secondary'] ?? null,
                'signature_directeur' => $data['signature_directeur'] ?? null,
                'tampon_ecole' => $data['tampon_ecole'] ?? null,
                'lycee_id' => $lycee_id
            ]);
        } catch (PDOException $e) {
            error_log("Error in ParamLycee::update: " . $e->getMessage());
            return false;
        }
    }
}
?>
