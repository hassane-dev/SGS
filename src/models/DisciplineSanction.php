<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/DisciplineTypeSanction.php';
require_once __DIR__ . '/DisciplineIncident.php';
require_once __DIR__ . '/DisciplineHistorique.php';

class DisciplineSanction {

    public static function findById($id, $lyceeId = null) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$id || !$lyceeId) {
            return false;
        }

        $sql = "SELECT s.*, ts.code as type_code, ts.libelle as type_libelle, ts.autorite_min_requise,
                       ts.demande_duree_jours, ts.demande_heures, ts.affiche_sur_bulletin,
                       e.nom as eleve_nom, e.prenom as eleve_prenom, COALESCE(e.identifiant_public, 'N/A') as eleve_matricule,
                       c.niveau as classe_niveau, c.serie as classe_serie, c.numero as classe_numero,
                       u.nom as prononcee_nom, u.prenom as prononcee_prenom,
                       i.date_incident, i.description_faits as incident_description,
                       ti.libelle as incident_type_libelle
                FROM discipline_sanctions s
                JOIN discipline_types_sanctions ts ON s.type_sanction_id = ts.id
                JOIN eleves e ON s.eleve_id = e.id_eleve
                JOIN classes c ON s.classe_id = c.id_classe
                JOIN utilisateurs u ON s.prononcee_par_user_id = u.id_user
                LEFT JOIN discipline_incidents i ON s.incident_id = i.id
                LEFT JOIN discipline_types_incidents ti ON i.type_incident_id = ti.id
                WHERE s.id = :id AND s.lycee_id = :lycee_id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'lycee_id' => $lyceeId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineSanction::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function search($filters = [], $lyceeId = null, $isTeacherScoped = false, $allowedClassIds = []) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$lyceeId) {
            return [];
        }

        $sql = "SELECT s.*, ts.code as type_code, ts.libelle as type_libelle, ts.autorite_min_requise,
                       e.nom as eleve_nom, e.prenom as eleve_prenom, COALESCE(e.identifiant_public, 'N/A') as eleve_matricule,
                       c.niveau as classe_niveau, c.serie as classe_serie, c.numero as classe_numero,
                       u.nom as prononcee_nom, u.prenom as prononcee_prenom
                FROM discipline_sanctions s
                JOIN discipline_types_sanctions ts ON s.type_sanction_id = ts.id
                JOIN eleves e ON s.eleve_id = e.id_eleve
                JOIN classes c ON s.classe_id = c.id_classe
                JOIN utilisateurs u ON s.prononcee_par_user_id = u.id_user
                WHERE s.lycee_id = :lycee_id";

        $params = ['lycee_id' => $lyceeId];

        if ($isTeacherScoped) {
            if (!empty($allowedClassIds)) {
                $placeholders = [];
                foreach (array_values($allowedClassIds) as $idx => $cid) {
                    $key = ':t_cls_' . $idx;
                    $placeholders[] = $key;
                    $params[$key] = (int)$cid;
                }
                $sql .= " AND s.classe_id IN (" . implode(',', $placeholders) . ")";
            } else {
                return [];
            }
        }

        if (!empty($filters['annee_academique_id'])) {
            $sql .= " AND s.annee_academique_id = :annee_id";
            $params['annee_id'] = $filters['annee_academique_id'];
        }

        if (!empty($filters['type_sanction_id'])) {
            $sql .= " AND s.type_sanction_id = :type_id";
            $params['type_id'] = $filters['type_sanction_id'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND s.statut = :statut";
            $params['statut'] = $filters['statut'];
        }

        if (!empty($filters['classe_id'])) {
            $sql .= " AND s.classe_id = :classe_id";
            $params['classe_id'] = $filters['classe_id'];
        }

        if (!empty($filters['eleve_id'])) {
            $sql .= " AND s.eleve_id = :eleve_id";
            $params['eleve_id'] = $filters['eleve_id'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND s.date_decision >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND s.date_decision <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }

        $sql .= " ORDER BY s.date_decision DESC, s.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineSanction::search: " . $e->getMessage());
            return [];
        }
    }

    public static function create($data) {
        $db = Database::getInstance();
        $lyceeId = Auth::getLyceeId();
        $userId = Auth::getUserId();

        if (!$lyceeId || !$userId) {
            throw new InvalidArgumentException("Session ou établissement non valide.");
        }

        $eleveId = (int)($data['eleve_id'] ?? 0);
        if ($eleveId <= 0) {
            throw new InvalidArgumentException("L'élève à sanctionner est obligatoire.");
        }

        $typeSanctionId = (int)($data['type_sanction_id'] ?? 0);
        if ($typeSanctionId <= 0) {
            throw new InvalidArgumentException("Le type de sanction est obligatoire.");
        }

        $typeSanction = DisciplineTypeSanction::findById($typeSanctionId, $lyceeId);
        if (!$typeSanction || (int)$typeSanction['actif'] !== 1) {
            throw new InvalidArgumentException("Type de sanction introuvable ou inactif.");
        }

        $motif = trim($data['motif'] ?? '');
        if (empty($motif)) {
            throw new InvalidArgumentException("Le motif de la sanction est obligatoire.");
        }

        $dateDecision = $data['date_decision'] ?? date('Y-m-d');
        if (empty($dateDecision)) {
            throw new InvalidArgumentException("La date de décision est obligatoire.");
        }

        // Active Academic Year
        $anneeId = $data['annee_academique_id'] ?? null;
        if (!$anneeId) {
            $stmtAnnee = $db->prepare("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
            $stmtAnnee->execute();
            $activeAnnee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
            if (!$activeAnnee) {
                throw new InvalidArgumentException("Aucune année académique active trouvée.");
            }
            $anneeId = $activeAnnee['id'];
        }

        // Verify Student & capture historical class_id
        $stmtEleveCheck = $db->prepare("
            SELECT e.id_eleve, et.classe_id
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE e.id_eleve = :eleve_id
              AND e.lycee_id = :lycee_id
              AND et.annee_academique_id = :annee_id
            LIMIT 1
        ");
        $stmtEleveCheck->execute([
            'eleve_id' => $eleveId,
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId
        ]);
        $eleveInfo = $stmtEleveCheck->fetch(PDO::FETCH_ASSOC);

        if (!$eleveInfo) {
            throw new InvalidArgumentException("L'élève n'appartient pas à cet établissement ou n'a pas de classe active pour cette année académique.");
        }

        $classeId = (int)$eleveInfo['classe_id'];

        // Validate incident_id if provided
        $incidentId = !empty($data['incident_id']) ? (int)$data['incident_id'] : null;
        if ($incidentId) {
            $incident = DisciplineIncident::findById($incidentId, $lyceeId);
            if (!$incident) {
                throw new InvalidArgumentException("L'incident spécifié est introuvable ou n'appartient pas à cet établissement.");
            }

            // Verify that eleve_id is involved in this incident
            $stmtCheckInvolvement = $db->prepare("SELECT id FROM discipline_incident_eleves WHERE incident_id = :inc_id AND eleve_id = :eleve_id LIMIT 1");
            $stmtCheckInvolvement->execute(['inc_id' => $incidentId, 'eleve_id' => $eleveId]);
            if (!$stmtCheckInvolvement->fetch()) {
                throw new InvalidArgumentException("L'élève sélectionné ne fait pas partie des élèves impliqués dans cet incident.");
            }
        }

        // Duration / Hours validation
        $dureeJours = null;
        if (!empty($typeSanction['demande_duree_jours'])) {
            $dureeJours = isset($data['duree_jours']) ? (int)$data['duree_jours'] : 0;
            if ($dureeJours <= 0) {
                throw new InvalidArgumentException("Une durée en jours supérieure à 0 est exigée pour ce type de sanction.");
            }
        }

        $dureeHeures = null;
        if (!empty($typeSanction['demande_heures'])) {
            $dureeHeures = isset($data['duree_heures']) ? (int)$data['duree_heures'] : 0;
            if ($dureeHeures <= 0) {
                throw new InvalidArgumentException("Un nombre d'heures supérieur à 0 est exigé pour ce type de sanction.");
            }
        }

        $dateDebutExecution = !empty($data['date_debut_execution']) ? $data['date_debut_execution'] : null;
        $dateFinExecution = !empty($data['date_fin_execution']) ? $data['date_fin_execution'] : null;
        $details = !empty($data['details']) ? trim($data['details']) : null;

        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO discipline_sanctions
                (lycee_id, annee_academique_id, incident_id, eleve_id, classe_id, type_sanction_id, motif, details, prononcee_par_user_id, date_decision, date_debut_execution, date_fin_execution, duree_jours, duree_heures, statut, created_at, updated_at)
                VALUES (:lycee_id, :annee_id, :incident_id, :eleve_id, :classe_id, :type_sanc_id, :motif, :details, :user_id, :date_dec, :date_deb, :date_fin, :duree_j, :duree_h, 'prononcee', '$now', '$now')";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'annee_id' => $anneeId,
                'incident_id' => $incidentId,
                'eleve_id' => $eleveId,
                'classe_id' => $classeId,
                'type_sanc_id' => $typeSanctionId,
                'motif' => $motif,
                'details' => $details,
                'user_id' => $userId,
                'date_dec' => $dateDecision,
                'date_deb' => $dateDebutExecution,
                'date_fin' => $dateFinExecution,
                'duree_j' => $dureeJours,
                'duree_h' => $dureeHeures
            ]);

            $sanctionId = (int)$db->lastInsertId();

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => $userId,
                'annee_academique_id' => $anneeId,
                'incident_id' => $incidentId,
                'sanction_id' => $sanctionId,
                'eleve_id' => $eleveId,
                'action' => 'PRONONCE_SANCTION',
                'statut_apres' => 'prononcee',
                'description' => "Sanction #{$sanctionId} prononcée pour '{$motif}'"
            ]);

            return $sanctionId;
        } catch (PDOException $e) {
            error_log("Error in DisciplineSanction::create: " . $e->getMessage());
            throw $e;
        }
    }

    public static function updateStatus($sanctionId, $newStatut, $motifLeveeAnnulation = null) {
        $db = Database::getInstance();
        $lyceeId = Auth::getLyceeId();
        $userId = Auth::getUserId();

        if (!$sanctionId || !$lyceeId) {
            return false;
        }

        $statutsValides = ['prononcee', 'en_cours', 'executee', 'levee', 'annulee'];
        if (!in_array($newStatut, $statutsValides, true)) {
            throw new InvalidArgumentException("Statut de sanction non valide.");
        }

        $sanction = self::findById($sanctionId, $lyceeId);
        if (!$sanction) {
            throw new InvalidArgumentException("Sanction introuvable.");
        }

        $currentStatut = $sanction['statut'];

        // Enforce transition rules
        if (in_array($currentStatut, ['executee', 'levee', 'annulee'], true)) {
            throw new InvalidArgumentException("Une sanction clôturée (exécutée, levée ou annulée) ne peut plus changer de statut.");
        }

        if ($currentStatut === 'prononcee' && !in_array($newStatut, ['en_cours', 'executee', 'annulee'], true)) {
            throw new InvalidArgumentException("Transition de statut non autorisée depuis le statut 'prononcée'.");
        }

        if ($currentStatut === 'en_cours' && !in_array($newStatut, ['executee', 'levee', 'annulee'], true)) {
            throw new InvalidArgumentException("Transition de statut non autorisée depuis le statut 'en cours'.");
        }

        $motifClean = trim($motifLeveeAnnulation ?? '');
        if (in_array($newStatut, ['levee', 'annulee'], true)) {
            if (empty($motifClean)) {
                $labelAction = ($newStatut === 'levee') ? "de la levée" : "de l'annulation";
                throw new InvalidArgumentException("Le motif " . $labelAction . " de la sanction est obligatoire.");
            }
        }

        $now = date('Y-m-d H:i:s');
        $params = [
            'statut' => $newStatut,
            'id' => $sanctionId,
            'lycee_id' => $lyceeId
        ];

        if (in_array($newStatut, ['levee', 'annulee'], true)) {
            $sql = "UPDATE discipline_sanctions SET
                    statut = :statut,
                    date_levee_annulation = '$now',
                    motif_levee_annulation = :motif_levee,
                    par_user_id_levee_annulation = :user_id_levee,
                    updated_at = '$now'
                    WHERE id = :id AND lycee_id = :lycee_id";
            $params['motif_levee'] = $motifClean;
            $params['user_id_levee'] = $userId;
        } else {
            $sql = "UPDATE discipline_sanctions SET statut = :statut, updated_at = '$now' WHERE id = :id AND lycee_id = :lycee_id";
        }

        try {
            $stmt = $db->prepare($sql);
            $res = $stmt->execute($params);

            if ($res) {
                $actionName = match($newStatut) {
                    'levee' => 'LEVEE_SANCTION',
                    'annulee' => 'ANNULATION_SANCTION',
                    'executee' => 'EXECUTION_SANCTION',
                    default => 'CHANGEMENT_STATUT_SANCTION'
                };

                $description = "Statut de la sanction #{$sanctionId} changé de '{$currentStatut}' vers '{$newStatut}'";
                if (in_array($newStatut, ['levee', 'annulee'], true)) {
                    $description .= " (Motif: '{$motifClean}')";
                }

                DisciplineHistorique::log([
                    'lycee_id' => $lyceeId,
                    'user_id' => $userId,
                    'annee_academique_id' => $sanction['annee_academique_id'],
                    'incident_id' => $sanction['incident_id'],
                    'sanction_id' => $sanctionId,
                    'eleve_id' => $sanction['eleve_id'],
                    'action' => $actionName,
                    'statut_avant' => $currentStatut,
                    'statut_apres' => $newStatut,
                    'description' => $description
                ]);
            }

            return $res;
        } catch (PDOException $e) {
            error_log("Error in DisciplineSanction::updateStatus: " . $e->getMessage());
            return false;
        }
    }
}
?>