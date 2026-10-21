<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthorizationScopeService.php';
require_once __DIR__ . '/../models/SurveillantGroupe.php';
require_once __DIR__ . '/../models/SurveillantAffectation.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/User.php';

class ConcurrentModificationException extends Exception {}

class SurveillantAssignmentService {

    /**
     * Increments the surveillant_config_version in param_lycee within a transaction.
     */
    public static function bumpConfigVersion(int $lycee_id, ?PDO $db = null): void {
        $db = $db ?: Database::getInstance();
        $stmt = $db->prepare("UPDATE param_lycee SET surveillant_config_version = surveillant_config_version + 1 WHERE id = :id");
        $stmt->execute(['id' => $lycee_id]);
    }

    /**
     * Validates that all entities referenced belong strictly to the specified establishment and academic year.
     */
    public static function validateTenantHomogeneity(array $data): void {
        $db = Database::getInstance();
        $lycee_id = (int)$data['lycee_id'];
        $annee_id = (int)($data['annee_academique_id'] ?? 0);

        if (!empty($data['surveillant_id'])) {
            $stmt = $db->prepare("SELECT lycee_id FROM utilisateurs WHERE id_user = :id");
            $stmt->execute(['id' => $data['surveillant_id']]);
            $u_lycee = $stmt->fetchColumn();
            if ($u_lycee !== false && (int)$u_lycee !== $lycee_id) {
                throw new InvalidArgumentException(_("Incohérence multi-établissement : Le surveillant n'appartient pas à cet établissement."));
            }
        }

        if (!empty($data['classe_id'])) {
            $stmt = $db->prepare("SELECT lycee_id FROM classes WHERE id_classe = :id");
            $stmt->execute(['id' => $data['classe_id']]);
            $c_lycee = $stmt->fetchColumn();
            if ($c_lycee !== false && (int)$c_lycee !== $lycee_id) {
                throw new InvalidArgumentException(_("Incohérence multi-établissement : La classe sélectionnée n'appartient pas à cet établissement."));
            }
        }

        if (!empty($data['groupe_id'])) {
            $stmt = $db->prepare("SELECT lycee_id, annee_academique_id FROM surveillant_groupes WHERE id = :id");
            $stmt->execute(['id' => $data['groupe_id']]);
            $g = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$g || (int)$g['lycee_id'] !== $lycee_id || ($annee_id > 0 && (int)$g['annee_academique_id'] !== $annee_id)) {
                throw new InvalidArgumentException(_("Incohérence multi-établissement ou d'année académique : Le groupe sélectionné est invalide."));
            }
        }

        if (!empty($data['cycle_id'])) {
            $stmt = $db->prepare("SELECT lycee_id FROM cycles WHERE id_cycle = :id");
            $stmt->execute(['id' => $data['cycle_id']]);
            $cy_lycee = $stmt->fetchColumn();
            if ($cy_lycee !== false && $cy_lycee !== null && (int)$cy_lycee !== $lycee_id) {
                throw new InvalidArgumentException(_("Incohérence multi-établissement : Le cycle sélectionné n'appartient pas à cet établissement."));
            }
        }
    }

    /**
     * Computes the deterministic SHA-256 state hash for a school and academic year.
     */
    public static function computeStateHash(int $lycee_id, int $annee_id): string {
        $db = Database::getInstance();

        // 1. Lycee config version
        $stmtV = $db->prepare("SELECT surveillant_config_version FROM param_lycee WHERE id = :id");
        $stmtV->execute(['id' => $lycee_id]);
        $ver = (int)$stmtV->fetchColumn() ?: 1;

        // 2. Classes checksum
        $stmtC = $db->prepare("SELECT id_classe, cycle_id, niveau, serie, numero FROM classes WHERE lycee_id = :lid ORDER BY id_classe ASC");
        $stmtC->execute(['lid' => $lycee_id]);
        $classes_data = json_encode($stmtC->fetchAll(PDO::FETCH_ASSOC));

        // 3. Supervisors checksum
        $stmtU = $db->prepare("
            SELECT u.id_user, u.actif
            FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id_role
            WHERE u.lycee_id = :lid
              AND (r.nom_role LIKE '%surveillant%' OR r.id_role = 5)
            ORDER BY u.id_user ASC
        ");
        $stmtU->execute(['lid' => $lycee_id]);
        $users_data = json_encode($stmtU->fetchAll(PDO::FETCH_ASSOC));

        // 4. Active assignments checksum
        $stmtA = $db->prepare("
            SELECT id, surveillant_id, classe_id, groupe_id, cycle_id, niveau_code, statut, created_at
            FROM surveillant_affectations
            WHERE lycee_id = :lid AND annee_academique_id = :aid AND statut = 'actif'
            ORDER BY id ASC
        ");
        $stmtA->execute(['lid' => $lycee_id, 'aid' => $annee_id]);
        $assign_data = json_encode($stmtA->fetchAll(PDO::FETCH_ASSOC));

        // 5. Groups checksum
        $stmtG = $db->prepare("
            SELECT sg.id, sgc.classe_id
            FROM surveillant_groupes sg
            LEFT JOIN surveillant_groupe_classes sgc ON sg.id = sgc.groupe_id
            WHERE sg.lycee_id = :lid AND sg.annee_academique_id = :aid
            ORDER BY sg.id ASC, sgc.classe_id ASC
        ");
        $stmtG->execute(['lid' => $lycee_id, 'aid' => $annee_id]);
        $groups_data = json_encode($stmtG->fetchAll(PDO::FETCH_ASSOC));

        $payload = "v={$ver}|c={$classes_data}|u={$users_data}|a={$assign_data}|g={$groups_data}";
        return hash('sha256', $payload);
    }

    /**
     * Proposes a deterministic distribution of classes among supervisors without writing to DB.
     */
    public static function proposeDistribution(
        int $lycee_id,
        int $annee_id,
        array $selected_classe_ids,
        array $selected_surveillant_ids,
        string $mode = 'equitable',
        ?int $max_per_surveillant = null
    ): array {
        $db = Database::getInstance();

        // Filter valid classes belonging to lycee
        if (empty($selected_classe_ids)) {
            throw new InvalidArgumentException(_("Veuillez sélectionner au moins une classe."));
        }
        if (empty($selected_surveillant_ids)) {
            throw new InvalidArgumentException(_("Veuillez sélectionner au moins un surveillant."));
        }

        $placeholders_c = implode(',', array_fill(0, count($selected_classe_ids), '?'));
        $stmtC = $db->prepare("
            SELECT c.id_classe, c.niveau, c.serie, c.numero, c.cycle_id, cy.nom_cycle
            FROM classes c
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE c.id_classe IN ($placeholders_c) AND c.lycee_id = ?
            ORDER BY c.cycle_id ASC, c.niveau ASC, c.serie ASC, c.numero ASC, c.id_classe ASC
        ");
        $paramsC = array_merge(array_map('intval', $selected_classe_ids), [$lycee_id]);
        $stmtC->execute($paramsC);
        $classes = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        // Filter valid active supervisors
        $placeholders_u = implode(',', array_fill(0, count($selected_surveillant_ids), '?'));
        $stmtU = $db->prepare("
            SELECT u.id_user, u.nom, u.prenom
            FROM utilisateurs u
            WHERE u.id_user IN ($placeholders_u) AND u.lycee_id = ? AND u.actif = 1
            ORDER BY u.nom ASC, u.prenom ASC, u.id_user ASC
        ");
        $paramsU = array_merge(array_map('intval', $selected_surveillant_ids), [$lycee_id]);
        $stmtU->execute($paramsU);
        $surveillants = $stmtU->fetchAll(PDO::FETCH_ASSOC);

        if (empty($classes)) {
            throw new InvalidArgumentException(_("Aucune classe valide sélectionnée pour cet établissement."));
        }
        if (empty($surveillants)) {
            throw new InvalidArgumentException(_("Aucun surveillant actif sélectionné pour cet établissement."));
        }

        $num_classes = count($classes);
        $num_surveillants = count($surveillants);

        $distribution = [];
        foreach ($surveillants as $s) {
            $distribution[$s['id_user']] = [
                'surveillant' => $s,
                'classes' => []
            ];
        }

        $unassigned_classes = [];

        if ($mode === 'equitable') {
            $base_quota = (int)floor($num_classes / $num_surveillants);
            $remainder = $num_classes % $num_surveillants;

            $class_idx = 0;
            foreach ($surveillants as $idx => $s) {
                $quota = $base_quota + ($idx < $remainder ? 1 : 0);
                for ($i = 0; $i < $quota && $class_idx < $num_classes; $i++) {
                    $distribution[$s['id_user']]['classes'][] = $classes[$class_idx];
                    $class_idx++;
                }
            }
        } elseif ($mode === 'plage_consecutive') {
            $chunk_size = $max_per_surveillant ?: 3;
            $class_idx = 0;
            foreach ($surveillants as $s) {
                for ($i = 0; $i < $chunk_size && $class_idx < $num_classes; $i++) {
                    $distribution[$s['id_user']]['classes'][] = $classes[$class_idx];
                    $class_idx++;
                }
            }
            while ($class_idx < $num_classes) {
                $unassigned_classes[] = $classes[$class_idx];
                $class_idx++;
            }
        } elseif ($mode === 'niveau') {
            // Group classes by level
            $by_level = [];
            foreach ($classes as $c) {
                $by_level[$c['niveau']][] = $c;
            }

            $surv_idx = 0;
            foreach ($by_level as $level_classes) {
                $target_surveillant = $surveillants[$surv_idx % $num_surveillants];
                foreach ($level_classes as $lc) {
                    $distribution[$target_surveillant['id_user']]['classes'][] = $lc;
                }
                $surv_idx++;
            }
        }

        $state_hash = self::computeStateHash($lycee_id, $annee_id);

        return [
            'state_hash' => $state_hash,
            'mode' => $mode,
            'lycee_id' => $lycee_id,
            'annee_academique_id' => $annee_id,
            'distribution' => array_values($distribution),
            'unassigned_classes' => $unassigned_classes,
            'selected_classe_ids' => array_map('intval', array_column($classes, 'id_classe')),
            'selected_surveillant_ids' => array_map('intval', array_column($surveillants, 'id_user'))
        ];
    }

    /**
     * Applies a distribution proposal atomically with OCC lock and state hash verification.
     */
    public static function applyDistribution(array $payload, int $current_user_id): bool {
        if (empty($payload['state_hash']) || empty($payload['lycee_id']) || empty($payload['annee_academique_id'])) {
            throw new InvalidArgumentException(_("Payload de répartition invalide."));
        }

        $lycee_id = (int)$payload['lycee_id'];
        $annee_id = (int)$payload['annee_academique_id'];
        $submitted_hash = $payload['state_hash'];
        $mode_apply = $payload['apply_mode'] ?? 'replace'; // 'replace' or 'append'
        $distribution = $payload['distribution'] ?? [];
        $selected_classe_ids = array_map('intval', $payload['selected_classe_ids'] ?? []);

        $db = Database::getInstance();
        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            // 1. VERROU EXCLUSIF SUR LA LIGNE ÉTABLISSEMENT POUR SÉRIALISER LA DISTRIBUTION
            $sqlLock = "SELECT id, surveillant_config_version FROM param_lycee WHERE id = :id";
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
                $sqlLock .= " FOR UPDATE";
            }
            $stmtLock = $db->prepare($sqlLock);
            $stmtLock->execute(['id' => $lycee_id]);

            // 2. RE-CALCULER LE HASH EN TEMPS RÉEL SOUS VERROU
            $current_hash = self::computeStateHash($lycee_id, $annee_id);

            if (!hash_equals($submitted_hash, $current_hash)) {
                throw new ConcurrentModificationException(_("La structure des classes, du personnel ou des affectations a été modifiée par un autre utilisateur. La proposition est caduque. Veuillez la rafraîchir."));
            }

            // 3. SI MODE REPLACE : Clôturer uniquement les affectations actives des classes soumises
            if ($mode_apply === 'replace' && !empty($selected_classe_ids)) {
                $placeholders = implode(',', array_fill(0, count($selected_classe_ids), '?'));
                $stmtClose = $db->prepare("
                    UPDATE surveillant_affectations
                    SET statut = 'termine', date_fin = CURRENT_DATE
                    WHERE lycee_id = ?
                      AND annee_academique_id = ?
                      AND statut = 'actif'
                      AND classe_id IN ($placeholders)
                ");
                $paramsClose = array_merge([$lycee_id, $annee_id], $selected_classe_ids);
                $stmtClose->execute($paramsClose);
            }

            // 4. INSERER LES NOUVELLES AFFECTATIONS
            $impacted_user_ids = [];
            $today = date('Y-m-d');

            foreach ($distribution as $item) {
                $surveillant_id = (int)$item['surveillant_id'];
                $classe_ids = array_map('intval', $item['classe_ids'] ?? []);

                if ($surveillant_id <= 0 || empty($classe_ids)) {
                    continue;
                }

                $impacted_user_ids[] = $surveillant_id;

                foreach ($classe_ids as $cid) {
                    // Check if an active assignment already exists
                    $stmtCheck = $db->prepare("
                        SELECT COUNT(*) FROM surveillant_affectations
                        WHERE lycee_id = :lid
                          AND annee_academique_id = :aid
                          AND surveillant_id = :uid
                          AND classe_id = :cid
                          AND statut = 'actif'
                    ");
                    $stmtCheck->execute([
                        'lid' => $lycee_id,
                        'aid' => $annee_id,
                        'uid' => $surveillant_id,
                        'cid' => $cid
                    ]);

                    if ((int)$stmtCheck->fetchColumn() === 0) {
                        SurveillantAffectation::save([
                            'lycee_id' => $lycee_id,
                            'annee_academique_id' => $annee_id,
                            'surveillant_id' => $surveillant_id,
                            'type_responsabilite' => 'surveillant_classe',
                            'portee_type' => 'classe',
                            'classe_id' => $cid,
                            'date_debut' => $today,
                            'statut' => 'actif',
                            'created_by' => $current_user_id
                        ]);
                    }
                }
            }

            // 5. INCRÉMENTER LA VERSION DE CONFIGURATION
            self::bumpConfigVersion($lycee_id, $db);

            // 6. INVALIDER LE CACHE DE SCOPE DE TOUS LES SURVEILLANTS IMPACTÉS
            foreach (array_unique($impacted_user_ids) as $uid) {
                AuthorizationScopeService::invalidateUserCache($uid);
            }

            if (!$inTx) {
                $db->commit();
            }
            return true;
        } catch (Throwable $e) {
            if (!$inTx && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Archive hook: Captures class snapshot and terminates active assignments before a class is deleted.
     */
    public static function archiveClassAssignmentsAndGroups(int $classe_id): void {
        $db = Database::getInstance();

        // 1. Fetch class details for snapshot
        $stmtC = $db->prepare("
            SELECT c.niveau, c.serie, c.numero, cy.nom_cycle
            FROM classes c
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE c.id_classe = :id
        ");
        $stmtC->execute(['id' => $classe_id]);
        $c = $stmtC->fetch(PDO::FETCH_ASSOC);

        if ($c) {
            $label = trim("{$c['niveau']} {$c['serie']} {$c['numero']} ({$c['nom_cycle']})");

            // Update active affectations to freeze snapshot and set status to termine
            $stmtUp = $db->prepare("
                UPDATE surveillant_affectations
                SET classe_nom_snapshot = COALESCE(classe_nom_snapshot, :label),
                    statut = 'termine',
                    classe_id = NULL
                WHERE classe_id = :classe_id
            ");
            $stmtUp->execute(['label' => $label, 'classe_id' => $classe_id]);
        }

        // 2. Pivot table surveillant_groupe_classes
        $stmtP = $db->prepare("DELETE FROM surveillant_groupe_classes WHERE classe_id = :classe_id");
        $stmtP->execute(['classe_id' => $classe_id]);
    }

    /**
     * Archive hook: Captures user snapshot and terminates active assignments before a user is deleted.
     */
    public static function archiveUserAssignments(int $user_id): void {
        $db = Database::getInstance();

        $stmtU = $db->prepare("SELECT nom, prenom, lycee_id FROM utilisateurs WHERE id_user = :id");
        $stmtU->execute(['id' => $user_id]);
        $u = $stmtU->fetch(PDO::FETCH_ASSOC);

        if ($u) {
            $full_name = trim("{$u['prenom']} {$u['nom']}");

            $stmtUp = $db->prepare("
                UPDATE surveillant_affectations
                SET surveillant_nom_snapshot = COALESCE(surveillant_nom_snapshot, :full_name),
                    statut = 'termine',
                    surveillant_id = NULL
                WHERE surveillant_id = :user_id
            ");
            $stmtUp->execute(['full_name' => $full_name, 'user_id' => $user_id]);

            if (!empty($u['lycee_id'])) {
                self::bumpConfigVersion((int)$u['lycee_id'], $db);
            }

            AuthorizationScopeService::invalidateUserCache($user_id);
        }
    }
}
