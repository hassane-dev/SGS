<?php

require_once __DIR__ . '/../config/database.php';

class DisciplineConseilMembre {

    public static function addMembre(array $data): int {
        $db = Database::getInstance();

        // 1. Verify council exists and is editable
        $stmtC = $db->prepare("SELECT statut, lycee_id FROM discipline_conseils WHERE id = :conseil_id");
        $stmtC->execute([':conseil_id' => $data['conseil_id']]);
        $conseil = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$conseil) {
            throw new InvalidArgumentException("Conseil de discipline introuvable.");
        }

        if (in_array($conseil['statut'], ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Impossible d'ajouter un membre à un conseil clôturé ou annulé.");
        }

        // 2. Verify target user exists and belongs to the same tenant
        $stmtU = $db->prepare("SELECT id_user, nom, prenom, lycee_id, fonction FROM utilisateurs WHERE id_user = :user_id");
        $stmtU->execute([':user_id' => $data['user_id']]);
        $user = $stmtU->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        if ((int)$user['lycee_id'] !== (int)$conseil['lycee_id']) {
            throw new InvalidArgumentException("L'utilisateur doit appartenir au même établissement que le conseil.");
        }

        // 3. Prevent duplicate member in same council
        $stmtDup = $db->prepare("SELECT COUNT(*) FROM discipline_conseil_membres WHERE conseil_id = :conseil_id AND user_id = :user_id");
        $stmtDup->execute([
            ':conseil_id' => $data['conseil_id'],
            ':user_id' => $data['user_id']
        ]);
        if ((int)$stmtDup->fetchColumn() > 0) {
            throw new InvalidArgumentException("Cet utilisateur est déjà membre de ce conseil.");
        }

        // 4. Create Member with immutable snapshots
        $nomSnapshot = trim($user['prenom'] . ' ' . $user['nom']);
        $fonctionSnapshot = $user['fonction'] ?? 'Membre';

        $stmt = $db->prepare("
            INSERT INTO discipline_conseil_membres (
                conseil_id, user_id, qualite_membre, a_droit_vote, est_present, nom_snapshot, fonction_snapshot
            ) VALUES (
                :conseil_id, :user_id, :qualite_membre, :a_droit_vote, :est_present, :nom_snapshot, :fonction_snapshot
            )
        ");

        $stmt->execute([
            ':conseil_id' => $data['conseil_id'],
            ':user_id' => $data['user_id'],
            ':qualite_membre' => $data['qualite_membre'] ?? 'membre_permanent',
            ':a_droit_vote' => isset($data['a_droit_vote']) ? (int)$data['a_droit_vote'] : 1,
            ':est_present' => isset($data['est_present']) ? (int)$data['est_present'] : 0,
            ':nom_snapshot' => $nomSnapshot,
            ':fonction_snapshot' => $fonctionSnapshot
        ]);

        return (int)$db->lastInsertId();
    }

    public static function removeMembre(int $conseilId, int $userId): bool {
        $db = Database::getInstance();

        $stmtC = $db->prepare("SELECT statut FROM discipline_conseils WHERE id = :id");
        $stmtC->execute([':id' => $conseilId]);
        $statut = $stmtC->fetchColumn();

        if (in_array($statut, ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Impossible de retirer un membre d'un conseil clôturé ou annulé.");
        }

        $stmt = $db->prepare("DELETE FROM discipline_conseil_membres WHERE conseil_id = :conseil_id AND user_id = :user_id");
        return $stmt->execute([
            ':conseil_id' => $conseilId,
            ':user_id' => $userId
        ]);
    }

    public static function updatePresence(int $conseilId, int $userId, int $estPresent): bool {
        $db = Database::getInstance();

        $stmtC = $db->prepare("SELECT statut FROM discipline_conseils WHERE id = :id");
        $stmtC->execute([':id' => $conseilId]);
        $statut = $stmtC->fetchColumn();

        if (!$statut) {
            throw new InvalidArgumentException("Conseil de discipline introuvable.");
        }

        if (in_array($statut, ['cloture', 'annule'], true)) {
            throw new InvalidArgumentException("Impossible de modifier la présence d'un membre pour un conseil clôturé ou annulé.");
        }

        $stmtM = $db->prepare("SELECT COUNT(*) FROM discipline_conseil_membres WHERE conseil_id = :conseil_id AND user_id = :user_id");
        $stmtM->execute([
            ':conseil_id' => $conseilId,
            ':user_id' => $userId
        ]);
        if ((int)$stmtM->fetchColumn() === 0) {
            throw new InvalidArgumentException("L'utilisateur spécifié n'est pas membre de ce conseil.");
        }

        $stmt = $db->prepare("
            UPDATE discipline_conseil_membres
            SET est_present = :est_present
            WHERE conseil_id = :conseil_id AND user_id = :user_id
        ");
        return $stmt->execute([
            ':est_present' => $estPresent ? 1 : 0,
            ':conseil_id' => $conseilId,
            ':user_id' => $userId
        ]);
    }

    public static function findByConseilId(int $conseilId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*
            FROM discipline_conseil_membres m
            WHERE m.conseil_id = :conseil_id
            ORDER BY m.id ASC
        ");
        $stmt->execute([':conseil_id' => $conseilId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
