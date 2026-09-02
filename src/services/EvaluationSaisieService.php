<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/AffectationPedagogique.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ParamTypeEvaluation.php';

/**
 * Service métier centralisé d'autorisation et de contrôle d'accès à la saisie des notes dans SGS.
 * Source unique de vérité pour l'ensemble des parcours de saisie (directSaisie, showForm, save, selectEvaluation).
 */
class EvaluationSaisieService {

    /**
     * Normalise un format datetime (ex. ISO HTML5 'Y-m-d\TH:i' ou 'Y-m-d H:i:s') au format canonique SQL 'Y-m-d H:i:s'.
     */
    public static function normalizeDateTime(?string $dateStr): ?string {
        if (empty($dateStr)) {
            return null;
        }
        $cleanStr = str_replace('T', ' ', trim($dateStr));
        if (strlen($cleanStr) === 16 && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $cleanStr)) {
            $cleanStr .= ':00';
        }
        $timestamp = strtotime($cleanStr);
        if ($timestamp === false || date('Y-m-d H:i:s', $timestamp) === '1970-01-01 00:00:00') {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Détermine si un utilisateur/enseignant est actuellement autorisé à saisir une note
     * pour le contexte (classe, matière, séquence, type d'évaluation).
     *
     * @param int $classe_id ID de la classe
     * @param int $matiere_id ID de la matière
     * @param int $sequence_id ID de la séquence
     * @param string|int $type Code du type (ex: 'devoir', 'composition') ou ID du type dans param_type_evaluation
     * @param int|null $enseignant_id ID de l'enseignant (par défaut l'utilisateur connecté)
     * @param string|null $simulatedNow Date/heure simulée pour les tests (Y-m-d H:i:s)
     * @param int|null $lycee_id ID du lycée (par défaut Auth::getLyceeId())
     * @param bool $checkRbac Effectuer ou non les contrôles d'habilitation RBAC/affectation (défaut true)
     * @return array Structure de décision explicite
     */
    public static function canTeacherGradeContext(
        int $classe_id,
        int $matiere_id,
        int $sequence_id,
        $type = 'devoir',
        ?int $enseignant_id = null,
        ?string $simulatedNow = null,
        ?int $lycee_id = null,
        bool $checkRbac = true
    ): array {
        $db = Database::getInstance();

        // Résolution du lycée et de l'utilisateur
        $resolvedLyceeId = $lycee_id ?? Auth::getLyceeId();
        $resolvedEnseignantId = $enseignant_id ?? Auth::getUserId();

        // Heure pivot du serveur / simulation
        $rawNow = $simulatedNow ?? date('Y-m-d H:i:s');
        $nowNorm = self::normalizeDateTime($rawNow) ?? date('Y-m-d H:i:s');

        // Résolution de l'année académique active
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            return self::buildDecision(false, 'DENIED_NO_ACTIVE_YEAR', _("Aucune année académique active n'est définie."), 'context', $nowNorm, []);
        }
        $anneeId = (int)$active_year['id'];

        if (!$resolvedLyceeId) {
            $resolvedLyceeId = (int)($active_year['lycee_id'] ?? 1);
        }

        // ------------------------------------------------------------------
        // RÈGLE 0 : Résolution dynamique du type d'évaluation
        // ------------------------------------------------------------------
        $typeRecord = null;
        if (is_numeric($type)) {
            $typeRecord = ParamTypeEvaluation::findById((int)$type);
        } else {
            $typeRecord = ParamTypeEvaluation::findByCode((string)$type, $resolvedLyceeId);
        }

        if (!$typeRecord || empty($typeRecord['actif'])) {
            // Fallback: Si la table est temporairement vide, autoriser les codes historiques 'devoir' et 'composition'
            $typeStr = strtolower(trim((string)$type));
            if (!in_array($typeStr, ['devoir', 'composition'], true)) {
                return self::buildDecision(
                    false,
                    'DENIED_INVALID_TYPE',
                    sprintf(_("Type d'évaluation invalide '%s' ou non configuré pour l'établissement."), (string)$type),
                    'validation',
                    $nowNorm,
                    []
                );
            }
            $typeRecord = [
                'id' => null,
                'code' => $typeStr,
                'libelle' => ucfirst($typeStr),
                'bareme_defaut' => 20.00
            ];
        }

        $typeCode = $typeRecord['code'];
        $typeId = $typeRecord['id'];

        $context = [
            'lycee_id' => $resolvedLyceeId,
            'annee_academique_id' => $anneeId,
            'classe_id' => $classe_id,
            'matiere_id' => $matiere_id,
            'sequence_id' => $sequence_id,
            'enseignant_id' => $resolvedEnseignantId,
            'type_code' => $typeCode,
            'type_id' => $typeId
        ];

        $hasGlobalWrite = false;

        // ------------------------------------------------------------------
        // RÈGLE 1 : Habilitation RBAC
        // ------------------------------------------------------------------
        if ($checkRbac) {
            if (Auth::check()) {
                $roleId = (int)Auth::get('role_id');
                $hasGlobalWrite = in_array($roleId, [1, 2])
                    || Auth::can('manage', 'note')
                    || Auth::can('create', 'note')
                    || Auth::can('edit', 'note')
                    || Auth::can('view_all', 'note')
                    || Auth::can('manage_settings', 'evaluation');

                $hasOwnCreate = $hasGlobalWrite || Auth::can('create_own', 'note');

                if (!$hasOwnCreate) {
                    return self::buildDecision(false, 'DENIED_RBAC', _("Permissions insuffisantes pour saisir les notes."), 'rbac', $nowNorm, $context);
                }
            } else {
                $hasGlobalWrite = true;
            }

            // ------------------------------------------------------------------
            // RÈGLE 2 : Vérification de l'affectation enseignant
            // ------------------------------------------------------------------
            if (!$hasGlobalWrite && $resolvedEnseignantId) {
                $subjects_taught = User::findSubjectsTaughtByTeacher($resolvedEnseignantId);
                $is_assigned = false;
                foreach ($subjects_taught as $sub) {
                    if ((int)$sub['classe_id'] === (int)$classe_id && (int)$sub['matiere_id'] === (int)$matiere_id) {
                        $is_assigned = true;
                        break;
                    }
                }
                if (!$is_assigned && !Auth::can('view_all', 'note')) {
                    return self::buildDecision(false, 'DENIED_NOT_ASSIGNED', _("Vous n'êtes pas affecté à cette classe et matière."), 'assignment', $nowNorm, $context);
                }
            }
        }

        if (!$resolvedEnseignantId) {
            $assignments = AffectationPedagogique::findAssignmentsForClass($classe_id);
            $resolvedEnseignantId = isset($assignments[$matiere_id]['enseignant_id']) ? (int)$assignments[$matiere_id]['enseignant_id'] : null;
            $context['enseignant_id'] = $resolvedEnseignantId;
        }

        // ------------------------------------------------------------------
        // Résolution de la séquence réellement ouverte pour l'année académique active
        // ------------------------------------------------------------------
        $sequence = Sequence::findActiveForYear($resolvedLyceeId, $anneeId, $nowNorm);
        if (!$sequence) {
            return self::buildDecision(
                false,
                'DENIED_NO_OPEN_SEQUENCE',
                _("Aucune séquence n'est actuellement ouverte pour l'année académique active."),
                'sequence',
                $nowNorm,
                $context
            );
        }

        $sequence_id = (int)$sequence['id'];
        $context['sequence_id'] = $sequence_id;

        // ------------------------------------------------------------------
        // RÈGLE 3 & 4 : Recherche d'un déblocage exceptionnel (PRIORITÉ ABSOLUE)
        // ------------------------------------------------------------------
        $unlockRecord = self::findMatchingUnlock($db, $resolvedLyceeId, $anneeId, $classe_id, $matiere_id, $sequence_id, $resolvedEnseignantId, $typeCode, $typeId, $nowNorm);

        if ($unlockRecord) {
            return self::buildDecision(
                true,
                'ALLOWED_DEBLOCAGE',
                _("Saisie autorisée via un déblocage exceptionnel."),
                'deblocage',
                $nowNorm,
                $context,
                $unlockRecord,
                null
            );
        }

        // ------------------------------------------------------------------
        // RÈGLE 6 & 7 : Vérification des règles de la période normale (parametres_evaluations)
        // ------------------------------------------------------------------
        $teacherCondition = ($resolvedEnseignantId !== null)
            ? "(type = 'enseignant' AND classe_id = :classe_id_t AND matiere_id = :matiere_id_t AND enseignant_id = :enseignant_id)"
            : "(1 = 0)";

        $sqlRules = "SELECT id, type, type_evaluation, type_evaluation_id, date_ouverture_saisie, date_fermeture_saisie, commentaire,
                            (CASE
                                WHEN type = 'enseignant' THEN 5
                                WHEN type = 'classe_matiere' THEN 4
                                WHEN type = 'classe' THEN 3
                                WHEN type = 'matiere' THEN 2
                                ELSE 1
                            END) as specificity
                     FROM parametres_evaluations
                     WHERE lycee_id = :lycee_id
                     AND annee_academique_id = :annee_id
                     AND (sequence_id IS NULL OR sequence_id = :sequence_id)
                     AND (
                         type = 'global'
                         OR (type = 'classe' AND classe_id = :classe_id_c)
                         OR (type = 'matiere' AND matiere_id = :matiere_id_m)
                         OR (type = 'classe_matiere' AND classe_id = :classe_id_cm AND matiere_id = :matiere_id_cm)
                         OR {$teacherCondition}
                     )
                     ORDER BY specificity DESC";

        try {
            $stmt = $db->prepare($sqlRules);
            $params = [
                'lycee_id' => $resolvedLyceeId,
                'annee_id' => $anneeId,
                'sequence_id' => $sequence_id,
                'classe_id_c' => $classe_id,
                'matiere_id_m' => $matiere_id,
                'classe_id_cm' => $classe_id,
                'matiere_id_cm' => $matiere_id
            ];
            if ($resolvedEnseignantId !== null) {
                $params['classe_id_t'] = $classe_id;
                $params['matiere_id_t'] = $matiere_id;
                $params['enseignant_id'] = $resolvedEnseignantId;
            }
            $stmt->execute($params);
            $matching_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($matching_rules)) {
                $max_specificity = (int)$matching_rules[0]['specificity'];
                $target_rules = array_filter($matching_rules, function($r) use ($max_specificity) {
                    return (int)$r['specificity'] === $max_specificity;
                });

                // Filtrage des règles couvrant le type demandé (par ID, par code, ou scope 'tous' / NULL)
                $covering_rules = array_filter($target_rules, function($r) use ($typeCode, $typeId) {
                    if ($typeId !== null && !empty($r['type_evaluation_id']) && (int)$r['type_evaluation_id'] === (int)$typeId) {
                        return true;
                    }
                    if (!empty($r['type_evaluation']) && ($r['type_evaluation'] === $typeCode || $r['type_evaluation'] === 'tous')) {
                        return true;
                    }
                    if (empty($r['type_evaluation_id']) && (empty($r['type_evaluation']) || $r['type_evaluation'] === 'tous')) {
                        return true;
                    }
                    return false;
                });

                if (empty($covering_rules)) {
                    return self::buildDecision(
                        false,
                        'DENIED_TYPE_MISMATCH',
                        sprintf(_("La saisie pour le type '%s' n'est pas autorisée par le paramétrage de l'évaluation."), $typeCode),
                        'parametres',
                        $nowNorm,
                        $context
                    );
                }

                $tsNow = strtotime($nowNorm);
                $isFuture = false;
                $isExpired = false;
                $lastRuleChecked = null;

                foreach ($covering_rules as $rule) {
                    $startNorm = self::normalizeDateTime($rule['date_ouverture_saisie']);
                    $endNorm = self::normalizeDateTime($rule['date_fermeture_saisie']);

                    if (!$startNorm || !$endNorm) {
                        continue;
                    }

                    $tsStart = strtotime($startNorm);
                    $tsEnd = strtotime($endNorm);

                    if ($tsNow >= $tsStart && $tsNow <= $tsEnd) {
                        return self::buildDecision(
                            true,
                            'ALLOWED_PERIOD',
                            _("Saisie autorisée dans la période normale d'évaluation."),
                            'parametres',
                            $nowNorm,
                            $context,
                            null,
                            $rule
                        );
                    }

                    if ($tsNow < $tsStart) {
                        $isFuture = true;
                    }
                    if ($tsNow > $tsEnd) {
                        $isExpired = true;
                    }
                    $lastRuleChecked = $rule;
                }

                if ($isFuture && !$isExpired) {
                    return self::buildDecision(
                        false,
                        'DENIED_PERIOD_NOT_STARTED',
                        _("La période de saisie des notes n'a pas encore commencé."),
                        'parametres',
                        $nowNorm,
                        $context,
                        null,
                        $lastRuleChecked
                    );
                }

                return self::buildDecision(
                    false,
                    'DENIED_PERIOD_EXPIRED',
                    _("La période de saisie des notes est fermée ou expirée."),
                    'parametres',
                    $nowNorm,
                    $context,
                    null,
                    $lastRuleChecked
                );
            }
        } catch (PDOException $e) {
            error_log("Error in EvaluationSaisieService::canTeacherGradeContext Level 3: " . $e->getMessage());
        }

        // RÈGLE 8 : Fallback
        try {
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM parametres_evaluations WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
            $stmtCount->execute([
                'lycee_id' => $resolvedLyceeId,
                'annee_id' => $anneeId
            ]);
            $hasAnyRules = ((int)$stmtCount->fetchColumn()) > 0;

            if ($hasAnyRules) {
                return self::buildDecision(
                    false,
                    'DENIED_POLICY_RESTRICTED',
                    _("Aucun paramétrage de saisie ne couvre cette évaluation dans l'établissement."),
                    'fallback',
                    $nowNorm,
                    $context
                );
            }
        } catch (PDOException $e) {
            error_log("Error in EvaluationSaisieService::canTeacherGradeContext Level 4: " . $e->getMessage());
        }

        return self::buildDecision(
            true,
            'ALLOWED_DEFAULT_FALLBACK',
            _("Aucun paramétrage restrictif configuré. Saisie autorisée par défaut."),
            'fallback',
            $nowNorm,
            $context
        );
    }

    /**
     * Renvoie la liste dynamique des types d'évaluation autorisés pour un contexte donné.
     *
     * @return array Liste des codes de types autorisés (ex: ['devoir', 'composition', 'interrogation'])
     */
    public static function getAllowedEvaluationTypes(
        int $classe_id,
        int $matiere_id,
        int $sequence_id,
        ?int $enseignant_id = null,
        ?string $simulatedNow = null,
        ?int $lycee_id = null,
        bool $checkRbac = true
    ): array {
        $resolvedLyceeId = $lycee_id ?? Auth::getLyceeId();
        $activeTypes = ParamTypeEvaluation::findActive($resolvedLyceeId);

        if (empty($activeTypes)) {
            $activeTypes = [
                ['id' => null, 'code' => 'devoir', 'libelle' => 'Devoir'],
                ['id' => null, 'code' => 'composition', 'libelle' => 'Composition']
            ];
        }

        $allowed = [];
        foreach ($activeTypes as $typeRec) {
            $code = $typeRec['code'];
            $decision = self::canTeacherGradeContext($classe_id, $matiere_id, $sequence_id, $code, $enseignant_id, $simulatedNow, $resolvedLyceeId, $checkRbac);
            if ($decision['allowed']) {
                $allowed[] = $code;
            }
        }
        return $allowed;
    }

    /**
     * Façade raccourcie renvoyant uniquement un booléen (autorisé ou non).
     */
    public static function isAllowed(
        int $classe_id,
        int $matiere_id,
        int $sequence_id,
        $type = 'devoir',
        ?int $enseignant_id = null,
        ?string $simulatedNow = null,
        ?int $lycee_id = null
    ): bool {
        $decision = self::canTeacherGradeContext($classe_id, $matiere_id, $sequence_id, $type, $enseignant_id, $simulatedNow, $lycee_id, false);
        return $decision['allowed'];
    }

    /**
     * Recherche un déblocage exceptionnel actif pour le contexte et la date courante.
     */
    private static function findMatchingUnlock(
        PDO $db,
        int $lyceeId,
        int $anneeId,
        int $classeId,
        int $matiereId,
        int $sequenceId,
        ?int $enseignantId,
        string $typeCode,
        ?int $typeId,
        string $nowNorm
    ): ?array {
        $teacherCond = ($enseignantId !== null)
            ? "(type = 'enseignant' AND classe_id = :classe_id_t AND matiere_id = :matiere_id_t AND enseignant_id = :enseignant_id)"
            : "(1 = 0)";

        $typeCond = ($typeId !== null)
            ? "(type_evaluation_id = :type_id OR type_evaluation = :type_code OR type_evaluation = 'tous' OR (type_evaluation_id IS NULL AND (type_evaluation IS NULL OR type_evaluation = 'tous')))"
            : "(type_evaluation = :type_code OR type_evaluation = 'tous' OR type_evaluation IS NULL)";

        $sql = "SELECT * FROM deblocages_notes
                WHERE lycee_id = :lycee_id
                AND annee_academique_id = :annee_id
                AND {$typeCond}
                AND (sequence_id IS NULL OR sequence_id = :sequence_id_unlock)
                AND (
                    type = 'global'
                    OR (type = 'classe' AND classe_id = :classe_id_c)
                    OR (type = 'matiere' AND matiere_id = :matiere_id_m)
                    OR (type = 'classe_matiere' AND classe_id = :classe_id_cm AND matiere_id = :matiere_id_cm)
                    OR {$teacherCond}
                )";

        try {
            $stmt = $db->prepare($sql);
            $params = [
                'lycee_id' => $lyceeId,
                'annee_id' => $anneeId,
                'type_code' => $typeCode,
                'sequence_id_unlock' => $sequenceId,
                'classe_id_c' => $classeId,
                'matiere_id_m' => $matiereId,
                'classe_id_cm' => $classeId,
                'matiere_id_cm' => $matiereId
            ];
            if ($typeId !== null) {
                $params['type_id'] = $typeId;
            }
            if ($enseignantId !== null) {
                $params['classe_id_t'] = $classeId;
                $params['matiere_id_t'] = $matiereId;
                $params['enseignant_id'] = $enseignantId;
            }
            $stmt->execute($params);
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $tsNow = strtotime($nowNorm);

            foreach ($candidates as $candidate) {
                $startNorm = self::normalizeDateTime($candidate['date_debut']);
                $endNorm = self::normalizeDateTime($candidate['date_fin']);

                if (!$startNorm || !$endNorm) {
                    continue;
                }

                $tsStart = strtotime($startNorm);
                $tsEnd = strtotime($endNorm);

                if ($tsNow >= $tsStart && $tsNow <= $tsEnd) {
                    return $candidate;
                }
            }
        } catch (PDOException $e) {
            error_log("Error in EvaluationSaisieService::findMatchingUnlock: " . $e->getMessage());
        }

        return null;
    }

    private static function buildDecision(
        bool $allowed,
        string $code,
        string $reason,
        string $source,
        string $now,
        array $context,
        ?array $deblocage = null,
        ?array $periode = null
    ): array {
        return [
            'allowed'   => $allowed,
            'code'      => $code,
            'reason'    => $reason,
            'source'    => $source,
            'now'       => $now,
            'context'   => $context,
            'deblocage' => $deblocage,
            'periode'   => $periode
        ];
    }
}
?>