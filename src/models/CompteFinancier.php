<?php

require_once __DIR__ . '/../config/database.php';

class CompteFinancier {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM comptes_financiers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM comptes_financiers
            WHERE lycee_id = :lycee_id
            ORDER BY nom_compte ASC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO comptes_financiers (lycee_id, nom_compte, type_compte, solde_courant, devise, responsable_id)
            VALUES (:lycee_id, :nom_compte, :type_compte, :solde_courant, :devise, :responsable_id)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'nom_compte' => $data['nom_compte'],
            'type_compte' => $data['type_compte'],
            'solde_courant' => $data['solde_courant'] ?? 0.00,
            'devise' => $data['devise'] ?? 'FCFA',
            'responsable_id' => $data['responsable_id'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function updateSolde($id, $montant, $type_mouvement) {
        $db = Database::getInstance();
        $compte = self::findById($id);
        if (!$compte) {
            throw new Exception("Compte financier introuvable.");
        }

        $solde = (float)$compte['solde_courant'];
        if ($type_mouvement === 'entree') {
            $solde += (float)$montant;
        } elseif ($type_mouvement === 'sortie') {
            $solde -= (float)$montant;
        }

        $stmt = $db->prepare("UPDATE comptes_financiers SET solde_courant = :solde WHERE id = :id");
        $stmt->execute(['solde' => $solde, 'id' => $id]);
        return $solde;
    }

    public static function reconstructSolde($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT
                SUM(CASE WHEN type_mouvement = 'entree' THEN montant ELSE 0 END) as entrees,
                SUM(CASE WHEN type_mouvement = 'sortie' THEN montant ELSE 0 END) as sorties
            FROM mouvements_tresorerie
            WHERE compte_id = :compte_id
        ");
        $stmt->execute(['compte_id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $solde = (float)($row['entrees'] ?? 0.00) - (float)($row['sorties'] ?? 0.00);

        // Update the cached value in the DB
        $stmt_up = $db->prepare("UPDATE comptes_financiers SET solde_courant = :solde WHERE id = :id");
        $stmt_up->execute(['solde' => $solde, 'id' => $id]);

        return $solde;
    }
}
?>