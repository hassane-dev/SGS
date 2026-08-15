<?php
// src/services/PersonnelAssignmentService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthorizationScopeService.php';
require_once __DIR__ . '/PersonnelHistoryService.php';

class PersonnelAssignmentService {

    /**
     * Finds all cycle assignments for a personnel with cycle details.
     */
    public static function getAssignmentsForPersonnel(int $personnel_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT pca.*, c.nom_cycle, c.niveau_debut, c.niveau_fin, c.lycee_id
            FROM personnel_cycles_assignments pca
            JOIN cycles c ON pca.cycle_id = c.id_cycle
            WHERE pca.personnel_id = :personnel_id
            ORDER BY pca.actif DESC, pca.date_debut DESC
        ");
        $stmt->execute(['personnel_id' => $personnel_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adds or updates a cycle assignment with strict non-overlapping validation,
     * atomic transaction, history audit, and auth_version cache invalidation.
     */
    public static function saveAssignment(array $data, int $author_id): bool {
        if (empty($data['personnel_id']) || empty($data['cycle_id']) || empty($data['date_debut'])) {
            throw new InvalidArgumentException(_("Les champs Personnel, Cycle et Date de début sont obligatoires."));
        }

        $personnel_id = (int)$data['personnel_id'];
        $cycle_id = (int)$data['cycle_id'];
        $date_debut = $data['date_debut'];
        $date_fin = !empty($data['date_fin']) ? $data['date_fin'] : null;
        $actif = isset($data['actif']) ? (int)$data['actif'] : 1;
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        // Date sanity check
        if ($date_fin && $date_fin < $date_debut) {
            throw new InvalidArgumentException(_("La date de fin ne peut pas être antérieure à la date de début."));
        }

        // 1. Verify non-overlapping active intervals
        if ($actif === 1) {
            AuthorizationScopeService::validateNonOverlapping($personnel_id, $cycle_id, $date_debut, $date_fin, $id);
        }

        $db = Database::getInstance();
        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            $old_assignment = null;
            if ($id) {
                $stmt_old = $db->prepare("SELECT * FROM personnel_cycles_assignments WHERE id = :id");
                $stmt_old->execute(['id' => $id]);
                $old_assignment = $stmt_old->fetch(PDO::FETCH_ASSOC);

                $sql = "UPDATE personnel_cycles_assignments
                        SET cycle_id = :cycle_id, date_debut = :date_debut, date_fin = :date_fin, actif = :actif
                        WHERE id = :id AND personnel_id = :personnel_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'cycle_id' => $cycle_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'actif' => $actif,
                    'id' => $id,
                    'personnel_id' => $personnel_id
                ]);
            } else {
                $sql = "INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
                        VALUES (:personnel_id, :cycle_id, :date_debut, :date_fin, :actif)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'personnel_id' => $personnel_id,
                    'cycle_id' => $cycle_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'actif' => $actif
                ]);
            }

            // 2. Log HR movement
            PersonnelHistoryService::logMovement([
                'personnel_id' => $personnel_id,
                'type_mouvement' => $id ? 'modification_affectation' : 'nouvelle_affectation',
                'motif' => $data['motif'] ?? ($id ? "Modification d'affectation de cycle" : "Nouvelle affectation de cycle"),
                'auteur_id' => $author_id,
                'ancien_etat' => $old_assignment,
                'nouvel_etat' => [
                    'cycle_id' => $cycle_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'actif' => $actif
                ],
                'cycle_id' => $cycle_id
            ]);

            // 3. Invalidate auth_version cache for the target user so their session picks up updated cycle scopes immediately
            AuthorizationScopeService::invalidateUserCache($personnel_id);

            if (!$inTx) {
                $db->commit();
            }
            return true;
        } catch (Exception $e) {
            if (!$inTx && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Toggles or revokes an assignment status and invalidates scope cache.
     */
    public static function deleteAssignment(int $id, int $author_id): bool {
        $db = Database::getInstance();
        $stmt_old = $db->prepare("SELECT * FROM personnel_cycles_assignments WHERE id = :id");
        $stmt_old->execute(['id' => $id]);
        $assignment = $stmt_old->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            return false;
        }

        $personnel_id = (int)$assignment['personnel_id'];

        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            $stmt = $db->prepare("DELETE FROM personnel_cycles_assignments WHERE id = :id");
            $stmt->execute(['id' => $id]);

            PersonnelHistoryService::logMovement([
                'personnel_id' => $personnel_id,
                'type_mouvement' => 'retrait_affectation',
                'motif' => "Retrait d'affectation de cycle",
                'auteur_id' => $author_id,
                'ancien_etat' => $assignment,
                'cycle_id' => $assignment['cycle_id']
            ]);

            AuthorizationScopeService::invalidateUserCache($personnel_id);

            if (!$inTx) {
                $db->commit();
            }
            return true;
        } catch (Exception $e) {
            if (!$inTx && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
