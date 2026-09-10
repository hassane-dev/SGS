<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/EvaluationCalculationService.php';
require_once __DIR__ . '/../core/Auth.php';

class SequenceClosureService {

    /**
     * Closes a sequence atomically, calculating and snapshotting all official results.
     *
     * @param int $sequence_id The ID of the sequence to close.
     * @param int|null $user_id The ID of the user triggering the closure.
     * @return array Array with success status and summary message.
     * @throws Exception
     */
    public static function closeSequence(int $sequence_id, ?int $user_id = null): array {
        $db = Database::getInstance();
        $user_id = $user_id ?? Auth::getUserId();

        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isSqlite = ($driver === 'sqlite');

        try {
            $db->beginTransaction();

            // 1. Lock sequence row and verify statut === 'ouverte'
            $lockSql = $isSqlite
                ? "SELECT * FROM sequences WHERE id = :id"
                : "SELECT * FROM sequences WHERE id = :id FOR UPDATE";

            $stmtLock = $db->prepare($lockSql);
            $stmtLock->execute(['id' => $sequence_id]);
            $sequence = $stmtLock->fetch(PDO::FETCH_ASSOC);

            if (!$sequence) {
                $db->rollBack();
                throw new InvalidArgumentException("Séquence introuvable (ID: {$sequence_id}).");
            }

            if ($sequence['statut'] !== 'ouverte') {
                $db->rollBack();
                throw new InvalidArgumentException("La séquence '{$sequence['nom']}' n'est pas ouverte (Statut actuel: {$sequence['statut']}). Seule une séquence ouverte peut être clôturée.");
            }

            $lycee_id = (int)$sequence['lycee_id'];
            $annee_id = (int)$sequence['annee_academique_id'];

            // 2. Fetch all active classes for this establishment
            $stmtClasses = $db->prepare("
                SELECT id_classe, niveau, serie, numero
                FROM classes
                WHERE lycee_id = :lycee_id
                ORDER BY id_classe ASC
            ");
            $stmtClasses->execute(['lycee_id' => $lycee_id]);
            $classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

            $processedCount = 0;
            $bulletinsCount = 0;

            foreach ($classes as $cls) {
                $classe_id = (int)$cls['id_classe'];
                $nom_classe = trim(($cls['niveau'] ?? '') . ' ' . ($cls['serie'] ?? '') . ' ' . ($cls['numero'] ?? ''));

                // Fetch active students for this class
                $stmtStudents = $db->prepare("
                    SELECT e.id_eleve, e.nom, e.prenom
                    FROM eleves e
                    JOIN etudes et ON e.id_eleve = et.eleve_id
                    WHERE et.classe_id = :classe_id
                      AND et.annee_academique_id = :annee_id
                      AND (et.is_active = 1 OR et.status = 'active')
                    ORDER BY e.nom ASC, e.prenom ASC
                ");
                $stmtStudents->execute([
                    'classe_id' => $classe_id,
                    'annee_id' => $annee_id
                ]);
                $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

                if (empty($students)) {
                    continue;
                }

                $effectif_classe = count($students);

                // Compute student reports for this class
                $studentReports = [];
                $classStudentScores = [];
                $subjectScores = []; // [matiere_id => [score1, score2, ...]]

                foreach ($students as $stu) {
                    $eleve_id = (int)$stu['id_eleve'];
                    $report = EvaluationCalculationService::computeStudentSequenceReport($eleve_id, $sequence_id);

                    $studentReports[$eleve_id] = $report;
                    $classStudentScores[] = [
                        'eleve_id' => $eleve_id,
                        'moyenne_generale' => $report['moyenne_generale']
                    ];

                    foreach ($report['matieres'] as $mId => $m) {
                        $subjectScores[$mId][] = $m['moyenne'];
                    }
                }

                // Compute class general average
                $sumGenerale = array_sum(array_column($classStudentScores, 'moyenne_generale'));
                $moyenne_classe_generale = ($effectif_classe > 0) ? round($sumGenerale / $effectif_classe, 2) : 0.0;

                // Compute rankings (standard competition ranking: 1, 2, 2, 4)
                $rankings = self::computeRanks($classStudentScores);

                // Compute subject class averages and subject rankings
                $subjectClassAverages = [];
                $subjectRankings = []; // [matiere_id => [eleve_id => '1er ex']]

                foreach ($subjectScores as $mId => $scores) {
                    $subjectClassAverages[$mId] = (count($scores) > 0) ? round(array_sum($scores) / count($scores), 2) : 0.0;

                    // Build subject score list for ranking
                    $subjStudentScores = [];
                    foreach ($students as $stu) {
                        $eId = (int)$stu['id_eleve'];
                        if (isset($studentReports[$eId]['matieres'][$mId])) {
                            $subjStudentScores[] = [
                                'eleve_id' => $eId,
                                'moyenne_generale' => $studentReports[$eId]['matieres'][$mId]['moyenne']
                            ];
                        }
                    }
                    $subjectRankings[$mId] = self::computeRanks($subjStudentScores);
                }

                // Save or update bulletin & bulletin_details for each student
                foreach ($students as $stu) {
                    $eleve_id = (int)$stu['id_eleve'];
                    $report = $studentReports[$eleve_id];
                    $rankInfo = $rankings[$eleve_id] ?? ['rang_int' => null, 'rang' => null];

                    // Check existing bulletin or insert new
                    $stmtCheckBul = $db->prepare("
                        SELECT id, statut, appreciation FROM bulletins
                        WHERE eleve_id = :eleve_id AND sequence_id = :sequence_id
                    ");
                    $stmtCheckBul->execute([
                        'eleve_id' => $eleve_id,
                        'sequence_id' => $sequence_id
                    ]);
                    $existingBul = $stmtCheckBul->fetch(PDO::FETCH_ASSOC);

                    $bulletin_statut = $existingBul['statut'] ?? 'valide';
                    $appreciation = $existingBul['appreciation'] ?? null;

                    if ($existingBul) {
                        $bulletin_id = (int)$existingBul['id'];
                        $stmtUpBul = $db->prepare("
                            UPDATE bulletins SET
                                classe_id = :classe_id,
                                nom_classe_snapshot = :nom_classe,
                                effectif_classe = :effectif,
                                moyenne_generale = :moyenne,
                                total_points = :pts,
                                total_coefficients = :coefs,
                                moyenne_classe = :moy_classe,
                                rang_int = :rang_int,
                                rang = :rang,
                                date_cloture = CURRENT_TIMESTAMP,
                                cloture_par_user_id = :user_id,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = :id
                        ");
                        $stmtUpBul->execute([
                            'classe_id' => $classe_id,
                            'nom_classe' => $nom_classe,
                            'effectif' => $effectif_classe,
                            'moyenne' => $report['moyenne_generale'],
                            'pts' => $report['total_points'],
                            'coefs' => $report['total_coefficients'],
                            'moy_classe' => $moyenne_classe_generale,
                            'rang_int' => $rankInfo['rang_int'],
                            'rang' => $rankInfo['rang'],
                            'user_id' => $user_id,
                            'id' => $bulletin_id
                        ]);
                    } else {
                        $stmtInsBul = $db->prepare("
                            INSERT INTO bulletins (
                                eleve_id, sequence_id, annee_academique_id, lycee_id, classe_id,
                                nom_classe_snapshot, effectif_classe, moyenne_generale, total_points,
                                total_coefficients, moyenne_classe, rang_int, rang, appreciation,
                                statut, date_cloture, cloture_par_user_id
                            ) VALUES (
                                :eleve_id, :sequence_id, :annee_id, :lycee_id, :classe_id,
                                :nom_classe, :effectif, :moyenne, :pts,
                                :coefs, :moy_classe, :rang_int, :rang, :appreciation,
                                :statut, CURRENT_TIMESTAMP, :user_id
                            )
                        ");
                        $stmtInsBul->execute([
                            'eleve_id' => $eleve_id,
                            'sequence_id' => $sequence_id,
                            'annee_id' => $annee_id,
                            'lycee_id' => $lycee_id,
                            'classe_id' => $classe_id,
                            'nom_classe' => $nom_classe,
                            'effectif' => $effectif_classe,
                            'moyenne' => $report['moyenne_generale'],
                            'pts' => $report['total_points'],
                            'coefs' => $report['total_coefficients'],
                            'moy_classe' => $moyenne_classe_generale,
                            'rang_int' => $rankInfo['rang_int'],
                            'rang' => $rankInfo['rang'],
                            'appreciation' => $appreciation,
                            'statut' => $bulletin_statut,
                            'user_id' => $user_id
                        ]);
                        $bulletin_id = (int)$db->lastInsertId();
                    }

                    $bulletinsCount++;

                    // Clear old details for this bulletin ID
                    $stmtDelDetails = $db->prepare("DELETE FROM bulletin_details WHERE bulletin_id = :bulletin_id");
                    $stmtDelDetails->execute(['bulletin_id' => $bulletin_id]);

                    // Insert snapshot bulletin_details
                    $stmtInsDetail = $db->prepare("
                        INSERT INTO bulletin_details (
                            bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere,
                            coefficient_snapshot, points_ponderes, rang_matiere,
                            moyenne_classe_matiere, appreciation_matiere
                        ) VALUES (
                            :bulletin_id, :matiere_id, :nom_matiere, :moyenne_matiere,
                            :coef, :pts, :rang_matiere,
                            :moy_classe_matiere, :appreciation
                        )
                    ");

                    foreach ($report['matieres'] as $mId => $m) {
                        $subRank = $subjectRankings[$mId][$eleve_id]['rang'] ?? null;
                        $subClassAvg = $subjectClassAverages[$mId] ?? null;

                        $stmtInsDetail->execute([
                            'bulletin_id' => $bulletin_id,
                            'matiere_id' => $mId,
                            'nom_matiere' => $m['nom'], // SNAPSHOT
                            'moyenne_matiere' => $m['moyenne'],
                            'coef' => $m['coefficient'], // SNAPSHOT
                            'pts' => $m['total_points'],
                            'rang_matiere' => $subRank,
                            'moy_classe_matiere' => $subClassAvg,
                            'appreciation' => null
                        ]);
                    }
                }

                $processedCount++;
            }

            // 8. Update sequences.statut = 'fermee'
            $stmtCloseSeq = $db->prepare("UPDATE sequences SET statut = 'fermee' WHERE id = :id");
            $stmtCloseSeq->execute(['id' => $sequence_id]);

            $db->commit();

            return [
                'success' => true,
                'message' => sprintf(_("La séquence '%s' a été clôturée avec succès. %d bulletins ont été officiellement figés."), $sequence['nom'], $bulletinsCount),
                'sequence' => $sequence,
                'bulletins_count' => $bulletinsCount,
                'classes_count' => $processedCount
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error in SequenceClosureService::closeSequence: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Standard competition rank calculation (1, 2, 2, 4).
     */
    public static function computeRanks(array $students): array {
        usort($students, function($a, $b) {
            if ($b['moyenne_generale'] == $a['moyenne_generale']) return 0;
            return ($b['moyenne_generale'] > $a['moyenne_generale']) ? 1 : -1;
        });

        $ranked = [];
        $total = count($students);
        $currentRank = 1;

        for ($i = 0; $i < $total; $i++) {
            if ($i > 0 && $students[$i]['moyenne_generale'] < $students[$i - 1]['moyenne_generale']) {
                $currentRank = $i + 1;
            }

            $eleveId = (int)$students[$i]['eleve_id'];
            $rankInt = $currentRank;

            $isTied = ($i > 0 && $students[$i]['moyenne_generale'] == $students[$i - 1]['moyenne_generale']) ||
                      ($i < $total - 1 && $students[$i]['moyenne_generale'] == $students[$i + 1]['moyenne_generale']);

            $rankStr = self::formatRank($rankInt, $isTied);

            $ranked[$eleveId] = [
                'rang_int' => $rankInt,
                'rang' => $rankStr
            ];
        }

        return $ranked;
    }

    public static function formatRank(int $rankInt, bool $isTied = false): string {
        if ($rankInt === 1) {
            $str = "1er";
        } else {
            $str = $rankInt . "ème";
        }
        if ($isTied) {
            $str .= " ex";
        }
        return $str;
    }
}
?>