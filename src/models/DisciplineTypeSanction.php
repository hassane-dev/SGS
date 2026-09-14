<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class DisciplineTypeSanction {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM discipline_types_sanctions
                WHERE lycee_id = :lycee_id
                ORDER BY code ASC, id ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeSanction::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findActive($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM discipline_types_sanctions
                WHERE lycee_id = :lycee_id AND actif = 1
                ORDER BY code ASC, id ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeSanction::findActive: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id, $lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$id || !$lycee_id) {
            return false;
        }

        $sql = "SELECT * FROM discipline_types_sanctions
                WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeSanction::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function findByCode($code, $lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id || !$code) {
            return false;
        }

        $sql = "SELECT * FROM discipline_types_sanctions
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
            error_log("Error in DisciplineTypeSanction::findByCode: " . $e->getMessage());
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
            throw new InvalidArgumentException("Le code du type de sanction est obligatoire.");
        }
        $libelle = trim($data['libelle'] ?? '');
        if (empty($libelle)) {
            throw new InvalidArgumentException("Le libellé du type de sanction est obligatoire.");
        }

        $autoritesValides = ['enseignant', 'surveillant_general', 'censeur', 'proviseur', 'conseil_discipline'];
        $autorite = strtolower(trim($data['autorite_min_requise'] ?? 'surveillant_general'));
        if (!in_array($autorite, $autoritesValides, true)) {
            $autorite = 'surveillant_general';
        }

        $demandeDuree = !empty($data['demande_duree_jours']) ? 1 : 0;
        $demandeHeures = !empty($data['demande_heures']) ? 1 : 0;
        $afficheBulletin = !empty($data['affiche_sur_bulletin']) ? 1 : 0;
        $actif = isset($data['actif']) ? (int)$data['actif'] : 1;

        $isUpdate = !empty($data['id']);

        if ($isUpdate) {
            // Verify ownership
            $existing = self::findById((int)$data['id'], $lycee_id);
            if (!$existing) {
                throw new InvalidArgumentException("Type de sanction introuvable ou non autorisé.");
            }

            // Check unique code collision on other record
            $codeCheck = self::findByCode($code, $lycee_id);
            if ($codeCheck && (int)$codeCheck['id'] !== (int)$data['id']) {
                throw new InvalidArgumentException("Un type de sanction avec le code '$code' existe déjà pour cet établissement.");
            }

            $sql = "UPDATE discipline_types_sanctions SET
                        code = :code,
                        libelle = :libelle,
                        demande_duree_jours = :demande_duree,
                        demande_heures = :demande_heures,
                        affiche_sur_bulletin = :affiche_bulletin,
                        autorite_min_requise = :autorite,
                        actif = :actif,
                        updated_at = NOW()
                    WHERE id = :id AND lycee_id = :lycee_id";
            $params = [
                'code' => $code,
                'libelle' => $libelle,
                'demande_duree' => $demandeDuree,
                'demande_heures' => $demandeHeures,
                'affiche_bulletin' => $afficheBulletin,
                'autorite' => $autorite,
                'actif' => $actif,
                'id' => (int)$data['id'],
                'lycee_id' => $lycee_id
            ];
        } else {
            // Check unique code collision
            if (self::findByCode($code, $lycee_id)) {
                throw new InvalidArgumentException("Un type de sanction avec le code '$code' existe déjà pour cet établissement.");
            }

            $sql = "INSERT INTO discipline_types_sanctions (lycee_id, code, libelle, demande_duree_jours, demande_heures, affiche_sur_bulletin, autorite_min_requise, actif, created_at, updated_at)
                    VALUES (:lycee_id, :code, :libelle, :demande_duree, :demande_heures, :affiche_bulletin, :autorite, :actif, NOW(), NOW())";
            $params = [
                'lycee_id' => $lycee_id,
                'code' => $code,
                'libelle' => $libelle,
                'demande_duree' => $demandeDuree,
                'demande_heures' => $demandeHeures,
                'affiche_bulletin' => $afficheBulletin,
                'autorite' => $autorite,
                'actif' => $actif
            ];
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeSanction::save: " . $e->getMessage());
            return false;
        }
    }

    public static function toggleActive($id) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id || !$id) {
            return false;
        }

        $sql = "UPDATE discipline_types_sanctions SET actif = 1 - actif, updated_at = NOW() WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
        } catch (PDOException $e) {
            error_log("Error in DisciplineTypeSanction::toggleActive: " . $e->getMessage());
            return false;
        }
    }
}
?>