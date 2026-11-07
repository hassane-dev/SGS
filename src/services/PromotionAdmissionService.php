<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../services/PromotionThresholdService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../services/EvaluationCalculationService.php';

/**
 * Service métier de calcul des décisions d'admission et de promotion des élèves.
 *
 * Exploite le référentiel hiérarchique param_promotion_seuils (via PromotionThresholdService)
 * et les résultats pédagogiques officiels consolidés (bulletins fermés/validés)
 * pour déterminer individuellement la décision d'admission de chaque élève dans un
 * contexte académique strictement isolé (Lycée + Année Académique + Classe).
 */
class PromotionAdmissionService {

    /**
     * Calcule les décisions d'admission pour l'ensemble des élèves d'une classe.
     *
     * @param int $lyceeId Identifiant de l'établissement
     * @param int $anneeId Identifiant de l'année académique
     * @param int $classeId Identifiant de la classe
     * @param array $options Options de calcul (ex: note_eliminatoire, autoriser_provisoire, check_scope)
     * @return array [
     *   'success' => bool,
     *   'lycee_id' => int,
     *   'annee_id' => int,
     *   'classe_id' => int,
     *   'classe_libelle' => string,
     *   'cycle_id' => int,
     *   'cycle_nom' => string,
     *   'niveau' => string,
     *   'serie' => string|null,
     *   'thresholds' => array,
     *   'total_eleves' => int,
     *   'decisions' => array, // Liste des décisions individuelles par élève
     *   'summary' => array // Comptages par décision (admis, deliberation, redoublement, exclu, etc.)
     * ]
     * @throws InvalidArgumentException|LogicException
     */
    public static function calculateClassDecisions(int $lyceeId, int $anneeId, int $classeId, array $options = []): array {
        $context = self::validateAndResolveContext($lyceeId, $anneeId, $classeId, $options);
        $classe = $context['classe'];
        $thresholds = $context['thresholds'];

        $students = self::getClassStudents($lyceeId, $anneeId, $classeId);

        $decisions = [];
        $summary = [
            'admis' => 0,
            'deliberation' => 0,
            'redoublement' => 0,
            'exclu' => 0,
            'non_determine' => 0
        ];

        foreach ($students as $student) {
            $eleveId = (int)$student['id_eleve'];
            $studentDecision = self::calculateStudentDecisionInternal(
                $lyceeId,
                $anneeId,
                $classeId,
                $eleveId,
                $classe,
                $thresholds,
                $student,
                $options
            );

            $decisions[] = $studentDecision;

            $status = $studentDecision['decision'] ?? 'non_determine';
            if (isset($summary[$status])) {
                $summary[$status]++;
            } else {
                $summary['non_determine']++;
            }
        }

        return [
            'success' => true,
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId,
            'classe_id' => $classeId,
            'classe_libelle' => Classe::getFormattedName($classe),
            'cycle_id' => (int)$classe['cycle_id'],
            'cycle_nom' => $classe['nom_cycle'] ?? '',
            'niveau' => (string)$classe['niveau'],
            'serie' => !empty($classe['serie']) ? (string)$classe['serie'] : null,
            'thresholds' => $thresholds,
            'total_eleves' => count($students),
            'decisions' => $decisions,
            'summary' => $summary
        ];
    }

    /**
     * Calcule ou détermine la décision d'admission d'un élève individuel.
     *
     * @param int $lyceeId Identifiant de l'établissement
     * @param int $anneeId Identifiant de l'année académique
     * @param int $classeId Identifiant de la classe
     * @param int $eleveId Identifiant de l'élève
     * @param array $options Options complémentaires
     * @return array Structure complète de la décision individuelle avec justification
     * @throws InvalidArgumentException|LogicException
     */
    public static function calculateStudentDecision(int $lyceeId, int $anneeId, int $classeId, int $eleveId, array $options = []): array {
        $context = self::validateAndResolveContext($lyceeId, $anneeId, $classeId, $options);
        $classe = $context['classe'];
        $thresholds = $context['thresholds'];

        $student = self::getStudentInClass($lyceeId, $anneeId, $classeId, $eleveId);
        if (!$student) {
            throw new InvalidArgumentException(sprintf(
                _("L'élève #%d n'appartient pas à la classe #%d pour l'année académique #%d dans l'établissement #%d."),
                $eleveId, $classeId, $anneeId, $lyceeId
            ));
        }

        return self::calculateStudentDecisionInternal(
            $lyceeId,
            $anneeId,
            $classeId,
            $eleveId,
            $classe,
            $thresholds,
            $student,
            $options
        );
    }

    /**
     * Résout et valide le contexte académique strict (Établissement, Année, Classe, Cycle, Seuils).
     */
    public static function validateAndResolveContext(int $lyceeId, int $anneeId, int $classeId, array $options = []): array {
        if ($lyceeId <= 0) {
            throw new InvalidArgumentException(_("Identifiant d'établissement invalide."));
        }
        if ($anneeId <= 0) {
            throw new InvalidArgumentException(_("Identifiant d'année académique invalide."));
        }
        if ($classeId <= 0) {
            throw new InvalidArgumentException(_("Identifiant de classe invalide."));
        }

        $checkScope = $options['check_scope'] ?? false;
        if ($checkScope) {
            if (!AuthorizationScopeService::canAccessLycee($lyceeId)) {
                throw new LogicException(_("Accès refusé pour cet établissement (hors périmètre autorisé)."));
            }
        }

        // 1. Vérification de la classe et de son établissement
        $classe = Classe::findById($classeId);
        if (!$classe) {
            throw new InvalidArgumentException(_("Classe introuvable."));
        }
        if ((int)$classe['lycee_id'] !== $lyceeId) {
            throw new InvalidArgumentException(_("Violation de cloisonnement : la classe n'appartient pas à cet établissement."));
        }

        if (empty($classe['cycle_id'])) {
            throw new InvalidArgumentException(_("La classe sélectionnée n'est associée à aucun cycle d'enseignement."));
        }

        $cycleId = (int)$classe['cycle_id'];
        if ($checkScope && !AuthorizationScopeService::canAccessCycle($cycleId)) {
            throw new LogicException(_("Accès refusé pour ce cycle d'enseignement (hors périmètre autorisé)."));
        }

        $cycle = Cycle::findById($cycleId);
        if ($cycle) {
            $classe['nom_cycle'] = $cycle['nom_cycle'];
        }

        // 2. Vérification de l'année académique
        $db = Database::getInstance();
        $stmtAnnee = $db->prepare("SELECT * FROM annees_academiques WHERE id = :id");
        $stmtAnnee->execute(['id' => $anneeId]);
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) {
            throw new InvalidArgumentException(_("Année académique introuvable."));
        }

        // 3. Résolution hiérarchique du seuil de promotion via PromotionThresholdService
        $niveau = (string)$classe['niveau'];
        $serie = !empty($classe['serie']) ? (string)$classe['serie'] : null;

        $thresholds = PromotionThresholdService::resolveThresholds($lyceeId, $cycleId, $niveau, $serie);

        if ($thresholds['status'] !== 'configured') {
            throw new LogicException(sprintf(
                _("Calcul d'admission bloqué : aucun seuil applicable n'est configuré pour l'établissement #%d (Cycle #%d, Niveau '%s'%s). Un paramétrage explicite des seuils est requis."),
                $lyceeId, $cycleId, $niveau, $serie ? " Série '{$serie}'" : ""
            ));
        }

        return [
            'classe' => $classe,
            'annee' => $annee,
            'thresholds' => $thresholds
        ];
    }

    /**
     * Récupère les élèves actifs inscrits dans une classe pour une année donnée.
     */
    public static function getClassStudents(int $lyceeId, int $anneeId, int $classeId): array {
        $db = Database::getInstance();
        $sql = "
            SELECT e.id_eleve, e.nom, e.prenom, COALESCE(e.identifiant_public, '') AS matricule,
                   e.statut AS statut_eleve, et.id_etude, et.status AS statut_etude, et.is_active
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE et.classe_id = :classe_id
              AND et.annee_academique_id = :annee_id
              AND et.lycee_id = :lycee_id
              AND (et.is_active = 1 OR et.status = 'active')
              AND e.statut = 'actif'
            ORDER BY e.nom ASC, e.prenom ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'classe_id' => $classeId,
            'annee_id' => $anneeId,
            'lycee_id' => $lyceeId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Vérifie et récupère un élève dans une classe et une année données.
     */
    public static function getStudentInClass(int $lyceeId, int $anneeId, int $classeId, int $eleveId): ?array {
        $db = Database::getInstance();
        $sql = "
            SELECT e.id_eleve, e.nom, e.prenom, COALESCE(e.identifiant_public, '') AS matricule,
                   e.statut AS statut_eleve, et.id_etude, et.status AS statut_etude, et.is_active
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE et.classe_id = :classe_id
              AND et.annee_academique_id = :annee_id
              AND et.lycee_id = :lycee_id
              AND e.id_eleve = :eleve_id
            LIMIT 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'classe_id' => $classeId,
            'annee_id' => $anneeId,
            'lycee_id' => $lyceeId,
            'eleve_id' => $eleveId
        ]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Récupère les résultats scolaires officiels de l'élève pour l'année académique.
     *
     * Consomme les bulletins officiels de la table `bulletins` (séquences fermées/validées)
     * et les moyennes par matière dans `bulletin_details`.
     *
     * @param int $lyceeId
     * @param int $anneeId
     * @param int $classeId
     * @param int $eleveId
     * @param array $options
     * @return array
     */
    public static function getAcademicAnnualResults(int $lyceeId, int $anneeId, int $classeId, int $eleveId, array $options = []): array {
        $db = Database::getInstance();

        // 1. Récupération des séquences attendues pour l'établissement et l'année
        $stmtSeq = $db->prepare("
            SELECT id, nom, type, statut, date_debut, date_fin
            FROM sequences
            WHERE lycee_id = :lycee_id
              AND annee_academique_id = :annee_id
            ORDER BY date_debut ASC, id ASC
        ");
        $stmtSeq->execute(['lycee_id' => $lyceeId, 'annee_id' => $anneeId]);
        $expectedSequences = $stmtSeq->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($expectedSequences)) {
            return [
                'is_complete' => false,
                'incomplete_reason' => _("Aucune séquence académique configurée pour cette année et cet établissement."),
                'sequences' => [],
                'annual_average' => null,
                'matieres' => []
            ];
        }

        // 2. Récupération des bulletins existants pour l'élève sur ces séquences
        $sqlBul = "
            SELECT b.id AS bulletin_id, b.sequence_id, b.moyenne_generale, b.statut,
                   s.nom AS sequence_nom, s.statut AS sequence_statut
            FROM bulletins b
            JOIN sequences s ON b.sequence_id = s.id
            WHERE b.eleve_id = :eleve_id
              AND b.annee_academique_id = :annee_id
              AND b.classe_id = :classe_id
              AND b.lycee_id = :lycee_id
        ";
        $stmtBul = $db->prepare($sqlBul);
        $stmtBul->execute([
            'eleve_id' => $eleveId,
            'annee_id' => $anneeId,
            'classe_id' => $classeId,
            'lycee_id' => $lyceeId
        ]);
        $bulletinsRows = $stmtBul->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $bulletinBySeq = [];
        foreach ($bulletinsRows as $b) {
            $bulletinBySeq[(int)$b['sequence_id']] = $b;
        }

        // 3. Vérification de complétude des séquences
        $requireClosedSequences = !isset($options['exiger_cloture_sequences']) || (bool)$options['exiger_cloture_sequences'];
        $requireValidatedBulletins = $options['exiger_bulletins_valides'] ?? false;

        $sequenceAverages = [];
        $bulletinIds = [];
        $incompleteReasons = [];

        foreach ($expectedSequences as $seq) {
            $sId = (int)$seq['id'];
            $sNom = $seq['nom'];

            if ($requireClosedSequences && $seq['statut'] !== 'fermee') {
                $incompleteReasons[] = sprintf(_("La séquence '%s' n'est pas encore clôturée (statut: %s)."), $sNom, $seq['statut']);
            }

            if (!isset($bulletinBySeq[$sId])) {
                $incompleteReasons[] = sprintf(_("Aucun bulletin officiel trouvé pour la séquence '%s'."), $sNom);
                continue;
            }

            $b = $bulletinBySeq[$sId];
            if ($requireValidatedBulletins && !in_array($b['statut'], ['valide', 'publie'], true)) {
                $incompleteReasons[] = sprintf(_("Le bulletin de la séquence '%s' est en statut '%s' (non validé)."), $sNom, $b['statut']);
            }

            if ($b['moyenne_generale'] === null) {
                $incompleteReasons[] = sprintf(_("La moyenne générale du bulletin pour '%s' n'est pas renseignée."), $sNom);
            } else {
                $sequenceAverages[] = (float)$b['moyenne_generale'];
                $bulletinIds[] = (int)$b['bulletin_id'];
            }
        }

        $isComplete = empty($incompleteReasons);

        // 4. Calcul de la moyenne annuelle consolidée
        $annualAverage = null;
        if (!empty($sequenceAverages)) {
            $annualAverage = round(array_sum($sequenceAverages) / count($sequenceAverages), 2);
        }

        // 5. Récupération des moyennes par matière à travers l'année (bulletin_details)
        $matieresSummary = [];
        if (!empty($bulletinIds)) {
            $inClause = implode(',', array_map('intval', $bulletinIds));
            $sqlDet = "
                SELECT bd.matiere_id, bd.nom_matiere_snapshot AS nom_matiere,
                       bd.moyenne_matiere, bd.coefficient_snapshot AS coefficient
                FROM bulletin_details bd
                WHERE bd.bulletin_id IN ($inClause)
                ORDER BY bd.matiere_id ASC
            ";
            $stmtDet = $db->query($sqlDet);
            $details = $stmtDet->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $groupedMatieres = [];
            foreach ($details as $d) {
                $mId = (int)$d['matiere_id'];
                if (!isset($groupedMatieres[$mId])) {
                    $groupedMatieres[$mId] = [
                        'matiere_id' => $mId,
                        'nom_matiere' => $d['nom_matiere'],
                        'scores' => [],
                        'coefficients' => []
                    ];
                }
                $groupedMatieres[$mId]['scores'][] = (float)$d['moyenne_matiere'];
                $groupedMatieres[$mId]['coefficients'][] = (float)$d['coefficient'];
            }

            foreach ($groupedMatieres as $mId => $mData) {
                $scores = $mData['scores'];
                $mAvg = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0.0;
                $matieresSummary[$mId] = [
                    'matiere_id' => $mId,
                    'nom_matiere' => $mData['nom_matiere'],
                    'moyenne_annuelle' => $mAvg,
                    'nombre_evaluations' => count($scores),
                    'coefficient' => !empty($mData['coefficients']) ? $mData['coefficients'][0] : 1.0
                ];
            }
        }

        return [
            'is_complete' => $isComplete,
            'incomplete_reason' => $isComplete ? null : implode(' ', $incompleteReasons),
            'expected_sequences_count' => count($expectedSequences),
            'recorded_bulletins_count' => count($sequenceAverages),
            'annual_average' => $annualAverage,
            'sequences_data' => $bulletinBySeq,
            'matieres' => $matieresSummary
        ];
    }

    /**
     * Applique les règles de décision en comparant la moyenne et les résultats aux seuils configurés.
     *
     * @param float|null $moyenne Moyenne annuelle de l'élève
     * @param array $thresholds Seuils résolus via PromotionThresholdService
     * @param array $academicResults Résultats académiques détaillés (incluant matieres)
     * @param array $options Options (ex: note_eliminatoire_seuil, matieres_eliminatoires)
     * @return array [
     *   'decision' => 'admis'|'deliberation'|'redoublement'|'exclu'|'non_determine',
     *   'decision_libelle' => string,
     *   'justification' => string,
     *   'rules_applied' => array
     * ]
     */
    public static function applyDecisionRules(?float $moyenne, array $thresholds, array $academicResults = [], array $options = []): array {
        if ($moyenne === null) {
            return [
                'decision' => 'non_determine',
                'decision_libelle' => _('Non déterminé'),
                'justification' => _("Impossible de déterminer la décision d'admission : résultats scolaires manquants ou incomplets."),
                'rules_applied' => [
                    'incomplete_results' => true
                ]
            ];
        }

        $seuilAdmission = (float)$thresholds['seuil_admission_directe'];
        $seuilDelibMin  = (float)$thresholds['seuil_deliberation_min'];
        $seuilDelibMax  = (float)$thresholds['seuil_deliberation_max'];
        $seuilRedoubl   = (float)$thresholds['seuil_redoublement_max'];
        $seuilExclusion = $thresholds['seuil_exclusion_max'] !== null ? (float)$thresholds['seuil_exclusion_max'] : null;

        $rulesApplied = [
            'seuil_admission_directe' => $seuilAdmission,
            'seuil_deliberation_min'  => $seuilDelibMin,
            'seuil_deliberation_max'  => $seuilDelibMax,
            'seuil_redoublement_max'  => $seuilRedoubl,
            'seuil_exclusion_max'     => $seuilExclusion,
            'portee_regle'            => $thresholds['portee_type'] ?? 'inconnue',
            'note_eliminatoire_active' => false
        ];

        // 1. Contrôle des notes éliminatoires configurées
        // Une note éliminatoire peut être spécifiée globalement dans $options['note_eliminatoire_seuil'] (ex: 5.00)
        // ou pour des matières spécifiques dans $options['matieres_eliminatoires'] = [matiere_id => seuil]
        $hasEliminatoryGrade = false;
        $eliminatorySubjectDetails = [];

        $globalElimSeuil = isset($options['note_eliminatoire_seuil']) && $options['note_eliminatoire_seuil'] !== null && $options['note_eliminatoire_seuil'] !== ''
            ? (float)$options['note_eliminatoire_seuil']
            : null;

        $matieresElim = $options['matieres_eliminatoires'] ?? []; // [matiere_id => min_note]

        if (!empty($academicResults['matieres'])) {
            foreach ($academicResults['matieres'] as $mId => $mInfo) {
                $mScore = (float)$mInfo['moyenne_annuelle'];
                $mNom = $mInfo['nom_matiere'];

                // Vérifier seuil éliminatoire spécifique à la matière
                if (isset($matieresElim[$mId])) {
                    $minReq = (float)$matieresElim[$mId];
                    if ($mScore < $minReq) {
                        $hasEliminatoryGrade = true;
                        $eliminatorySubjectDetails[] = sprintf(_("%s (%.2f < seuil éliminatoire %.2f)"), $mNom, $mScore, $minReq);
                    }
                } elseif ($globalElimSeuil !== null) {
                    // Vérifier seuil éliminatoire global
                    if ($mScore < $globalElimSeuil) {
                        $hasEliminatoryGrade = true;
                        $eliminatorySubjectDetails[] = sprintf(_("%s (%.2f < seuil éliminatoire %.2f)"), $mNom, $mScore, $globalElimSeuil);
                    }
                }
            }
        }

        if ($hasEliminatoryGrade) {
            $rulesApplied['note_eliminatoire_active'] = true;
            $rulesApplied['matieres_eliminatoires_detectees'] = $eliminatorySubjectDetails;
        }

        // 2. Détermination de la décision
        if ($seuilExclusion !== null && $moyenne <= $seuilExclusion) {
            $decision = 'exclu';
            $libelle = _('Exclusion');
            $justification = sprintf(
                _("Moyenne annuelle de %.2f/20 inférieure ou égale au seuil d'exclusion (%.2f). Exclusion prononcée."),
                $moyenne, $seuilExclusion
            );
        } elseif ($moyenne >= $seuilAdmission) {
            if ($hasEliminatoryGrade) {
                // Présence d'une note éliminatoire : l'élève ne peut bénéficier de l'admission directe automatique
                // Il est basculé en délibération spéciale avec mention explicite
                $decision = 'deliberation';
                $libelle = _('À délibérer (Note éliminatoire)');
                $justification = sprintf(
                    _("Moyenne générale de %.2f/20 suffisante pour l'admission (seuil %.2f), mais présence d'une ou plusieurs notes éliminatoires : %s. Décision soumise à la délibération du conseil."),
                    $moyenne, $seuilAdmission, implode(', ', $eliminatorySubjectDetails)
                );
            } else {
                $decision = 'admis';
                $libelle = _('Admis');
                $justification = sprintf(
                    _("Moyenne annuelle de %.2f/20 supérieure ou égale au seuil d'admission directe (%.2f)."),
                    $moyenne, $seuilAdmission
                );
            }
        } elseif ($moyenne >= $seuilDelibMin && $moyenne <= $seuilDelibMax) {
            $decision = 'deliberation';
            $libelle = _('À délibérer');
            if ($hasEliminatoryGrade) {
                $justification = sprintf(
                    _("Moyenne annuelle de %.2f/20 située dans l'intervalle de délibération [%.2f - %.2f], avec note éliminatoire : %s."),
                    $moyenne, $seuilDelibMin, $seuilDelibMax, implode(', ', $eliminatorySubjectDetails)
                );
            } else {
                $justification = sprintf(
                    _("Moyenne annuelle de %.2f/20 située dans l'intervalle de délibération [%.2f - %.2f]. Cas soumis à l'arbitrage du conseil de classe."),
                    $moyenne, $seuilDelibMin, $seuilDelibMax
                );
            }
        } else {
            // Moyenne inférieure à la délibération
            $decision = 'redoublement';
            $libelle = _('Redoublement');
            $justification = sprintf(
                _("Moyenne annuelle de %.2f/20 inférieure au seuil de délibération minimale (%.2f). Redoublement préconisé (seuil max redoublement: %.2f)."),
                $moyenne, $seuilDelibMin, $seuilRedoubl
            );
        }

        return [
            'decision' => $decision,
            'decision_libelle' => $libelle,
            'justification' => $justification,
            'rules_applied' => $rulesApplied
        ];
    }

    /**
     * Implémentation interne de la détermination pour un élève individuel.
     */
    private static function calculateStudentDecisionInternal(
        int $lyceeId,
        int $anneeId,
        int $classeId,
        int $eleveId,
        array $classe,
        array $thresholds,
        array $student,
        array $options = []
    ): array {
        // 1. Récupération des résultats académiques annuels
        $academic = self::getAcademicAnnualResults($lyceeId, $anneeId, $classeId, $eleveId, $options);

        // Si les résultats sont incomplets et qu'on n'autorise pas le calcul partiel, refuser ou marquer incomplet
        $allowPartial = $options['autoriser_resultats_incomplets'] ?? false;
        if (!$academic['is_complete'] && !$allowPartial) {
            return [
                'eleve_id' => $eleveId,
                'matricule' => $student['matricule'] ?? '',
                'nom' => $student['nom'],
                'prenom' => $student['prenom'],
                'lycee_id' => $lyceeId,
                'annee_id' => $anneeId,
                'classe_id' => $classeId,
                'classe_libelle' => Classe::getFormattedName($classe),
                'cycle_id' => (int)$classe['cycle_id'],
                'niveau' => (string)$classe['niveau'],
                'serie' => !empty($classe['serie']) ? (string)$classe['serie'] : null,
                'moyenne_annuelle' => $academic['annual_average'],
                'is_complete' => false,
                'incomplete_reason' => $academic['incomplete_reason'],
                'thresholds' => $thresholds,
                'decision' => 'non_determine',
                'decision_libelle' => _('Résultats incomplets'),
                'justification' => sprintf(_("Calcul d'admission refusé : %s"), $academic['incomplete_reason']),
                'rules_applied' => [
                    'refusal_reason' => $academic['incomplete_reason']
                ]
            ];
        }

        // 2. Application des règles de décision
        $moyenne = $academic['annual_average'];
        $decisionData = self::applyDecisionRules($moyenne, $thresholds, $academic, $options);

        return [
            'eleve_id' => $eleveId,
            'matricule' => $student['matricule'] ?? '',
            'nom' => $student['nom'],
            'prenom' => $student['prenom'],
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId,
            'classe_id' => $classeId,
            'classe_libelle' => Classe::getFormattedName($classe),
            'cycle_id' => (int)$classe['cycle_id'],
            'niveau' => (string)$classe['niveau'],
            'serie' => !empty($classe['serie']) ? (string)$classe['serie'] : null,
            'moyenne_annuelle' => $moyenne,
            'is_complete' => $academic['is_complete'],
            'incomplete_reason' => $academic['incomplete_reason'],
            'thresholds' => $thresholds,
            'academic_details' => [
                'sequences_count' => $academic['expected_sequences_count'],
                'recorded_bulletins_count' => $academic['recorded_bulletins_count'],
                'matieres' => $academic['matieres']
            ],
            'decision' => $decisionData['decision'],
            'decision_libelle' => $decisionData['decision_libelle'],
            'justification' => $decisionData['justification'],
            'rules_applied' => $decisionData['rules_applied']
        ];
    }

    /**
     * Vérifie si une décision validée est déjà enregistrée dans le stockage de session/historique
     * et empêche tout écrasement silencieux d'une décision validée.
     *
     * @param array $existingDecision Décision existante
     * @param array $newDecision Nouvelle décision calculée
     * @param bool $force Si true, autorise explicitement le remplacement (délibération manuelle)
     * @return bool
     * @throws LogicException
     */
    public static function assertCanUpdateDecision(array $existingDecision, array $newDecision, bool $force = false): bool {
        $existingStatus = $existingDecision['statut'] ?? $existingDecision['decision_statut'] ?? null;
        if ($existingStatus === 'valide' && !$force) {
            throw new LogicException(sprintf(
                _("Écrasement interdit : une décision validée existe déjà pour l'élève #%d (Décision: '%s'). L'écrasement silencieux d'une décision validée est strictement interdit."),
                $existingDecision['eleve_id'] ?? 0,
                $existingDecision['decision'] ?? $existingStatus
            ));
        }
        return true;
    }
}
