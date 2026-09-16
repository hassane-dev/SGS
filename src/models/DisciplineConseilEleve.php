<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/DisciplineSanction.php';
require_once __DIR__ . '/../models/DisciplineHistorique.php';

class DisciplineConseilEleve {

    public static function recordDecision(int $conseilId, int $eleveId, array $data): bool {
        $db = Database::getInstance();

        // 1. Fetch Council & verify status is 'delibere'
        $stmtC = $db->prepare("SELECT * FROM discipline_conseils WHERE id = :id");
        $stmtC->execute([':id' => $conseilId]);
        $conseil = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$conseil) {
            throw new InvalidArgumentException("Conseil de discipline introuvable.");
        }

        if ($conseil['statut'] !== 'delibere') {
            throw new InvalidArgumentException("Les décisions ne peuvent être enregistrées que lorsque le conseil est en statut 'En délibération'.");
        }

        // 2. Verify student convocation in council
        $stmtCE = $db->prepare("SELECT * FROM discipline_conseil_eleves WHERE conseil_id = :conseil_id AND eleve_id = :eleve_id");
        $stmtCE->execute([':conseil_id' => $conseilId, ':eleve_id' => $eleveId]);
        $convocation = $stmtCE->fetch(PDO::FETCH_ASSOC);

        if (!$convocation) {
            throw new InvalidArgumentException("L'élève spécifié n'est pas convoqué à ce conseil.");
        }

        // 3. Extract & validate decision parameters
        $decisionStatut = trim($data['decision_statut'] ?? '');
        $allowedStatuts = ['en_attente', 'relaxe', 'averti', 'reoriente', 'sanctionne'];
        if (!in_array($decisionStatut, $allowedStatuts, true)) {
            throw new InvalidArgumentException("Statut de décision invalide.");
        }

        $votesPour = isset($data['votes_pour']) ? (int)$data['votes_pour'] : 0;
        $votesContre = isset($data['votes_contre']) ? (int)$data['votes_contre'] : 0;
        $abstentions = isset($data['abstentions']) ? (int)$data['abstentions'] : 0;
        $motivation = isset($data['motivation_decision']) ? trim($data['motivation_decision']) : '';

        // Voting validation
        $totalVotes = $votesPour + $votesContre + $abstentions;
        if ($decisionStatut !== 'en_attente' && $totalVotes <= 0) {
            throw new InvalidArgumentException("Un vote préalable des membres est obligatoire avant de valider une décision.");
        }

        // Majority rule for 'sanctionne': votes_pour > votes_contre
        if ($decisionStatut === 'sanctionne' && $votesPour <= $votesContre) {
            throw new InvalidArgumentException("Une décision de sanction exige la majorité simple des votes pour (pour > contre).");
        }

        // Motivation is mandatory except when 'en_attente'
        if ($decisionStatut !== 'en_attente' && empty($motivation)) {
            throw new InvalidArgumentException("La motivation de la décision est obligatoire.");
        }

        // 4. Begin SQL Transaction for atomicity
        $db->beginTransaction();

        try {
            $sanctionId = $convocation['sanction_id'] ? (int)$convocation['sanction_id'] : null;

            if ($decisionStatut === 'sanctionne') {
                $typeSanctionId = isset($data['type_sanction_id']) ? (int)$data['type_sanction_id'] : 0;
                if ($typeSanctionId <= 0) {
                    throw new InvalidArgumentException("Le type de sanction est obligatoire pour une décision 'sanctionné'.");
                }

                // Verify type_sanction belongs to same lycee_id & is active
                $typeSanction = DisciplineTypeSanction::findById($typeSanctionId, $conseil['lycee_id']);
                if (!$typeSanction || (int)$typeSanction['actif'] !== 1) {
                    throw new InvalidArgumentException("Type de sanction introuvable, inactif ou invalide.");
                }

                // IDEMPOTENCY: create sanction only if sanction_id not already set
                if (!$sanctionId) {
                    // Check if an associated incident exists for this council & student
                    $stmtInc = $db->prepare("
                        SELECT incident_id
                        FROM discipline_conseil_incidents
                        WHERE conseil_id = :conseil_id AND eleve_id = :eleve_id
                        LIMIT 1
                    ");
                    $stmtInc->execute([':conseil_id' => $conseilId, ':eleve_id' => $eleveId]);
                    $incidentId = $stmtInc->fetchColumn() ?: null;

                    $savedUserId = Auth::getUserId();
                    $savedLyceeId = Auth::getLyceeId();

                    // Ensure Auth has valid context during creation
                    Auth::setSessionContext([
                        'id_user' => $conseil['president_user_id'],
                        'lycee_id' => $conseil['lycee_id'],
                    ]);

                    try {
                        $sanctionData = [
                            'eleve_id' => $eleveId,
                            'classe_id' => (int)$convocation['classe_id'],
                            'type_sanction_id' => $typeSanctionId,
                            'motif' => $motivation,
                            'date_decision' => $conseil['date_conseil'],
                            'annee_academique_id' => $conseil['annee_academique_id'],
                            'incident_id' => $incidentId,
                            'duree_jours' => $data['duree_jours'] ?? null,
                            'duree_heures' => $data['duree_heures'] ?? null,
                            'date_debut_execution' => $data['date_debut_execution'] ?? null,
                            'date_fin_execution' => $data['date_fin_execution'] ?? null,
                            'details' => $data['details'] ?? null,
                        ];

                        $sanctionId = DisciplineSanction::create($sanctionData);
                    } finally {
                        if ($savedUserId && $savedLyceeId) {
                            Auth::setSessionContext([
                                'id_user' => $savedUserId,
                                'lycee_id' => $savedLyceeId,
                            ]);
                        }
                    }

                    // Explicit trace for sanction creation from council
                    DisciplineHistorique::log([
                        'lycee_id' => $conseil['lycee_id'],
                        'user_id' => Auth::getUserId() ?: $conseil['president_user_id'],
                        'annee_academique_id' => $conseil['annee_academique_id'],
                        'sanction_id' => $sanctionId,
                        'eleve_id' => $eleveId,
                        'action' => 'SANCTION_ISSUE_CONSEIL',
                        'statut_apres' => 'prononcee',
                        'description' => "Sanction #{$sanctionId} prononcée suite au Conseil de discipline {$conseil['code']}."
                    ]);
                }
            }

            // Update convocation record in discipline_conseil_eleves
            $stmtUpd = $db->prepare("
                UPDATE discipline_conseil_eleves
                SET
                    decision_statut = :decision_statut,
                    motivation_decision = :motivation,
                    votes_pour = :votes_pour,
                    votes_contre = :votes_contre,
                    abstentions = :abstentions,
                    sanction_id = :sanction_id
                WHERE conseil_id = :conseil_id AND eleve_id = :eleve_id
            ");

            $stmtUpd->execute([
                ':decision_statut' => $decisionStatut,
                ':motivation' => $motivation,
                ':votes_pour' => $votesPour,
                ':votes_contre' => $votesContre,
                ':abstentions' => $abstentions,
                ':sanction_id' => $sanctionId,
                ':conseil_id' => $conseilId,
                ':eleve_id' => $eleveId,
            ]);

            // Log decision in discipline_historique
            DisciplineHistorique::log([
                'lycee_id' => $conseil['lycee_id'],
                'user_id' => Auth::getUserId() ?: $conseil['president_user_id'],
                'annee_academique_id' => $conseil['annee_academique_id'],
                'eleve_id' => $eleveId,
                'action' => 'DECISION_CONSEIL',
                'statut_avant' => $convocation['decision_statut'],
                'statut_apres' => $decisionStatut,
                'description' => "Décision '{$decisionStatut}' enregistrée pour l'élève ID {$eleveId} lors du Conseil {$conseil['code']} (Pour: {$votesPour}, Contre: {$votesContre}, Abs: {$abstentions})."
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function addEleve(array $data): int {
        $db = Database::getInstance();

        // 1. Verify council exists and is editable
        $stmtC = $db->prepare("SELECT lycee_id, annee_academique_id, statut FROM discipline_conseils WHERE id = :conseil_id");
        $stmtC->execute([':conseil_id' => $data['conseil_id']]);
        $conseil = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$conseil) {
            throw new InvalidArgumentException("Conseil de discipline introuvable.");
        }

        if (in_array($conseil['statut'], ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Impossible d'ajouter un élève à un conseil clôturé ou annulé.");
        }

        // 2. Verify student exists and belongs to the same tenant
        $stmtE = $db->prepare("SELECT id_eleve, lycee_id FROM eleves WHERE id_eleve = :eleve_id");
        $stmtE->execute([':eleve_id' => $data['eleve_id']]);
        $eleve = $stmtE->fetch(PDO::FETCH_ASSOC);

        if (!$eleve) {
            throw new InvalidArgumentException("Élève introuvable.");
        }

        if ((int)$eleve['lycee_id'] !== (int)$conseil['lycee_id']) {
            throw new InvalidArgumentException("L'élève doit appartenir au même établissement que le conseil.");
        }

        // 3. Resolve student active class snapshot server-side (ignore client-supplied classe_id)
        $stmtEt = $db->prepare("
            SELECT classe_id
            FROM etudes
            WHERE eleve_id = :eleve_id
              AND annee_academique_id = :annee_id
        ");
        $stmtEt->execute([
            ':eleve_id' => $data['eleve_id'],
            ':annee_id' => $conseil['annee_academique_id']
        ]);
        $classeSnapshotId = $stmtEt->fetchColumn();

        if (!$classeSnapshotId) {
            throw new InvalidArgumentException("L'élève n'a aucune inscription active pour l'année académique de ce conseil.");
        }

        // 4. Prevent duplicate student in same council
        $stmtDup = $db->prepare("SELECT COUNT(*) FROM discipline_conseil_eleves WHERE conseil_id = :conseil_id AND eleve_id = :eleve_id");
        $stmtDup->execute([
            ':conseil_id' => $data['conseil_id'],
            ':eleve_id' => $data['eleve_id']
        ]);
        if ((int)$stmtDup->fetchColumn() > 0) {
            throw new InvalidArgumentException("Cet élève est déjà convoqué à ce conseil de discipline.");
        }

        // 5. Insert student convocation with server-resolved class snapshot
        $stmt = $db->prepare("
            INSERT INTO discipline_conseil_eleves (
                conseil_id, eleve_id, classe_id, motif_convocation,
                presence_eleve, presence_representant_legal, nom_representant_legal, decision_statut
            ) VALUES (
                :conseil_id, :eleve_id, :classe_id, :motif_convocation,
                :presence_eleve, :presence_representant_legal, :nom_representant_legal, 'en_attente'
            )
        ");

        $stmt->execute([
            ':conseil_id' => $data['conseil_id'],
            ':eleve_id' => $data['eleve_id'],
            ':classe_id' => (int)$classeSnapshotId,
            ':motif_convocation' => $data['motif_convocation'],
            ':presence_eleve' => isset($data['presence_eleve']) ? (int)$data['presence_eleve'] : 0,
            ':presence_representant_legal' => isset($data['presence_representant_legal']) ? (int)$data['presence_representant_legal'] : 0,
            ':nom_representant_legal' => $data['nom_representant_legal'] ?? null,
        ]);

        return (int)$db->lastInsertId();
    }

    public static function linkIncident(int $conseilId, int $incidentId, int $eleveId): bool {
        $db = Database::getInstance();

        // 1. Verify council exists and is editable
        $stmtC = $db->prepare("SELECT lycee_id, statut FROM discipline_conseils WHERE id = :conseil_id");
        $stmtC->execute([':conseil_id' => $conseilId]);
        $conseil = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$conseil || in_array($conseil['statut'], ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Conseil de discipline introuvable ou clôturé.");
        }

        // 2. Verify student is actually convoked in this council
        $stmtCE = $db->prepare("SELECT COUNT(*) FROM discipline_conseil_eleves WHERE conseil_id = :conseil_id AND eleve_id = :eleve_id");
        $stmtCE->execute([':conseil_id' => $conseilId, ':eleve_id' => $eleveId]);
        if ((int)$stmtCE->fetchColumn() === 0) {
            throw new InvalidArgumentException("L'élève doit être préalablement convoqué au conseil avant de lui associer un incident.");
        }

        // 3. Strict verification: Student MUST be actually implicated in this incident via discipline_incident_eleves
        $stmtCheckImp = $db->prepare("
            SELECT COUNT(*)
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            WHERE ie.incident_id = :incident_id
              AND ie.eleve_id = :eleve_id
              AND i.lycee_id = :lycee_id
        ");
        $stmtCheckImp->execute([
            ':incident_id' => $incidentId,
            ':eleve_id' => $eleveId,
            ':lycee_id' => $conseil['lycee_id']
        ]);

        if ((int)$stmtCheckImp->fetchColumn() === 0) {
            throw new InvalidArgumentException("L'élève n'est pas impliqué dans cet incident pour cet établissement.");
        }

        // 4. Prevent duplicate incident link
        $stmtDup = $db->prepare("
            SELECT COUNT(*)
            FROM discipline_conseil_incidents
            WHERE conseil_id = :conseil_id AND incident_id = :incident_id AND eleve_id = :eleve_id
        ");
        $stmtDup->execute([
            ':conseil_id' => $conseilId,
            ':incident_id' => $incidentId,
            ':eleve_id' => $eleveId
        ]);
        if ((int)$stmtDup->fetchColumn() > 0) {
            return true; // Already linked
        }

        $stmt = $db->prepare("
            INSERT INTO discipline_conseil_incidents (conseil_id, incident_id, eleve_id)
            VALUES (:conseil_id, :incident_id, :eleve_id)
        ");

        return $stmt->execute([
            ':conseil_id' => $conseilId,
            ':incident_id' => $incidentId,
            ':eleve_id' => $eleveId
        ]);
    }

    public static function updateConvocation(int $conseilId, int $eleveId, array $data): bool {
        $db = Database::getInstance();

        $stmtC = $db->prepare("SELECT statut FROM discipline_conseils WHERE id = :id");
        $stmtC->execute([':id' => $conseilId]);
        $statut = $stmtC->fetchColumn();

        if (!$statut) {
            throw new InvalidArgumentException("Conseil de discipline introuvable.");
        }

        if (in_array($statut, ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Impossible de modifier les informations de convocation pour un conseil clôturé ou annulé.");
        }

        $stmtE = $db->prepare("SELECT COUNT(*) FROM discipline_conseil_eleves WHERE conseil_id = :conseil_id AND eleve_id = :eleve_id");
        $stmtE->execute([':conseil_id' => $conseilId, ':eleve_id' => $eleveId]);
        if ((int)$stmtE->fetchColumn() === 0) {
            throw new InvalidArgumentException("L'élève n'est pas convoqué à ce conseil.");
        }

        $stmt = $db->prepare("
            UPDATE discipline_conseil_eleves
            SET
                motif_convocation = :motif_convocation,
                presence_eleve = :presence_eleve,
                presence_representant_legal = :presence_representant_legal,
                nom_representant_legal = :nom_representant_legal
            WHERE conseil_id = :conseil_id AND eleve_id = :eleve_id
        ");

        return $stmt->execute([
            ':motif_convocation' => isset($data['motif_convocation']) ? trim($data['motif_convocation']) : null,
            ':presence_eleve' => $data['presence_eleve'] ?? 'non_specifie',
            ':presence_representant_legal' => $data['presence_representant_legal'] ?? 'non_specifie',
            ':nom_representant_legal' => !empty($data['nom_representant_legal']) ? trim($data['nom_representant_legal']) : null,
            ':conseil_id' => $conseilId,
            ':eleve_id' => $eleveId,
        ]);
    }

    public static function unlinkIncident(int $conseilId, int $incidentId, int $eleveId): bool {
        $db = Database::getInstance();

        $stmtC = $db->prepare("SELECT statut FROM discipline_conseils WHERE id = :id");
        $stmtC->execute([':id' => $conseilId]);
        $statut = $stmtC->fetchColumn();

        if (!$statut) {
            throw new InvalidArgumentException("Conseil de discipline introuvable.");
        }

        if (in_array($statut, ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Impossible de retirer un incident d'un conseil clôturé ou annulé.");
        }

        $stmtExist = $db->prepare("SELECT COUNT(*) FROM discipline_conseil_incidents WHERE conseil_id = :conseil_id AND incident_id = :incident_id AND eleve_id = :eleve_id");
        $stmtExist->execute([
            ':conseil_id' => $conseilId,
            ':incident_id' => $incidentId,
            ':eleve_id' => $eleveId,
        ]);

        if ((int)$stmtExist->fetchColumn() === 0) {
            throw new InvalidArgumentException("L'association entre cet incident et cet élève dans ce conseil n'existe pas.");
        }

        $stmt = $db->prepare("
            DELETE FROM discipline_conseil_incidents
            WHERE conseil_id = :conseil_id AND incident_id = :incident_id AND eleve_id = :eleve_id
        ");

        return $stmt->execute([
            ':conseil_id' => $conseilId,
            ':incident_id' => $incidentId,
            ':eleve_id' => $eleveId,
        ]);
    }

    public static function findByConseilId(int $conseilId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT
                ce.*,
                CONCAT(e.prenom, ' ', e.nom) AS eleve_nom_complet,
                e.identifiant_public AS eleve_matricule,
                TRIM(CONCAT(COALESCE(c.niveau, ''), ' ', COALESCE(c.serie, ''), ' ', COALESCE(c.numero, ''))) AS nom_classe_snapshot
            FROM discipline_conseil_eleves ce
            LEFT JOIN eleves e ON e.id_eleve = ce.eleve_id
            LEFT JOIN classes c ON c.id_classe = ce.classe_id
            WHERE ce.conseil_id = :conseil_id
            ORDER BY ce.id ASC
        ");
        $stmt->execute([':conseil_id' => $conseilId]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach linked incidents
        foreach ($eleves as &$el) {
            $stmtInc = $db->prepare("
                SELECT
                    ci.incident_id,
                    ti.code AS incident_code,
                    ti.libelle AS type_incident_libelle,
                    i.date_incident,
                    i.description_faits
                FROM discipline_conseil_incidents ci
                JOIN discipline_incidents i ON i.id = ci.incident_id
                LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
                WHERE ci.conseil_id = :conseil_id AND ci.eleve_id = :eleve_id
            ");
            $stmtInc->execute([':conseil_id' => $conseilId, ':eleve_id' => $el['eleve_id']]);
            $el['incidents'] = $stmtInc->fetchAll(PDO::FETCH_ASSOC);
        }

        return $eleves;
    }
}
