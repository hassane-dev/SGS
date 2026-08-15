<?php
// src/services/PersonnelContractService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/PersonnelHistoryService.php';

class PersonnelContractService {

    /**
     * Finds all contract records for a specific personnel.
     */
    public static function getContractsForPersonnel(int $personnel_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT pch.*, tc.libelle AS contrat_libelle, tc.type_paiement, tc.prise_en_charge
            FROM personnel_contrats_historique pch
            JOIN type_contrat tc ON pch.type_contrat_id = tc.id_contrat
            WHERE pch.personnel_id = :personnel_id
            ORDER BY pch.date_debut DESC, pch.id DESC
        ");
        $stmt->execute(['personnel_id' => $personnel_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the active current contract for a personnel.
     */
    public static function getActiveContract(int $personnel_id): ?array {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT pch.*, tc.libelle AS contrat_libelle, tc.type_paiement, tc.prise_en_charge
            FROM personnel_contrats_historique pch
            JOIN type_contrat tc ON pch.type_contrat_id = tc.id_contrat
            WHERE pch.personnel_id = :personnel_id
              AND pch.statut_contrat = 'actif'
              AND pch.date_debut <= :today_start
              AND (pch.date_fin IS NULL OR pch.date_fin >= :today_end)
            ORDER BY pch.date_debut DESC
            LIMIT 1
        ");
        $stmt->execute(['personnel_id' => $personnel_id, 'today_start' => $today, 'today_end' => $today]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Saves a contract record while preserving historical integrity.
     */
    public static function saveContract(array $data, int $author_id): bool {
        if (empty($data['personnel_id']) || empty($data['type_contrat_id']) || empty($data['date_debut'])) {
            throw new InvalidArgumentException(_("Les champs Personnel, Type de contrat et Date de début sont obligatoires."));
        }

        $personnel_id = (int)$data['personnel_id'];
        $type_contrat_id = (int)$data['type_contrat_id'];
        $date_debut = $data['date_debut'];
        $date_fin = !empty($data['date_fin']) ? $data['date_fin'] : null;
        $salaire_base = (float)($data['salaire_base'] ?? 0.00);
        $devise = $data['devise'] ?? 'XAF';
        $volume_horaire = !empty($data['volume_horaire_mensuel']) ? (float)$data['volume_horaire_mensuel'] : null;
        $statut_contrat = $data['statut_contrat'] ?? 'actif';
        $commentaire = $data['commentaire'] ?? null;
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        if ($date_fin && $date_fin < $date_debut) {
            throw new InvalidArgumentException(_("La date de fin ne peut pas être antérieure à la date de début."));
        }

        $db = Database::getInstance();
        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            // If marking a new active contract, terminate previous active contracts if needed
            if ($statut_contrat === 'actif' && !$id) {
                $stmt_term = $db->prepare("
                    UPDATE personnel_contrats_historique
                    SET statut_contrat = 'renouvele', date_fin = COALESCE(date_fin, :date_debut)
                    WHERE personnel_id = :personnel_id AND statut_contrat = 'actif'
                ");
                $stmt_term->execute([
                    'personnel_id' => $personnel_id,
                    'date_debut' => $date_debut
                ]);
            }

            $old_contract = null;
            if ($id) {
                $stmt_old = $db->prepare("SELECT * FROM personnel_contrats_historique WHERE id = :id");
                $stmt_old->execute(['id' => $id]);
                $old_contract = $stmt_old->fetch(PDO::FETCH_ASSOC);

                $sql = "UPDATE personnel_contrats_historique
                        SET type_contrat_id = :type_contrat_id, date_debut = :date_debut, date_fin = :date_fin,
                            salaire_base = :salaire_base, devise = :devise, volume_horaire_mensuel = :volume_horaire_mensuel,
                            statut_contrat = :statut_contrat, commentaire = :commentaire
                        WHERE id = :id AND personnel_id = :personnel_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'type_contrat_id' => $type_contrat_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'salaire_base' => $salaire_base,
                    'devise' => $devise,
                    'volume_horaire_mensuel' => $volume_horaire,
                    'statut_contrat' => $statut_contrat,
                    'commentaire' => $commentaire,
                    'id' => $id,
                    'personnel_id' => $personnel_id
                ]);
            } else {
                $sql = "INSERT INTO personnel_contrats_historique (
                            personnel_id, type_contrat_id, date_debut, date_fin, salaire_base, devise,
                            volume_horaire_mensuel, statut_contrat, commentaire, cree_par
                        ) VALUES (
                            :personnel_id, :type_contrat_id, :date_debut, :date_fin, :salaire_base, :devise,
                            :volume_horaire_mensuel, :statut_contrat, :commentaire, :cree_par
                        )";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'personnel_id' => $personnel_id,
                    'type_contrat_id' => $type_contrat_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'salaire_base' => $salaire_base,
                    'devise' => $devise,
                    'volume_horaire_mensuel' => $volume_horaire,
                    'statut_contrat' => $statut_contrat,
                    'commentaire' => $commentaire,
                    'cree_par' => $author_id
                ]);
            }

            // Sync legacy contrat_id column in utilisateurs for backward compatibility
            $stmt_u = $db->prepare("UPDATE utilisateurs SET contrat_id = :contrat_id WHERE id_user = :id");
            $stmt_u->execute(['contrat_id' => $type_contrat_id, 'id' => $personnel_id]);

            // Audit
            PersonnelHistoryService::logMovement([
                'personnel_id' => $personnel_id,
                'type_mouvement' => $id ? 'changement_contrat' : 'nouveau_contrat',
                'motif' => $commentaire ?? ($id ? "Mise à jour du contrat" : "Création d'un nouveau contrat"),
                'auteur_id' => $author_id,
                'ancien_etat' => $old_contract,
                'nouvel_etat' => [
                    'type_contrat_id' => $type_contrat_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'salaire_base' => $salaire_base,
                    'statut_contrat' => $statut_contrat
                ]
            ]);

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
