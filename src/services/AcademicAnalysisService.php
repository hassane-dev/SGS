<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Service applicatif Read-Only d'Analyse Académique Longitudinale.
 *
 * Consomme les résultats officiels figés (bulletins + bulletin_details) pour les séquences fermées,
 * et les données dynamiques (evaluations) pour les séquences ouvertes.
 * NE PERSISTE AUCUNE DONNÉE MÉTIER.
 */
class AcademicAnalysisService {

    /**
     * Récupère la frise chronologique complète des inscriptions et évaluations/bulletins de l'élève.
     */
    public static function getStudentLongitudinalTimeline(int $eleveId): array {
        $db = Database::getInstance();

        // 1. Récupérer toutes les études/inscriptions historiques de l'élève
        $sqlEtudes = "
            SELECT
                et.id_etude,
                et.eleve_id,
                et.classe_id,
                et.lycee_id,
                et.annee_academique_id,
                et.status,
                et.is_active,
                aa.libelle AS annee_libelle,
                aa.date_debut AS annee_date_debut,
                c.niveau,
                c.serie,
                c.numero AS classe_numero,
                cy.nom_cycle
            FROM etudes et
            JOIN annees_academiques aa ON et.annee_academique_id = aa.id
            JOIN classes c ON et.classe_id = c.id_classe
            LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE et.eleve_id = :eleve_id
            ORDER BY aa.date_debut ASC, et.id_etude ASC
        ";

        $stmtE = $db->prepare($sqlEtudes);
        $stmtE->execute(['eleve_id' => $eleveId]);
        $etudes = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        if (empty($etudes)) {
            return [];
        }

        // 2. For closed sequences, fetch official bulletin details snapshots
        $sqlClosedSnapshots = "
            SELECT
                bd.matiere_id,
                bd.nom_matiere_snapshot AS nom_matiere,
                bd.moyenne_matiere AS note,
                bd.coefficient_snapshot AS coefficient,
                bd.appreciation_matiere AS appreciation,
                b.sequence_id,
                b.annee_academique_id,
                b.classe_id,
                s.nom AS sequence_nom,
                s.date_debut AS sequence_date_debut
            FROM bulletin_details bd
            JOIN bulletins b ON bd.bulletin_id = b.id
            JOIN sequences s ON b.sequence_id = s.id
            WHERE b.eleve_id = :eleve_id
              AND s.statut = 'fermee'
            ORDER BY b.annee_academique_id ASC, s.date_debut ASC, bd.nom_matiere_snapshot ASC
        ";
        $stmtClosed = $db->prepare($sqlClosedSnapshots);
        $stmtClosed->execute(['eleve_id' => $eleveId]);
        $closedSnapshots = $stmtClosed->fetchAll(PDO::FETCH_ASSOC);

        // 3. For evaluations (open sequences or fallbacks)
        $sqlEvals = "
            SELECT
                ev.id AS evaluation_id,
                ev.eleve_id,
                ev.classe_id,
                ev.annee_academique_id,
                ev.sequence_id,
                ev.matiere_id,
                ev.type AS eval_type,
                ev.note,
                ev.bareme_snapshot,
                ev.coefficient,
                ev.appreciation,
                ev.date_saisie,
                m.nom_matiere,
                s.nom AS sequence_nom,
                s.date_debut AS sequence_date_debut,
                s.statut AS sequence_statut
            FROM evaluations ev
            JOIN matieres m ON ev.matiere_id = m.id_matiere
            JOIN sequences s ON ev.sequence_id = s.id
            WHERE ev.eleve_id = :eleve_id
            ORDER BY ev.annee_academique_id ASC, s.date_debut ASC, m.nom_matiere ASC, ev.type ASC
        ";

        $stmtOpen = $db->prepare($sqlEvals);
        $stmtOpen->execute(['eleve_id' => $eleveId]);
        $openEvals = $stmtOpen->fetchAll(PDO::FETCH_ASSOC);

        $groupedData = [];
        $closedSeqKeys = [];

        foreach ($closedSnapshots as $cs) {
            $key = $cs['annee_academique_id'] . '_' . $cs['sequence_id'] . '_' . $cs['matiere_id'];
            $closedSeqKeys[$key] = true;

            $groupedData[(int)$cs['annee_academique_id']][(int)$cs['classe_id']][] = [
                'source' => 'snapshot',
                'matiere_id' => (int)$cs['matiere_id'],
                'nom_matiere' => $cs['nom_matiere'],
                'sequence_id' => (int)$cs['sequence_id'],
                'sequence_nom' => $cs['sequence_nom'],
                'note' => (float)$cs['note'],
                'coefficient' => (float)$cs['coefficient'],
                'appreciation' => $cs['appreciation']
            ];
        }

        foreach ($openEvals as $ev) {
            $key = $ev['annee_academique_id'] . '_' . $ev['sequence_id'] . '_' . $ev['matiere_id'];
            if (isset($closedSeqKeys[$key])) {
                continue; // Prefer snapshot if present
            }

            $bareme = (!empty($ev['bareme_snapshot']) && (float)$ev['bareme_snapshot'] > 0) ? (float)$ev['bareme_snapshot'] : 20.00;
            $normNote = round(((float)$ev['note'] / $bareme) * 20.00, 2);

            $groupedData[(int)$ev['annee_academique_id']][(int)$ev['classe_id']][] = [
                'source' => 'evaluation',
                'evaluation_id' => (int)$ev['evaluation_id'],
                'matiere_id' => (int)$ev['matiere_id'],
                'nom_matiere' => $ev['nom_matiere'],
                'sequence_id' => (int)$ev['sequence_id'],
                'sequence_nom' => $ev['sequence_nom'],
                'type' => $ev['eval_type'],
                'note' => $normNote,
                'coefficient' => (float)$ev['coefficient'],
                'appreciation' => $ev['appreciation'],
                'date_saisie' => $ev['date_saisie']
            ];
        }

        // Assembler la timeline
        $timeline = [];
        foreach ($etudes as $etude) {
            $anneeId = (int)$etude['annee_academique_id'];
            $classeId = (int)$etude['classe_id'];

            $items = $groupedData[$anneeId][$classeId] ?? [];

            $matieresData = [];
            foreach ($items as $item) {
                $mId = $item['matiere_id'];
                $sId = $item['sequence_id'];

                if (!isset($matieresData[$mId])) {
                    $matieresData[$mId] = [
                        'matiere_id' => $mId,
                        'nom_matiere' => $item['nom_matiere'],
                        'sequences' => []
                    ];
                }

                if (!isset($matieresData[$mId]['sequences'][$sId])) {
                    $matieresData[$mId]['sequences'][$sId] = [
                        'sequence_id' => $sId,
                        'sequence_nom' => $item['sequence_nom'],
                        'items' => []
                    ];
                }

                $matieresData[$mId]['sequences'][$sId]['items'][] = $item;
            }

            $timeline[] = [
                'etude' => $etude,
                'matieres' => array_values($matieresData)
            ];
        }

        return $timeline;
    }

    /**
     * Calcule les moyennes annuelles par matière pour chaque année d'inscription en utilisant les snapshots fermés et les évaluations ouvertes/fallbacks.
     */
    public static function getSubjectAnnualAverages(int $eleveId): array {
        $db = Database::getInstance();

        // 1. Fetch closed sequence subject averages directly from bulletin_details (SNAPSHOTS)
        $sqlClosed = "
            SELECT
                b.annee_academique_id,
                aa.libelle AS annee_libelle,
                aa.date_debut AS annee_date_debut,
                b.classe_id,
                b.nom_classe_snapshot AS classe_libelle,
                bd.matiere_id,
                bd.nom_matiere_snapshot AS nom_matiere,
                bd.moyenne_matiere,
                bd.coefficient_snapshot AS coefficient
            FROM bulletin_details bd
            JOIN bulletins b ON bd.bulletin_id = b.id
            JOIN annees_academiques aa ON b.annee_academique_id = aa.id
            JOIN sequences s ON b.sequence_id = s.id
            WHERE b.eleve_id = :eleve_id
              AND s.statut = 'fermee'
            ORDER BY aa.date_debut ASC, bd.nom_matiere_snapshot ASC
        ";

        $stmtClosed = $db->prepare($sqlClosed);
        $stmtClosed->execute(['eleve_id' => $eleveId]);
        $closedRows = $stmtClosed->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch raw evaluations
        $sqlEvals = "
            SELECT
                ev.annee_academique_id,
                aa.libelle AS annee_libelle,
                aa.date_debut AS annee_date_debut,
                ev.classe_id,
                c.niveau AS classe_niveau,
                c.serie AS classe_serie,
                c.numero AS classe_numero,
                ev.matiere_id,
                m.nom_matiere,
                ev.sequence_id,
                ev.note,
                ev.bareme_snapshot,
                ev.coefficient
            FROM evaluations ev
            JOIN annees_academiques aa ON ev.annee_academique_id = aa.id
            JOIN classes c ON ev.classe_id = c.id_classe
            JOIN matieres m ON ev.matiere_id = m.id_matiere
            JOIN sequences s ON ev.sequence_id = s.id
            WHERE ev.eleve_id = :eleve_id
            ORDER BY aa.date_debut ASC, m.nom_matiere ASC
        ";

        $stmtEvals = $db->prepare($sqlEvals);
        $stmtEvals->execute(['eleve_id' => $eleveId]);
        $evalRows = $stmtEvals->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        $closedKeys = [];

        foreach ($closedRows as $r) {
            $aId = (int)$r['annee_academique_id'];
            $mId = (int)$r['matiere_id'];
            $closedKeys[$aId . '_' . $mId] = true;

            if (!isset($grouped[$aId])) {
                $grouped[$aId] = [
                    'annee_academique_id' => $aId,
                    'annee_libelle' => $r['annee_libelle'],
                    'annee_date_debut' => $r['annee_date_debut'],
                    'classe_libelle' => $r['classe_libelle'],
                    'matieres' => []
                ];
            }

            if (!isset($grouped[$aId]['matieres'][$mId])) {
                $grouped[$aId]['matieres'][$mId] = [
                    'matiere_id' => $mId,
                    'nom_matiere' => $r['nom_matiere'],
                    'sum_averages' => 0.0,
                    'count_sequences' => 0
                ];
            }

            $grouped[$aId]['matieres'][$mId]['sum_averages'] += (float)$r['moyenne_matiere'];
            $grouped[$aId]['matieres'][$mId]['count_sequences']++;
        }

        foreach ($evalRows as $r) {
            $aId = (int)$r['annee_academique_id'];
            $mId = (int)$r['matiere_id'];

            if (isset($closedKeys[$aId . '_' . $mId])) {
                continue; // Prefer closed snapshot if present
            }

            if (!isset($grouped[$aId])) {
                $classeLib = trim(($r['classe_niveau'] ?? '') . ' ' . ($r['classe_serie'] ?? '') . ' ' . ($r['classe_numero'] ?? ''));
                $grouped[$aId] = [
                    'annee_academique_id' => $aId,
                    'annee_libelle' => $r['annee_libelle'],
                    'annee_date_debut' => $r['annee_date_debut'],
                    'classe_libelle' => $classeLib,
                    'matieres' => []
                ];
            }

            if (!isset($grouped[$aId]['matieres'][$mId])) {
                $grouped[$aId]['matieres'][$mId] = [
                    'matiere_id' => $mId,
                    'nom_matiere' => $r['nom_matiere'],
                    'sum_averages' => 0.0,
                    'count_sequences' => 0
                ];
            }

            $note = (float)$r['note'];
            $bareme = (!empty($r['bareme_snapshot']) && (float)$r['bareme_snapshot'] > 0) ? (float)$r['bareme_snapshot'] : 20.00;
            $normNote = ($note / $bareme) * 20.00;

            $grouped[$aId]['matieres'][$mId]['sum_averages'] += $normNote;
            $grouped[$aId]['matieres'][$mId]['count_sequences']++;
        }

        // Calculate final annual subject averages
        $result = [];
        foreach ($grouped as $aId => $anneeData) {
            $matieresComputed = [];
            foreach ($anneeData['matieres'] as $mId => $m) {
                $cnt = $m['count_sequences'];
                $avg = ($cnt > 0) ? ($m['sum_averages'] / $cnt) : 0.0;
                $matieresComputed[$mId] = [
                    'matiere_id' => $mId,
                    'nom_matiere' => $m['nom_matiere'],
                    'annual_average' => round($avg, 2),
                    'nb_sequences' => $cnt
                ];
            }
            $anneeData['matieres'] = array_values($matieresComputed);
            $result[] = $anneeData;
        }

        return $result;
    }

    /**
     * Évalue les variations interannuelles (Δ = Année N+1 - Année N) par matière.
     */
    public static function getInterannualVariations(int $eleveId): array {
        $annualData = self::getSubjectAnnualAverages($eleveId);

        if (count($annualData) < 2) {
            return [];
        }

        $subjectSeries = [];
        foreach ($annualData as $yearIdx => $year) {
            $anneeLibelle = $year['annee_libelle'];
            foreach ($year['matieres'] as $m) {
                $mId = $m['matiere_id'];
                if (!isset($subjectSeries[$mId])) {
                    $subjectSeries[$mId] = [
                        'matiere_id' => $mId,
                        'nom_matiere' => $m['nom_matiere'],
                        'years' => []
                    ];
                }
                $subjectSeries[$mId]['years'][] = [
                    'year_index' => $yearIdx,
                    'annee_libelle' => $anneeLibelle,
                    'classe_libelle' => $year['classe_libelle'],
                    'average' => $m['annual_average']
                ];
            }
        }

        $variations = [];
        foreach ($subjectSeries as $mId => $series) {
            $yearsCount = count($series['years']);
            if ($yearsCount < 2) {
                continue;
            }

            $seriesVariations = [];
            for ($i = 0; $i < $yearsCount - 1; $i++) {
                $prev = $series['years'][$i];
                $curr = $series['years'][$i + 1];
                $delta = round($curr['average'] - $prev['average'], 2);

                $seriesVariations[] = [
                    'from_year' => $prev['annee_libelle'],
                    'to_year' => $curr['annee_libelle'],
                    'from_classe' => $prev['classe_libelle'],
                    'to_classe' => $curr['classe_libelle'],
                    'from_average' => $prev['average'],
                    'to_average' => $curr['average'],
                    'delta' => $delta
                ];
            }

            $firstYear = $series['years'][0];
            $lastYear = $series['years'][$yearsCount - 1];
            $totalDelta = round($lastYear['average'] - $firstYear['average'], 2);

            $variations[] = [
                'matiere_id' => $mId,
                'nom_matiere' => $series['nom_matiere'],
                'years_tracked' => $yearsCount,
                'first_average' => $firstYear['average'],
                'latest_average' => $lastYear['average'],
                'total_delta' => $totalDelta,
                'consecutive_variations' => $seriesVariations
            ];
        }

        return $variations;
    }

    /**
     * Extrait les mesures de performance sans imposer de seuil codé en dur.
     */
    public static function getRawPerformanceMetrics(int $eleveId): array {
        $annualData = self::getSubjectAnnualAverages($eleveId);
        $variations = self::getInterannualVariations($eleveId);

        if (empty($annualData)) {
            return [
                'latest_year' => null,
                'latest_subjects_sorted' => [],
                'top_progressions' => [],
                'top_regressions' => []
            ];
        }

        $latestYearData = end($annualData);
        $subjects = $latestYearData['matieres'];

        usort($subjects, fn($a, $b) => $b['annual_average'] <=> $a['annual_average']);

        $progressions = $variations;
        usort($progressions, fn($a, $b) => $b['total_delta'] <=> $a['total_delta']);

        $regressions = $variations;
        usort($regressions, fn($a, $b) => $a['total_delta'] <=> $b['total_delta']);

        return [
            'latest_year' => $latestYearData['annee_libelle'],
            'latest_classe' => $latestYearData['classe_libelle'],
            'latest_subjects_sorted' => $subjects,
            'top_progressions' => $progressions,
            'top_regressions' => $regressions
        ];
    }

    /**
     * Récupère les snapshots officiels des bulletins enregistrés dans la table `bulletins`.
     */
    public static function getOfficialBulletinSnapshots(int $eleveId): array {
        $db = Database::getInstance();

        $sql = "
            SELECT
                b.id AS bulletin_id,
                b.eleve_id,
                b.sequence_id,
                b.annee_academique_id,
                b.classe_id,
                b.nom_classe_snapshot,
                b.effectif_classe,
                b.moyenne_generale,
                b.total_points,
                b.total_coefficients,
                b.moyenne_classe,
                b.rang_int,
                b.rang,
                b.appreciation,
                b.statut,
                b.date_cloture,
                b.cloture_par_user_id,
                s.nom AS sequence_nom,
                aa.libelle AS annee_libelle
            FROM bulletins b
            JOIN sequences s ON b.sequence_id = s.id
            JOIN annees_academiques aa ON b.annee_academique_id = aa.id
            WHERE b.eleve_id = :eleve_id
            ORDER BY aa.date_debut ASC, s.date_debut ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute(['eleve_id' => $eleveId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les données séquentielles pour les graphiques (officiels + ouverts/provisoires).
     * Trie chronologiquement par année et date de début de séquence.
     */
    public static function getSequentialSeriesData(int $eleveId): array {
        $db = Database::getInstance();

        $sql = "
            SELECT
                b.id AS bulletin_id,
                b.sequence_id,
                b.annee_academique_id,
                b.moyenne_generale,
                b.moyenne_classe,
                b.rang_int,
                b.rang,
                b.effectif_classe,
                b.statut AS bulletin_statut,
                s.nom AS sequence_nom,
                s.statut AS sequence_statut,
                s.date_debut AS sequence_date_debut,
                aa.libelle AS annee_libelle,
                aa.date_debut AS annee_date_debut
            FROM bulletins b
            JOIN sequences s ON b.sequence_id = s.id
            JOIN annees_academiques aa ON b.annee_academique_id = aa.id
            WHERE b.eleve_id = :eleve_id
            ORDER BY aa.date_debut ASC, s.date_debut ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute(['eleve_id' => $eleveId]);
        $bulletins = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Track sequence IDs already present in bulletins
        $existingSequenceIds = array_column($bulletins, 'sequence_id');

        // Check if student has open sequence raw evaluations not yet in bulletins
        $sqlEvals = "
            SELECT DISTINCT
                ev.sequence_id,
                ev.annee_academique_id,
                s.nom AS sequence_nom,
                s.statut AS sequence_statut,
                s.date_debut AS sequence_date_debut,
                aa.libelle AS annee_libelle,
                aa.date_debut AS annee_date_debut
            FROM evaluations ev
            JOIN sequences s ON ev.sequence_id = s.id
            JOIN annees_academiques aa ON ev.annee_academique_id = aa.id
            WHERE ev.eleve_id = :eleve_id
              AND s.statut = 'ouverte'
            ORDER BY aa.date_debut ASC, s.date_debut ASC
        ";
        $stmtEvals = $db->prepare($sqlEvals);
        $stmtEvals->execute(['eleve_id' => $eleveId]);
        $openSeqs = $stmtEvals->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!empty($openSeqs)) {
            require_once __DIR__ . '/EvaluationCalculationService.php';

            foreach ($openSeqs as $seq) {
                if (in_array($seq['sequence_id'], $existingSequenceIds, true)) {
                    continue; // Skip if already present in official bulletins
                }

                $report = EvaluationCalculationService::computeStudentSequenceReport($eleveId, (int)$seq['sequence_id']);
                if (!empty($report['matieres'])) {
                    $bulletins[] = [
                        'bulletin_id' => null,
                        'sequence_id' => $seq['sequence_id'],
                        'annee_academique_id' => $seq['annee_academique_id'],
                        'moyenne_generale' => $report['moyenne_generale'],
                        'moyenne_classe' => null,
                        'rang_int' => null,
                        'rang' => null,
                        'effectif_classe' => null,
                        'bulletin_statut' => 'provisoire',
                        'sequence_nom' => $seq['sequence_nom'],
                        'sequence_statut' => $seq['sequence_statut'],
                        'sequence_date_debut' => $seq['sequence_date_debut'],
                        'annee_libelle' => $seq['annee_libelle'],
                        'annee_date_debut' => $seq['annee_date_debut']
                    ];
                }
            }

            // Sort all bulletins chronologically by academic year start date and sequence start date
            usort($bulletins, function($a, $b) {
                if ($a['annee_date_debut'] === $b['annee_date_debut']) {
                    return strcmp($a['sequence_date_debut'] ?? '', $b['sequence_date_debut'] ?? '');
                }
                return strcmp($a['annee_date_debut'] ?? '', $b['annee_date_debut'] ?? '');
            });
        }

        return $bulletins;
    }

    /**
     * G1 — Série de l'évolution de la moyenne générale (Axe X: Année • Séquence, Axe Y: Moyenne /20)
     */
    public static function getGeneralAverageTrendSeries(int $eleveId): array {
        $bulletins = self::getSequentialSeriesData($eleveId);

        $labels = [];
        $values = [];
        $statuses = [];

        foreach ($bulletins as $b) {
            $label = $b['annee_libelle'] . ' • ' . $b['sequence_nom'];
            $isOfficial = ($b['sequence_statut'] === 'fermee');

            $labels[] = $label;
            $values[] = (float)$b['moyenne_generale'];
            $statuses[] = $isOfficial ? 'officiel' : 'provisoire';
        }

        return [
            'categories' => $labels,
            'series' => [
                [
                    'name' => _('Moyenne Générale'),
                    'data' => $values
                ]
            ],
            'statuses' => $statuses
        ];
    }

    /**
     * G2 — Série Élève vs Classe (Double courbe)
     */
    public static function getStudentVsClassSeries(int $eleveId): array {
        $bulletins = self::getSequentialSeriesData($eleveId);

        $labels = [];
        $studentValues = [];
        $classValues = [];
        $gaps = [];

        foreach ($bulletins as $b) {
            $label = $b['annee_libelle'] . ' • ' . $b['sequence_nom'];
            $stuAvg = (float)$b['moyenne_generale'];
            $clsAvg = $b['moyenne_classe'] !== null ? (float)$b['moyenne_classe'] : null;

            $labels[] = $label;
            $studentValues[] = $stuAvg;
            $classValues[] = $clsAvg;
            $gaps[] = ($clsAvg !== null) ? round($stuAvg - $clsAvg, 2) : null;
        }

        return [
            'categories' => $labels,
            'series' => [
                [
                    'name' => _('Élève'),
                    'data' => $studentValues
                ],
                [
                    'name' => _('Moyenne Classe'),
                    'data' => $classValues
                ]
            ],
            'gaps' => $gaps
        ];
    }

    /**
     * G3 — Série de l'évolution du rang
     */
    public static function getRankTrendSeries(int $eleveId): array {
        $bulletins = self::getSequentialSeriesData($eleveId);

        $labels = [];
        $ranks = [];
        $effectifs = [];
        $rankStrings = [];

        foreach ($bulletins as $b) {
            $label = $b['annee_libelle'] . ' • ' . $b['sequence_nom'];
            $rankInt = $b['rang_int'] !== null ? (int)$b['rang_int'] : null;
            $eff = $b['effectif_classe'] !== null ? (int)$b['effectif_classe'] : null;

            $labels[] = $label;
            $ranks[] = $rankInt;
            $effectifs[] = $eff;
            $rankStrings[] = $b['rang'] ?? ($rankInt ? $rankInt . 'e' : 'N/A');
        }

        return [
            'categories' => $labels,
            'ranks' => $ranks,
            'effectifs' => $effectifs,
            'rank_strings' => $rankStrings
        ];
    }

    /**
     * G4 — Série d'évolution par matière
     */
    public static function getSubjectTrendSeries(int $eleveId): array {
        $db = Database::getInstance();

        // Query official closed bulletin_details
        $sqlDetails = "
            SELECT
                bd.matiere_id,
                bd.nom_matiere_snapshot AS nom_matiere,
                bd.moyenne_matiere,
                b.sequence_id,
                s.nom AS sequence_nom,
                s.date_debut AS sequence_date_debut,
                aa.libelle AS annee_libelle,
                aa.date_debut AS annee_date_debut
            FROM bulletin_details bd
            JOIN bulletins b ON bd.bulletin_id = b.id
            JOIN sequences s ON b.sequence_id = s.id
            JOIN annees_academiques aa ON b.annee_academique_id = aa.id
            WHERE b.eleve_id = :eleve_id
            ORDER BY aa.date_debut ASC, s.date_debut ASC, bd.nom_matiere_snapshot ASC
        ";

        $stmt = $db->prepare($sqlDetails);
        $stmt->execute(['eleve_id' => $eleveId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sequenceKeys = [];
        $subjectsData = [];

        foreach ($rows as $r) {
            $seqKey = $r['annee_libelle'] . ' • ' . $r['sequence_nom'];
            if (!in_array($seqKey, $sequenceKeys, true)) {
                $sequenceKeys[] = $seqKey;
            }

            $mId = (int)$r['matiere_id'];
            if (!isset($subjectsData[$mId])) {
                $subjectsData[$mId] = [
                    'matiere_id' => $mId,
                    'nom_matiere' => $r['nom_matiere'],
                    'averages_by_seq' => []
                ];
            }
            $subjectsData[$mId]['averages_by_seq'][$seqKey] = (float)$r['moyenne_matiere'];
        }

        // Align each subject data to all categories
        $resultSubjects = [];
        foreach ($subjectsData as $mId => $sData) {
            $dataPoints = [];
            foreach ($sequenceKeys as $sKey) {
                $dataPoints[] = $sData['averages_by_seq'][$sKey] ?? null;
            }
            $resultSubjects[] = [
                'matiere_id' => $mId,
                'nom_matiere' => $sData['nom_matiere'],
                'data' => $dataPoints
            ];
        }

        return [
            'categories' => $sequenceKeys,
            'subjects' => $resultSubjects
        ];
    }

    /**
     * G5 — Profil de la dernière séquence disponible (Matières fortes / faibles / radar)
     */
    public static function getLatestSubjectProfile(int $eleveId): array {
        $db = Database::getInstance();

        // Get the latest bulletin
        $sqlLatest = "
            SELECT b.id AS bulletin_id, b.sequence_id, s.nom AS sequence_nom, aa.libelle AS annee_libelle
            FROM bulletins b
            JOIN sequences s ON b.sequence_id = s.id
            JOIN annees_academiques aa ON b.annee_academique_id = aa.id
            WHERE b.eleve_id = :eleve_id
            ORDER BY aa.date_debut DESC, s.date_debut DESC
            LIMIT 1
        ";

        $stmt = $db->prepare($sqlLatest);
        $stmt->execute(['eleve_id' => $eleveId]);
        $latestBul = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$latestBul) {
            return [
                'period_label' => null,
                'subjects' => [],
                'averages' => [],
                'class_averages' => []
            ];
        }

        $sqlDetails = "
            SELECT
                bd.matiere_id,
                bd.nom_matiere_snapshot AS nom_matiere,
                bd.moyenne_matiere,
                bd.moyenne_classe_matiere
            FROM bulletin_details bd
            WHERE bd.bulletin_id = :bulletin_id
            ORDER BY bd.moyenne_matiere DESC
        ";

        $stmtDet = $db->prepare($sqlDetails);
        $stmtDet->execute(['bulletin_id' => $latestBul['bulletin_id']]);
        $details = $stmtDet->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $subjectNames = [];
        $studentAvgs = [];
        $classAvgs = [];

        foreach ($details as $d) {
            $subjectNames[] = $d['nom_matiere'];
            $studentAvgs[] = (float)$d['moyenne_matiere'];
            $classAvgs[] = $d['moyenne_classe_matiere'] !== null ? (float)$d['moyenne_classe_matiere'] : null;
        }

        return [
            'period_label' => $latestBul['annee_libelle'] . ' • ' . $latestBul['sequence_nom'],
            'subjects' => $subjectNames,
            'averages' => $studentAvgs,
            'class_averages' => $classAvgs
        ];
    }

    /**
     * Résumé décisionnel global (Cartes de synthèse et classification de tendance)
     */
    public static function getPerformanceSummary(int $eleveId): array {
        $bulletins = self::getSequentialSeriesData($eleveId);
        $snapshots = self::getOfficialBulletinSnapshots($eleveId);
        $latestProfile = self::getLatestSubjectProfile($eleveId);
        $variations = self::getInterannualVariations($eleveId);

        if (empty($bulletins)) {
            return [
                'has_data' => false,
                'latest_average' => null,
                'average_delta' => null,
                'latest_rank' => null,
                'rank_delta' => null,
                'latest_class_gap' => null,
                'best_subject' => null,
                'worst_subject' => null,
                'general_trend' => _('Historique insuffisant')
            ];
        }

        $count = count($bulletins);
        $latestBul = $bulletins[$count - 1];
        $prevBul = ($count >= 2) ? $bulletins[$count - 2] : null;

        $latestAvg = (float)$latestBul['moyenne_generale'];
        $prevAvg = $prevBul ? (float)$prevBul['moyenne_generale'] : null;
        $avgDelta = ($prevAvg !== null) ? round($latestAvg - $prevAvg, 2) : null;

        $latestRankInt = $latestBul['rang_int'] !== null ? (int)$latestBul['rang_int'] : null;
        $prevRankInt = ($prevBul && $prevBul['rang_int'] !== null) ? (int)$prevBul['rang_int'] : null;

        // Rank delta: prevRank - latestRank (Positive means rank improved, e.g. 18 -> 6 = +12)
        $rankDelta = ($prevRankInt !== null && $latestRankInt !== null) ? ($prevRankInt - $latestRankInt) : null;

        $clsAvg = $latestBul['moyenne_classe'] !== null ? (float)$latestBul['moyenne_classe'] : null;
        $classGap = ($clsAvg !== null) ? round($latestAvg - $clsAvg, 2) : null;

        // Best and worst subjects from latest profile
        $bestSubj = null;
        $worstSubj = null;

        if (!empty($latestProfile['subjects'])) {
            $bestSubj = [
                'nom' => $latestProfile['subjects'][0],
                'note' => $latestProfile['averages'][0]
            ];
            $lastIndex = count($latestProfile['subjects']) - 1;
            $worstSubj = [
                'nom' => $latestProfile['subjects'][$lastIndex],
                'note' => $latestProfile['averages'][$lastIndex]
            ];
        }

        // Determine general trend safely based on avgDelta or overall history
        if ($avgDelta === null) {
            $generalTrend = _('Données initiales');
        } elseif ($avgDelta >= 0.50) {
            $generalTrend = _('En progression');
        } elseif ($avgDelta <= -0.50) {
            $generalTrend = _('En régression');
        } else {
            $generalTrend = _('Stable');
        }

        return [
            'has_data' => true,
            'period_label' => $latestBul['annee_libelle'] . ' • ' . $latestBul['sequence_nom'],
            'latest_average' => $latestAvg,
            'average_delta' => $avgDelta,
            'latest_rank' => $latestBul['rang'] ?? ($latestRankInt ? $latestRankInt . 'e' : 'N/A'),
            'latest_rank_int' => $latestRankInt,
            'effectif' => $latestBul['effectif_classe'],
            'rank_delta' => $rankDelta,
            'latest_class_gap' => $classGap,
            'best_subject' => $bestSubj,
            'worst_subject' => $worstSubj,
            'general_trend' => $generalTrend
        ];
    }
}
?>