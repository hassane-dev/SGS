<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class EleveDashboardService {

    /**
     * Retrieves aggregated dashboard KPIs and charts data for student demographics and enrollments.
     *
     * @param array $filters
     * @param int $userId
     * @return array
     */
    public static function getDashboardData(array $filters, int $userId): array {
        $db = Database::getInstance();

        // 1. Resolve Lycée ID
        $lyceeId = !empty($filters['lycee_id']) ? (int)$filters['lycee_id'] : Auth::getLyceeId();
        if (!$lyceeId) {
            throw new InvalidArgumentException("Établissement non spécifié.");
        }

        // Check Lycée multi-tenant authorization
        if (!Auth::can('view_all_lycees', 'reporting') && $lyceeId !== Auth::getLyceeId()) {
            throw new Exception("Accès non autorisé à cet établissement.");
        }

        // 2. Resolve Academic Year
        $anneeId = !empty($filters['annee_academique_id']) ? (int)$filters['annee_academique_id'] : null;
        if (!$anneeId) {
            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear ? (int)$activeYear['id'] : null;
        }

        if (!$anneeId) {
            return [
                'success' => false,
                'message' => "Aucune année académique active trouvée."
            ];
        }

        // Get current academic year details for date comparison
        $stmtYear = $db->prepare("SELECT * FROM annees_academiques WHERE id = :id");
        $stmtYear->execute(['id' => $anneeId]);
        $currentYear = $stmtYear->fetch(PDO::FETCH_ASSOC);

        // 3. Resolve Cycle and Scope Restrictions
        $hasGlobalView = Auth::can('view_all', 'eleve') || Auth::can('view_stats', 'eleve') || Auth::can('view_all_lycees', 'lycee');

        $permittedCycles = AuthorizationScopeService::getPermittedCycles($lyceeId);
        $permittedCycleIds = array_map('intval', array_column($permittedCycles, 'id_cycle'));
        if (empty($permittedCycleIds)) {
            return [
                'success' => false,
                'message' => "Aucun cycle autorisé pour cet utilisateur."
            ];
        }

        $targetCycleId = !empty($filters['cycle_id']) ? (int)$filters['cycle_id'] : null;
        if ($targetCycleId) {
            if (!in_array($targetCycleId, $permittedCycleIds)) {
                throw new Exception("Accès non autorisé au cycle demandé.");
            }
            $cycleIdsToUse = [$targetCycleId];
        } else {
            $cycleIdsToUse = $permittedCycleIds;
        }

        // Teacher scoping if restricted
        $teacherClassIds = null;
        if (!$hasGlobalView) {
            $assignments = User::getTeacherAssignments($userId, $anneeId, $lyceeId);
            $teacherClassIds = [];
            foreach ($assignments as $a) {
                $cId = (int)($a['id_classe'] ?? $a['classe_id'] ?? 0);
                if ($cId > 0 && !in_array($cId, $teacherClassIds)) {
                    $teacherClassIds[] = $cId;
                }
            }
            if (empty($teacherClassIds)) {
                return [
                    'success' => true,
                    'kpis' => [
                        'total_actifs' => 0,
                        'filles' => 0,
                        'garcons' => 0,
                        'taux_filles' => 0.0,
                        'nouvelles_inscriptions' => 0,
                        'reinscriptions' => 0,
                        'en_attente_paiement' => 0,
                        'transferes' => 0,
                        'abandonnes' => 0,
                        'radies' => 0
                    ],
                    'breakdowns' => [
                        'by_cycle' => [],
                        'by_niveau' => [],
                        'by_classe' => []
                    ],
                    'charts' => [
                        'effectif_cycle_niveau' => ['categories' => [], 'series' => []],
                        'gender_by_niveau' => ['categories' => [], 'series' => []],
                        'inscription_types' => ['labels' => [], 'series' => []],
                        'movements' => ['labels' => [], 'series' => []]
                    ]
                ];
            }
        }

        // 4. Construct Common WHERE clause & SQL parameters
        $whereClauses = ["e.lycee_id = :lycee_id", "et.annee_academique_id = :annee_id"];
        $params = [
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId
        ];

        // Cycle filter
        $cyclePlaceholders = [];
        foreach ($cycleIdsToUse as $idx => $cId) {
            $key = ":cyc_$idx";
            $cyclePlaceholders[] = $key;
            $params[$key] = $cId;
        }
        $whereClauses[] = "c.cycle_id IN (" . implode(',', $cyclePlaceholders) . ")";

        // Niveau filter
        if (!empty($filters['niveau'])) {
            $whereClauses[] = "c.niveau = :niveau";
            $params['niveau'] = $filters['niveau'];
        }

        // Classe filter
        if (!empty($filters['classe_id'])) {
            $whereClauses[] = "et.classe_id = :classe_id";
            $params['classe_id'] = (int)$filters['classe_id'];
        } elseif ($teacherClassIds !== null) {
            $clsPlaceholders = [];
            foreach ($teacherClassIds as $idx => $clsId) {
                $key = ":tcls_$idx";
                $clsPlaceholders[] = $key;
                $params[$key] = $clsId;
            }
            $whereClauses[] = "et.classe_id IN (" . implode(',', $clsPlaceholders) . ")";
        }

        $baseWhere = implode(' AND ', $whereClauses);

        // 5. Total Active Effectif KPI
        $sqlActive = "
            SELECT COUNT(DISTINCT e.id_eleve)
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE $baseWhere
              AND et.is_active = 1
              AND et.status = 'active'
              AND e.statut = 'actif'
        ";
        $stmtActive = $db->prepare($sqlActive);
        $stmtActive->execute($params);
        $totalActifs = (int)$stmtActive->fetchColumn();

        // 6. Gender Breakdown (Active students)
        $sqlGender = "
            SELECT
                COALESCE(e.sexe, 'Non spécifié') AS genre,
                COUNT(DISTINCT e.id_eleve) AS total
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE $baseWhere
              AND et.is_active = 1
              AND et.status = 'active'
              AND e.statut = 'actif'
            GROUP BY COALESCE(e.sexe, 'Non spécifié')
        ";
        $stmtGender = $db->prepare($sqlGender);
        $stmtGender->execute($params);
        $genderRows = $stmtGender->fetchAll(PDO::FETCH_ASSOC);

        $filles = 0;
        $garcons = 0;
        foreach ($genderRows as $g) {
            if ($g['genre'] === 'Féminin') {
                $filles = (int)$g['total'];
            } elseif ($g['genre'] === 'Masculin') {
                $garcons = (int)$g['total'];
            }
        }
        $tauxFilles = $totalActifs > 0 ? round(($filles / $totalActifs) * 100, 1) : 0.0;

        // 7. Deterministic New Registrations vs Re-enrollments
        // New: Student active in current year AND has NO active/inactive etudes record in any academic year with date_debut < current year date_debut
        $sqlInscrTypes = "
            SELECT
                CASE
                    WHEN (
                        SELECT COUNT(*)
                        FROM etudes et_prev
                        JOIN annees_academiques aa_prev ON et_prev.annee_academique_id = aa_prev.id
                        WHERE et_prev.eleve_id = e.id_eleve
                          AND aa_prev.date_debut < :curr_date_debut
                    ) > 0 THEN 'reinscription'
                    ELSE 'nouvelle'
                END AS type_inscription,
                COUNT(DISTINCT e.id_eleve) AS total
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE $baseWhere
              AND (et.is_active = 1 OR et.status = 'active' OR e.statut = 'actif')
            GROUP BY type_inscription
        ";
        $paramsInscr = array_merge($params, ['curr_date_debut' => $currentYear['date_debut'] ?? '2000-01-01']);
        $stmtInscr = $db->prepare($sqlInscrTypes);
        $stmtInscr->execute($paramsInscr);
        $inscrRows = $stmtInscr->fetchAll(PDO::FETCH_ASSOC);

        $nouvellesInscriptions = 0;
        $reinscriptions = 0;
        foreach ($inscrRows as $r) {
            if ($r['type_inscription'] === 'nouvelle') {
                $nouvellesInscriptions = (int)$r['total'];
            } elseif ($r['type_inscription'] === 'reinscription') {
                $reinscriptions = (int)$r['total'];
            }
        }

        // 8. Movements & Statuses
        $sqlMovements = "
            SELECT
                e.statut AS eleve_statut,
                et.status AS etude_status,
                COUNT(DISTINCT e.id_eleve) AS total
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE $baseWhere
            GROUP BY e.statut, et.status
        ";
        $stmtMov = $db->prepare($sqlMovements);
        $stmtMov->execute($params);
        $movRows = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

        $enAttentePaiement = 0;
        $transferes = 0;
        $abandonnes = 0;
        $radies = 0;

        foreach ($movRows as $m) {
            $eStat = $m['eleve_statut'];
            $etStat = $m['etude_status'];
            $cnt = (int)$m['total'];

            if ($etStat === 'en_attente_paiement' || $eStat === 'en_attente_paiement') {
                $enAttentePaiement += $cnt;
            }
            if ($eStat === 'transféré') {
                $transferes += $cnt;
            }
            if ($eStat === 'abandonné') {
                $abandonnes += $cnt;
            }
            if ($eStat === 'radié') {
                $radies += $cnt;
            }
        }

        // 9. Breakdown by Cycle & Niveau
        $sqlBreakdownCycleNiveau = "
            SELECT
                cy.id_cycle,
                cy.nom_cycle,
                c.niveau,
                SUM(CASE WHEN e.sexe = 'Féminin' THEN 1 ELSE 0 END) AS filles,
                SUM(CASE WHEN e.sexe = 'Masculin' THEN 1 ELSE 0 END) AS garcons,
                COUNT(DISTINCT e.id_eleve) AS total_actifs
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE $baseWhere
              AND et.is_active = 1
              AND et.status = 'active'
              AND e.statut = 'actif'
            GROUP BY cy.id_cycle, cy.nom_cycle, c.niveau
            ORDER BY cy.id_cycle ASC, c.niveau ASC
        ";
        $stmtBCN = $db->prepare($sqlBreakdownCycleNiveau);
        $stmtBCN->execute($params);
        $breakdownCycleNiveau = $stmtBCN->fetchAll(PDO::FETCH_ASSOC);

        // 10. Breakdown by Class
        $sqlBreakdownClasse = "
            SELECT
                c.id_classe,
                c.niveau,
                c.serie,
                c.numero,
                cy.nom_cycle,
                SUM(CASE WHEN e.sexe = 'Féminin' THEN 1 ELSE 0 END) AS filles,
                SUM(CASE WHEN e.sexe = 'Masculin' THEN 1 ELSE 0 END) AS garcons,
                COUNT(DISTINCT e.id_eleve) AS total_actifs
            FROM classes c
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            LEFT JOIN etudes et ON et.classe_id = c.id_classe AND et.annee_academique_id = :annee_id AND et.is_active = 1 AND et.status = 'active'
            LEFT JOIN eleves e ON e.id_eleve = et.eleve_id AND e.statut = 'actif'
            WHERE c.lycee_id = :lycee_id
              AND c.cycle_id IN (" . implode(',', $cyclePlaceholders) . ")
              " . (!empty($filters['niveau']) ? "AND c.niveau = :niveau" : "") . "
              " . (!empty($filters['classe_id']) ? "AND c.id_classe = :classe_id" : "") . "
            GROUP BY c.id_classe, c.niveau, c.serie, c.numero, cy.nom_cycle
            ORDER BY cy.id_cycle ASC, c.niveau ASC, c.serie ASC, c.numero ASC
        ";
        $stmtBCL = $db->prepare($sqlBreakdownClasse);
        $stmtBCL->execute($params);
        $breakdownClasse = $stmtBCL->fetchAll(PDO::FETCH_ASSOC);

        // Format Class Names
        foreach ($breakdownClasse as &$bRow) {
            $bRow['nom_classe'] = trim(($bRow['niveau'] ?? '') . ' ' . ($bRow['serie'] ?? '') . ' ' . ($bRow['numero'] ?? ''));
            $bRow['filles'] = (int)$bRow['filles'];
            $bRow['garcons'] = (int)$bRow['garcons'];
            $bRow['total_actifs'] = (int)$bRow['total_actifs'];
        }
        unset($bRow);

        // 11. Prepare ApexCharts Series Payloads
        // Chart 1: Effectif par Cycle/Niveau
        $niveauxMap = [];
        foreach ($breakdownCycleNiveau as $row) {
            $niv = $row['niveau'];
            if (!isset($niveauxMap[$niv])) {
                $niveauxMap[$niv] = [
                    'niveau' => $niv,
                    'filles' => 0,
                    'garcons' => 0,
                    'total' => 0
                ];
            }
            $niveauxMap[$niv]['filles'] += (int)$row['filles'];
            $niveauxMap[$niv]['garcons'] += (int)$row['garcons'];
            $niveauxMap[$niv]['total'] += (int)$row['total_actifs'];
        }

        $chartNiveauCategories = array_keys($niveauxMap);
        $chartNiveauFilles = [];
        $chartNiveauGarcons = [];
        $chartNiveauTotals = [];

        foreach ($niveauxMap as $dataN) {
            $chartNiveauFilles[] = $dataN['filles'];
            $chartNiveauGarcons[] = $dataN['garcons'];
            $chartNiveauTotals[] = $dataN['total'];
        }

        $charts = [
            'effectif_niveau' => [
                'categories' => $chartNiveauCategories,
                'series' => [
                    ['name' => 'Élèves Actifs', 'data' => $chartNiveauTotals]
                ]
            ],
            'gender_by_niveau' => [
                'categories' => $chartNiveauCategories,
                'series' => [
                    ['name' => 'Filles', 'data' => $chartNiveauFilles],
                    ['name' => 'Garçons', 'data' => $chartNiveauGarcons]
                ]
            ],
            'inscription_types' => [
                'labels' => ['Nouvelles Inscriptions', 'Réinscriptions'],
                'series' => [$nouvellesInscriptions, $reinscriptions]
            ],
            'movements' => [
                'labels' => ['Actifs', 'En attente paiement', 'Transférés', 'Abandonnés', 'Radiés'],
                'series' => [$totalActifs, $enAttentePaiement, $transferes, $abandonnes, $radies]
            ]
        ];

        return [
            'success' => true,
            'kpis' => [
                'total_actifs' => $totalActifs,
                'filles' => $filles,
                'garcons' => $garcons,
                'taux_filles' => $tauxFilles,
                'nouvelles_inscriptions' => $nouvellesInscriptions,
                'reinscriptions' => $reinscriptions,
                'en_attente_paiement' => $enAttentePaiement,
                'transferes' => $transferes,
                'abandonnes' => $abandonnes,
                'radies' => $radies
            ],
            'breakdowns' => [
                'by_niveau' => array_values($niveauxMap),
                'by_classe' => $breakdownClasse
            ],
            'charts' => $charts
        ];
    }
}
?>
