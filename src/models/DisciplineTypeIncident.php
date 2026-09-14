<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class DisciplineTypeIncident {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM discipline_types_incidents
                WHERE lycee_id = :lycee_id
                ORDER BY code ASC, id ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeIncident::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findActive($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM discipline_types_incidents
                WHERE lycee_id = :lycee_id AND actif = 1
                ORDER BY code ASC, id ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeIncident::findActive: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id, $lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$id || !$lycee_id) {
            return false;
        }

        $sql = "SELECT * FROM discipline_types_incidents
                WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeIncident::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function findByCode($code, $lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id || !$code) {
            return false;
        }

        $sql = "SELECT * FROM discipline_types_incidents
                WHERE lycee_id = :lycee_id AND code = :code
                LIMIT 1";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'code' => strtoupper(trim($code))
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeIncident::findByCode: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        $code = strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($data['code'] ?? '')));
        if (empty($code)) {
            throw new InvalidArgumentException("Le code du type d'incident est obligatoire.");
        }
        $libelle = trim($data['libelle'] ?? '');
        if (empty($libelle)) {
            throw new InvalidArgumentException("Le libellé du type d'incident est obligatoire.");
        }

        $gravitesValides = ['mineur', 'moyen', 'grave', 'tres_grave'];
        $niveauGravite = strtolower(trim($data['niveau_gravite'] ?? 'moyen'));
        if (!in_array($niveauGravite, $gravitesValides, true)) {
            $niveauGravite = 'moyen';
        }

        $isUpdate = !empty($data['id']);

        if ($isUpdate) {
            // Verify ownership
            $existing = self::findById((int)$data['id'], $lycee_id);
            if (!$existing) {
                throw new InvalidArgumentException("Type d'incident introuvable ou non autorisé.");
            }

            // Check unique code collision on other record
            $codeCheck = self::findByCode($code, $lycee_id);
            if ($codeCheck && (int)$codeCheck['id'] !== (int)$data['id']) {
                throw new InvalidArgumentException("Un type d'incident avec le code '$code' existe déjà pour cet établissement.");
            }

            $sql = "UPDATE discipline_types_incidents SET
                        code = :code,
                        libelle = :libelle,
                        niveau_gravite = :niveau_gravite,
                        actif = :actif,
                        updated_at = NOW()
                    WHERE id = :id AND lycee_id = :lycee_id";
            $params = [
                'code' => $code,
                'libelle' => $libelle,
                'niveau_gravite' => $niveauGravite,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
                'id' => (int)$data['id'],
                'lycee_id' => $lycee_id
            ];
        } else {
            // Check unique code collision
            if (self::findByCode($code, $lycee_id)) {
                throw new InvalidArgumentException("Un type d'incident avec le code '$code' existe déjà pour cet établissement.");
            }

            $sql = "INSERT INTO discipline_types_incidents (lycee_id, code, libelle, niveau_gravite, actif, created_at, updated_at)
                    VALUES (:lycee_id, :code, :libelle, :niveau_gravite, :actif, NOW(), NOW())";
            $params = [
                'lycee_id' => $lycee_id,
                'code' => $code,
                'libelle' => $libelle,
                'niveau_gravite' => $niveauGravite,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1
            ];
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeIncident::save: " . $e->getMessage());
            return false;
        }
    }

    public static function toggleActive($id) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id || !$id) {
            return false;
        }

        $sql = "UPDATE discipline_types_incidents SET actif = 1 - actif, updated_at = NOW() WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeIncident::toggleActive: " . $e->getMessage());
            return false;
        }
    }
}
?>