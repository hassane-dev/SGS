<?php
// src/services/PersonnelService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthorizationScopeService.php';
require_once __DIR__ . '/PersonnelAssignmentService.php';
require_once __DIR__ . '/PersonnelContractService.php';
require_once __DIR__ . '/PersonnelDocumentService.php';
require_once __DIR__ . '/PersonnelHistoryService.php';

class PersonnelService {

    /**
     * Search personnel directory respecting tenant scope, cycle scope, and search filters.
     */
    public static function searchPersonnel(array $filters = []): array {
        $db = Database::getInstance();

        // 1. Resolve authorized tenant schools
        $authorized_lycees = AuthorizationScopeService::getAuthorizedLyceeIds();
        if (empty($authorized_lycees)) {
            return [];
        }

        // 2. Resolve authorized cycles
        $authorized_cycles = AuthorizationScopeService::getAuthorizedCycleIds();

        $params = [];
        $sql = "SELECT DISTINCT u.id_user, u.nom, u.prenom, u.sexe, u.email, u.telephone, u.fonction,
                        u.identifiant_public, u.actif, u.lycee_id, u.role_id,
                        r.nom_role, pl.nom_lycee,
                        pd.statut_rh, pd.num_cnss, pd.situation_matrimoniale,
                        tc.libelle AS contrat_actuel
                FROM utilisateurs u
                LEFT JOIN roles r ON u.role_id = r.id_role
                LEFT JOIN param_lycee pl ON u.lycee_id = pl.id
                LEFT JOIN personnel_details pd ON u.id_user = pd.personnel_id
                LEFT JOIN type_contrat tc ON u.contrat_id = tc.id_contrat
                LEFT JOIN personnel_cycles_assignments pca ON u.id_user = pca.personnel_id AND pca.actif = 1";

        $where = ["u.lycee_id IN (" . implode(',', array_map('intval', $authorized_lycees)) . ")"];

        // Cycle filter
        $isGlobalAdmin = Auth::can('view_all_lycees', 'lycee') || Auth::can('view_all_cycles', 'cycle');

        if (!empty($filters['cycle_id'])) {
            $cycle_id = (int)$filters['cycle_id'];
            if (!$isGlobalAdmin && !in_array($cycle_id, $authorized_cycles)) {
                return []; // Requested cycle outside scope
            }
            $where[] = "pca.cycle_id = :filter_cycle_id";
            $params['filter_cycle_id'] = $cycle_id;
        } else {
            if (!$isGlobalAdmin) {
                if (empty($authorized_cycles)) {
                    return []; // Deny by default if user has no authorized cycles
                }
                $where[] = "(pca.cycle_id IN (" . implode(',', array_map('intval', $authorized_cycles)) . ") OR pca.cycle_id IS NULL)";
            }
        }

        // Search term (name, matricule, email, phone)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $where[] = "(u.nom LIKE :s_nom OR u.prenom LIKE :s_prenom OR u.identifiant_public LIKE :s_identifiant OR u.email LIKE :s_email OR u.telephone LIKE :s_telephone)";
            $params['s_nom'] = $searchTerm;
            $params['s_prenom'] = $searchTerm;
            $params['s_identifiant'] = $searchTerm;
            $params['s_email'] = $searchTerm;
            $params['s_telephone'] = $searchTerm;
        }

        // HR Status filter
        if (!empty($filters['statut_rh'])) {
            $where[] = "COALESCE(pd.statut_rh, 'en_activite') = :statut_rh";
            $params['statut_rh'] = $filters['statut_rh'];
        }

        // Contract type filter
        if (!empty($filters['contrat_id'])) {
            $where[] = "u.contrat_id = :contrat_id";
            $params['contrat_id'] = (int)$filters['contrat_id'];
        }

        // Role filter
        if (!empty($filters['role_id'])) {
            $where[] = "u.role_id = :role_id";
            $params['role_id'] = (int)$filters['role_id'];
        }

        $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY u.nom ASC, u.prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get complete 360° details for a personnel record with strict IDOR assertion.
     */
    public static function get360Details(int $personnel_id, bool $can_view_sensitive = false): ?array {
        $db = Database::getInstance();

        // Fetch primary user info
        $stmt = $db->prepare("
            SELECT u.*, r.nom_role, pl.nom_lycee,
                   pd.num_cnss, pd.situation_matrimoniale, pd.nombre_enfants,
                   COALESCE(pd.statut_rh, 'en_activite') AS statut_rh,
                   pd.date_sortie, pd.motif_sortie, pd.remarques,
                   tc.libelle AS contrat_libelle
            FROM utilisateurs u
            LEFT JOIN roles r ON u.role_id = r.id_role
            LEFT JOIN param_lycee pl ON u.lycee_id = pl.id
            LEFT JOIN personnel_details pd ON u.id_user = pd.personnel_id
            LEFT JOIN type_contrat tc ON u.contrat_id = tc.id_contrat
            WHERE u.id_user = :id
        ");
        $stmt->execute(['id' => $personnel_id]);
        $personnel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$personnel) {
            return null;
        }

        // Assert Tenant & Cycle Scope Access
        AuthorizationScopeService::assertAccessToObject($personnel['lycee_id']);

        // Fetch relations
        $assignments = PersonnelAssignmentService::getAssignmentsForPersonnel($personnel_id);
        $contracts = PersonnelContractService::getContractsForPersonnel($personnel_id);
        $documents = PersonnelDocumentService::getDocumentsForPersonnel($personnel_id, $can_view_sensitive);
        $history = PersonnelHistoryService::getHistoryForPersonnel($personnel_id);
        $activeContract = PersonnelContractService::getActiveContract($personnel_id);

        // Mask sensitive data if caller does not have sensitive permission
        if (!$can_view_sensitive) {
            unset($personnel['num_cnss']);
            foreach ($contracts as &$c) {
                unset($c['salaire_base']);
            }
            if ($activeContract) {
                unset($activeContract['salaire_base']);
            }
        }

        return [
            'personnel' => $personnel,
            'assignments' => $assignments,
            'contracts' => $contracts,
            'active_contract' => $activeContract,
            'documents' => $documents,
            'history' => $history
        ];
    }

    /**
     * Save or update personnel core identity and HR details atomically.
     */
    public static function savePersonnel(array $data, int $author_id): int {
        $isUpdate = !empty($data['id_user']);

        if (empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['role_id'])) {
            throw new InvalidArgumentException(_("Les champs Nom, Prénom, Email et Rôle applicatif sont obligatoires."));
        }

        $db = Database::getInstance();
        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            // Save core user identity via User::save
            User::save($data);
            $personnel_id = $isUpdate ? (int)$data['id_user'] : (int)$db->lastInsertId();

            // Save/Update personnel_details
            $statut_rh = $data['statut_rh'] ?? 'en_activite';
            $num_cnss = !empty($data['num_cnss']) ? trim($data['num_cnss']) : null;
            $situation_matrimoniale = $data['situation_matrimoniale'] ?? 'celibataire';
            $nombre_enfants = (int)($data['nombre_enfants'] ?? 0);
            $date_sortie = !empty($data['date_sortie']) ? $data['date_sortie'] : null;
            $motif_sortie = $data['motif_sortie'] ?? null;
            $remarques = $data['remarques'] ?? null;

            $sql_det = "INSERT INTO personnel_details (
                            personnel_id, num_cnss, situation_matrimoniale, nombre_enfants,
                            statut_rh, date_sortie, motif_sortie, remarques
                        ) VALUES (
                            :personnel_id, :num_cnss, :situation_matrimoniale, :nombre_enfants,
                            :statut_rh, :date_sortie, :motif_sortie, :remarques
                        ) ON DUPLICATE KEY UPDATE
                            num_cnss = VALUES(num_cnss),
                            situation_matrimoniale = VALUES(situation_matrimoniale),
                            nombre_enfants = VALUES(nombre_enfants),
                            statut_rh = VALUES(statut_rh),
                            date_sortie = VALUES(date_sortie),
                            motif_sortie = VALUES(motif_sortie),
                            remarques = VALUES(remarques)";

            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $sql_det = "INSERT INTO personnel_details (
                                personnel_id, num_cnss, situation_matrimoniale, nombre_enfants,
                                statut_rh, date_sortie, motif_sortie, remarques
                            ) VALUES (
                                :personnel_id, :num_cnss, :situation_matrimoniale, :nombre_enfants,
                                :statut_rh, :date_sortie, :motif_sortie, :remarques
                            ) ON CONFLICT(personnel_id) DO UPDATE SET
                                num_cnss = excluded.num_cnss,
                                situation_matrimoniale = excluded.situation_matrimoniale,
                                nombre_enfants = excluded.nombre_enfants,
                                statut_rh = excluded.statut_rh,
                                date_sortie = excluded.date_sortie,
                                motif_sortie = excluded.motif_sortie,
                                remarques = excluded.remarques";
            }

            $stmt_det = $db->prepare($sql_det);
            $stmt_det->execute([
                'personnel_id' => $personnel_id,
                'num_cnss' => $num_cnss,
                'situation_matrimoniale' => $situation_matrimoniale,
                'nombre_enfants' => $nombre_enfants,
                'statut_rh' => $statut_rh,
                'date_sortie' => $date_sortie,
                'motif_sortie' => $motif_sortie,
                'remarques' => $remarques
            ]);

            // If initial contract provided on creation
            if (!$isUpdate && !empty($data['contrat_id'])) {
                PersonnelContractService::saveContract([
                    'personnel_id' => $personnel_id,
                    'type_contrat_id' => $data['contrat_id'],
                    'date_debut' => $data['date_embauche'] ?? date('Y-m-d'),
                    'salaire_base' => $data['salaire_base'] ?? 0.00,
                    'statut_contrat' => 'actif'
                ], $author_id);
            }

            // Invalidate auth_version cache so scope/permissions take effect immediately
            AuthorizationScopeService::invalidateUserCache($personnel_id);

            // Audit movement
            PersonnelHistoryService::logMovement([
                'personnel_id' => $personnel_id,
                'type_mouvement' => $isUpdate ? 'modification_identite' : 'embauche',
                'motif' => $isUpdate ? "Mise à jour du dossier du personnel" : "Création initiale du personnel",
                'auteur_id' => $author_id,
                'nouvel_etat' => [
                    'nom' => $data['nom'],
                    'prenom' => $data['prenom'],
                    'email' => $data['email'],
                    'role_id' => $data['role_id'],
                    'statut_rh' => $statut_rh
                ],
                'lycee_id' => $data['lycee_id'] ?? null
            ]);

            if (!$inTx) {
                $db->commit();
            }
            return $personnel_id;
        } catch (Exception $e) {
            if (!$inTx && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Explicitly update HR Status (en_activite, suspendu, en_conge, demissionne, licencie, retraite).
     */
    public static function updateStatus(int $personnel_id, string $statut_rh, ?string $motif, ?string $date_sortie, int $author_id): bool {
        $allowed = ['en_activite', 'suspendu', 'en_conge', 'demissionne', 'licencie', 'retraite'];
        if (!in_array($statut_rh, $allowed)) {
            throw new InvalidArgumentException(_("Statut RH non valide."));
        }

        $db = Database::getInstance();
        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            // Get old status
            $stmt_old = $db->prepare("SELECT statut_rh FROM personnel_details WHERE personnel_id = :id");
            $stmt_old->execute(['id' => $personnel_id]);
            $old_statut = $stmt_old->fetchColumn() ?: 'en_activite';

            // Sync user active state in utilisateurs table
            $is_user_active = in_array($statut_rh, ['en_activite', 'en_conge']) ? 1 : 0;
            $stmt_u = $db->prepare("UPDATE utilisateurs SET actif = :actif WHERE id_user = :id");
            $stmt_u->execute(['actif' => $is_user_active, 'id' => $personnel_id]);

            // Update personnel_details
            $sql_d = "UPDATE personnel_details
                      SET statut_rh = :statut_rh, motif_sortie = :motif, date_sortie = :date_sortie
                      WHERE personnel_id = :personnel_id";
            $stmt_d = $db->prepare($sql_d);
            $stmt_d->execute([
                'statut_rh' => $statut_rh,
                'motif' => $motif,
                'date_sortie' => $date_sortie,
                'personnel_id' => $personnel_id
            ]);

            // Log movement
            PersonnelHistoryService::logMovement([
                'personnel_id' => $personnel_id,
                'type_mouvement' => 'changement_statut_rh',
                'motif' => $motif ?? "Changement de statut RH vers {$statut_rh}",
                'auteur_id' => $author_id,
                'ancien_etat' => ['statut_rh' => $old_statut],
                'nouvel_etat' => ['statut_rh' => $statut_rh, 'date_sortie' => $date_sortie]
            ]);

            // Invalidate auth_version cache immediately
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
     * Fetches reference list of HR Job Functions.
     */
    public static function getFonctions(?int $lycee_id = null): array {
        $db = Database::getInstance();
        $sql = "SELECT * FROM personnel_fonctions WHERE actif = 1";
        $params = [];
        if ($lycee_id !== null) {
            $sql .= " AND (lycee_id = :lycee_id OR lycee_id IS NULL)";
            $params['lycee_id'] = $lycee_id;
        }
        $sql .= " ORDER BY departement ASC, libelle ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
