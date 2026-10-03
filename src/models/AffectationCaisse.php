<?php

require_once __DIR__ . '/../config/database.php';

class AffectationCaisse {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM affectations_caisses WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findActiveForUserAndCompte($userId, $compteId, $lyceeId) {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT a.*, c.nom_compte
            FROM affectations_caisses a
            JOIN comptes_financiers c ON a.compte_id = c.id
            WHERE a.lycee_id = :lycee_id
              AND a.user_id = :user_id
              AND a.compte_id = :compte_id
              AND a.statut = 'actif'
              AND a.date_debut <= :today
              AND (a.date_fin IS NULL OR a.date_fin >= :today)
            ORDER BY (CASE WHEN a.type_affectation = 'remplacant' THEN 1 ELSE 2 END), a.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            'lycee_id' => $lyceeId,
            'user_id' => $userId,
            'compte_id' => $compteId,
            'today' => $today
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findActiveCaissesForUser($userId, $lyceeId) {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT DISTINCT c.*, a.type_affectation, a.motif_remplacement
            FROM comptes_financiers c
            JOIN affectations_caisses a ON c.id = a.compte_id
            WHERE a.lycee_id = :lycee_id
              AND a.user_id = :user_id
              AND a.statut = 'actif'
              AND c.type_compte = 'caisse'
              AND c.est_coffre = 0
              AND c.statut = 'actif'
              AND a.date_debut <= :today
              AND (a.date_fin IS NULL OR a.date_fin >= :today)
            ORDER BY c.nom_compte ASC
        ");
        $stmt->execute([
            'lycee_id' => $lyceeId,
            'user_id' => $userId,
            'today' => $today
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();

        $lyceeId = $data['lycee_id'];
        $compteId = $data['compte_id'];
        $userId = $data['user_id'];
        $typeAffectation = $data['type_affectation'] ?? 'titulaire';
        $dateDebut = $data['date_debut'];
        $dateFin = !empty($data['date_fin']) ? $data['date_fin'] : null;
        $attribuePar = $data['attribue_par'];
        $motif = $data['motif_remplacement'] ?? null;

        // 1. Vault check
        $compte = CompteFinancier::findById($compteId);
        if (!$compte || !empty($compte['est_coffre'])) {
            throw new Exception("Opération interdite : impossible d'affecter un caissier au Coffre Principal.");
        }

        // 2. Overlap validation for Titulaire
        if ($typeAffectation === 'titulaire') {
            $stmtCheck = $db->prepare("
                SELECT COUNT(*) FROM affectations_caisses
                WHERE lycee_id = :lycee_id
                  AND compte_id = :compte_id
                  AND type_affectation = 'titulaire'
                  AND statut = 'actif'
                  AND (date_fin IS NULL OR date_fin >= :date_debut)
                  AND (:date_fin IS NULL OR date_debut <= :date_fin)
            ");
            $stmtCheck->execute([
                'lycee_id' => $lyceeId,
                'compte_id' => $compteId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                throw new Exception("Cette caisse possède déjà un titulaire actif sur cette période.");
            }
        }

        $stmt = $db->prepare("
            INSERT INTO affectations_caisses (
                lycee_id, compte_id, user_id, type_affectation, date_debut, date_fin, statut, attribue_par, motif_remplacement
            ) VALUES (
                :lycee_id, :compte_id, :user_id, :type_affectation, :date_debut, :date_fin, 'actif', :attribue_par, :motif
            )
        ");

        $stmt->execute([
            'lycee_id' => $lyceeId,
            'compte_id' => $compteId,
            'user_id' => $userId,
            'type_affectation' => $typeAffectation,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'attribue_par' => $attribuePar,
            'motif' => $motif
        ]);

        return $db->lastInsertId();
    }
}
