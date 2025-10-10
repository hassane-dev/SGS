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
        $sql .= " ORDER BY s.periode_annee DESC, s.periode_mois DESC";

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

    public static function save($data) {
        $isUpdate = !empty($data['id_salaire']);
        $db = Database::getInstance();

        if ($isUpdate) {
            $sql = "UPDATE salaires SET
                        personnel_id = :personnel_id, montant = :montant, mode_paiement = :mode_paiement,
                        nb_heures_travaillees = :nb_heures_travaillees, periode_mois = :periode_mois,
                        periode_annee = :periode_annee, date_paiement = :date_paiement,
                        etat_paiement = :etat_paiement, lycee_id = :lycee_id, annee_id = :annee_id
                    WHERE id_salaire = :id_salaire";
        } else {
            $sql = "INSERT INTO salaires (
                        personnel_id, montant, mode_paiement, nb_heures_travaillees, periode_mois,
                        periode_annee, date_paiement, etat_paiement, lycee_id, annee_id
                    ) VALUES (
                        :personnel_id, :montant, :mode_paiement, :nb_heures_travaillees, :periode_mois,
                        :periode_annee, :date_paiement, :etat_paiement, :lycee_id, :annee_id
                    )";
        }

        $stmt = $db->prepare($sql);

        $params = [
            'personnel_id' => $data['personnel_id'],
            'montant' => $data['montant'],
            'mode_paiement' => $data['mode_paiement'],
            'nb_heures_travaillees' => $data['nb_heures_travaillees'] ?? null,
            'periode_mois' => $data['periode_mois'],
            'periode_annee' => $data['periode_annee'],
            'date_paiement' => $data['date_paiement'] ?? null,
            'etat_paiement' => $data['etat_paiement'] ?? 'non_paye',
            'lycee_id' => $data['lycee_id'],
            'annee_id' => $data['annee_id'] ?? null,
        ];

        if ($isUpdate) {
            $params['id_salaire'] = $data['id_salaire'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM salaires WHERE id_salaire = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>