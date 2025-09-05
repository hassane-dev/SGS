<?php

class Bulletin {

    /**
     * Generate all data required for a student's report card.
     * @param int $etude_id The ID of the enrollment record.
     * @return array|false An array containing all report card data, or false on error.
     */
    public static function generateForEtude($etude_id) {
        $db = Database::getInstance();

        // 1. Get Enrollment, Student, and Class Info
        $stmt = $db->prepare("
            SELECT et.annee_academique, e.*, c.*, l.nom_lycee
            FROM etudes et
            JOIN eleves e ON et.eleve_id = e.id_eleve
            JOIN classes c ON et.classe_id = c.id_classe
            JOIN lycees l ON c.lycee_id = l.id_lycee
            WHERE et.id_etude = :etude_id
        ");
        $stmt->execute(['etude_id' => $etude_id]);
        $report_data['info'] = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report_data['info']) {
            return false;
        }

        $eleve_id = $report_data['info']['id_eleve'];
        $classe_id = $report_data['info']['id_classe'];

        // 2. Get all subjects for the class
        $stmt_matieres = $db->prepare("
            SELECT m.id_matiere, m.nom_matiere, m.coef
            FROM matieres m
            JOIN classe_matieres cm ON m.id_matiere = cm.matiere_id
            WHERE cm.classe_id = :classe_id
        ");
        $stmt_matieres->execute(['classe_id' => $classe_id]);
        $matieres = $stmt_matieres->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get all grades for the student in this class
        $stmt_devoirs = $db->prepare("SELECT matiere_id, note FROM notes_devoirs WHERE eleve_id = :eleve_id AND classe_id = :classe_id");
        $stmt_devoirs->execute(['eleve_id' => $eleve_id, 'classe_id' => $classe_id]);
        $notes_devoirs_raw = $stmt_devoirs->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        $stmt_comps = $db->prepare("SELECT matiere_id, note FROM notes_compositions WHERE eleve_id = :eleve_id AND classe_id = :classe_id");
        $stmt_comps->execute(['eleve_id' => $eleve_id, 'classe_id' => $classe_id]);
        $notes_comps_raw = $stmt_comps->fetchAll(PDO::FETCH_KEY_PAIR);

        // 4. Calculate averages
        $report_data['results'] = [];
        $total_points = 0;
        $total_coef = 0;

        foreach ($matieres as $matiere) {
            $matiere_id = $matiere['id_matiere'];

            $devoirs = $notes_devoirs_raw[$matiere_id] ?? [];
            $sum_devoirs = 0;
            $count_devoirs = 0;
            foreach($devoirs as $d) {
                $sum_devoirs += $d['note'];
                $count_devoirs++;
            }
            $moyenne_devoirs = ($count_devoirs > 0) ? $sum_devoirs / $count_devoirs : null;

            $note_composition = $notes_comps_raw[$matiere_id] ?? null;

            // Calculate subject average using the agreed formula
            $moyenne_matiere = null;
            if ($moyenne_devoirs !== null && $note_composition !== null) {
                $moyenne_matiere = ($moyenne_devoirs + ($note_composition * 2)) / 3;
            } elseif ($note_composition !== null) {
                $moyenne_matiere = $note_composition; // Fallback if no homework grades
            } elseif ($moyenne_devoirs !== null) {
                $moyenne_matiere = $moyenne_devoirs; // Fallback if no exam grade
            }

            $report_data['results'][$matiere_id] = [
                'nom_matiere' => $matiere['nom_matiere'],
                'coef' => $matiere['coef'],
                'moyenne_devoirs' => $moyenne_devoirs,
                'note_composition' => $note_composition,
                'moyenne_matiere' => $moyenne_matiere,
            ];

            if ($moyenne_matiere !== null && $matiere['coef'] > 0) {
                $total_points += $moyenne_matiere * $matiere['coef'];
                $total_coef += $matiere['coef'];
            }
        }

        $report_data['moyenne_generale'] = ($total_coef > 0) ? $total_points / $total_coef : 0;

        return $report_data;
    }
}
?>
