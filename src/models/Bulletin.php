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
     * Generates persistent unique serial number for a bulletin in format YYYYMMDDHHmmssNNNNNN.
     *
     * @param array $bulletinRow Database record from `bulletins` table containing `id` and `created_at`
     * @return string Serial number, e.g. "20260309113657000123"
     */
    public static function generateSerialNumber(array $bulletinRow): string {
        $id = (int)($bulletinRow['id'] ?? 0);
        $createdAt = $bulletinRow['created_at'] ?? date('Y-m-d H:i:s');
        $ts = strtotime($createdAt) ?: time();
        $dateFormatted = date('YmdHis', $ts);
        $idFormatted = str_pad((string)$id, 6, '0', STR_PAD_LEFT);
        return $dateFormatted . $idFormatted;
    }

    /**
     * Generates signed QR code token payload for a bulletin.
     */
    public static function generateQrToken(int $eleve_id, int $lycee_id, int $sequence_id, string $serialNumber): string {
        $dataToSign = $eleve_id . '-' . $lycee_id . '-' . $sequence_id . '-' . $serialNumber;
        $secret = defined('CARD_SIGNATURE_SECRET') ? CARD_SIGNATURE_SECRET : 'SECURE_SCHOOL_APP_2024';
        $signature = hash_hmac('sha256', $dataToSign, $secret);
        $secureToken = $dataToSign . '|' . $signature;
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        return "{$scheme}://{$host}/verify-bulletin?data=" . urlencode($secureToken);
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

            // Fetch or create persistent bulletin record
            $stmt_bulletin = $db->prepare("SELECT * FROM bulletins WHERE eleve_id = :eleve_id AND sequence_id = :sequence_id");
            $stmt_bulletin->execute(['eleve_id' => $eleve_id, 'sequence_id' => $sequence_id]);
            $bulletin_record = $stmt_bulletin->fetch(PDO::FETCH_ASSOC);

            if (!$bulletin_record) {
                // Ensure a persistent DB record exists so ID and created_at are permanently fixed
                $annee_academique_id = (int)($sequence_info['annee_academique_id'] ?? 0);
                $stmtIns = $db->prepare("
                    INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, lycee_id, moyenne_generale, statut)
                    VALUES (:eleve_id, :sequence_id, :annee_id, :lycee_id, 0.00, 'provisoire')
                ");
                $stmtIns->execute([
                    'eleve_id' => $eleve_id,
                    'sequence_id' => $sequence_id,
                    'annee_id' => $annee_academique_id,
                    'lycee_id' => $lycee_id
                ]);
                $bulletin_id = (int)$db->lastInsertId();

                $stmt_bulletin->execute(['eleve_id' => $eleve_id, 'sequence_id' => $sequence_id]);
                $bulletin_record = $stmt_bulletin->fetch(PDO::FETCH_ASSOC);
            }

            $numero_serie = self::generateSerialNumber($bulletin_record);
            $qr_code_url = self::generateQrToken((int)$eleve_id, (int)$lycee_id, (int)$sequence_id, $numero_serie);

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
                        $evOcc = (int)($ev['numero_evaluation'] ?? $ev['numero'] ?? 1);
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
                    'is_provisoire' => false,
                    'numero_serie' => $numero_serie,
                    'qr_code_url' => $qr_code_url
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
                        $evOcc = (int)($ev['numero_evaluation'] ?? $ev['numero'] ?? 1);
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
                    'is_provisoire' => true,
                    'numero_serie' => $numero_serie,
                    'qr_code_url' => $qr_code_url
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

    /**
     * Saves or updates the Class Council Appreciation (Appréciation du conseil de classe).
     * Rule:
     * - If bulletin exists: UPDATE appreciation_conseil_classe (unless statut is 'valide' or 'publie').
     * - If bulletin does NOT exist: Computes real average via EvaluationCalculationService (never 0.00 fake average) and inserts provisional bulletin.
     *
     * @param int $eleve_id
     * @param int $sequence_id
     * @param int $annee_id
     * @param int $lycee_id
     * @param string|null $appreciation_conseil_classe
     * @return bool
     * @throws LogicException If bulletin status is locked ('valide' or 'publie').
     */
    public static function saveAppreciationConseil($eleve_id, $sequence_id, $annee_id, $lycee_id, $appreciation_conseil_classe) {
        $db = Database::getInstance();

        // 1. Fetch existing bulletin row
        $stmtBul = $db->prepare("SELECT id, statut FROM bulletins WHERE eleve_id = :eleve_id AND sequence_id = :sequence_id");
        $stmtBul->execute(['eleve_id' => $eleve_id, 'sequence_id' => $sequence_id]);
        $existing = $stmtBul->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Check status locking
            if (in_array($existing['statut'], ['valide', 'publie'])) {
                throw new LogicException("Le bulletin est déjà dans le statut '{$existing['statut']}' et ne peut plus être modifié par le professeur principal.");
            }

            $stmtUp = $db->prepare("
                UPDATE bulletins
                SET appreciation_conseil_classe = :apprec, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            return $stmtUp->execute([
                'apprec' => $appreciation_conseil_classe,
                'id' => (int)$existing['id']
            ]);
        } else {
            // Bulletin does not exist yet: compute REAL dynamic report metrics via EvaluationCalculationService
            $report = EvaluationCalculationService::computeStudentSequenceReport((int)$eleve_id, (int)$sequence_id);
            $moyenneGenerale = (float)($report['moyenne_generale'] ?? 0.00);

            $stmtIns = $db->prepare("
                INSERT INTO bulletins (
                    eleve_id, sequence_id, annee_academique_id, lycee_id,
                    moyenne_generale, statut, appreciation_conseil_classe
                ) VALUES (
                    :eleve_id, :sequence_id, :annee_id, :lycee_id,
                    :moyenne, 'provisoire', :apprec
                )
            ");
            return $stmtIns->execute([
                'eleve_id' => $eleve_id,
                'sequence_id' => $sequence_id,
                'annee_id' => $annee_id,
                'lycee_id' => $lycee_id,
                'moyenne' => $moyenneGenerale,
                'apprec' => $appreciation_conseil_classe
            ]);
        }
    }

    /**
     * Retrieves Class Council Appreciations for all students in a class for a given sequence.
     * @param int $classe_id
     * @param int $sequence_id
     * @return array Map of [eleve_id => ['appreciation_conseil_classe' => ..., 'statut' => ...]]
     */
    public static function findAppreciationsConseilByClasseAndSequence($classe_id, $sequence_id) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                SELECT b.eleve_id, b.appreciation_conseil_classe, b.statut
                FROM bulletins b
                JOIN etudes et ON b.eleve_id = et.eleve_id
                WHERE et.classe_id = :classe_id
                  AND b.sequence_id = :sequence_id
            ");
            $stmt->execute(['classe_id' => $classe_id, 'sequence_id' => $sequence_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows as $r) {
                $result[(int)$r['eleve_id']] = $r;
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error in Bulletin::findAppreciationsConseilByClasseAndSequence: " . $e->getMessage());
            return [];
        }
    }
}
?>