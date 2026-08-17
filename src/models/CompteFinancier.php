<?php

require_once __DIR__ . '/../config/database.php';

class CompteFinancier {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM comptes_financiers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['compte_comptable_id'])) {
            try {
                $stmtCC = $db->prepare("SELECT numero, libelle FROM comptes_comptables WHERE id = :id");
                $stmtCC->execute(['id' => $row['compte_comptable_id']]);
                $cc = $stmtCC->fetch(PDO::FETCH_ASSOC);
                if ($cc) {
                    $row['compte_comptable_numero'] = $cc['numero'];
                    $row['compte_comptable_libelle'] = $cc['libelle'];
                }
            } catch (Exception $e) {
                // Ignore if comptes_comptables table does not exist in legacy isolated test runner
            }
        }
        return $row ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM comptes_financiers
            WHERE lycee_id = :lycee_id
            ORDER BY nom_compte ASC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (!empty($row['compte_comptable_id'])) {
                try {
                    $stmtCC = $db->prepare("SELECT numero, libelle FROM comptes_comptables WHERE id = :id");
                    $stmtCC->execute(['id' => $row['compte_comptable_id']]);
                    $cc = $stmtCC->fetch(PDO::FETCH_ASSOC);
                    if ($cc) {
                        $row['compte_comptable_numero'] = $cc['numero'];
                        $row['compte_comptable_libelle'] = $cc['libelle'];
                    }
                } catch (Exception $e) {
                    // Ignore
                }
            }
        }
        unset($row);
        return $rows;
    }

    public static function findCoffreByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT id FROM comptes_financiers
            WHERE lycee_id = :lycee_id AND est_coffre = 1 AND statut = 'actif'
            LIMIT 1
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchColumn() ?: null;
    }

    public static function create($data) {
        $db = Database::getInstance();

        // Strict Uniqueness check of Coffre Principal per lycée
        $estCoffre = !empty($data['est_coffre']) ? 1 : 0;
        if ($estCoffre === 1) {
            $existingCoffre = self::findCoffreByLycee($data['lycee_id']);
            if ($existingCoffre !== null) {
                throw new Exception("Un seul Coffre Principal est autorisé par établissement.");
            }
        }

        // Resolve compte_comptable_id and compte_comptable_numero
        $compteComptableId = !empty($data['compte_comptable_id']) ? (int)$data['compte_comptable_id'] : null;
        $compteComptableNumero = !empty($data['compte_comptable_numero']) ? trim($data['compte_comptable_numero']) : null;

        if ($compteComptableId) {
            $stmtCC = $db->prepare("SELECT id, numero, actif, autoriser_ecriture FROM comptes_comptables WHERE id = :id");
            $stmtCC->execute(['id' => $compteComptableId]);
            $cc = $stmtCC->fetch(PDO::FETCH_ASSOC);
            if ($cc) {
                $compteComptableNumero = $cc['numero'];
            }
        } elseif ($compteComptableNumero) {
            $stmtCC = $db->prepare("SELECT id, numero, actif, autoriser_ecriture FROM comptes_comptables WHERE TRIM(numero) = :num");
            $stmtCC->execute(['num' => $compteComptableNumero]);
            $cc = $stmtCC->fetch(PDO::FETCH_ASSOC);
            if ($cc) {
                $compteComptableId = (int)$cc['id'];
                $compteComptableNumero = $cc['numero'];
            }
        }


        try {
            $stmt = $db->prepare("
                INSERT INTO comptes_financiers (lycee_id, nom_compte, type_compte, solde_courant, devise, responsable_id, est_coffre, compte_comptable_id, compte_comptable_numero)
                VALUES (:lycee_id, :nom_compte, :type_compte, :solde_courant, :devise, :responsable_id, :est_coffre, :compte_comptable_id, :compte_comptable_numero)
            ");
            $stmt->execute([
                'lycee_id' => $data['lycee_id'],
                'nom_compte' => $data['nom_compte'],
                'type_compte' => $data['type_compte'],
                'solde_courant' => $data['solde_courant'] ?? 0.00,
                'devise' => $data['devise'] ?? 'FCFA',
                'responsable_id' => $data['responsable_id'] ?? null,
                'est_coffre' => $estCoffre,
                'compte_comptable_id' => $compteComptableId,
                'compte_comptable_numero' => $compteComptableNumero
            ]);
        } catch (Exception $e) {
            $stmt = $db->prepare("
                INSERT INTO comptes_financiers (lycee_id, nom_compte, type_compte, solde_courant, devise, responsable_id, est_coffre, compte_comptable_numero)
                VALUES (:lycee_id, :nom_compte, :type_compte, :solde_courant, :devise, :responsable_id, :est_coffre, :compte_comptable_numero)
            ");
            $stmt->execute([
                'lycee_id' => $data['lycee_id'],
                'nom_compte' => $data['nom_compte'],
                'type_compte' => $data['type_compte'],
                'solde_courant' => $data['solde_courant'] ?? 0.00,
                'devise' => $data['devise'] ?? 'FCFA',
                'responsable_id' => $data['responsable_id'] ?? null,
                'est_coffre' => $estCoffre,
                'compte_comptable_numero' => $compteComptableNumero
            ]);
        }
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