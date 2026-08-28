<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AffectationPedagogique.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class AffectationPedagogiqueService {

    /**
     * Create a new pedagogical assignment with strict transactional overlap locking and 8 invariant checks.
     */
    public static function createAssignment(array $data, int $author_id): int {
        $db = Database::getInstance();

        $enseignant_id = isset($data['enseignant_id']) ? (int)$data['enseignant_id'] : 0;
        $classe_id = isset($data['classe_id']) ? (int)$data['classe_id'] : 0;
        $matiere_id = isset($data['matiere_id']) ? (int)$data['matiere_id'] : 0;
        $volume_horaire = isset($data['volume_horaire_hebdo']) ? (float)$data['volume_horaire_hebdo'] : 0.00;
        $statut = !empty($data['statut']) ? $data['statut'] : 'actif';
        $motif = $data['motif_changement'] ?? null;

        if (!$enseignant_id || !$classe_id || !$matiere_id) {
            throw new InvalidArgumentException(_("L'enseignant, la classe et la matière sont obligatoires."));
        }

        // 1. Verify User exists and is a valid teacher
        $user = User::findById($enseignant_id);
        if (!$user) {
            throw new InvalidArgumentException(_("Enseignant introuvable."));
        }
        if (empty($user['actif'])) {
            throw new InvalidArgumentException(_("Cet utilisateur est inactif."));
        }

        // Check teacher role/function
        $stmt_role = $db->prepare("SELECT nom_role FROM roles WHERE id_role = :id");
        $stmt_role->execute(['id' => $user['role_id']]);
        $role_name = strtolower($stmt_role->fetchColumn() ?: '');
        if (strpos($role_name, 'enseignant') === false && strpos(strtolower($user['fonction'] ?? ''), 'enseignant') === false) {
            // Check if superadmin/director override or valid teacher
            if (!in_array($role_name, ['enseignant', 'proviseur', 'censeur', 'surveillant', 'directeur', 'admin_local', 'super_admin_createur'])) {
                throw new InvalidArgumentException(_("L'utilisateur sélectionné n'est pas qualifié comme enseignant."));
            }
        }

        // 2. Verify Class
        $classe = Classe::findById($classe_id);
        if (!$classe) {
            throw new InvalidArgumentException(_("Classe introuvable."));
        }

        // 3. Verify Subject exists in class curriculum (classe_matieres)
        $assigned_matieres = Matiere::findByClassId($classe_id);
        $subject_in_class = false;
        foreach ($assigned_matieres as $m) {
            if ($m['id_matiere'] == $matiere_id) {
                $subject_in_class = true;
                break;
            }
        }
        if (!$subject_in_class) {
            throw new InvalidArgumentException(_("Cette matière n'est pas inscrite au programme de cette classe."));
        }

        // 4. Verify Active Academic Year
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            throw new InvalidArgumentException(_("Aucune année académique active trouvée."));
        }
        $annee_id = $active_year['id'];

        // 5. Dates Resolution
        $date_debut = !empty($data['date_debut']) ? $data['date_debut'] : ($active_year['date_debut'] ?? date('Y-m-d'));
        $date_fin = !empty($data['date_fin']) ? $data['date_fin'] : null;

        // 6. Verify Cycle Authorization across the whole assignment period
        $cycle_id = $classe['cycle_id'];
        if ($cycle_id) {
            $end_date_comp = $date_fin ?: '2099-12-31';
            $stmt_cycle = $db->prepare("
                SELECT COUNT(*) FROM personnel_cycles_assignments
                WHERE personnel_id = :p AND cycle_id = :c AND actif = 1
                AND date_debut <= :start_date
                AND (date_fin IS NULL OR date_fin >= :end_date)
            ");
            $stmt_cycle->execute([
                'p' => $enseignant_id,
                'c' => $cycle_id,
                'start_date' => $date_debut,
                'end_date' => $end_date_comp
            ]);
            if ((int)$stmt_cycle->fetchColumn() === 0) {
                throw new InvalidArgumentException(_("L'enseignant n'a pas d'affectation de cycle valide couvrant la période demandée pour cette classe."));
            }
        }

        if ($date_fin && strtotime($date_fin) < strtotime($date_debut)) {
            throw new InvalidArgumentException(_("La date de fin ne peut pas être antérieure à la date de début."));
        }

        // 7. Security Scope Assert
        AuthorizationScopeService::assertAccessToObject($classe['lycee_id']);

        // 8. Transactional Overlap Lock & Save (Invariant 7)
        $in_existing_transaction = $db->inTransaction();

        try {
            if (!$in_existing_transaction) {
                $db->beginTransaction();
            }

            // Broad lock on ALL assignments for this class + subject + year to prevent concurrent overlaps
            $lock_sql = "
                SELECT * FROM affectations_pedagogiques
                WHERE classe_id = :c AND matiere_id = :m AND annee_academique_id = :a
                AND statut IN ('actif', 'suspendu', 'provisoire')
            ";
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
                $lock_sql .= " FOR UPDATE";
            }
            $stmt_lock = $db->prepare($lock_sql);
            $stmt_lock->execute([
                'c' => $classe_id,
                'm' => $matiere_id,
                'a' => $annee_id
            ]);
            $existing_assignments = $stmt_lock->fetchAll(PDO::FETCH_ASSOC);

            // Overlap evaluation
            if ($statut === 'actif' || $statut === 'suspendu') {
                foreach ($existing_assignments as $existing) {
                    if ($existing['statut'] === 'actif' || $existing['statut'] === 'suspendu') {
                        $ex_start = $existing['date_debut'] ?? '1970-01-01';
                        $ex_end = $existing['date_fin'] ?? '2099-12-31';
                        $new_start = $date_debut;
                        $new_end = $date_fin ?: '2099-12-31';

                        // Check temporal intersection
                        if (max($new_start, $ex_start) <= min($new_end, $ex_end)) {
                            if (!$in_existing_transaction) {
                                $db->rollBack();
                            }
                            throw new InvalidArgumentException(_("Conflit de chevauchement : Un enseignant actif/suspendu est déjà affecté à ce cours sur cette période."));
                        }
                    }
                }
            }

            // Insert new assignment
            $insert_sql = "
                INSERT INTO affectations_pedagogiques (
                    enseignant_id, classe_id, matiere_id, annee_academique_id,
                    volume_horaire_hebdo, date_debut, date_fin, statut,
                    motif_changement, created_by, created_at, updated_at
                ) VALUES (
                    :enseignant_id, :classe_id, :matiere_id, :annee_academique_id,
                    :volume_horaire_hebdo, :date_debut, :date_fin, :statut,
                    :motif_changement, :created_by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ";
            $stmt_insert = $db->prepare($insert_sql);
            $stmt_insert->execute([
                'enseignant_id' => $enseignant_id,
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'annee_academique_id' => $annee_id,
                'volume_horaire_hebdo' => $volume_horaire,
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'statut' => $statut,
                'motif_changement' => $motif,
                'created_by' => $author_id
            ]);

            $assignment_id = (int)$db->lastInsertId();
            if (!$in_existing_transaction) {
                $db->commit();
            }

            return $assignment_id;

        } catch (Exception $e) {
            if (!$in_existing_transaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update status (suspend, terminate, activate) or assignment parameters.
     */
    public static function updateStatus(int $assignment_id, string $new_statut, ?string $motif, ?string $date_fin, int $author_id): bool {
        $db = Database::getInstance();
        $assignment = AffectationPedagogique::findById($assignment_id);
        if (!$assignment) {
            throw new InvalidArgumentException(_("Affectation introuvable."));
        }

        AuthorizationScopeService::assertAccessToObject($assignment['lycee_id']);

        $allowed_statuts = ['provisoire', 'actif', 'suspendu', 'termine', 'annule'];
        if (!in_array($new_statut, $allowed_statuts)) {
            throw new InvalidArgumentException(_("Statut d'affectation invalide."));
        }

        $sql = "
            UPDATE affectations_pedagogiques SET
                statut = :statut,
                motif_changement = :motif,
                date_fin = :date_fin,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $effective_date_fin = $date_fin ?: ($new_statut === 'termine' || $new_statut === 'annule' ? date('Y-m-d') : $assignment['date_fin']);

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'statut' => $new_statut,
            'motif' => $motif ?: $assignment['motif_changement'],
            'date_fin' => $effective_date_fin,
            'id' => $assignment_id
        ]);
    }


    /**
     * Reactivate a suspended assignment after verifying all 8 invariant checks.
     */
    public static function reactivateAssignment(int $assignment_id, int $author_id): bool {
        $db = Database::getInstance();
        $assignment = AffectationPedagogique::findById($assignment_id);
        if (!$assignment) {
            throw new InvalidArgumentException(_("Affectation introuvable."));
        }

        if ($assignment['statut'] !== 'suspendu') {
            throw new InvalidArgumentException(_("Seule une affectation suspendue peut être réactivée."));
        }

        AuthorizationScopeService::assertAccessToObject($assignment['lycee_id']);

        // Execute overlap lock test within transaction
        try {
            $db->beginTransaction();

            $lock_sql = "
                SELECT * FROM affectations_pedagogiques
                WHERE classe_id = :c AND matiere_id = :m AND annee_academique_id = :a
                AND id != :id AND statut IN ('actif', 'suspendu')
            ";
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
                $lock_sql .= " FOR UPDATE";
            }
            $stmt_lock = $db->prepare($lock_sql);
            $stmt_lock->execute([
                'c' => $assignment['classe_id'],
                'm' => $assignment['matiere_id'],
                'a' => $assignment['annee_academique_id'],
                'id' => $assignment_id
            ]);
            $existing_assignments = $stmt_lock->fetchAll(PDO::FETCH_ASSOC);

            foreach ($existing_assignments as $existing) {
                $ex_start = $existing['date_debut'] ?? '1970-01-01';
                $ex_end = $existing['date_fin'] ?? '2099-12-31';
                $new_start = $assignment['date_debut'];
                $new_end = $assignment['date_fin'] ?: '2099-12-31';

                if (max($new_start, $ex_start) <= min($new_end, $ex_end)) {
                    $db->rollBack();
                    throw new InvalidArgumentException(_("Conflit de chevauchement : Un autre enseignant actif/suspendu est déjà affecté à ce cours sur cette période."));
                }
            }

            $stmt_upd = $db->prepare("
                UPDATE affectations_pedagogiques SET
                    statut = 'actif',
                    motif_changement = :motif,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt_upd->execute([
                'motif' => "Réactivation de l'affectation suspendue",
                'id' => $assignment_id
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

    /**
     * Update/Modify an assignment respecting historization rules.
     * If structural data (enseignant_id, classe_id, matiere_id) changes:
     *   - The old assignment is closed ('termine', date_fin = new date_debut - 1 day)
     *   - A new assignment is created with the updated structural data.
     * If non-structural data changes (volume_horaire, dates, statut, motif):
     *   - Updated in place after verifying all invariant and overlap checks.
     */
    public static function updateAssignment(int $assignment_id, array $data, int $author_id): int {
        $db = Database::getInstance();
        $old = AffectationPedagogique::findById($assignment_id);
        if (!$old) {
            throw new InvalidArgumentException(_("Affectation à modifier introuvable."));
        }

        AuthorizationScopeService::assertAccessToObject($old['lycee_id']);

        $new_enseignant_id = !empty($data['enseignant_id']) ? (int)$data['enseignant_id'] : (int)$old['enseignant_id'];
        $new_classe_id = !empty($data['classe_id']) ? (int)$data['classe_id'] : (int)$old['classe_id'];
        $new_matiere_id = !empty($data['matiere_id']) ? (int)$data['matiere_id'] : (int)$old['matiere_id'];

        $is_structural_change = ($new_enseignant_id !== (int)$old['enseignant_id'])
                             || ($new_classe_id !== (int)$old['classe_id'])
                             || ($new_matiere_id !== (int)$old['matiere_id']);

        $in_existing_transaction = $db->inTransaction();

        try {
            if (!$in_existing_transaction) {
                $db->beginTransaction();
            }

            if ($is_structural_change) {
                // Historization: Close existing assignment and create new assignment
                $new_start_date = !empty($data['date_debut']) ? $data['date_debut'] : date('Y-m-d');
                $effective_end_date = date('Y-m-d', strtotime($new_start_date . ' -1 day'));

                $close_motif = $data['motif_changement'] ?? 'Modification structurante de l\'affectation (clôture de l\'historique)';
                self::updateStatus($assignment_id, 'termine', $close_motif, $effective_end_date, $author_id);

                $create_data = array_merge($data, [
                    'enseignant_id' => $new_enseignant_id,
                    'classe_id' => $new_classe_id,
                    'matiere_id' => $new_matiere_id,
                    'date_debut' => $new_start_date,
                    'statut' => !empty($data['statut']) ? $data['statut'] : 'actif',
                ]);

                $new_id = self::createAssignment($create_data, $author_id);

                if (!$in_existing_transaction) {
                    $db->commit();
                }
                return $new_id;

            } else {
                // Non-structural update in place with full validation and overlap lock
                $volume_horaire = isset($data['volume_horaire_hebdo']) ? (float)$data['volume_horaire_hebdo'] : (float)$old['volume_horaire_hebdo'];
                $statut = !empty($data['statut']) ? $data['statut'] : $old['statut'];
                $date_debut = !empty($data['date_debut']) ? $data['date_debut'] : $old['date_debut'];
                $date_fin = array_key_exists('date_fin', $data) ? ($data['date_fin'] ?: null) : $old['date_fin'];
                $motif = $data['motif_changement'] ?? $old['motif_changement'];

                if ($date_fin && strtotime($date_fin) < strtotime($date_debut)) {
                    throw new InvalidArgumentException(_("La date de fin ne peut pas être antérieure à la date de début."));
                }

                // Verify cycle assignment coverage if date_debut/date_fin changed
                $classe = Classe::findById($new_classe_id);
                if ($classe && $classe['cycle_id']) {
                    $end_date_comp = $date_fin ?: '2099-12-31';
                    $stmt_cycle = $db->prepare("
                        SELECT COUNT(*) FROM personnel_cycles_assignments
                        WHERE personnel_id = :p AND cycle_id = :c AND actif = 1
                        AND date_debut <= :start_date
                        AND (date_fin IS NULL OR date_fin >= :end_date)
                    ");
                    $stmt_cycle->execute([
                        'p' => $new_enseignant_id,
                        'c' => $classe['cycle_id'],
                        'start_date' => $date_debut,
                        'end_date' => $end_date_comp
                    ]);
                    if ((int)$stmt_cycle->fetchColumn() === 0) {
                        throw new InvalidArgumentException(_("L'enseignant n'a pas d'affectation de cycle valide couvrant la période demandée pour cette classe."));
                    }
                }

                // Check overlap against other assignments (excluding current ID)
                if ($statut === 'actif' || $statut === 'suspendu') {
                    $lock_sql = "
                        SELECT * FROM affectations_pedagogiques
                        WHERE classe_id = :c AND matiere_id = :m AND annee_academique_id = :a
                        AND id != :id AND statut IN ('actif', 'suspendu')
                    ";
                    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
                        $lock_sql .= " FOR UPDATE";
                    }
                    $stmt_lock = $db->prepare($lock_sql);
                    $stmt_lock->execute([
                        'c' => $new_classe_id,
                        'm' => $new_matiere_id,
                        'a' => $old['annee_academique_id'],
                        'id' => $assignment_id
                    ]);
                    $existing_assignments = $stmt_lock->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($existing_assignments as $existing) {
                        $ex_start = $existing['date_debut'] ?? '1970-01-01';
                        $ex_end = $existing['date_fin'] ?? '2099-12-31';
                        $new_start = $date_debut;
                        $new_end = $date_fin ?: '2099-12-31';

                        if (max($new_start, $ex_start) <= min($new_end, $ex_end)) {
                            if (!$in_existing_transaction) {
                                $db->rollBack();
                            }
                            throw new InvalidArgumentException(_("Conflit de chevauchement : Un enseignant actif/suspendu est déjà affecté à ce cours sur cette période."));
                        }
                    }
                }

                $update_sql = "
                    UPDATE affectations_pedagogiques SET
                        volume_horaire_hebdo = :vol,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        statut = :statut,
                        motif_changement = :motif,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ";
                $stmt_upd = $db->prepare($update_sql);
                $stmt_upd->execute([
                    'vol' => $volume_horaire,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'statut' => $statut,
                    'motif' => $motif,
                    'id' => $assignment_id
                ]);

                if (!$in_existing_transaction) {
                    $db->commit();
                }
                return $assignment_id;
            }

        } catch (Exception $e) {
            if (!$in_existing_transaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Replace an active teacher with a new teacher without overwriting history.
     */
    public static function replaceAssignment(int $old_assignment_id, array $new_data, int $author_id): int {
        $db = Database::getInstance();
        $old_assignment = AffectationPedagogique::findById($old_assignment_id);
        if (!$old_assignment) {
            throw new InvalidArgumentException(_("Affectation d'origine introuvable."));
        }

        $effective_end_date = !empty($new_data['date_debut']) ? date('Y-m-d', strtotime($new_data['date_debut'] . ' -1 day')) : date('Y-m-d');

        $in_existing_transaction = $db->inTransaction();

        try {
            if (!$in_existing_transaction) {
                $db->beginTransaction();
            }

            // 1. Close old assignment on date before new start date
            self::updateStatus($old_assignment_id, 'termine', $new_data['motif_changement'] ?? 'Remplacement par un nouvel enseignant', $effective_end_date, $author_id);

            // 2. Create new assignment
            $new_data['classe_id'] = $old_assignment['classe_id'];
            $new_data['matiere_id'] = $old_assignment['matiere_id'];
            $new_assignment_id = self::createAssignment($new_data, $author_id);

            if (!$in_existing_transaction) {
                $db->commit();
            }
            return $new_assignment_id;

        } catch (Exception $e) {
            if (!$in_existing_transaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

}
