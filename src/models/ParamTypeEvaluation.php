<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class ParamTypeEvaluation {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM param_type_evaluation
                WHERE lycee_id = :lycee_id
                ORDER BY ordre_affichage ASC, id ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParamTypeEvaluation::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findActive($lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id) {
            return [];
        }

        $sql = "SELECT * FROM param_type_evaluation
                WHERE lycee_id = :lycee_id AND actif = 1
                ORDER BY ordre_affichage ASC, id ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParamTypeEvaluation::findActive: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM param_type_evaluation WHERE id = :id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParamTypeEvaluation::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function findByCode($code, $lycee_id = null) {
        $db = Database::getInstance();
        $lycee_id = $lycee_id ?? Auth::getLyceeId();
        if (!$lycee_id || !$code) {
            return false;
        }

        $sql = "SELECT * FROM param_type_evaluation
                WHERE lycee_id = :lycee_id AND code = :code
                LIMIT 1";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'code' => strtolower(trim($code))
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ParamTypeEvaluation::findByCode: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        $code = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($data['code'] ?? '')));
        if (empty($code)) {
            throw new InvalidArgumentException("Le code du type d'évaluation est obligatoire.");
        }
        $libelle = trim($data['libelle'] ?? '');
        if (empty($libelle)) {
            throw new InvalidArgumentException("Le libellé du type d'évaluation est obligatoire.");
        }
        $bareme = (!empty($data['bareme_defaut']) && (float)$data['bareme_defaut'] > 0) ? (float)$data['bareme_defaut'] : 20.00;

        $nombreEval = (int)($data['nombre_evaluation'] ?? 1);
        if ($nombreEval < 1 || $nombreEval > 3) {
            throw new InvalidArgumentException("Le nombre d'évaluations prévues doit être obligatoirement 1, 2 ou 3.");
        }

        $isUpdate = !empty($data['id']);

        if ($isUpdate) {
            $sql = "UPDATE param_type_evaluation SET
                        code = :code,
                        libelle = :libelle,
                        bareme_defaut = :bareme,
                        nombre_evaluation = :nombre_eval,
                        actif = :actif,
                        ordre_affichage = :ordre
                    WHERE id = :id AND lycee_id = :lycee_id";
            $params = [
                'code' => $code,
                'libelle' => $libelle,
                'bareme' => $bareme,
                'nombre_eval' => $nombreEval,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
                'ordre' => (int)($data['ordre_affichage'] ?? 0),
                'id' => (int)$data['id'],
                'lycee_id' => $lycee_id
            ];
        } else {
            $sql = "INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage)
                    VALUES (:lycee_id, :code, :libelle, :bareme, :nombre_eval, :actif, :ordre)";
            $params = [
                'lycee_id' => $lycee_id,
                'code' => $code,
                'libelle' => $libelle,
                'bareme' => $bareme,
                'nombre_eval' => $nombreEval,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
                'ordre' => (int)($data['ordre_affichage'] ?? 0)
            ];
        }

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in ParamTypeEvaluation::save: " . $e->getMessage());
            return false;
        }
    }

    public static function toggleActive($id) {
        $db = Database::getInstance();
        $lycee_id = Auth::getLyceeId();
        if (!$lycee_id) {
            return false;
        }

        $sql = "UPDATE param_type_evaluation SET actif = 1 - actif WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
        } catch (PDOException $e) {
            error_log("Error in ParamTypeEvaluation::toggleActive: " . $e->getMessage());
            return false;
        }
    }
}
?>