<?php
require_once __DIR__ . '/src/config/database.php';

try {
    $db = Database::getInstance();

    echo "Starting harmonization migration for parametres_evaluations...\n";

    // 1. Add 'type' column to parametres_evaluations
    echo "Adding 'type' column...\n";
    $db->exec("ALTER TABLE parametres_evaluations ADD COLUMN type ENUM('global', 'classe', 'matiere', 'classe_matiere', 'enseignant') NOT NULL DEFAULT 'enseignant' AFTER annee_academique_id");

    // 2. Make classe_id and matiere_id nullable
    echo "Making classe_id and matiere_id nullable...\n";
    $db->exec("ALTER TABLE parametres_evaluations MODIFY COLUMN classe_id INT DEFAULT NULL");
    $db->exec("ALTER TABLE parametres_evaluations MODIFY COLUMN matiere_id INT DEFAULT NULL");

    // 3. Update existing records to 'enseignant' type (which was the previous implicit behavior)
    // Actually, previously it was class+subject. In my new system 'enseignant' is class+subject+enseignant.
    // If enseignant_id was NULL, maybe it was 'classe_matiere'.
    $db->exec("UPDATE parametres_evaluations SET type = 'enseignant' WHERE enseignant_id IS NOT NULL");
    $db->exec("UPDATE parametres_evaluations SET type = 'classe_matiere' WHERE enseignant_id IS NULL");

    // 4. Clean up unique indexes for both tables to handle NULLs in code
    echo "Cleaning up unique indexes...\n";
    try {
        $db->exec("ALTER TABLE parametres_evaluations DROP INDEX unique_param_eval");
    } catch (Exception $e) {}

    // 5. Ensure deblocages_notes is also harmonized
    echo "Checking deblocages_notes...\n";
    // Ensure all columns exist and are nullable as needed
    // deblocages_notes already has 'type', 'classe_id', 'matiere_id', 'enseignant_id', 'sequence_id', 'type_evaluation'
    // Let's just make sure they are nullable
    $db->exec("ALTER TABLE deblocages_notes MODIFY COLUMN classe_id INT DEFAULT NULL");
    $db->exec("ALTER TABLE deblocages_notes MODIFY COLUMN matiere_id INT DEFAULT NULL");
    $db->exec("ALTER TABLE deblocages_notes MODIFY COLUMN enseignant_id INT DEFAULT NULL");
    $db->exec("ALTER TABLE deblocages_notes MODIFY COLUMN sequence_id INT DEFAULT NULL");

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
