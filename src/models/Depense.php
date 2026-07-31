<?php
// src/models/Depense.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/DepenseCategorie.php';
require_once __DIR__ . '/DepenseCentreCout.php';
require_once __DIR__ . '/DepenseBeneficiaire.php';
require_once __DIR__ . '/DepensePiece.php';

class Depense {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depenses WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByPieceNumber($lyceeId, $numeroPiece) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depenses WHERE lycee_id = :lycee_id AND numero_piece = :numero_piece");
        $stmt->execute(['lycee_id' => $lyceeId, 'numero_piece' => $numeroPiece]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId, $filters = [], $limit = 20, $offset = 0) {
        $db = Database::getInstance();

        $sql = "SELECT d.*,
                       c.nom_categorie,
                       cc.nom_centre,
                       b.nom_beneficiaire
                FROM depenses d
                LEFT JOIN depense_categories c ON d.categorie_id = c.id
                LEFT JOIN depense_centres_couts cc ON d.centre_cout_id = cc.id
                LEFT JOIN depense_beneficiaires b ON d.beneficiaire_id = b.id
                WHERE d.lycee_id = :lycee_id";

        $params = ['lycee_id' => $lyceeId];

        if (!empty($filters['statut'])) {
            $sql .= " AND d.statut = :statut";
            $params['statut'] = $filters['statut'];
        }
        if (!empty($filters['categorie_id'])) {
            $sql .= " AND d.categorie_id = :categorie_id";
            $params['categorie_id'] = $filters['categorie_id'];
        }
        if (!empty($filters['date_debut'])) {
            $sql .= " AND d.date_creation >= :date_debut";
            $params['date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND d.date_creation <= :date_fin";
            $params['date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (d.numero_piece LIKE :search OR d.motif LIKE :search OR b.nom_beneficiaire LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY d.date_creation DESC LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        // Bind integer parameters explicitly for limit and offset to work across all PDO drivers
        $stmt->bindValue(':lycee_id', $lyceeId, PDO::PARAM_INT);
        if (!empty($filters['statut'])) $stmt->bindValue(':statut', $params['statut']);
        if (!empty($filters['categorie_id'])) $stmt->bindValue(':categorie_id', $params['categorie_id'], PDO::PARAM_INT);
        if (!empty($filters['date_debut'])) $stmt->bindValue(':date_debut', $params['date_debut']);
        if (!empty($filters['date_fin'])) $stmt->bindValue(':date_fin', $params['date_fin']);
        if (!empty($filters['search'])) $stmt->bindValue(':search', $params['search']);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countByLycee($lyceeId, $filters = []) {
        $db = Database::getInstance();

        $sql = "SELECT COUNT(*) FROM depenses d
                LEFT JOIN depense_beneficiaires b ON d.beneficiaire_id = b.id
                WHERE d.lycee_id = :lycee_id";

        $params = ['lycee_id' => $lyceeId];

        if (!empty($filters['statut'])) {
            $sql .= " AND d.statut = :statut";
            $params['statut'] = $filters['statut'];
        }
        if (!empty($filters['categorie_id'])) {
            $sql .= " AND d.categorie_id = :categorie_id";
            $params['categorie_id'] = $filters['categorie_id'];
        }
        if (!empty($filters['date_debut'])) {
            $sql .= " AND d.date_creation >= :date_debut";
            $params['date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND d.date_creation <= :date_fin";
            $params['date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (d.numero_piece LIKE :search OR d.motif LIKE :search OR b.nom_beneficiaire LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function create($data) {
        $db = Database::getInstance();

        // Check if unique constraint would fail
        $existing = self::findByPieceNumber($data['lycee_id'], $data['numero_piece']);
        if ($existing) {
            throw new Exception("IDEMPOTENCY_CONFLICT: Le numéro de pièce '" . $data['numero_piece'] . "' existe déjà pour cet établissement.");
        }

        // Validate basic parameters
        if (empty($data['numero_piece'])) {
            throw new Exception("Le numéro de pièce est requis.");
        }
        if (empty($data['categorie_id'])) {
            throw new Exception("La catégorie de dépense est requise.");
        }
        if (empty($data['beneficiaire_id'])) {
            throw new Exception("Le bénéficiaire est requis.");
        }
        if (empty($data['montant']) || (float)$data['montant'] <= 0) {
            throw new Exception("Le montant doit être strictement positif.");
        }
        if (empty($data['motif'])) {
            throw new Exception("Le motif de la dépense est requis.");
        }
        if (empty($data['exercice_financier_id'])) {
            throw new Exception("L'exercice financier est requis.");
        }

        $stmt = $db->prepare("
            INSERT INTO depenses (
                lycee_id, numero_piece, categorie_id, centre_cout_id, beneficiaire_id,
                montant, motif, statut, cree_par, exercice_financier_id, date_creation, date_modification
            ) VALUES (
                :lycee_id, :numero_piece, :categorie_id, :centre_cout_id, :beneficiaire_id,
                :montant, :motif, 'brouillon', :cree_par, :exercice_financier_id, :date_creation, :date_modification
            )
        ");

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'numero_piece' => $data['numero_piece'],
            'categorie_id' => $data['categorie_id'],
            'centre_cout_id' => $data['centre_cout_id'] ?? null,
            'beneficiaire_id' => $data['beneficiaire_id'],
            'montant' => $data['montant'],
            'motif' => $data['motif'],
            'cree_par' => $data['cree_par'],
            'exercice_financier_id' => $data['exercice_financier_id'],
            'date_creation' => $now,
            'date_modification' => $now
        ]);

        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Dépense introuvable.");
        }

        // Only allow modification under draft/brouillon state
        if ($current['statut'] !== 'brouillon') {
            throw new Exception("Impossible de modifier une dépense qui n'est plus à l'état de brouillon.");
        }

        // If changing piece number, ensure unique constraint holds
        if (isset($data['numero_piece']) && $data['numero_piece'] !== $current['numero_piece']) {
            $existing = self::findByPieceNumber($current['lycee_id'], $data['numero_piece']);
            if ($existing) {
                throw new Exception("Le numéro de pièce '" . $data['numero_piece'] . "' existe déjà pour cet établissement.");
            }
        }

        $stmt = $db->prepare("
            UPDATE depenses
            SET numero_piece = :numero_piece,
                categorie_id = :categorie_id,
                centre_cout_id = :centre_cout_id,
                beneficiaire_id = :beneficiaire_id,
                montant = :montant,
                motif = :motif,
                date_modification = :date_modification
            WHERE id = :id
        ");

        return $stmt->execute([
            'numero_piece' => $data['numero_piece'] ?? $current['numero_piece'],
            'categorie_id' => $data['categorie_id'] ?? $current['categorie_id'],
            'centre_cout_id' => isset($data['centre_cout_id']) ? $data['centre_cout_id'] : $current['centre_cout_id'],
            'beneficiaire_id' => $data['beneficiaire_id'] ?? $current['beneficiaire_id'],
            'montant' => $data['montant'] ?? $current['montant'],
            'motif' => $data['motif'] ?? $current['motif'],
            'date_modification' => date('Y-m-d H:i:s'),
            'id' => $id
        ]);
    }

    /**
     * Exclusion of direct access to write state: We enforce that updates to status can only be performed
     * when passing a special workflow guard or authorization token.
     */
    public static function updateStatus($id, $newStatus, $guardToken) {
        if ($guardToken !== 'WORKFLOW_SERVICE_AUTHORIZED') {
            throw new Exception("Accès refusé: La modification directe de l'état d'une dépense est interdite. Utilisez DepenseWorkflowService.");
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE depenses SET statut = :statut, date_modification = :now WHERE id = :id");
        return $stmt->execute([
            'statut' => $newStatus,
            'now' => date('Y-m-d H:i:s'),
            'id' => $id
        ]);
    }

    public static function updatePaymentDetails($id, $compteId, $mvtId, $newStatus, $guardToken) {
        if ($guardToken !== 'WORKFLOW_SERVICE_AUTHORIZED') {
            throw new Exception("Accès refusé.");
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE depenses
            SET compte_id = :compte_id,
                mouvement_tresorerie_id = :mvt_id,
                statut = :statut,
                date_modification = :now
            WHERE id = :id
        ");
        return $stmt->execute([
            'compte_id' => $compteId,
            'mvt_id' => $mvtId,
            'statut' => $newStatus,
            'now' => date('Y-m-d H:i:s'),
            'id' => $id
        ]);
    }

    public static function getPieces($depenseId) {
        return DepensePiece::findByDepense($depenseId);
    }
}
?>