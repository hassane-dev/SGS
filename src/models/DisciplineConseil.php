<?php

require_once __DIR__ . '/../config/database.php';

class DisciplineConseil {

    public static function create(array $data): int {
        $db = Database::getInstance();

        // Ensure unique code per tenant
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM discipline_conseils WHERE lycee_id = :lycee_id AND code = :code");
        $stmtCheck->execute([
            ':lycee_id' => $data['lycee_id'],
            ':code' => $data['code']
        ]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            throw new InvalidArgumentException("Un conseil avec le code '" . $data['code'] . "' existe déjà pour cet établissement.");
        }

        $stmt = $db->prepare("
            INSERT INTO discipline_conseils (
                lycee_id, annee_academique_id, code, titre, date_conseil, heure_debut, heure_fin,
                lieu, president_user_id, secretaire_user_id, statut, observations_generales
            ) VALUES (
                :lycee_id, :annee_academique_id, :code, :titre, :date_conseil, :heure_debut, :heure_fin,
                :lieu, :president_user_id, :secretaire_user_id, :statut, :observations_generales
            )
        ");

        $stmt->execute([
            ':lycee_id' => $data['lycee_id'],
            ':annee_academique_id' => $data['annee_academique_id'],
            ':code' => $data['code'],
            ':titre' => $data['titre'],
            ':date_conseil' => $data['date_conseil'],
            ':heure_debut' => $data['heure_debut'] ?? null,
            ':heure_fin' => $data['heure_fin'] ?? null,
            ':lieu' => $data['lieu'] ?? null,
            ':president_user_id' => $data['president_user_id'],
            ':secretaire_user_id' => $data['secretaire_user_id'] ?? null,
            ':statut' => $data['statut'] ?? 'planifie',
            ':observations_generales' => $data['observations_generales'] ?? null,
        ]);

        return (int)$db->lastInsertId();
    }

    public static function findById(int $id, ?int $lycee_id = null): ?array {
        $db = Database::getInstance();
        $sql = "
            SELECT
                c.*,
                CONCAT(u_pres.prenom, ' ', u_pres.nom) AS president_nom,
                CONCAT(u_sec.prenom, ' ', u_sec.nom) AS secretaire_nom,
                a.libelle AS annee_libelle
            FROM discipline_conseils c
            LEFT JOIN utilisateurs u_pres ON u_pres.id_user = c.president_user_id
            LEFT JOIN utilisateurs u_sec ON u_sec.id_user = c.secretaire_user_id
            LEFT JOIN annees_academiques a ON a.id = c.annee_academique_id
            WHERE c.id = :id
        ";
        $params = [':id' => $id];

        if ($lycee_id !== null) {
            $sql .= " AND c.lycee_id = :lycee_id ";
            $params[':lycee_id'] = $lycee_id;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findAll(int $lycee_id, array $filters = []): array {
        $db = Database::getInstance();
        $where = ["c.lycee_id = :lycee_id"];
        $params = [':lycee_id' => $lycee_id];

        if (!empty($filters['annee_academique_id'])) {
            $where[] = "c.annee_academique_id = :annee_id";
            $params[':annee_id'] = (int)$filters['annee_academique_id'];
        }

        if (!empty($filters['statut'])) {
            $where[] = "c.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        if (!empty($filters['date_debut'])) {
            $where[] = "c.date_conseil >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $where[] = "c.date_conseil <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['scoped_class_ids'])) {
            $inClause = implode(',', array_map('intval', $filters['scoped_class_ids']));
            $where[] = "c.id IN (SELECT conseil_id FROM discipline_conseil_eleves WHERE classe_id IN ({$inClause}))";
        }

        $sqlWhere = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT
                c.*,
                CONCAT(u_pres.prenom, ' ', u_pres.nom) AS president_nom,
                (SELECT COUNT(*) FROM discipline_conseil_eleves WHERE conseil_id = c.id) AS total_eleves,
                (SELECT COUNT(*) FROM discipline_conseil_membres WHERE conseil_id = c.id) AS total_membres
            FROM discipline_conseils c
            LEFT JOIN utilisateurs u_pres ON u_pres.id_user = c.president_user_id
            WHERE {$sqlWhere}
            ORDER BY c.date_conseil DESC, c.created_at DESC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateStatus(int $id, string $newStatut, int $userId): bool {
        $db = Database::getInstance();
        $conseil = self::findById($id);

        if (!$conseil) {
            throw new InvalidArgumentException("Conseil de discipline introuvable.");
        }

        $currentStatut = $conseil['statut'];

        // Immutability checks
        if (in_array($currentStatut, ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Un conseil de discipline clôturé ou annulé ne peut plus changer de statut.");
        }

        // Allowed transitions for Phase 6.1
        $allowedTransitions = [
            'planifie' => ['convoque', 'annule'],
            'convoque' => ['en_session', 'annule'],
            'en_session' => ['delibere', 'annule'],
            'delibere' => ['cloture', 'annule'],
        ];

        if (!isset($allowedTransitions[$currentStatut]) || !in_array($newStatut, $allowedTransitions[$currentStatut], true)) {
            throw new InvalidArgumentException("Transition de statut non autorisée de '{$currentStatut}' vers '{$newStatut}'.");
        }

        $extraSql = "";
        $params = [
            ':new_statut' => $newStatut,
            ':id' => $id,
        ];

        if ($newStatut === 'cloture') {
            // Server-side check of all convoked students
            $stmtPending = $db->prepare("
                SELECT COUNT(*)
                FROM discipline_conseil_eleves
                WHERE conseil_id = :conseil_id
                  AND (decision_statut = 'en_attente' OR decision_statut NOT IN ('relaxe', 'averti', 'reoriente', 'sanctionne'))
            ");
            $stmtPending->execute([':conseil_id' => $id]);
            if ((int)$stmtPending->fetchColumn() > 0) {
                throw new InvalidArgumentException("Impossible de clôturer le conseil : tous les élèves convoqués doivent avoir une décision enregistrée (aucune décision en attente).");
            }

            $extraSql = ", cloture_par_user_id = :cloture_by, date_cloture = CURRENT_TIMESTAMP ";
            $params[':cloture_by'] = $userId;
        }

        $stmt = $db->prepare("
            UPDATE discipline_conseils
            SET statut = :new_statut {$extraSql}
            WHERE id = :id
        ");

        return $stmt->execute($params);
    }
}
