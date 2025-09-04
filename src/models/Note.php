<?php

require_once __DIR__ . '/../config/database.php';

class Note {

    /**
     * Get all students for a given class, along with their existing grade for a specific subject and type.
     * @param int $class_id
     * @param int $matiere_id
     * @param string $type 'devoir' or 'composition'
     * @return array
     */
    public static function getStudentsForGrading($class_id, $matiere_id, $type) {
        $db = Database::getInstance();

        $note_table = ($type === 'devoir') ? 'notes_devoirs' : 'notes_compositions';

        // First, get all active students in the class
        $sql = "SELECT e.id_eleve, e.nom, e.prenom
                FROM eleves e
                JOIN etudes et ON e.id_eleve = et.eleve_id
                WHERE et.classe_id = :class_id AND et.actif = 1
                ORDER BY e.nom, e.prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['class_id' => $class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Now, get existing notes for these students
        $sql_notes = "SELECT eleve_id, note FROM {$note_table} WHERE classe_id = :class_id AND matiere_id = :matiere_id";
        $stmt_notes = $db->prepare($sql_notes);
        $stmt_notes->execute(['class_id' => $class_id, 'matiere_id' => $matiere_id]);
        $notes_raw = $stmt_notes->fetchAll(PDO::FETCH_KEY_PAIR); // eleve_id => note

        // Merge notes into the student list
        foreach ($students as &$student) {
            $student['note'] = $notes_raw[$student['id_eleve']] ?? null;
        }

        return $students;
    }

    /**
     * Save a batch of grades. This uses INSERT ... ON DUPLICATE KEY UPDATE.
     * For this to work, we need a unique key on (eleve_id, classe_id, matiere_id) in the notes tables.
     * Let's assume we will add this unique key to the schema.
     * @param int $class_id
     * @param int $matiere_id
     * @param string $type 'devoir' or 'composition'
     * @param array $notes An associative array of ['eleve_id' => 'note']
     * @return bool
     */
    public static function saveBatch($class_id, $matiere_id, $type, $notes) {
        $db = Database::getInstance();
        $note_table = ($type === 'devoir') ? 'notes_devoirs' : 'notes_compositions';
        $date_col = ($type === 'devoir') ? 'date_devoir' : 'date_composition';

        // We need to add a UNIQUE constraint in our schema for this to work reliably.
        // ALTER TABLE `notes_devoirs` ADD UNIQUE `unique_grade`(`eleve_id`, `classe_id`, `matiere_id`);
        // ALTER TABLE `notes_compositions` ADD UNIQUE `unique_grade`(`eleve_id`, `classe_id`, `matiere_id`);

        $sql = "INSERT INTO {$note_table} (eleve_id, classe_id, matiere_id, note, {$date_col}) VALUES (:eleve_id, :classe_id, :matiere_id, :note, CURDATE())
                ON DUPLICATE KEY UPDATE note = VALUES(note), {$date_col} = VALUES({$date_col})";

        try {
            $db->beginTransaction();
            $stmt = $db->prepare($sql);
            foreach ($notes as $eleve_id => $note) {
                if ($note !== '' && is_numeric($note)) {
                    $stmt->execute([
                        'eleve_id' => $eleve_id,
                        'classe_id' => $class_id,
                        'matiere_id' => $matiere_id,
                        'note' => $note
                    ]);
                }
            }
            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error in Note::saveBatch: " . $e->getMessage());
            return false;
        }
    }
}
?>
