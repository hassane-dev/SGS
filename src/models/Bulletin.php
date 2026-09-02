<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EvaluationCalculationService.php';

class Bulletin {

    /**
     * Gathers all necessary data and calculates averages for a student's report card for a specific sequence.
     *
     * @param int $eleve_id The student's ID.
     * @param int $sequence_id The sequence's ID.
     * @return array|false An array containing the report card data or false on failure.
     */
    public static function generateForClass($classe_id, $sequence_id) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT id_eleve, nom, prenom FROM eleves el JOIN etudes et ON el.id_eleve = et.eleve_id WHERE et.classe_id = :classe_id AND et.actif = 1 ORDER BY el.nom, el.prenom");
            $stmt->execute(['classe_id' => $classe_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($students as $stu) {
                $report = EvaluationCalculationService::computeStudentSequenceReport((int)$stu['id_eleve'], (int)$sequence_id);
                $results[] = [
                    'id_eleve' => $stu['id_eleve'],
                    'nom' => $stu['nom'],
                    'prenom' => $stu['prenom'],
                    'moyenne_generale' => $report['moyenne_generale']
                ];
            }
            return $results;
        } catch (PDOException $e) {
            error_log("Error in Bulletin::generateForClass: " . $e->getMessage());
            return [];
        }
    }

    public static function generateForStudent($eleve_id, $sequence_id) {
        $db = Database::getInstance();

        try {
            $report = EvaluationCalculationService::computeStudentSequenceReport((int)$eleve_id, (int)$sequence_id);

            if (empty($report['matieres'])) {
                return false; // No grades found
            }

            // Fetch student and sequence info for the report card header
            $stmt_eleve = $db->prepare("
                SELECT e.*, l.nom_lycee, aa.libelle as annee_academique
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                JOIN classes c ON et.classe_id = c.id_classe
                JOIN param_lycee l ON c.lycee_id = l.id
                JOIN annees_academiques aa ON et.annee_academique_id = aa.id
                WHERE e.id_eleve = :id AND et.actif = 1
            ");
            $stmt_eleve->execute(['id' => $eleve_id]);
            $eleve_info = $stmt_eleve->fetch(PDO::FETCH_ASSOC);

            $stmt_sequence = $db->prepare("SELECT * FROM sequences WHERE id = :id");
            $stmt_sequence->execute(['id' => $sequence_id]);
            $sequence_info = $stmt_sequence->fetch(PDO::FETCH_ASSOC);

            // Fetch existing bulletin record if it exists
            $stmt_bulletin = $db->prepare("SELECT * FROM bulletins WHERE eleve_id = :eleve_id AND sequence_id = :sequence_id");
            $stmt_bulletin->execute(['eleve_id' => $eleve_id, 'sequence_id' => $sequence_id]);
            $bulletin_record = $stmt_bulletin->fetch(PDO::FETCH_ASSOC);

            // Format matieres array for view rendering
            $formattedMatieres = [];
            foreach ($report['matieres'] as $mId => $m) {
                // Determine appreciation or first evaluation appreciation
                $firstAppreciation = null;
                foreach ($m['evaluations'] as $ev) {
                    if (!empty($ev['appreciation'])) {
                        $firstAppreciation = $ev['appreciation'];
                        break;
                    }
                }
                $formattedMatieres[$mId] = [
                    'nom' => $m['nom'],
                    'note' => $m['moyenne'],
                    'coefficient' => $m['coefficient'],
                    'appreciation' => $firstAppreciation,
                    'total_points' => $m['total_points'],
                    'evaluations' => $m['evaluations']
                ];
            }

            return [
                'eleve' => $eleve_info,
                'sequence' => $sequence_info,
                'matieres' => $formattedMatieres,
                'total_points' => $report['total_points'],
                'total_coefficients' => $report['total_coefficients'],
                'moyenne_generale' => $report['moyenne_generale'],
                'bulletin_record' => $bulletin_record
            ];

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