<?php

require_once __DIR__ . '/../config/database.php';

class Salaire {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT s.*, u.nom, u.prenom
                FROM salaires s
                JOIN utilisateurs u ON s.personnel_id = u.id_user";
        if ($lycee_id !== null) {
            $sql .= " WHERE s.lycee_id = :lycee_id";
        }
        $sql .= " ORDER BY s.annee DESC, s.mois DESC";

        $stmt = $db->prepare($sql);
        if ($lycee_id !== null) {
            $stmt->execute(['lycee_id' => $lycee_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT s.*, u.nom, u.prenom
            FROM salaires s
            JOIN utilisateurs u ON s.personnel_id = u.id_user
            WHERE s.id_salaire = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO salaires (personnel_id, mois, annee, montant_brut, montant_net, date_paiement, lycee_id)
                VALUES (:personnel_id, :mois, :annee, :montant_brut, :montant_net, :date_paiement, :lycee_id)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'personnel_id' => $data['personnel_id'],
            'mois' => $data['mois'],
            'annee' => $data['annee'],
            'montant_brut' => $data['montant_brut'] ?: null,
            'montant_net' => $data['montant_net'] ?: null,
            'date_paiement' => $data['date_paiement'] ?: null,
            'lycee_id' => $data['lycee_id'],
        ]);
    }
}
?>
