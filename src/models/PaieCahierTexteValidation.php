<?php

require_once __DIR__ . '/../config/database.php';

class PaieCahierTexteValidation {

    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_cahier_texte_validations
            (cahier_id, enseignant_id, cycle_id, classe_id, matiere_id, duree_heures, taux_horaire, statut_validation, valide_par, valide_le, created_at)
            VALUES (:cahier_id, :enseignant_id, :cycle_id, :classe_id, :matiere_id, :duree_heures, :taux_horaire, :statut_validation, :valide_par, :valide_le, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'cahier_id' => $data['cahier_id'],
            'enseignant_id' => $data['enseignant_id'],
            'cycle_id' => $data['cycle_id'] ?? null,
            'classe_id' => $data['classe_id'] ?? null,
            'matiere_id' => $data['matiere_id'] ?? null,
            'duree_heures' => $data['duree_heures'],
            'taux_horaire' => $data['taux_horaire'],
            'statut_validation' => $data['statut_validation'] ?? 'en_attente',
            'valide_par' => $data['valide_par'] ?? null,
            'valide_le' => $data['valide_le'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByCahierId(int $cahierId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_cahier_texte_validations WHERE cahier_id = :cahier_id");
        $stmt->execute(['cahier_id' => $cahierId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findValidatedForTeacherAndDates(int $enseignantId, string $dateDebut, string $dateFin): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT v.*, c.date_cours, c.contenu_cours
            FROM paie_cahier_texte_validations v
            JOIN cahier_texte c ON v.cahier_id = c.cahier_id
            LEFT JOIN paie_bulletin_heures bh ON v.id = bh.cahier_validation_id
            LEFT JOIN paie_bulletins b ON bh.bulletin_id = b.id AND b.est_version_active = 1
            WHERE v.enseignant_id = :enseignant_id
              AND v.statut_validation = 'valide'
              AND c.date_cours BETWEEN :date_debut AND :date_fin
              AND b.id IS NULL
        ");
        $stmt->execute([
            'enseignant_id' => $enseignantId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function resolveContractHourlyRate(int $personnelId): float {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT cc.valeur_numerique
            FROM personnel_contrats_historique c
            JOIN personnel_contrat_composants cc ON c.id = cc.contrat_id
            WHERE c.personnel_id = :personnel_id
              AND c.statut_contrat = 'actif'
              AND (cc.nature_composant = 'taux_horaire' OR cc.code_composant = 'TAUX_HORAIRE' OR cc.unite_remuneration = 'heure')
            ORDER BY cc.id DESC LIMIT 1
        ");
        $stmt->execute(['personnel_id' => $personnelId]);
        $val = $stmt->fetchColumn();
        return ($val !== false && (float)$val > 0) ? (float)$val : 5000.00;
    }

    public static function validateSession(int $cahierId, int $userId, ?float $tauxHoraire = null): int {
        $db = Database::getInstance();
        $stmtC = $db->prepare("SELECT * FROM cahier_texte WHERE cahier_id = :id");
        $stmtC->execute(['id' => $cahierId]);
        $cahier = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$cahier) {
            throw new InvalidArgumentException("Séance du cahier de texte introuvable ID #{$cahierId}");
        }

        $dureeHeures = 2.0;
        if (!empty($cahier['heure_debut']) && !empty($cahier['heure_fin'])) {
            $t1 = strtotime($cahier['heure_debut']);
            $t2 = strtotime($cahier['heure_fin']);
            if ($t2 > $t1) {
                $dureeHeures = round(($t2 - $t1) / 3600.0, 2);
            }
        }

        $stmtCl = $db->prepare("SELECT cycle_id FROM classes WHERE id_classe = :id");
        $stmtCl->execute(['id' => $cahier['classe_id']]);
        $cycleId = $stmtCl->fetchColumn() ?: null;

        if ($tauxHoraire === null || $tauxHoraire <= 0) {
            $tauxHoraire = self::resolveContractHourlyRate((int)$cahier['personnel_id']);
        }

        $existing = self::findByCahierId($cahierId);
        if ($existing) {
            $stmtUp = $db->prepare("
                UPDATE paie_cahier_texte_validations
                SET duree_heures = :duree_heures,
                    taux_horaire = :taux_horaire,
                    statut_validation = 'valide',
                    valide_par = :valide_par,
                    valide_le = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmtUp->execute([
                'duree_heures' => $dureeHeures,
                'taux_horaire' => $tauxHoraire,
                'valide_par' => $userId,
                'id' => $existing['id']
            ]);
            return (int)$existing['id'];
        }

        return self::create([
            'cahier_id' => $cahierId,
            'enseignant_id' => $cahier['personnel_id'],
            'cycle_id' => $cycleId,
            'classe_id' => $cahier['classe_id'],
            'matiere_id' => $cahier['matiere_id'],
            'duree_heures' => $dureeHeures,
            'taux_horaire' => $tauxHoraire,
            'statut_validation' => 'valide',
            'valide_par' => $userId,
            'valide_le' => date('Y-m-d H:i:s')
        ]);
    }

    public static function bulkValidateSessions(array $cahierIds, int $userId, ?float $tauxHoraire = null): int {
        $count = 0;
        foreach ($cahierIds as $cid) {
            $cidInt = (int)$cid;
            if ($cidInt > 0) {
                self::validateSession($cidInt, $userId, $tauxHoraire);
                $count++;
            }
        }
        return $count;
    }

    public static function getTeacherHoursMetrics(array $context): array {
        $db = Database::getInstance();

        $lyceeId = $context['lycee_id'] ?? null;
        $teacherId = !empty($context['teacher_id']) ? (int)$context['teacher_id'] : null;
        $classeId = !empty($context['classe_id']) ? (int)$context['classe_id'] : null;
        $matiereId = !empty($context['matiere_id']) ? (int)$context['matiere_id'] : null;
        $cycleId = !empty($context['cycle_id']) ? (int)$context['cycle_id'] : null;
        $niveau = !empty($context['niveau']) ? trim($context['niveau']) : null;
        $serie = !empty($context['serie']) ? trim($context['serie']) : null;
        $numero = (isset($context['numero']) && $context['numero'] !== '') ? trim($context['numero']) : null;

        // 1. Calculate Realized Hours directly from cahier_texte
        $sqlReal = "
            SELECT ct.heure_debut, ct.heure_fin
            FROM cahier_texte ct
            LEFT JOIN classes cl ON ct.classe_id = cl.id_classe
            WHERE 1=1
        ";
        $paramsReal = [];

        if ($lyceeId) {
            $sqlReal .= " AND (ct.lycee_id = :lycee_id OR cl.lycee_id = :lycee_id)";
            $paramsReal['lycee_id'] = $lyceeId;
        }
        if ($teacherId) {
            $sqlReal .= " AND ct.personnel_id = :teacher_id";
            $paramsReal['teacher_id'] = $teacherId;
        }
        if ($classeId) {
            $sqlReal .= " AND ct.classe_id = :classe_id";
            $paramsReal['classe_id'] = $classeId;
        }
        if ($matiereId) {
            $sqlReal .= " AND ct.matiere_id = :matiere_id";
            $paramsReal['matiere_id'] = $matiereId;
        }
        if ($cycleId) {
            $sqlReal .= " AND cl.cycle_id = :cycle_id";
            $paramsReal['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $sqlReal .= " AND cl.niveau = :niveau";
            $paramsReal['niveau'] = $niveau;
        }
        if ($serie) {
            $sqlReal .= " AND cl.serie = :serie";
            $paramsReal['serie'] = $serie;
        }
        if ($numero !== null) {
            $sqlReal .= " AND cl.numero = :numero";
            $paramsReal['numero'] = $numero;
        }
        if (!empty($context['date_debut'])) {
            $sqlReal .= " AND ct.date_cours >= :date_debut";
            $paramsReal['date_debut'] = $context['date_debut'];
        }
        if (!empty($context['date_fin'])) {
            $sqlReal .= " AND ct.date_cours <= :date_fin";
            $paramsReal['date_fin'] = $context['date_fin'];
        }

        $stmtReal = $db->prepare($sqlReal);
        $stmtReal->execute($paramsReal);
        $rowsReal = $stmtReal->fetchAll(PDO::FETCH_ASSOC);

        $heuresRealisees = 0.0;
        foreach ($rowsReal as $r) {
            if (!empty($r['heure_debut']) && !empty($r['heure_fin'])) {
                $t1 = strtotime($r['heure_debut']);
                $t2 = strtotime($r['heure_fin']);
                if ($t2 > $t1) {
                    $heuresRealisees += ($t2 - $t1) / 3600.0;
                } else {
                    $heuresRealisees += 2.0;
                }
            } else {
                $heuresRealisees += 2.0;
            }
        }

        // 2. Calculate Validated / Paid Hours
        $sqlVal = "
            SELECT v.id, v.duree_heures, v.taux_horaire, v.statut_validation,
                   MAX(CASE WHEN b.est_version_active = 1 THEN 1 ELSE 0 END) as est_paye
            FROM paie_cahier_texte_validations v
            JOIN cahier_texte c ON v.cahier_id = c.cahier_id
            LEFT JOIN classes cl ON c.classe_id = cl.id_classe
            LEFT JOIN paie_bulletin_heures bh ON v.id = bh.cahier_validation_id
            LEFT JOIN paie_bulletins b ON bh.bulletin_id = b.id AND b.est_version_active = 1
            WHERE 1=1
        ";
        $paramsVal = [];

        if ($lyceeId) {
            $sqlVal .= " AND (c.lycee_id = :lycee_id OR cl.lycee_id = :lycee_id)";
            $paramsVal['lycee_id'] = $lyceeId;
        }
        if ($teacherId) {
            $sqlVal .= " AND v.enseignant_id = :teacher_id";
            $paramsVal['teacher_id'] = $teacherId;
        }
        if ($classeId) {
            $sqlVal .= " AND c.classe_id = :classe_id";
            $paramsVal['classe_id'] = $classeId;
        }
        if ($matiereId) {
            $sqlVal .= " AND c.matiere_id = :matiere_id";
            $paramsVal['matiere_id'] = $matiereId;
        }
        if ($cycleId) {
            $sqlVal .= " AND cl.cycle_id = :cycle_id";
            $paramsVal['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $sqlVal .= " AND cl.niveau = :niveau";
            $paramsVal['niveau'] = $niveau;
        }
        if ($serie) {
            $sqlVal .= " AND cl.serie = :serie";
            $paramsVal['serie'] = $serie;
        }
        if ($numero !== null) {
            $sqlVal .= " AND cl.numero = :numero";
            $paramsVal['numero'] = $numero;
        }
        if (!empty($context['date_debut'])) {
            $sqlVal .= " AND c.date_cours >= :date_debut";
            $paramsVal['date_debut'] = $context['date_debut'];
        }
        if (!empty($context['date_fin'])) {
            $sqlVal .= " AND c.date_cours <= :date_fin";
            $paramsVal['date_fin'] = $context['date_fin'];
        }

        $sqlVal .= " GROUP BY v.id, v.duree_heures, v.taux_horaire, v.statut_validation";

        $stmtVal = $db->prepare($sqlVal);
        $stmtVal->execute($paramsVal);
        $rowsVal = $stmtVal->fetchAll(PDO::FETCH_ASSOC);

        $heuresValidees = 0.0;
        $heuresRefusees = 0.0;
        $heuresPayees = 0.0;
        $montantEstime = 0.0;
        $montantPaye = 0.0;

        foreach ($rowsVal as $rv) {
            $dh = (float)$rv['duree_heures'];
            $th = (float)$rv['taux_horaire'];
            if ($rv['statut_validation'] === 'valide') {
                $heuresValidees += $dh;
                $montantEstime += ($dh * $th);
                if (!empty($rv['est_paye'])) {
                    $heuresPayees += $dh;
                    $montantPaye += ($dh * $th);
                }
            } elseif ($rv['statut_validation'] === 'refuse') {
                $heuresRefusees += $dh;
            }
        }

        $heuresAConsolider = max(0.0, $heuresValidees - $heuresPayees);

        return [
            'heures_realisees' => round($heuresRealisees, 2),
            'heures_validees' => round($heuresValidees, 2),
            'heures_refusees' => round($heuresRefusees, 2),
            'heures_payees' => round($heuresPayees, 2),
            'heures_a_consolider' => round($heuresAConsolider, 2),
            'montant_estime' => round($montantEstime, 2),
            'montant_paye' => round($montantPaye, 2)
        ];
    }

    public static function findSessionsForContext(array $filters): array {
        $db = Database::getInstance();
        $sql = "
            SELECT ct.*,
                   u.nom as enseignant_nom, u.prenom as enseignant_prenom, u.identifiant_public as enseignant_identifiant,
                   cl.niveau, cl.serie, cl.numero, cl.cycle_id, cy.nom_cycle,
                   m.nom_matiere,
                   v.id as validation_id, v.duree_heures, v.taux_horaire, v.statut_validation, v.valide_le,
                   v_u.nom as validator_nom, v_u.prenom as validator_prenom,
                   b.id as bulletin_id, b.version_num as bulletin_version, b.est_version_active as bulletin_active
            FROM cahier_texte ct
            LEFT JOIN utilisateurs u ON ct.personnel_id = u.id_user
            LEFT JOIN classes cl ON ct.classe_id = cl.id_classe
            LEFT JOIN cycles cy ON cl.cycle_id = cy.id_cycle
            LEFT JOIN matieres m ON ct.matiere_id = m.id_matiere
            LEFT JOIN paie_cahier_texte_validations v ON ct.cahier_id = v.cahier_id
            LEFT JOIN utilisateurs v_u ON v.valide_par = v_u.id_user
            LEFT JOIN (
                paie_bulletin_heures bh
                JOIN paie_bulletins b ON bh.bulletin_id = b.id AND b.est_version_active = 1
            ) ON v.id = bh.cahier_validation_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['lycee_id'])) {
            $sql .= " AND (ct.lycee_id = :lycee_id OR cl.lycee_id = :lycee_id)";
            $params['lycee_id'] = $filters['lycee_id'];
        }
        if (!empty($filters['teacher_id'])) {
            $sql .= " AND ct.personnel_id = :teacher_id";
            $params['teacher_id'] = $filters['teacher_id'];
        }
        if (!empty($filters['cycle_id'])) {
            $sql .= " AND cl.cycle_id = :cycle_id";
            $params['cycle_id'] = $filters['cycle_id'];
        }
        if (!empty($filters['niveau'])) {
            $sql .= " AND cl.niveau = :niveau";
            $params['niveau'] = $filters['niveau'];
        }
        if (!empty($filters['serie'])) {
            $sql .= " AND cl.serie = :serie";
            $params['serie'] = $filters['serie'];
        }
        if (isset($filters['numero']) && $filters['numero'] !== '') {
            $sql .= " AND cl.numero = :numero";
            $params['numero'] = $filters['numero'];
        }
        if (!empty($filters['classe_id'])) {
            $sql .= " AND ct.classe_id = :classe_id";
            $params['classe_id'] = $filters['classe_id'];
        }
        if (!empty($filters['matiere_id'])) {
            $sql .= " AND ct.matiere_id = :matiere_id";
            $params['matiere_id'] = $filters['matiere_id'];
        }
        if (!empty($filters['date_debut'])) {
            $sql .= " AND ct.date_cours >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND ct.date_cours <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }

        $sql .= " ORDER BY ct.date_cours DESC, ct.heure_debut DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$r) {
            $dh = 2.0;
            if (!empty($r['duree_heures'])) {
                $dh = (float)$r['duree_heures'];
            } elseif (!empty($r['heure_debut']) && !empty($r['heure_fin'])) {
                $t1 = strtotime($r['heure_debut']);
                $t2 = strtotime($r['heure_fin']);
                if ($t2 > $t1) $dh = round(($t2 - $t1) / 3600.0, 2);
            }
            $r['calculated_duration'] = $dh;

            if (!empty($r['bulletin_active'])) {
                $r['status_code'] = 'paye';
                $r['status_label'] = _("Payée");
            } elseif (!empty($r['statut_validation']) && $r['statut_validation'] === 'valide') {
                $r['status_code'] = 'valide';
                $r['status_label'] = _("Validée");
            } elseif (!empty($r['statut_validation']) && $r['statut_validation'] === 'refuse') {
                $r['status_code'] = 'refuse';
                $r['status_label'] = _("Refusée");
            } else {
                $r['status_code'] = 'a_valider';
                $r['status_label'] = _("À valider");
            }
        }

        return $results;
    }
}
