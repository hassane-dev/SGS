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
        try {
            $db->beginTransaction();

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
                            $db->rollBack();
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
            $db->commit();

            return $assignment_id;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
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
}
