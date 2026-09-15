<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class DisciplineConseilEleve {

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
