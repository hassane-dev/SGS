<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EvaluationCalculationService.php';
require_once __DIR__ . '/ParamTypeEvaluation.php';

class Bulletin {

    /**
     * Resolves dynamic evaluation columns for a school / class / sequence.
     * Uses standardized keys:
     * [
     *   'type' => $evaluationType,
     *   'occurrence' => $evaluationOccurrence,
     *   'label' => $evaluationLabel,
     *   'key' => $evaluationColumnKey,
     *   'type_id' => $id
     * ]
     */
    public static function getEvaluationColumns(int $lycee_id, ?int $classe_id = null, ?int $sequence_id = null): array {
        $types = ParamTypeEvaluation::findActive($lycee_id);

        $evaluationColumns = [];
        foreach ($types as $t) {
            $evaluationType = $t['code'];
            $typeLibelle = $t['libelle'];
            $nombre = (int)($t['nombre_evaluation'] ?? 1);
            if ($nombre < 1) $nombre = 1;
            if ($nombre > 3) $nombre = 3;

            $evaluationType = strtolower(trim($evaluationType));

            for ($occ = 1; $occ <= $nombre; $occ++) {
                $evaluationOccurrence = $occ;
                $evaluationColumnKey = $evaluationType . '_' . $evaluationOccurrence;
                $evaluationLabel = ($nombre === 1) ? $typeLibelle : ($typeLibelle . ' ' . $evaluationOccurrence);

                $evaluationColumns[] = [
                    'type' => $evaluationType,
                    'occurrence' => $evaluationOccurrence,
                    'label' => $evaluationLabel,
                    'key' => $evaluationColumnKey,
                    'type_id' => $t['id']
                ];
            }
        }
        return $evaluationColumns;
    }

    /**
     * Gathers all necessary data and calculates averages for a class.
     */
    public static function generateForClass($classe_id, $sequence_id) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT id_eleve, nom, prenom FROM eleves el JOIN etudes et ON el.id_eleve = et.eleve_id WHERE et.classe_id = :classe_id AND (et.is_active = 1 OR et.status = 'active') ORDER BY el.nom, el.prenom");
            $stmt->execute(['classe_id' => $classe_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($students as $stu) {
                $report = self::generateForStudent((int)$stu['id_eleve'], (int)$sequence_id);
                if ($report) {
                    $results[] = [
                        'id_eleve' => $stu['id_eleve'],
                        'nom' => $stu['nom'],
                        'prenom' => $stu['prenom'],
                        'moyenne_generale' => $report['moyenne_generale']
                    ];
                }
            }
            return $results;
        } catch (PDOException $e) {
            error_log("Error in Bulletin::generateForClass: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generates report card data for a student.
     * - Sequence 'ouverte': dynamic computation from evaluations (provisional).
     * - Sequence 'fermee': reading strictly from official snapshot tables bulletins + bulletin_details.
     */
    public static function generateForStudent($eleve_id, $sequence_id) {
        $db = Database::getInstance();

        try {
            // Fetch sequence info
            $stmt_sequence = $db->prepare("SELECT * FROM sequences WHERE id = :id");
            $stmt_sequence->execute(['id' => $sequence_id]);
            $sequence_info = $stmt_sequence->fetch(PDO::FETCH_ASSOC);

            if (!$sequence_info) {
                return false;
            }

            // Fetch student info
            $stmt_eleve = $db->prepare("
                SELECT e.*, l.nom_lycee, aa.libelle as annee_academique, c.id_classe as classe_id, c.niveau, c.serie, c.numero
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                JOIN classes c ON et.classe_id = c.id_classe
                JOIN param_lycee l ON c.lycee_id = l.id
                JOIN annees_academiques aa ON et.annee_academique_id = aa.id
                WHERE e.id_eleve = :id
                ORDER BY et.is_active DESC, et.id_etude DESC
                LIMIT 1
            ");
            $stmt_eleve->execute(['id' => $eleve_id]);
            $eleve_info = $stmt_eleve->fetch(PDO::FETCH_ASSOC);

            if (!$eleve_info) {
                return false;
            }

            $eleve_info['nom_classe'] = trim(($eleve_info['niveau'] ?? '') . ' ' . ($eleve_info['serie'] ?? '') . ' ' . ($eleve_info['numero'] ?? ''));

            $lycee_id = (int)$eleve_info['lycee_id'];
            $classe_id = (int)$eleve_info['classe_id'];

            // Fetch existing bulletin record if any
            $stmt_bulletin = $db->prepare("SELECT * FROM bulletins WHERE eleve_id = :eleve_id AND sequence_id = :sequence_id");
            $stmt_bulletin->execute(['eleve_id' => $eleve_id, 'sequence_id' => $sequence_id]);
            $bulletin_record = $stmt_bulletin->fetch(PDO::FETCH_ASSOC);

            $isClosed = ($sequence_info['statut'] === 'fermee');

            if ($isClosed && $bulletin_record) {
                // CLOSED SEQUENCE: Read from snapshot bulletin_details
                $stmtDetails = $db->prepare("
                    SELECT bd.*, m.nom_matiere
                    FROM bulletin_details bd
                    LEFT JOIN matieres m ON bd.matiere_id = m.id_matiere
                    WHERE bd.bulletin_id = :bulletin_id
                    ORDER BY bd.id ASC
                ");
                $stmtDetails->execute(['bulletin_id' => $bulletin_record['id']]);
                $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

                $evaluationColumns = self::getEvaluationColumns($lycee_id, $classe_id, (int)$sequence_id);

                $formattedMatieres = [];
                $totalPoints = 0.0;
                $totalCoefficients = 0.0;

                foreach ($details as $d) {
                    $mId = (int)$d['matiere_id'];

                    // Fetch evaluation values for individual column rendering
                    $evaluationValues = [];
                    foreach ($evaluationColumns as $col) {
                        $evaluationValues[$col['key']] = null;
                    }

                    $stmtEvals = $db->prepare("
                        SELECT e.*, p.code AS type_code
                        FROM evaluations e
                        LEFT JOIN param_type_evaluation p ON e.type_evaluation_id = p.id
                        WHERE e.eleve_id = :eleve_id
                          AND e.matiere_id = :matiere_id
                          AND e.sequence_id = :sequence_id
                    ");
                    $stmtEvals->execute([
                        'eleve_id' => $eleve_id,
                        'matiere_id' => $mId,
                        'sequence_id' => $sequence_id
                    ]);
                    $evs = $stmtEvals->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($evs as $ev) {
                        $evTypeCode = !empty($ev['type_code']) ? $ev['type_code'] : $ev['type'];
                        $evType = strtolower(trim((string)$evTypeCode));
                        $evOcc = (int)($ev['numero_evaluation'] ?? 1);
                        if ($evOcc < 1) $evOcc = 1;
                        $colKey = $evType . '_' . $evOcc;
                        $bareme = (!empty($ev['bareme_snapshot']) && (float)$ev['bareme_snapshot'] > 0) ? (float)$ev['bareme_snapshot'] : 20.00;
                        $normNote = EvaluationCalculationService::normalizeGrade((float)$ev['note'], $bareme);
                        $evaluationValues[$colKey] = round($normNote, 2);
                    }

                    $coef = (float)$d['coefficient_snapshot'];
                    $pts = (float)$d['points_ponderes'];
                    $totalPoints += $pts;
                    $totalCoefficients += $coef;

                    $subjAvg = (float)$d['moyenne_matiere'];
                    $subjAppreciation = $d['appreciation_matiere'] ?? EvaluationCalculationService::getInstitutionalAppreciation($subjAvg);

                    $formattedMatieres[$mId] = [
                        'matiere_id' => $mId,
                        'nom' => $d['nom_matiere_snapshot'], // HISTORICAL SNAPSHOT
                        'note' => $subjAvg,
                        'coefficient' => $coef, // HISTORICAL SNAPSHOT
                        'total_points' => $pts,
                        'rang_matiere' => $d['rang_matiere'],
                        'moyenne_classe_matiere' => $d['moyenne_classe_matiere'],
                        'appreciation' => $subjAppreciation,
                        'evaluation_values' => $evaluationValues
                    ];
                }

                if (!empty($bulletin_record['nom_classe_snapshot'])) {
                    $eleve_info['nom_classe'] = $bulletin_record['nom_classe_snapshot'];
                }

                return [
                    'eleve' => $eleve_info,
                    'sequence' => $sequence_info,
                    'matieres' => $formattedMatieres,
                    'evaluation_columns' => $evaluationColumns,
                    'total_points' => (float)($bulletin_record['total_points'] ?? $totalPoints),
                    'total_coefficients' => (float)($bulletin_record['total_coefficients'] ?? $totalCoefficients),
                    'moyenne_generale' => (float)$bulletin_record['moyenne_generale'],
                    'bulletin_record' => $bulletin_record,
                    'is_provisoire' => false
                ];

            } else {
                // OPEN SEQUENCE: Compute dynamically
                $report = EvaluationCalculationService::computeStudentSequenceReport((int)$eleve_id, (int)$sequence_id);

                if (empty($report['matieres'])) {
                    return false;
                }

                $evaluationColumns = self::getEvaluationColumns($lycee_id, $classe_id, (int)$sequence_id);

                $formattedMatieres = [];
                foreach ($report['matieres'] as $mId => $m) {
                    $evaluationValues = [];
                    foreach ($evaluationColumns as $col) {
                        $evaluationValues[$col['key']] = null;
                    }

                    foreach ($m['evaluations'] as $ev) {
                        $evTypeCode = !empty($ev['type_code']) ? $ev['type_code'] : ($ev['type'] ?? '');
                        $evType = strtolower(trim((string)$evTypeCode));
                        $evOcc = (int)($ev['numero'] ?? $ev['numero_evaluation'] ?? 1);
                        if ($evOcc < 1) $evOcc = 1;
                        $colKey = $evType . '_' . $evOcc;
                        $evaluationValues[$colKey] = $ev['note_normalisee'];
                    }

                    $subjAvg = (float)$m['moyenne'];
                    $subjAppreciation = EvaluationCalculationService::getInstitutionalAppreciation($subjAvg);

                    $formattedMatieres[$mId] = [
                        'matiere_id' => $mId,
                        'nom' => $m['nom'],
                        'note' => $subjAvg,
                        'coefficient' => $m['coefficient'],
                        'total_points' => $m['total_points'],
                        'appreciation' => $subjAppreciation,
                        'evaluation_values' => $evaluationValues,
                        'evaluations' => $m['evaluations']
                    ];
                }

                return [
                    'eleve' => $eleve_info,
                    'sequence' => $sequence_info,
                    'matieres' => $formattedMatieres,
                    'evaluation_columns' => $evaluationColumns,
                    'total_points' => $report['total_points'],
                    'total_coefficients' => $report['total_coefficients'],
                    'moyenne_generale' => $report['moyenne_generale'],
                    'bulletin_record' => $bulletin_record ?: [
                        'statut' => 'provisoire',
                        'rang' => null,
                        'appreciation' => null
                    ],
                    'is_provisoire' => true
                ];
            }

        } catch (PDOException $e) {
            error_log("Error in Bulletin::generateForStudent: " . $e->getMessage());
            return false;
        }
    }

    public static function saveAppreciation($data) {
        $active_year = AnneeAcademique::findActive();
        $lycee_id = Auth::getLyceeId();
        if (!$active_year || !$lycee_id) return false;

        $sql = "
            INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, lycee_id, moyenne_generale, rang, appreciation, statut)
            VALUES (:eleve_id, :sequence_id, :annee_id, :lycee_id, :moyenne, :rang, :appreciation, :statut)
            ON DUPLICATE KEY UPDATE
                moyenne_generale = VALUES(moyenne_generale),
                rang = VALUES(rang),
                appreciation = VALUES(appreciation),
                statut = VALUES(statut);
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'eleve_id' => $data['eleve_id'],
                'sequence_id' => $data['sequence_id'],
                'annee_id' => $active_year['id'],
                'lycee_id' => $lycee_id,
                'moyenne' => $data['moyenne_generale'],
                'rang' => $data['rang'],
                'appreciation' => $data['appreciation'],
                'statut' => $data['statut']
            ]);
        } catch (PDOException $e) {
            error_log("Error in Bulletin::saveAppreciation: " . $e->getMessage());
            return false;
        }
    }
}
?>