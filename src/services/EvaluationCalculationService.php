<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Service centralisé de calcul des notes, moyennes de matières et moyennes générales pour SGS.
 *
 * Applique la règle pédagogique fondamentale de SGS :
 * 1. Normalisation de chaque note sur 20 selon son bareme_snapshot.
 * 2. Moyenne de la matière = Moyenne simple des évaluations disponibles pour cette matière.
 * 3. Moyenne générale = Somme(Moyenne Matière * Coefficient Matière) / Somme(Coefficients Matières).
 *    Le coefficient appartient exclusivement à la matière (classe_matieres.coefficient).
 */
class EvaluationCalculationService {

    /**
     * Normalise une note brute sur 20 points.
     */
    public static function normalizeGrade(float $note, float $bareme = 20.00): float {
        if ($bareme <= 0) {
            $bareme = 20.00;
        }
        return ($note / $bareme) * 20.00;
    }

    /**
     * Calcule la moyenne d'une matière pour un élève et une séquence donnée.
     *
     * @param int $eleve_id ID de l'élève
     * @param int $matiere_id ID de la matière
     * @param int $sequence_id ID de la séquence
     * @return float|null Moyenne de la matière sur 20, ou null si aucune note
     */
    public static function computeSubjectAverage(int $eleve_id, int $matiere_id, int $sequence_id): ?float {
        $db = Database::getInstance();
        $sql = "SELECT note, bareme_snapshot
                FROM evaluations
                WHERE eleve_id = :eleve_id
                  AND matiere_id = :matiere_id
                  AND sequence_id = :sequence_id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'eleve_id' => $eleve_id,
                'matiere_id' => $matiere_id,
                'sequence_id' => $sequence_id
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null;
            }

            $sumNormalized = 0.0;
            $count = 0;

            foreach ($rows as $r) {
                if (!is_numeric($r['note'])) {
                    continue;
                }
                $note = (float)$r['note'];
                $bareme = (!empty($r['bareme_snapshot']) && (float)$r['bareme_snapshot'] > 0) ? (float)$r['bareme_snapshot'] : 20.00;
                $sumNormalized += self::normalizeGrade($note, $bareme);
                $count++;
            }

            if ($count === 0) {
                return null;
            }

            return round($sumNormalized / $count, 2);
        } catch (PDOException $e) {
            error_log("Error in EvaluationCalculationService::computeSubjectAverage: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcule les moyennes de toutes les matières et la moyenne générale pour un élève dans une séquence.
     *
     * @param int $eleve_id ID de l'élève
     * @param int $sequence_id ID de la séquence
     * @return array Structure contenant les moyennes par matière et la moyenne générale
     */
    public static function computeStudentSequenceReport(int $eleve_id, int $sequence_id): array {
        $db = Database::getInstance();

        // 1. Récupérer la classe active de l'élève et son année académique
        $sqlClass = "SELECT et.classe_id, et.annee_academique_id
                     FROM etudes et
                     WHERE et.eleve_id = :eleve_id AND et.actif = 1
                     LIMIT 1";
        $stmtC = $db->prepare($sqlClass);
        $stmtC->execute(['eleve_id' => $eleve_id]);
        $etude = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$etude) {
            return [
                'matieres' => [],
                'total_points' => 0.0,
                'total_coefficients' => 0.0,
                'moyenne_generale' => 0.0
            ];
        }

        $classe_id = (int)$etude['classe_id'];
        $annee_id = (int)$etude['annee_academique_id'];

        // 2. Récupérer toutes les matières rattachées à la classe avec leur coefficient
        $sqlSubjects = "SELECT cm.matiere_id, cm.coefficient, m.nom_matiere
                        FROM classe_matieres cm
                        JOIN matieres m ON cm.matiere_id = m.id_matiere
                        WHERE cm.classe_id = :classe_id
                        ORDER BY m.nom_matiere ASC";
        $stmtS = $db->prepare($sqlSubjects);
        $stmtS->execute(['classe_id' => $classe_id]);
        $classSubjects = $stmtS->fetchAll(PDO::FETCH_ASSOC);

        // 3. Récupérer toutes les évaluations de l'élève pour la séquence
        $sqlEvals = "SELECT e.*, m.nom_matiere, p.code AS type_code, p.libelle AS type_libelle
                     FROM evaluations e
                     JOIN matieres m ON e.matiere_id = m.id_matiere
                     LEFT JOIN param_type_evaluation p ON e.type_evaluation_id = p.id
                     WHERE e.eleve_id = :eleve_id
                       AND e.sequence_id = :sequence_id
                     ORDER BY m.nom_matiere ASC, e.type_evaluation_id ASC, e.numero_evaluation ASC";
        $stmtE = $db->prepare($sqlEvals);
        $stmtE->execute([
            'eleve_id' => $eleve_id,
            'sequence_id' => $sequence_id
        ]);
        $evals = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        // Grouper les évaluations par matiere_id
        $evalsBySubject = [];
        foreach ($evals as $ev) {
            $evalsBySubject[(int)$ev['matiere_id']][] = $ev;
        }

        $matieresReport = [];
        $totalPoints = 0.0;
        $totalCoefficients = 0.0;

        foreach ($classSubjects as $sub) {
            $mId = (int)$sub['matiere_id'];
            $coef = (float)$sub['coefficient'];
            $subEvals = $evalsBySubject[$mId] ?? [];

            if (empty($subEvals)) {
                continue; // Pas de note pour cette matière dans cette séquence
            }

            $sumNorm = 0.0;
            $evalList = [];

            foreach ($subEvals as $ev) {
                $rawNote = (float)$ev['note'];
                $bareme = (!empty($ev['bareme_snapshot']) && (float)$ev['bareme_snapshot'] > 0) ? (float)$ev['bareme_snapshot'] : 20.00;
                $normNote = self::normalizeGrade($rawNote, $bareme);

                $sumNorm += $normNote;

                $evalList[] = [
                    'id' => (int)$ev['id'],
                    'type_code' => $ev['type_code'] ?? $ev['type'],
                    'type_libelle' => $ev['type_libelle'] ?? ucfirst($ev['type']),
                    'numero' => (int)$ev['numero_evaluation'],
                    'libelle' => $ev['libelle_evaluation'],
                    'note_brute' => $rawNote,
                    'bareme' => $bareme,
                    'note_normalisee' => round($normNote, 2),
                    'appreciation' => $ev['appreciation']
                ];
            }

            $nbEvals = count($evalList);
            $moyenneMatiere = ($nbEvals > 0) ? round($sumNorm / $nbEvals, 2) : 0.0;
            $pointsPonderes = round($moyenneMatiere * $coef, 2);

            $matieresReport[$mId] = [
                'matiere_id' => $mId,
                'nom' => $sub['nom_matiere'],
                'coefficient' => $coef,
                'moyenne' => $moyenneMatiere,
                'total_points' => $pointsPonderes,
                'evaluations' => $evalList
            ];

            $totalPoints += $pointsPonderes;
            $totalCoefficients += $coef;
        }

        $moyenneGenerale = ($totalCoefficients > 0) ? round($totalPoints / $totalCoefficients, 2) : 0.0;

        return [
            'matieres' => $matieresReport,
            'total_points' => round($totalPoints, 2),
            'total_coefficients' => round($totalCoefficients, 2),
            'moyenne_generale' => $moyenneGenerale
        ];
    }
}
?>