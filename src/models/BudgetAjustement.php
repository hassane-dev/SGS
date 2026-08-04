<?php
// src/models/BudgetAjustement.php

require_once __DIR__ . '/../config/database.php';

class BudgetAjustement {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_ajustements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ba.*,
                   u.nom as user_nom, u.prenom as user_prenom,
                   src_cat.nom_categorie as src_cat_nom, src_cc.nom_centre as src_cc_nom,
                   dst_cat.nom_categorie as dst_cat_nom, dst_cc.nom_centre as dst_cc_nom
            FROM budget_ajustements ba
            LEFT JOIN utilisateurs u ON ba.execute_par = u.id_user
            LEFT JOIN budget_lignes src_bl ON ba.ligne_source_id = src_bl.id
            LEFT JOIN depense_categories src_cat ON src_bl.categorie_id = src_cat.id
            LEFT JOIN depense_centres_couts src_cc ON src_bl.centre_cout_id = src_cc.id
            LEFT JOIN budget_lignes dst_bl ON ba.ligne_destination_id = dst_bl.id
            LEFT JOIN depense_categories dst_cat ON dst_bl.categorie_id = dst_cat.id
            LEFT JOIN depense_centres_couts dst_cc ON dst_bl.centre_cout_id = dst_cc.id
            WHERE ba.lycee_id = :lycee_id
            ORDER BY ba.date_ajustement DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();

        if (empty($data['motif'])) {
            throw new Exception("Le motif de l'ajustement est obligatoire.");
        }
        if (empty($data['montant']) || (float)$data['montant'] <= 0) {
            throw new Exception("Le montant de l'ajustement doit être strictement supérieur à zéro.");
        }

        $stmt = $db->prepare("
            INSERT INTO budget_ajustements (
                lycee_id, type_ajustement, ligne_source_id, ligne_destination_id,
                montant, motif, execute_par, date_ajustement
            ) VALUES (
                :lycee_id, :type_ajustement, :ligne_source_id, :ligne_destination_id,
                :montant, :motif, :execute_par, :date_ajustement
            )
        ");

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'type_ajustement' => $data['type_ajustement'],
            'ligne_source_id' => $data['ligne_source_id'] ?? null,
            'ligne_destination_id' => $data['ligne_destination_id'],
            'montant' => $data['montant'],
            'motif' => $data['motif'],
            'execute_par' => $data['execute_par'],
            'date_ajustement' => $now
        ]);

        return $db->lastInsertId();
    }
}
?>