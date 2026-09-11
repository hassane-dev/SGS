<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/AuthorizationScopeService.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class BulletinValidationService {

    /**
     * Resolves the list of target classes according to scope_type, scope_value, multi-tenant isolation, and RBAC cycles.
     */
    public static function resolveClassesForScope(int $lycee_id, string $scope_type, $scope_value): array {
        $db = Database::getInstance();
        $authorized_cycles = AuthorizationScopeService::getAuthorizedCycleIds();

        if (empty($authorized_cycles)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($authorized_cycles), '?'));

        if ($scope_type === 'cycle') {
            $cycle_id = (int)$scope_value;
            if (!in_array($cycle_id, array_map('intval', $authorized_cycles))) {
                throw new InvalidArgumentException("Vous n'avez pas l'accès autorisé à ce cycle.");
            }
            $sql = "
                SELECT c.*, cy.nom_cycle
                FROM classes c
                JOIN cycles cy ON c.cycle_id = cy.id_cycle
                WHERE c.lycee_id = ? AND c.cycle_id = ? AND c.cycle_id IN ({$placeholders})
                ORDER BY c.niveau ASC, c.serie ASC, c.numero ASC
            ";
            $params = array_merge([$lycee_id, $cycle_id], $authorized_cycles);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($scope_type === 'niveau') {
            $niveau = trim((string)$scope_value);
            $sql = "
                SELECT c.*, cy.nom_cycle
                FROM classes c
                JOIN cycles cy ON c.cycle_id = cy.id_cycle
                WHERE c.lycee_id = ? AND c.niveau = ? AND c.cycle_id IN ({$placeholders})
                ORDER BY c.serie ASC, c.numero ASC
            ";
            $params = array_merge([$lycee_id, $niveau], $authorized_cycles);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($scope_type === 'classe') {
            $classe_id = (int)$scope_value;
            $sql = "
                SELECT c.*, cy.nom_cycle
                FROM classes c
                JOIN cycles cy ON c.cycle_id = cy.id_cycle
                WHERE c.lycee_id = ? AND c.id_classe = ? AND c.cycle_id IN ({$placeholders})
            ";
            $params = array_merge([$lycee_id, $classe_id], $authorized_cycles);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            throw new InvalidArgumentException("Type de périmètre invalide. Choisir 'cycle', 'niveau' ou 'classe'.");
        }
    }

    /**
     * Analyses and generates the validation summary for a target scope.
     */
    public static function getValidationSummary(int $lycee_id, int $sequence_id, string $scope_type, $scope_value): array {
        $db = Database::getInstance();

        // 1. Fetch sequence and verify status
        $stmtSeq = $db->prepare("SELECT * FROM sequences WHERE id = :id AND (lycee_id = :lycee_id OR lycee_id IS NULL)");
        $stmtSeq->execute(['id' => $sequence_id, 'lycee_id' => $lycee_id]);
        $sequence = $stmtSeq->fetch(PDO::FETCH_ASSOC);

        if (!$sequence) {
            throw new InvalidArgumentException("Séquence introuvable.");
        }

        $is_closed = ($sequence['statut'] === 'fermee');

        // 2. Resolve target classes
        $classes = self::resolveClassesForScope($lycee_id, $scope_type, $scope_value);
        if (empty($classes)) {
            throw new InvalidArgumentException("Aucune classe trouvée pour le périmètre sélectionné.");
        }

        $activeYear = AnneeAcademique::findActive();
        $annee_id = $activeYear ? (int)$activeYear['id'] : (int)$sequence['annee_academique_id'];

        $totalStudents = 0;
        $provisoireList = [];
        $valideList = [];
        $publieList = [];
        $bloqueList = [];
        $bloqueDetails = [];

        foreach ($classes as $cls) {
            $classe_id = (int)$cls['id_classe'];
            $nom_classe = Classe::getFormattedName($cls);

            // Fetch mandatory subjects for class
            $stmtSubj = $db->prepare("
                SELECT cm.matiere_id, m.nom_matiere
                FROM classe_matieres cm
                JOIN matieres m ON cm.matiere_id = m.id_matiere
                WHERE cm.classe_id = :classe_id
            ");
            $stmtSubj->execute(['classe_id' => $classe_id]);
            $classSubjects = $stmtSubj->fetchAll(PDO::FETCH_ASSOC);
            $requiredSubjectIds = array_column($classSubjects, 'matiere_id');
            $subjectNameMap = array_column($classSubjects, 'nom_matiere', 'matiere_id');

            // Fetch active students in class
            $stmtStudents = $db->prepare("
                SELECT e.id_eleve, e.nom, e.prenom, COALESCE(e.identifiant_public, '') AS matricule
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                WHERE et.classe_id = :classe_id
                  AND et.annee_academique_id = :annee_id
                  AND (et.is_active = 1 OR et.status = 'active')
                ORDER BY e.nom ASC, e.prenom ASC
            ");
            $stmtStudents->execute(['classe_id' => $classe_id, 'annee_id' => $annee_id]);
            $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

            foreach ($students as $stu) {
                $eleve_id = (int)$stu['id_eleve'];
                $totalStudents++;

                // Fetch student's evaluations for this sequence
                $stmtEvals = $db->prepare("
                    SELECT DISTINCT matiere_id
                    FROM evaluations
                    WHERE eleve_id = :eleve_id AND sequence_id = :sequence_id
                ");
                $stmtEvals->execute(['eleve_id' => $eleve_id, 'sequence_id' => $sequence_id]);
                $assessedSubjectIds = array_map('intval', $stmtEvals->fetchAll(PDO::FETCH_COLUMN));

                // Check completeness
                $missingSubjectIds = array_diff($requiredSubjectIds, $assessedSubjectIds);

                // Fetch bulletin record
                $stmtBul = $db->prepare("SELECT * FROM bulletins WHERE eleve_id = :eleve_id AND sequence_id = :sequence_id");
                $stmtBul->execute(['eleve_id' => $eleve_id, 'sequence_id' => $sequence_id]);
                $bulletin = $stmtBul->fetch(PDO::FETCH_ASSOC);

                $currentStatus = $bulletin['statut'] ?? 'provisoire';

                if (!empty($missingSubjectIds) || empty($assessedSubjectIds)) {
                    // Incomplete bulletin
                    $missingNames = [];
                    foreach ($missingSubjectIds as $mId) {
                        $missingNames[] = $subjectNameMap[$mId] ?? "Matière #{$mId}";
                    }

                    $reason = empty($assessedSubjectIds)
                        ? "Aucune évaluation enregistrée pour cette séquence."
                        : "Matières obligatoires non évaluées : " . implode(', ', $missingNames);

                    $bloqueList[] = $eleve_id;
                    $bloqueDetails[] = [
                        'eleve_id' => $eleve_id,
                        'nom_complet' => $stu['prenom'] . ' ' . $stu['nom'],
                        'matricule' => $stu['matricule'] ?? '',
                        'classe' => $nom_classe,
                        'raison' => $reason
                    ];
                } else {
                    // Complete bulletin
                    if ($currentStatus === 'publie') {
                        $publieList[] = $eleve_id;
                    } elseif ($currentStatus === 'valide') {
                        $valideList[] = $eleve_id;
                    } else {
                        $provisoireList[] = $eleve_id;
                    }
                }
            }
        }

        // Scope human-readable label
        $scope_label = '';
        if ($scope_type === 'cycle') {
            $cy = Cycle::findById((int)$scope_value);
            $scope_label = "Cycle : " . ($cy['nom_cycle'] ?? $scope_value);
        } elseif ($scope_type === 'niveau') {
            $scope_label = "Niveau : " . $scope_value;
        } elseif ($scope_type === 'classe') {
            $cls = Classe::findById((int)$scope_value);
            $scope_label = "Classe : " . ($cls ? Classe::getFormattedName($cls) : $scope_value);
        }

        return [
            'success' => true,
            'sequence' => $sequence,
            'is_sequence_closed' => $is_closed,
            'scope_type' => $scope_type,
            'scope_value' => $scope_value,
            'scope_label' => $scope_label,
            'classes_count' => count($classes),
            'classes' => array_map(function($c) { return ['id' => $c['id_classe'], 'nom' => Classe::getFormattedName($c)]; }, $classes),
            'students_count' => $totalStudents,
            'provisoire_count' => count($provisoireList),
            'provisoire_ids' => $provisoireList,
            'valide_count' => count($valideList),
            'publie_count' => count($publieList),
            'bloque_count' => count($bloqueList),
            'bloque_details' => $bloqueDetails
        ];
    }

    /**
     * Executes bulk institutional validation for all eligible provisional report cards in target scope.
     */
    public static function executeValidation(int $lycee_id, int $sequence_id, string $scope_type, $scope_value, int $user_id): array {
        $db = Database::getInstance();

        // 1. Run summary check
        $summary = self::getValidationSummary($lycee_id, $sequence_id, $scope_type, $scope_value);

        // 2. Strict Guards
        if (!$summary['is_sequence_closed']) {
            throw new LogicException("La séquence '{$summary['sequence']['nom']}' est actuellement ouverte. Vous devez procéder à sa clôture officielle avant de pouvoir exécuter la validation institutionnelle des bulletins.");
        }

        if ($summary['bloque_count'] > 0) {
            throw new LogicException(sprintf(
                "Validation refusée : %d bulletin(s) sur ce périmètre sont incomplets ou comportent des matières sans évaluation. La validation silencieuse de bulletins incomplets est stricement interdite.",
                $summary['bloque_count']
            ));
        }

        if ($summary['provisoire_count'] === 0) {
            if ($summary['valide_count'] > 0 || $summary['publie_count'] > 0) {
                return [
                    'success' => true,
                    'validated_count' => 0,
                    'message' => "Tous les bulletins de ce périmètre sont déjà validés ou publiés."
                ];
            }
            throw new LogicException("Aucun bulletin au statut 'provisoire' n'a été trouvé dans ce périmètre à valider.");
        }

        $provisoireIds = $summary['provisoire_ids'];

        // 3. Atomic Transaction
        try {
            $db->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($provisoireIds), '?'));
            $sql = "
                UPDATE bulletins
                SET statut = 'valide',
                    valide_le = CURRENT_TIMESTAMP,
                    valide_par = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE sequence_id = ?
                  AND eleve_id IN ({$placeholders})
                  AND statut = 'provisoire'
            ";

            $params = array_merge([$user_id, $sequence_id], $provisoireIds);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $affectedRows = $stmt->rowCount();

            $db->commit();

            return [
                'success' => true,
                'validated_count' => $affectedRows,
                'message' => sprintf(_("%d bulletin(s) au statut 'provisoire' ont été officiellement validés avec succès pour le périmètre '%s'."), $affectedRows, $summary['scope_label'])
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error in BulletinValidationService::executeValidation: " . $e->getMessage());
            throw $e;
        }
    }
}
?>