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
}
?>