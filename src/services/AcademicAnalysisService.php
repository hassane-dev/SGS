<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Service applicatif Read-Only d'Analyse Académique Longitudinale.
 *
 * Génère des projections analytiques en mémoire à partir des tables sources
 * (etudes, evaluations, sequences, annees_academiques, classes, matieres, bulletins).
 * NE PERSISTE AUCUNE DONNÉE MÉTIER.
 */
class AcademicAnalysisService {

    /**
     * Récupère la frise chronologique complète des inscriptions et évaluations de l'élève.
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

        // 2. Récupérer toutes les évaluations de cet élève
        // Jointure explicite sur e.eleve_id, e.annee_academique_id ET e.classe_id
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
                ev.coefficient,
                ev.appreciation,
                ev.date_saisie,
                m.nom_matiere,
                s.nom AS sequence_nom,
                s.date_debut AS sequence_date_debut
            FROM evaluations ev
            JOIN matieres m ON ev.matiere_id = m.id_matiere
            JOIN sequences s ON ev.sequence_id = s.id
            WHERE ev.eleve_id = :eleve_id
            ORDER BY ev.annee_academique_id ASC, s.date_debut ASC, m.nom_matiere ASC, ev.type ASC
        ";

        $stmtEv = $db->prepare($sqlEvals);
        $stmtEv->execute(['eleve_id' => $eleveId]);
        $evaluations = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

        // Grouper les évaluations par [annee_academique_id][classe_id]
        $evalsGrouped = [];
        foreach ($evaluations as $ev) {
            $evalsGrouped[$ev['annee_academique_id']][$ev['classe_id']][] = $ev;
        }

        // Assembler le résultat
        $timeline = [];
        foreach ($etudes as $etude) {
            $anneeId = $etude['annee_academique_id'];
            $classeId = $etude['classe_id'];

            $etudeEvals = $evalsGrouped[$anneeId][$classeId] ?? [];

            // Structuralisation des évaluations par matière et par séquence
            $matieresData = [];
            foreach ($etudeEvals as $ev) {
                $mId = $ev['matiere_id'];
                $sId = $ev['sequence_id'];

                if (!isset($matieresData[$mId])) {
                    $matieresData[$mId] = [
                        'matiere_id' => $mId,
                        'nom_matiere' => $ev['nom_matiere'],
                        'sequences' => []
                    ];
                }

                if (!isset($matieresData[$mId]['sequences'][$sId])) {
                    $matieresData[$mId]['sequences'][$sId] = [
                        'sequence_id' => $sId,
                        'sequence_nom' => $ev['sequence_nom'],
                        'evaluations' => []
                    ];
                }

                $matieresData[$mId]['sequences'][$sId]['evaluations'][] = [
                    'evaluation_id' => $ev['evaluation_id'],
                    'type' => $ev['eval_type'],
                    'note' => (float)$ev['note'],
                    'coefficient' => (float)$ev['coefficient'],
                    'appreciation' => $ev['appreciation'],
                    'date_saisie' => $ev['date_saisie']
                ];
            }

            $timeline[] = [
                'etude' => $etude,
                'matieres' => array_values($matieresData)
            ];
        }

        return $timeline;
    }

    /**
     * Calcule les moyennes annuelles par matière pour chaque année d'inscription.
     */
    public static function getSubjectAnnualAverages(int $eleveId): array {
        $db = Database::getInstance();

        // Récupérer toutes les évaluations de l'élève avec coefficient d'évaluation (immutabilité)
        $sql = "
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
                ev.coefficient
            FROM evaluations ev
            JOIN annees_academiques aa ON ev.annee_academique_id = aa.id
            JOIN classes c ON ev.classe_id = c.id_classe
            JOIN matieres m ON ev.matiere_id = m.id_matiere
            WHERE ev.eleve_id = :eleve_id
            ORDER BY aa.date_debut ASC, m.nom_matiere ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute(['eleve_id' => $eleveId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Aggréger par [annee_academique_id][matiere_id]
        $grouped = [];
        foreach ($rows as $r) {
            $aId = $r['annee_academique_id'];
            $mId = $r['matiere_id'];

            if (!isset($grouped[$aId])) {
                $grouped[$aId] = [
                    'annee_academique_id' => $aId,
                    'annee_libelle' => $r['annee_libelle'],
                    'annee_date_debut' => $r['annee_date_debut'],
                    'classe_libelle' => trim(($r['classe_niveau'] ?? '') . ' ' . ($r['classe_serie'] ?? '') . ' ' . ($r['classe_numero'] ?? '')),
                    'matieres' => []
                ];
            }

            if (!isset($grouped[$aId]['matieres'][$mId])) {
                $grouped[$aId]['matieres'][$mId] = [
                    'matiere_id' => $mId,
                    'nom_matiere' => $r['nom_matiere'],
                    'total_points' => 0.0,
                    'total_coefficients' => 0.0,
                    'nb_evaluations' => 0
                ];
            }

            $note = (float)$r['note'];
            $coef = (float)$r['coefficient'];

            $grouped[$aId]['matieres'][$mId]['total_points'] += ($note * $coef);
            $grouped[$aId]['matieres'][$mId]['total_coefficients'] += $coef;
            $grouped[$aId]['matieres'][$mId]['nb_evaluations']++;
        }

        // Calculer la moyenne pondérée finale pour chaque matière/année
        $result = [];
        foreach ($grouped as $aId => $anneeData) {
            $matieresComputed = [];
            foreach ($anneeData['matieres'] as $mId => $m) {
                $avg = ($m['total_coefficients'] > 0) ? ($m['total_points'] / $m['total_coefficients']) : 0.0;
                $matieresComputed[$mId] = [
                    'matiere_id' => $mId,
                    'nom_matiere' => $m['nom_matiere'],
                    'annual_average' => round($avg, 2),
                    'total_points' => round($m['total_points'], 2),
                    'total_coefficients' => round($m['total_coefficients'], 2),
                    'nb_evaluations' => $m['nb_evaluations']
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
            return []; // Besoin d'au moins 2 années pour calculer des variations
        }

        // Construire une matrice [matiere_id][annee_index]
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

        // Calculer les variations consécutives
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

            // Calcul de la variation globale (Première vs Dernière année)
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
     * Extrait les mesures de performance (dernière année active et globale) sans imposer de seuil codé en dur.
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

        // Prendre la dernière année d'étude enregistrée
        $latestYearData = end($annualData);
        $subjects = $latestYearData['matieres'];

        // Tri par moyenne décroissante
        usort($subjects, fn($a, $b) => $b['annual_average'] <=> $a['annual_average']);

        // Tri des variations par delta décroissant / croissant
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
                b.moyenne_generale,
                b.rang,
                b.appreciation,
                b.statut,
                b.created_at,
                b.updated_at,
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
