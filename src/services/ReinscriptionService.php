<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/ClasseParametre.php';
require_once __DIR__ . '/PromotionAdmissionService.php';
require_once __DIR__ . '/PromotionThresholdService.php';

/**
 * Service orchestrant la réinscription des élèves avec intégration des décisions pédagogiques
 * et affectation automatique selon les places disponibles.
 * Single Source of Truth pour le module de réinscription.
 */
class ReinscriptionService {

    /**
     * Récupère la dernière inscription académique de référence de l'élève.
     */
    public static function getLatestEnrollment(int $eleveId, ?int $lyceeId = null): ?array {
        $db = Database::getInstance();
        $sql = "
            SELECT et.*, c.niveau, c.serie, c.numero, c.cycle_id,
                   aa.libelle AS annee_libelle, aa.date_debut, aa.date_fin,
                   cy.nom_cycle, cy.niveau_debut AS cycle_niveau_debut, cy.niveau_fin AS cycle_niveau_fin,
                   cy.utilise_series AS cycle_utilise_series
            FROM etudes et
            JOIN classes c ON et.classe_id = c.id_classe
            LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
            JOIN annees_academiques aa ON et.annee_academique_id = aa.id
            WHERE et.eleve_id = :eleve_id
        ";
        $params = ['eleve_id' => $eleveId];
        if ($lyceeId !== null) {
            $sql .= " AND et.lycee_id = :lycee_id";
            $params['lycee_id'] = $lyceeId;
        }
        $sql .= " ORDER BY aa.date_debut DESC, et.id_etude DESC LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Analyse la situation pédagogique de l'élève pour son inscription de référence.
     * Réutilise fidèlement PromotionAdmissionService.
     */
    public static function getPedagogicalSituation(int $eleveId, ?array $latestEnrollment = null): array {
        if (!$latestEnrollment) {
            $latestEnrollment = self::getLatestEnrollment($eleveId);
        }

        if (!$latestEnrollment) {
            return [
                'has_previous_enrollment' => false,
                'status' => 'no_history',
                'decision' => 'non_determine',
                'decision_libelle' => _("Aucun historique scolaire trouvé"),
                'annual_average' => null,
                'justification' => _("Cet élève ne possède aucun historique scolaire permettant une réinscription. Veuillez utiliser le module d'inscription initiale."),
                'is_complete' => false,
                'enrollment' => null
            ];
        }

        $lyceeId = (int)$latestEnrollment['lycee_id'];
        $anneeId = (int)$latestEnrollment['annee_academique_id'];
        $classeId = (int)$latestEnrollment['classe_id'];

        try {
            $decisionData = PromotionAdmissionService::calculateStudentDecision(
                $lyceeId,
                $anneeId,
                $classeId,
                $eleveId,
                ['autoriser_resultats_incomplets' => true]
            );

            return [
                'has_previous_enrollment' => true,
                'status' => 'ok',
                'decision' => $decisionData['decision'] ?? 'non_determine',
                'decision_libelle' => $decisionData['decision_libelle'] ?? _("Non déterminé"),
                'annual_average' => $decisionData['annual_average'] ?? null,
                'justification' => $decisionData['justification'] ?? '',
                'is_complete' => $decisionData['is_complete'] ?? false,
                'details' => $decisionData,
                'enrollment' => $latestEnrollment
            ];
        } catch (Exception $e) {
            return [
                'has_previous_enrollment' => true,
                'status' => 'error',
                'decision' => 'non_determine',
                'decision_libelle' => _("Décision non calculée"),
                'annual_average' => null,
                'justification' => $e->getMessage(),
                'is_complete' => false,
                'enrollment' => $latestEnrollment
            ];
        }
    }

    /**
     * Détermine le niveau, la série et le cycle de destination selon la décision pédagogique.
     */
    public static function resolveDestination(array $pedagogicalSituation, int $targetLyceeId): array {
        if (!$pedagogicalSituation['has_previous_enrollment'] || empty($pedagogicalSituation['enrollment'])) {
            return [
                'can_proceed' => false,
                'reason' => _("Cet élève ne possède aucun historique scolaire permettant une réinscription. Veuillez utiliser le module d'inscription initiale."),
                'type' => 'no_history'
            ];
        }

        $enrollment = $pedagogicalSituation['enrollment'];
        $currentNiveau = (string)$enrollment['niveau'];
        $currentSerie = !empty($enrollment['serie']) ? (string)$enrollment['serie'] : null;
        $currentCycleId = (int)$enrollment['cycle_id'];
        $decision = $pedagogicalSituation['decision'];

        // Cas 1 : Exclu
        if ($decision === 'exclu') {
            return [
                'can_proceed' => false,
                'reason' => _("L'élève a fait l'objet d'une décision d'exclusion scolaire définitive. La réinscription ordinaire est interdite."),
                'type' => 'exclu',
                'niveau' => null,
                'serie' => null,
                'cycle_id' => null
            ];
        }

        // Cas 2 : En délibération ou dossier incomplet
        if ($decision === 'deliberation') {
            return [
                'can_proceed' => false,
                'reason' => _("Le dossier de l'élève est en délibération par le conseil de classe. Une décision finale doit être validée avant toute réinscription."),
                'type' => 'deliberation',
                'niveau' => null,
                'serie' => null,
                'cycle_id' => null
            ];
        }

        if ($decision === 'non_determine' || !$pedagogicalSituation['is_complete']) {
            return [
                'can_proceed' => false,
                'reason' => _("Les résultats scolaires de l'année précédente sont incomplets ou la décision annuelle n'est pas déterminée. Veuillez vérifier les bulletins et clôtures de séquences."),
                'type' => 'incomplete',
                'niveau' => null,
                'serie' => null,
                'cycle_id' => null
            ];
        }

        // Cas 3 : Redoublant
        if ($decision === 'redoublement') {
            return [
                'can_proceed' => true,
                'action' => 'redoublement',
                'type' => 'redoublement',
                'niveau' => $currentNiveau,
                'serie' => $currentSerie,
                'cycle_id' => $currentCycleId,
                'is_terminal_cycle' => false,
                'reason' => sprintf(_("Redoublement : réinscription au même niveau (%s)."), $currentNiveau)
            ];
        }

        // Cas 4 : Admis -> Calcul du niveau suivant
        if ($decision === 'admis') {
            $order = Classe::resolveLevelOrder($currentNiveau);
            if ($order === null) {
                return [
                    'can_proceed' => false,
                    'reason' => sprintf(_("Le niveau actuel '%s' n'est pas reconnu par le référentiel des niveaux."), $currentNiveau),
                    'type' => 'unknown_level'
                ];
            }

            // Bornes de fin de cycle
            $cycleNiveauFin = $enrollment['cycle_niveau_fin'] ?? null;
            $orderFin = $cycleNiveauFin ? Classe::resolveLevelOrder($cycleNiveauFin) : null;

            $isEndOfCycle = false;
            if ($order >= 7) { // Terminale = 7
                $isEndOfCycle = true;
            } elseif ($orderFin !== null && $order >= $orderFin) {
                $isEndOfCycle = true;
            }

            if ($isEndOfCycle) {
                // Si l'élève a fini la Terminale
                if ($order >= 7) {
                    return [
                        'can_proceed' => false,
                        'reason' => _("L'élève a achevé le cycle secondaire complet (Terminale). Fin de cursus secondaire : aucune classe supérieure n'existe dans l'établissement."),
                        'type' => 'end_of_secondary',
                        'is_terminal_cycle' => true
                    ];
                }

                // Fin de premier cycle (3e)
                $nextOrder = $order + 1;
                $canonicalMap = Classe::getCanonicalLevelMap();
                $targetNiveau = $canonicalMap[$nextOrder] ?? null;

                if (!$targetNiveau) {
                    return [
                        'can_proceed' => false,
                        'reason' => _("Fin de cycle : aucun niveau supérieur défini dans le référentiel scolaire."),
                        'type' => 'end_of_cycle',
                        'is_terminal_cycle' => true
                    ];
                }

                $targetCycle = self::findCycleForLevel($targetLyceeId, $targetNiveau);
                if (!$targetCycle) {
                    return [
                        'can_proceed' => false,
                        'reason' => sprintf(_("L'élève a achevé son cycle (%s). L'établissement ne propose pas de cycle d'enseignement pour le niveau supérieur (%s)."), $currentNiveau, $targetNiveau),
                        'type' => 'end_of_cycle',
                        'is_terminal_cycle' => true
                    ];
                }

                return [
                    'can_proceed' => true,
                    'action' => 'passage_cycle_superieur',
                    'type' => 'admis',
                    'niveau' => $targetNiveau,
                    'serie' => null,
                    'cycle_id' => (int)$targetCycle['id_cycle'],
                    'is_terminal_cycle' => false,
                    'reason' => sprintf(_("Admission et transition de cycle vers le niveau supérieur (%s - %s)."), $targetNiveau, $targetCycle['nom_cycle'])
                ];
            }

            // Progression normale
            $nextOrder = $order + 1;
            $canonicalMap = Classe::getCanonicalLevelMap();
            $targetNiveau = $canonicalMap[$nextOrder] ?? null;

            if (!$targetNiveau) {
                return [
                    'can_proceed' => false,
                    'reason' => _("Aucun niveau supérieur défini dans le système."),
                    'type' => 'unknown_next_level'
                ];
            }

            $targetCycle = self::findCycleForLevel($targetLyceeId, $targetNiveau) ?: Cycle::findById($currentCycleId);

            return [
                'can_proceed' => true,
                'action' => 'passage_niveau_superieur',
                'type' => 'admis',
                'niveau' => $targetNiveau,
                'serie' => $currentSerie,
                'cycle_id' => $targetCycle ? (int)$targetCycle['id_cycle'] : $currentCycleId,
                'is_terminal_cycle' => false,
                'reason' => sprintf(_("Admission en classe supérieure : passage en %s."), $targetNiveau)
            ];
        }

        return [
            'can_proceed' => false,
            'reason' => sprintf(_("Décision pédagogique inconnue : '%s'."), $decision),
            'type' => 'unknown_decision'
        ];
    }

    /**
     * Recherche le cycle d'un établissement couvrant un niveau donné.
     */
    public static function findCycleForLevel(int $lyceeId, string $niveau): ?array {
        $db = Database::getInstance();
        $targetOrder = Classe::resolveLevelOrder($niveau);
        if ($targetOrder === null) {
            return null;
        }

        $stmt = $db->prepare("SELECT * FROM cycles WHERE (lycee_id = :lycee_id OR lycee_id IS NULL) ORDER BY lycee_id DESC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cycles as $cy) {
            if (empty($cy['niveau_debut']) || empty($cy['niveau_fin'])) {
                continue;
            }
            $start = Classe::resolveLevelOrder($cy['niveau_debut']);
            $end = Classe::resolveLevelOrder($cy['niveau_fin']);
            if ($start !== null && $end !== null && $targetOrder >= $start && $targetOrder <= $end) {
                return $cy;
            }
        }

        return null;
    }

    /**
     * Calcule la capacité, l'effectif actuel et les places restantes d'une classe pour une année donnée.
     * SSoT Capacité : classe_parametres.nombre_places
     * SSoT Effectif comptabilisé : inscriptions actives et en attente de paiement dans la table etudes
     */
    public static function getClassCapacityDetails(int $classeId, int $anneeId): array {
        $db = Database::getInstance();

        // 1. Capacité maximale configurée
        $param = ClasseParametre::findByClasseAndAnnee($classeId, $anneeId);
        $capacite = null;
        if ($param && isset($param['nombre_places']) && $param['nombre_places'] !== null && $param['nombre_places'] !== '') {
            $capacite = (int)$param['nombre_places'];
        }

        // 2. Effectif réel comptabilisé pour l'année scolaire cible
        // Les statuts 'active' et 'en_attente_paiement' (réservation de place) sont comptabilisés
        $sqlCount = "
            SELECT COUNT(DISTINCT eleve_id) AS effectif
            FROM etudes
            WHERE classe_id = :classe_id
              AND annee_academique_id = :annee_id
              AND (is_active = 1 OR status IN ('active', 'en_attente_paiement'))
              AND status NOT IN ('inactive', 'suspended', 'annule')
        ";
        $stmtCount = $db->prepare($sqlCount);
        $stmtCount->execute(['classe_id' => $classeId, 'annee_id' => $anneeId]);
        $effectif = (int)$stmtCount->fetchColumn();

        // 3. Calcul des places restantes
        $placesRestantes = null;
        $isComplete = false;
        $hasCapacityLimit = ($capacite !== null && $capacite > 0);

        if ($hasCapacityLimit) {
            $placesRestantes = max(0, $capacite - $effectif);
            $isComplete = ($placesRestantes <= 0);
        } else {
            // Règle métier stricte : une capacité absente, NULL ou non configurée
            // ne doit JAMAIS être considérée comme illimitée.
            // Elle est considérée comme non déterminée et donc non éligible à l'affectation automatique.
            $placesRestantes = 0;
            $isComplete = true; // Non éligible automatiquement tant que non configurée
        }

        return [
            'classe_id' => $classeId,
            'annee_id' => $anneeId,
            'capacite' => $capacite,
            'effectif' => $effectif,
            'places_restantes' => $placesRestantes,
            'has_capacity_limit' => $hasCapacityLimit,
            'is_complete' => $isComplete,
            'is_unconfigured' => !$hasCapacityLimit
        ];
    }

    /**
     * Recherche séquentielle des classes compatibles pour un niveau, une série et un établissement.
     * Classe candidate :
     * 1. Même niveau
     * 2. Même série (si fournie/applicable)
     * 3. Même établissement
     * Ordonnée selon l'ordre réel des classes configurées (numero ASC, id_classe ASC).
     *
     * Retourne la première classe disposant de places (places_restantes > 0)
     * ainsi que la liste détaillée de toutes les classes candidates et leur statut.
     */
    public static function findAvailableClasses(int $lyceeId, int $anneeId, string $niveau, ?string $serie = null, ?int $cycleId = null): array {
        $db = Database::getInstance();

        $sql = "
            SELECT c.*, cy.nom_cycle
            FROM classes c
            LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE c.lycee_id = :lycee_id
              AND c.niveau = :niveau
        ";
        $params = [
            'lycee_id' => $lyceeId,
            'niveau' => $niveau
        ];

        if ($serie !== null && $serie !== '') {
            $sql .= " AND (c.serie = :serie OR c.serie IS NULL OR c.serie = '')";
            $params['serie'] = $serie;
        }

        if ($cycleId !== null && $cycleId > 0) {
            $sql .= " AND c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }

        $sql .= " ORDER BY c.numero ASC, c.id_classe ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $candidateClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $classesWithCapacity = [];
        $firstAvailable = null;

        foreach ($candidateClasses as $cls) {
            $clsId = (int)$cls['id_classe'];
            $capDetails = self::getClassCapacityDetails($clsId, $anneeId);

            $nomAffiche = Classe::getFormattedName($cls);
            $item = array_merge($cls, [
                'nom_classe' => $nomAffiche,
                'capacite' => $capDetails['capacite'],
                'effectif' => $capDetails['effectif'],
                'places_restantes' => $capDetails['places_restantes'],
                'has_capacity_limit' => $capDetails['has_capacity_limit'],
                'is_complete' => $capDetails['is_complete']
            ]);

            $classesWithCapacity[] = $item;

            // Retenir la première classe qui a des places restantes (ou sans limite si non bloquée)
            if ($firstAvailable === null && !$capDetails['is_complete']) {
                $firstAvailable = $item;
            }
        }

        $allComplete = !empty($classesWithCapacity) && ($firstAvailable === null);
        $noneConfigured = empty($classesWithCapacity);

        return [
            'classes' => $classesWithCapacity,
            'suggested_classe' => $firstAvailable,
            'all_complete' => $allComplete,
            'has_candidates' => !$noneConfigured,
            'count' => count($classesWithCapacity)
        ];
    }
}
