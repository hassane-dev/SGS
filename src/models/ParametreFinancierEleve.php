<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class ParametreFinancierEleve {

    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM parametres_financiers_eleves WHERE eleve_id = :eleve_id");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data, $author_user_id, $motif = '') {
        $db = Database::getInstance();

        $oldState = self::findByEleveId($data['eleve_id']);

        $frais_concernes = isset($data['frais_concernes']) ? $data['frais_concernes'] : [];
        if (!is_string($frais_concernes)) {
            $frais_concernes_json = json_encode($frais_concernes);
        } else {
            $frais_concernes_json = $frais_concernes;
        }

        $params = [
            'eleve_id' => $data['eleve_id'],
            'type_avantage' => $data['type_avantage'] ?? 'Aucun',
            'valeur_type' => $data['valeur_type'] ?? 'Pourcentage',
            'valeur' => $data['valeur'] ?? 0.00,
            'date_debut' => !empty($data['date_debut']) ? $data['date_debut'] : null,
            'date_fin' => !empty($data['date_fin']) ? $data['date_fin'] : null,
            'motif' => $data['motif'] ?? null,
            'organisme_financeur' => $data['organisme_financeur'] ?? null,
            'frais_concernes' => $frais_concernes_json,
            'tous_frais' => !empty($data['tous_frais']) ? 1 : 0
        ];

        if ($oldState) {
            $stmt = $db->prepare("UPDATE parametres_financiers_eleves SET
                type_avantage = :type_avantage,
                valeur_type = :valeur_type,
                valeur = :valeur,
                date_debut = :date_debut,
                date_fin = :date_fin,
                motif = :motif,
                organisme_financeur = :organisme_financeur,
                frais_concernes = :frais_concernes,
                tous_frais = :tous_frais
                WHERE eleve_id = :eleve_id");
            $success = $stmt->execute($params);
        } else {
            $stmt = $db->prepare("INSERT INTO parametres_financiers_eleves (
                eleve_id, type_avantage, valeur_type, valeur, date_debut, date_fin, motif, organisme_financeur, frais_concernes, tous_frais
            ) VALUES (
                :eleve_id, :type_avantage, :valeur_type, :valeur, :date_debut, :date_fin, :motif, :organisme_financeur, :frais_concernes, :tous_frais
            )");
            $success = $stmt->execute($params);
        }

        if ($success) {
            $newState = self::findByEleveId($data['eleve_id']);
            self::addHistory($data['eleve_id'], $author_user_id, $oldState, $newState, $motif);
        }

        return $success;
    }

    public static function addHistory($eleve_id, $user_id, $oldState, $newState, $motif) {
        $db = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $role = Auth::get('role_name') ?? 'N/A';

        $stmt = $db->prepare("INSERT INTO parametres_financiers_historique (eleve_id, user_id, ancienne_valeur, nouvelle_valeur, motif, ip_modification, role_utilisateur) VALUES (:eleve_id, :user_id, :ancienne_valeur, :nouvelle_valeur, :motif, :ip, :role)");
        return $stmt->execute([
            'eleve_id' => $eleve_id,
            'user_id' => $user_id,
            'ancienne_valeur' => $oldState ? json_encode($oldState) : null,
            'nouvelle_valeur' => $newState ? json_encode($newState) : null,
            'motif' => $motif ?: ($newState['motif'] ?? ''),
            'ip' => $ip,
            'role' => $role
        ]);
    }

    public static function getHistoryByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT h.*, u.nom as user_nom, u.prenom as user_prenom FROM parametres_financiers_historique h
            JOIN utilisateurs u ON h.user_id = u.id_user
            WHERE h.eleve_id = :eleve_id ORDER BY h.date_modification DESC");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compute adjusted fee based on student advantages.
     */
    public static function getAdjustedFee($eleveId, $feeKey, $baseAmount) {
        $params = self::findByEleveId($eleveId);
        if (!$params || $params['type_avantage'] === 'Aucun') {
            return $baseAmount;
        }

        $today = date('Y-m-d');
        if (!empty($params['date_debut']) && $today < $params['date_debut']) {
            return $baseAmount;
        }
        if (!empty($params['date_fin']) && $today > $params['date_fin']) {
            return $baseAmount;
        }

        $frais_concernes = json_decode($params['frais_concernes'] ?? '[]', true);
        if (!is_array($frais_concernes)) {
            $frais_concernes = [];
        }

        $applies = !empty($params['tous_frais']) || in_array($feeKey, $frais_concernes);

        if (!$applies) {
            return $baseAmount;
        }

        $valeur = (float)$params['valeur'];
        if ($params['type_avantage'] === 'Exonération' && empty($params['valeur'])) {
            return 0.00;
        }

        if ($params['valeur_type'] === 'Pourcentage') {
            $adjusted = $baseAmount * (1 - ($valeur / 100));
        } else { // Montant fixe
            $adjusted = max(0, $baseAmount - $valeur);
        }

        return $adjusted;
    }

    /**
     * Get a list of all distinct fee keys across all configurations for the given lycée and year.
     */
    public static function getAvailableFeesList($lycee_id, $annee_academique_id) {
        $fees = [
            'frais_inscription' => "Frais d'inscription",
            'frais_mensuel' => "Mensualités",
            'frais_carte' => "Carte scolaire",
            'frais_logo' => "Logo",
        ];

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT autres_frais FROM frais WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
            $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $annee_academique_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if (!empty($row['autres_frais'])) {
                    $extra = json_decode($row['autres_frais'], true);
                    if (is_array($extra)) {
                        foreach ($extra as $key => $val) {
                            if (!isset($fees[$key])) {
                                $fees[$key] = ucfirst(str_replace('_', ' ', $key));
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error in getAvailableFeesList: " . $e->getMessage());
        }

        $standards = [
            'assurance' => "Assurance",
            'bibliotheque' => "Bibliothèque",
            'transport' => "Transport",
            'cantine' => "Cantine"
        ];
        foreach ($standards as $k => $v) {
            if (!isset($fees[$k])) {
                $fees[$k] = $v;
            }
        }

        return $fees;
    }
}
