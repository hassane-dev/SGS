<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthorizationScopeService.php';
require_once __DIR__ . '/../models/SurveillantGroupe.php';
require_once __DIR__ . '/../models/SurveillantAffectation.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class ConcurrentModificationException extends Exception {}

class SurveillantAssignmentService {

    /**
     * Validates that an academic year identifier is strictly a positive integer (int or string composed solely of digits, > 0).
     * Rejects floats, scientific notation, negative numbers, zero, booleans, arrays, null and empty strings.
     *
     * @param mixed $value
     * @return int
     * @throws InvalidArgumentException
     */
    public static function filterStrictPositiveInteger(mixed $value, string $fieldName = 'annee_academique_id'): int {
        // Reject null, empty strings, arrays, objects, booleans, resources
        if ($value === null || $value === '' || is_array($value) || is_object($value) || is_bool($value) || is_resource($value)) {
            throw new InvalidArgumentException(sprintf(_("Format invalide pour %s : valeur absente, nulle ou de type inattendu."), $fieldName));
        }

        // Native integer check
        if (is_int($value)) {
            if ($value <= 0) {
                throw new InvalidArgumentException(sprintf(_("Format invalide pour %s : l'entier doit être strictement positif (%d fourni)."), $fieldName, $value));
            }
            return $value;
        }

        // String check: must strictly match digits without leading zero, and be representable without overflow
        if (is_string($value)) {
            // Reject any whitespace, negative signs, decimals, scientific notations, or leading zeros
            if (!preg_match('/^[1-9][0-9]*$/', $value)) {
                throw new InvalidArgumentException(sprintf(_("Format invalide pour %s : la chaîne '%s' n'est pas un entier strictement positif (décimaux, notations scientifiques et zéros initiaux interdits)."), $fieldName, $value));
            }

            // Strictly validate that the string is representable as an integer <= PHP_INT_MAX without overflow
            $filtered = filter_var($value, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1]
            ]);

            if ($filtered === false) {
                throw new InvalidArgumentException(sprintf(_("Format invalide pour %s : la valeur '%s' dépasse la capacité maximale d'un entier (%d)."), $fieldName, $value, PHP_INT_MAX));
            }

            return $filtered;
        }

        // Reject floats (e.g. 99020.8) or any other non-integer type
        throw new InvalidArgumentException(sprintf(_("Format invalide pour %s : type ou format non entier interdit."), $fieldName));
    }

    /**
     * Resolves the active academic year and its official date boundaries.
     * Throws an explicit RuntimeException if no properly configured active academic year exists.
     *
     * @param ?int $lycee_id
     * @param mixed $annee_id
     * @return array
     */
    public static function resolveActiveAcademicYear(?int $lycee_id = null, mixed $annee_id = null): array {
        $activeYear = AnneeAcademique::findActive($lycee_id);
        if (!$activeYear || empty($activeYear['date_debut']) || empty($activeYear['date_fin'])) {
            throw new RuntimeException(_("Configuration temporelle invalide : Aucune année académique active correctement configurée n'a été trouvée pour cet établissement."));
        }

        // If an explicit annee_id is provided, validate strict positive integer format without permissive coercion
        if ($annee_id !== null) {
            $annee_id_int = self::filterStrictPositiveInteger($annee_id, 'annee_id');
            $requestedYear = AnneeAcademique::findById($annee_id_int);
            if (!$requestedYear || empty($requestedYear['date_debut']) || empty($requestedYear['date_fin'])) {
                throw new RuntimeException(_("Année académique spécifiée introuvable ou dates non configurées."));
            }
            if (empty($requestedYear['est_active'])) {
                throw new RuntimeException(sprintf(_("L'année académique spécifiée #%d n'est pas active."), $annee_id_int));
            }
            if ((int)$requestedYear['id'] !== (int)$activeYear['id']) {
                throw new RuntimeException(_("L'année académique spécifiée ne correspond pas à l'année académique active de l'établissement."));
            }
        }

        return $activeYear;
    }

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

        // Validate cycle compliance against personnel_cycles_assignments
        self::validateCycleCompliance($data);
    }

    /**
     * Returns the array of active cycle IDs assigned to a supervisor across both
     * personnel_cycles_assignments and surveillant_affectations for the active academic year.
     */
    public static function getSupervisorActiveCycleIds(int $user_id, ?int $lycee_id = null, ?int $annee_id = null): array {
        $db = Database::getInstance();
        $activeYear = self::resolveActiveAcademicYear($lycee_id, $annee_id);
        $annee_debut = $activeYear['date_debut'];
        $annee_fin = $activeYear['date_fin'];
        $annee_pk = (int)$activeYear['id'];

        $resolvedLyceeId = $lycee_id ?: (int)($activeYear['lycee_id'] ?? 0);
        if ($resolvedLyceeId <= 0) {
            $stmtUserLycee = $db->prepare("SELECT lycee_id FROM utilisateurs WHERE id_user = :uid");
            $stmtUserLycee->execute(['uid' => $user_id]);
            $resolvedLyceeId = (int)$stmtUserLycee->fetchColumn();
        }

        if ($resolvedLyceeId <= 0) {
            return [];
        }

        // 1. Explicit cycle assignments (personnel_cycles_assignments) strictly scoped to lycee
        $stmt1 = $db->prepare("
            SELECT DISTINCT pca.cycle_id
            FROM personnel_cycles_assignments pca
            JOIN cycles cy ON pca.cycle_id = cy.id_cycle
            WHERE pca.personnel_id = :uid
              AND cy.lycee_id = :lid
              AND pca.actif = 1
              AND (pca.date_debut IS NULL OR pca.date_debut <= :annee_fin)
              AND (pca.date_fin IS NULL OR pca.date_fin = '' OR pca.date_fin = '0000-00-00' OR pca.date_fin >= :annee_debut)
        ");
        $stmt1->execute([
            'uid' => $user_id,
            'lid' => $resolvedLyceeId,
            'annee_fin' => $annee_fin,
            'annee_debut' => $annee_debut
        ]);
        $cycles1 = $stmt1->fetchAll(PDO::FETCH_COLUMN);

        // 2. Operational supervisor assignments (surveillant_affectations) strictly scoped to lycee
        $stmt2 = $db->prepare("
            SELECT sa.portee_type, sa.cycle_id, sa.lycee_id
            FROM surveillant_affectations sa
            LEFT JOIN cycles cy ON sa.cycle_id = cy.id_cycle
            WHERE sa.surveillant_id = :uid
              AND sa.lycee_id = :lid
              AND sa.annee_academique_id = :aid
              AND sa.statut = 'actif'
              AND (
                  (sa.portee_type = 'cycle' AND cy.lycee_id = :lid_cyc)
                  OR
                  (sa.portee_type = 'etablissement')
              )
              AND (sa.date_debut IS NULL OR sa.date_debut <= :annee_fin)
              AND (sa.date_fin IS NULL OR sa.date_fin = '' OR sa.date_fin = '0000-00-00' OR sa.date_fin >= :annee_debut)
        ");
        $stmt2->execute([
            'uid' => $user_id,
            'lid' => $resolvedLyceeId,
            'aid' => $annee_pk,
            'lid_cyc' => $resolvedLyceeId,
            'annee_fin' => $annee_fin,
            'annee_debut' => $annee_debut
        ]);
        $affs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $cycles2 = [];
        foreach ($affs as $aff) {
            if ($aff['portee_type'] === 'cycle' && !empty($aff['cycle_id'])) {
                $cycles2[] = (int)$aff['cycle_id'];
            } elseif ($aff['portee_type'] === 'etablissement' && !empty($aff['lycee_id'])) {
                $stmtCy = $db->prepare("SELECT id_cycle FROM cycles WHERE lycee_id = :lid");
                $stmtCy->execute(['lid' => (int)$aff['lycee_id']]);
                $cycles2 = array_merge($cycles2, $stmtCy->fetchAll(PDO::FETCH_COLUMN));
            }
        }

        $allCycles = array_merge($cycles1, $cycles2);
        return array_values(array_unique(array_map('intval', array_filter($allCycles, fn($v) => $v !== null && $v !== ''))));
    }

    /**
     * Returns active supervisors for a given school who have an active cycle assignment for $cycle_id
     * covering the active academic year (via personnel_cycles_assignments or surveillant_affectations).
     */
    public static function getSupervisorsByCycle(int $cycle_id, int $lycee_id, ?int $annee_id = null): array {
        $db = Database::getInstance();

        // Validate that the requested cycle strictly belongs to this lycee
        $stmtCycCheck = $db->prepare("SELECT id_cycle FROM cycles WHERE id_cycle = :cid AND lycee_id = :lid");
        $stmtCycCheck->execute(['cid' => $cycle_id, 'lid' => $lycee_id]);
        if (!$stmtCycCheck->fetchColumn()) {
            return []; // Requested cycle belongs to another establishment or does not exist
        }

        $activeYear = self::resolveActiveAcademicYear($lycee_id, $annee_id);
        $annee_debut = $activeYear['date_debut'];
        $annee_fin = $activeYear['date_fin'];
        $annee_pk = (int)$activeYear['id'];

        $sql = "
            SELECT DISTINCT u.id_user, u.nom, u.prenom, u.identifiant_public
            FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id_role
            WHERE u.lycee_id = :lycee_id
              AND (u.actif = 1 OR u.actif IS NULL)
              AND (r.nom_role LIKE '%surveillant%' OR r.id_role = 5)
              AND (
                  u.id_user IN (
                      SELECT pca.personnel_id
                      FROM personnel_cycles_assignments pca
                      JOIN cycles cy ON pca.cycle_id = cy.id_cycle
                      WHERE pca.cycle_id = :cycle_id_pca
                        AND cy.lycee_id = :lid_pca
                        AND pca.actif = 1
                        AND (pca.date_debut IS NULL OR pca.date_debut <= :annee_fin_pca)
                        AND (pca.date_fin IS NULL OR pca.date_fin = '' OR pca.date_fin = '0000-00-00' OR pca.date_fin >= :annee_debut_pca)
                  )
                  OR
                  u.id_user IN (
                      SELECT sa.surveillant_id
                      FROM surveillant_affectations sa
                      LEFT JOIN cycles cy2 ON sa.cycle_id = cy2.id_cycle
                      WHERE sa.lycee_id = :lycee_id_sa
                        AND sa.annee_academique_id = :annee_id_sa
                        AND sa.statut = 'actif'
                        AND (
                            (sa.portee_type = 'cycle' AND sa.cycle_id = :cycle_id_sa AND cy2.lycee_id = :lid_sa_cyc)
                            OR
                            (sa.portee_type = 'etablissement')
                        )
                        AND (sa.date_debut IS NULL OR sa.date_debut <= :annee_fin_sa)
                        AND (sa.date_fin IS NULL OR sa.date_fin = '' OR sa.date_fin = '0000-00-00' OR sa.date_fin >= :annee_debut_sa)
                  )
              )
            ORDER BY u.nom ASC, u.prenom ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'lycee_id' => $lycee_id,
            'cycle_id_pca' => $cycle_id,
            'lid_pca' => $lycee_id,
            'annee_fin_pca' => $annee_fin,
            'annee_debut_pca' => $annee_debut,
            'lycee_id_sa' => $lycee_id,
            'annee_id_sa' => $annee_pk,
            'cycle_id_sa' => $cycle_id,
            'lid_sa_cyc' => $lycee_id,
            'annee_fin_sa' => $annee_fin,
            'annee_debut_sa' => $annee_debut,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validates that the supervisor has an active cycle assignment covering the target class, group, level, cycle or establishment.
     */
    public static function validateCycleCompliance(array $data): void {
        if (empty($data['surveillant_id'])) {
            return;
        }

        $surveillant_id = (int)$data['surveillant_id'];
        $lycee_id = (int)($data['lycee_id'] ?? 0);
        $annee_id = !empty($data['annee_academique_id']) ? (int)$data['annee_academique_id'] : null;
        $active_cycles = self::getSupervisorActiveCycleIds($surveillant_id, $lycee_id, $annee_id);

        if (empty($active_cycles)) {
            throw new InvalidArgumentException(_("Erreur de périmètre : Le surveillant sélectionné n'a aucune affectation de cycle active dans l'établissement."));
        }

        $db = Database::getInstance();

        // 1. Single class target
        if (!empty($data['classe_id'])) {
            $stmt = $db->prepare("SELECT cycle_id FROM classes WHERE id_classe = :cid");
            $stmt->execute(['cid' => $data['classe_id']]);
            $c_cycle = (int)$stmt->fetchColumn();

            if (!in_array($c_cycle, $active_cycles, true)) {
                throw new InvalidArgumentException(_("Erreur de périmètre : Le surveillant n'est pas autorisé sur le cycle de la classe sélectionnée."));
            }
        }

        // 2. Direct cycle target
        if (!empty($data['cycle_id'])) {
            if (!in_array((int)$data['cycle_id'], $active_cycles, true)) {
                throw new InvalidArgumentException(_("Erreur de périmètre : Le surveillant n'est pas autorisé sur le cycle sélectionné."));
            }
        }

        // 3. Level target
        if (!empty($data['niveau_code'])) {
            $stmt = $db->prepare("SELECT DISTINCT cycle_id FROM classes WHERE niveau = :niv AND lycee_id = :lid");
            $stmt->execute(['niv' => $data['niveau_code'], 'lid' => $lycee_id]);
            $niv_cycles = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            foreach ($niv_cycles as $n_cyc) {
                if (!in_array($n_cyc, $active_cycles, true)) {
                    throw new InvalidArgumentException(_("Erreur de périmètre : Le niveau contient des classes d'un cycle sur lequel le surveillant n'est pas autorisé."));
                }
            }
        }

        // 4. Group target
        if (!empty($data['groupe_id'])) {
            $stmt = $db->prepare("
                SELECT DISTINCT c.cycle_id
                FROM surveillant_groupe_classes sgc
                JOIN classes c ON sgc.classe_id = c.id_classe
                WHERE sgc.groupe_id = :gid
            ");
            $stmt->execute(['gid' => $data['groupe_id']]);
            $grp_cycles = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            foreach ($grp_cycles as $g_cyc) {
                if (!in_array($g_cyc, $active_cycles, true)) {
                    throw new InvalidArgumentException(_("Erreur de périmètre : Le groupe de classes contient des classes d'un cycle non autorisé pour ce surveillant."));
                }
            }
        }

        // 5. Whole establishment target
        if (!empty($data['portee_type']) && $data['portee_type'] === 'etablissement') {
            $stmt = $db->prepare("SELECT id_cycle FROM cycles WHERE lycee_id = :lid OR lycee_id IS NULL");
            $stmt->execute(['lid' => $lycee_id]);
            $all_school_cycles = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            foreach ($all_school_cycles as $s_cyc) {
                if (!in_array($s_cyc, $active_cycles, true)) {
                    throw new InvalidArgumentException(_("Erreur de périmètre : Le surveillant n'est pas autorisé sur tous les cycles de l'établissement."));
                }
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
        ?int $max_per_surveillant = null,
        ?int $cycle_id = null
    ): array {
        $db = Database::getInstance();

        // Filter valid classes belonging to lycee and cycle (if cycle_id is specified)
        if (empty($selected_classe_ids)) {
            throw new InvalidArgumentException(_("Veuillez sélectionner au moins une classe."));
        }
        if (empty($selected_surveillant_ids)) {
            throw new InvalidArgumentException(_("Veuillez sélectionner au moins un surveillant."));
        }

        $today = date('Y-m-d');

        $cycle_filter_c = "";
        $paramsC = array_map('intval', $selected_classe_ids);
        if ($cycle_id !== null && $cycle_id > 0) {
            $cycle_filter_c = " AND c.cycle_id = ? ";
            $paramsC[] = $cycle_id;
        }
        $paramsC[] = $lycee_id;

        $placeholders_c = implode(',', array_fill(0, count($selected_classe_ids), '?'));
        $stmtC = $db->prepare("
            SELECT c.id_classe, c.niveau, c.serie, c.numero, c.cycle_id, cy.nom_cycle
            FROM classes c
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE c.id_classe IN ($placeholders_c) $cycle_filter_c AND c.lycee_id = ?
            ORDER BY c.cycle_id ASC, c.niveau ASC, c.serie ASC, c.numero ASC, c.id_classe ASC
        ");
        $stmtC->execute($paramsC);
        $classes = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        // Filter valid active supervisors having active cycle assignments
        $activeYear = self::resolveActiveAcademicYear($lycee_id, $annee_id);
        $annee_debut = $activeYear['date_debut'];
        $annee_fin = $activeYear['date_fin'];
        $annee_pk = (int)$activeYear['id'];

        $cycle_filter_u = "";
        $paramsU = array_map('intval', $selected_surveillant_ids);
        if ($cycle_id !== null && $cycle_id > 0) {
            $cycle_filter_u = " AND (
                u.id_user IN (
                    SELECT DISTINCT pca.personnel_id FROM personnel_cycles_assignments pca
                    JOIN cycles cy ON pca.cycle_id = cy.id_cycle
                    WHERE pca.cycle_id = ? AND cy.lycee_id = ? AND pca.actif = 1
                      AND (pca.date_debut IS NULL OR pca.date_debut <= ?)
                      AND (pca.date_fin IS NULL OR pca.date_fin = '' OR pca.date_fin = '0000-00-00' OR pca.date_fin >= ?)
                )
                OR
                u.id_user IN (
                    SELECT DISTINCT sa.surveillant_id FROM surveillant_affectations sa
                    LEFT JOIN cycles cy2 ON sa.cycle_id = cy2.id_cycle
                    WHERE sa.lycee_id = ? AND sa.annee_academique_id = ? AND sa.statut = 'actif'
                      AND ((sa.portee_type = 'cycle' AND sa.cycle_id = ? AND cy2.lycee_id = ?) OR (sa.portee_type = 'etablissement'))
                      AND (sa.date_debut IS NULL OR sa.date_debut <= ?)
                      AND (sa.date_fin IS NULL OR sa.date_fin = '' OR sa.date_fin = '0000-00-00' OR sa.date_fin >= ?)
                )
            ) ";
            $paramsU[] = $cycle_id;
            $paramsU[] = $lycee_id;
            $paramsU[] = $annee_fin;
            $paramsU[] = $annee_debut;
            $paramsU[] = $lycee_id;
            $paramsU[] = $annee_pk;
            $paramsU[] = $cycle_id;
            $paramsU[] = $lycee_id;
            $paramsU[] = $annee_fin;
            $paramsU[] = $annee_debut;
        }
        $paramsU[] = $lycee_id;

        $placeholders_u = implode(',', array_fill(0, count($selected_surveillant_ids), '?'));
        $stmtU = $db->prepare("
            SELECT u.id_user, u.nom, u.prenom
            FROM utilisateurs u
            WHERE u.id_user IN ($placeholders_u) $cycle_filter_u AND u.lycee_id = ? AND (u.actif = 1 OR u.actif IS NULL)
            ORDER BY u.nom ASC, u.prenom ASC, u.id_user ASC
        ");
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
                'surveillant_id' => (int)$s['id_user'],
                'surveillant' => $s,
                'classe_ids' => [],
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
                    $distribution[$s['id_user']]['classe_ids'][] = (int)$classes[$class_idx]['id_classe'];
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
                    $distribution[$target_surveillant['id_user']]['classe_ids'][] = (int)$lc['id_classe'];
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
        if (!is_array($payload) || empty($payload['state_hash']) || !is_string($payload['state_hash'])) {
            throw new InvalidArgumentException(_("Payload de répartition invalide : state_hash manquant ou non textuel."));
        }

        if (!array_key_exists('lycee_id', $payload)) {
            throw new InvalidArgumentException(_("Payload de répartition invalide : identifiant d'établissement manquant."));
        }
        $lycee_id = self::filterStrictPositiveInteger($payload['lycee_id'], 'lycee_id');

        // Validate that annee_academique_id is strictly a positive integer without permissive coercion
        if (!array_key_exists('annee_academique_id', $payload)) {
            throw new InvalidArgumentException(_("Identifiant d'année académique manquant dans le payload de répartition."));
        }
        $candidate_annee_id = self::filterStrictPositiveInteger($payload['annee_academique_id'], 'annee_academique_id');

        // Validate apply_mode
        $mode_apply = $payload['apply_mode'] ?? 'replace'; // 'replace' or 'append'
        if (!in_array($mode_apply, ['replace', 'append'], true)) {
            throw new InvalidArgumentException(sprintf(_("Mode d'application de répartition invalide : '%s'."), var_export($mode_apply, true)));
        }

        // Validate distribution structure
        if (!isset($payload['distribution']) || !is_array($payload['distribution'])) {
            throw new InvalidArgumentException(_("Structure de distribution invalide dans le payload."));
        }

        // Strict academic year validation: verify that candidate_annee_id matches the active year for this lycee
        $activeYear = self::resolveActiveAcademicYear($lycee_id, $candidate_annee_id);
        $annee_id = (int)$activeYear['id'];
        $submitted_hash = trim($payload['state_hash']);
        $distribution = $payload['distribution'];
        $selected_classe_ids = array_map('intval', $payload['selected_classe_ids'] ?? []);

        $db = Database::getInstance();
        $inTx = $db->inTransaction();
        $savepointName = 'sp_apply_distribution_' . uniqid();

        if (!$inTx) {
            $db->beginTransaction();
        } else {
            $db->exec("SAVEPOINT $savepointName");
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
                $surveillant_id = (int)($item['surveillant_id'] ?? 0);
                $classe_ids = array_map('intval', $item['classe_ids'] ?? []);

                if ($surveillant_id <= 0 || empty($classe_ids)) {
                    continue;
                }

                // Verify supervisor belongs strictly to this lycee and is active
                $stmtCheckSurv = $db->prepare("SELECT lycee_id, actif FROM utilisateurs WHERE id_user = :uid");
                $stmtCheckSurv->execute(['uid' => $surveillant_id]);
                $uData = $stmtCheckSurv->fetch(PDO::FETCH_ASSOC);
                if (!$uData || (int)$uData['lycee_id'] !== $lycee_id || ($uData['actif'] !== null && (int)$uData['actif'] === 0)) {
                    throw new InvalidArgumentException(_("Validation de sécurité : Le surveillant sélectionné est invalide ou n'appartient pas à cet établissement."));
                }

                $impacted_user_ids[] = $surveillant_id;

                $active_cycles = self::getSupervisorActiveCycleIds($surveillant_id, $lycee_id, $annee_id);

                foreach ($classe_ids as $cid) {
                    // Validate class belongs to this lycee and cycle compliance
                    $stmtCyc = $db->prepare("SELECT cycle_id, lycee_id FROM classes WHERE id_classe = :cid");
                    $stmtCyc->execute(['cid' => $cid]);
                    $cRow = $stmtCyc->fetch(PDO::FETCH_ASSOC);

                    if (!$cRow || (int)$cRow['lycee_id'] !== $lycee_id) {
                        throw new InvalidArgumentException(_("Validation de sécurité : La classe sélectionnée n'appartient pas à cet établissement."));
                    }

                    $c_cycle = (int)$cRow['cycle_id'];
                    if (!in_array($c_cycle, $active_cycles, true)) {
                        throw new InvalidArgumentException(_("Validation de sécurité : Le surveillant sélectionné n'a pas d'affectation active sur le cycle de cette classe."));
                    }

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
            } else {
                $db->exec("RELEASE SAVEPOINT $savepointName");
            }
            return true;
        } catch (Throwable $e) {
            if (!$inTx && $db->inTransaction()) {
                $db->rollBack();
            } elseif ($inTx) {
                $db->exec("ROLLBACK TO SAVEPOINT $savepointName");
                $db->exec("RELEASE SAVEPOINT $savepointName");
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
