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

    public static function genererSalaires($lycee_id, $annee, $mois) {
        $db = Database::getInstance();

        // 1. Find all eligible personnel who don't already have a salary for this period
        $sql = "SELECT
                    u.id_user, u.lycee_id, tc.type_paiement, tc.montant_fixe, tc.taux_horaire
                FROM
                    utilisateurs u
                JOIN
                    type_contrat tc ON u.contrat_id = tc.id_contrat
                LEFT JOIN
                    salaires s ON u.id_user = s.personnel_id AND s.periode_annee = :annee AND s.periode_mois = :mois AND s.lycee_id = :lycee_id
                WHERE
                    u.lycee_id = :lycee_id
                    AND u.actif = 1
                    AND (tc.prise_en_charge = 'Ecole' OR tc.prise_en_charge = 'Mixte')
                    AND tc.type_paiement != 'aucun'
                    AND s.id_salaire IS NULL";

        $stmt = $db->prepare($sql);
        $stmt->execute(['lycee_id' => $lycee_id, 'annee' => $annee, 'mois' => $mois]);
        $personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $salairesCrees = 0;

        foreach ($personnels as $personnel) {
            $salaireData = [
                'personnel_id' => $personnel['id_user'],
                'periode_annee' => $annee,
                'periode_mois' => $mois,
                'lycee_id' => $personnel['lycee_id'],
                'etat_paiement' => 'non_paye',
                'nb_heures_travaillees' => null
            ];

            if ($personnel['type_paiement'] == 'fixe') {
                $salaireData['montant'] = $personnel['montant_fixe'];
                $salaireData['mode_paiement'] = 'mensuel';

            } elseif ($personnel['type_paiement'] == 'a_l_heure') {
                // Calculate total hours from cahier_texte
                $heuresStmt = $db->prepare("
                    SELECT SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))) / 3600 AS total_heures
                    FROM cahier_texte
                    WHERE personnel_id = :personnel_id
                      AND ecole_id = :ecole_id
                      AND YEAR(date_cours) = :annee
                      AND MONTH(date_cours) = :mois
                ");
                $heuresStmt->execute([
                    'personnel_id' => $personnel['id_user'],
                    'ecole_id' => $lycee_id,
                    'annee' => $annee,
                    'mois' => $mois
                ]);
                $resultatHeures = $heuresStmt->fetch(PDO::FETCH_ASSOC);
                $heuresTravaillees = $resultatHeures['total_heures'] ?? 0;

                $salaireData['montant'] = $heuresTravaillees * $personnel['taux_horaire'];
                $salaireData['mode_paiement'] = 'horaire';
                $salaireData['nb_heures_travaillees'] = $heuresTravaillees;
            }

            // Insert the new salary record using the existing save method
            if (isset($salaireData['montant'])) {
                self::save($salaireData);
                $salairesCrees++;
            }
        }

        return $salairesCrees;
    }
}
?>