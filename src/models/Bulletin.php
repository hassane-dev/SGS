<?php

require_once __DIR__ . '/../config/database.php';

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
        // This more complex query calculates the weighted average for each student in the class.
        $sql = "
            SELECT
                el.id_eleve,
                el.nom,
                el.prenom,
                SUM(ev.note * cm.coefficient) / SUM(cm.coefficient) as moyenne_generale
            FROM evaluations ev
            JOIN eleves el ON ev.eleve_id = el.id_eleve
            JOIN etudes et ON el.id_eleve = et.eleve_id
            JOIN classe_matieres cm ON et.classe_id = cm.classe_id AND ev.matiere_id = cm.matiere_id
            WHERE et.classe_id = :classe_id
              AND ev.sequence_id = :sequence_id
              AND et.actif = 1
            GROUP BY el.id_eleve, el.nom, el.prenom
            ORDER BY el.nom, el.prenom;
        ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['classe_id' => $classe_id, 'sequence_id' => $sequence_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Bulletin::generateForClass: " . $e->getMessage());
            return [];
        }
    }

    public static function generateForStudent($eleve_id, $sequence_id) {
        $db = Database::getInstance();

        // This complex query fetches all grades for a student in a sequence,
        // along with the correct subject name and, crucially, the specific coefficient
        // for that subject within that student's class.
        $sql = "
            SELECT
                e.note,
                e.appreciation,
                m.nom_matiere,
                m.id_matiere,
                cm.coefficient
            FROM evaluations e
            JOIN matieres m ON e.matiere_id = m.id_matiere
            JOIN etudes et ON e.eleve_id = et.eleve_id AND e.annee_academique_id = et.annee_academique_id
            JOIN classe_matieres cm ON et.classe_id = cm.classe_id AND e.matiere_id = cm.matiere_id
            WHERE e.eleve_id = :eleve_id
              AND e.sequence_id = :sequence_id
              AND et.actif = 1 -- Ensure we're looking at the current active enrollment
            ORDER BY m.nom_matiere;
        ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'eleve_id' => $eleve_id,
                'sequence_id' => $sequence_id
            ]);
            $notes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($notes_data)) {
                return false; // No grades found for this student in this sequence.
            }

            // --- Calculations ---
            $matieres = [];
            $total_points = 0;
            $total_coefficients = 0;

            foreach ($notes_data as $note) {
                $coefficient = (float)$note['coefficient'];
                $matieres[$note['id_matiere']] = [
                    'nom' => $note['nom_matiere'],
                    'note' => (float)$note['note'],
                    'coefficient' => $coefficient,
                    'appreciation' => $note['appreciation'],
                    'total_points' => (float)$note['note'] * $coefficient
                ];
                $total_points += $matieres[$note['id_matiere']]['total_points'];
                $total_coefficients += $coefficient;
            }

            $moyenne_generale = ($total_coefficients > 0) ? $total_points / $total_coefficients : 0;

            // Fetch student and sequence info for the report card header
            $stmt_eleve = $db->prepare("
                SELECT e.*, c.nom_classe, l.nom_lycee, aa.libelle as annee_academique
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                JOIN classes c ON et.classe_id = c.id_classe
                JOIN lycees l ON c.lycee_id = l.id_lycee
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


            return [
                'eleve' => $eleve_info,
                'sequence' => $sequence_info,
                'matieres' => $matieres,
                'total_points' => $total_points,
                'total_coefficients' => $total_coefficients,
                'moyenne_generale' => round($moyenne_generale, 2),
                'bulletin_record' => $bulletin_record // Contains appreciation, rang, statut
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