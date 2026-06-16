<?php
require_once __DIR__ . '/src/config/database.php';

try {
    $db = Database::getInstance();

    echo "Starting migration...\n";

    // 1. Update evaluations table
    echo "Updating evaluations table...\n";
    $db->exec("ALTER TABLE evaluations ADD COLUMN type ENUM('devoir', 'composition') NOT NULL DEFAULT 'devoir' AFTER annee_academique_id");
    $db->exec("ALTER TABLE evaluations DROP INDEX unique_evaluation_note");
    $db->exec("ALTER TABLE evaluations ADD UNIQUE KEY unique_evaluation_note (eleve_id, matiere_id, sequence_id, annee_academique_id, type)");

    // 2. Update deblocages_notes table
    echo "Updating deblocages_notes table...\n";
    $db->exec("ALTER TABLE deblocages_notes ADD COLUMN type_evaluation ENUM('devoir', 'composition', 'tous') NOT NULL DEFAULT 'tous' AFTER sequence_id");

    // 3. Update parametres_evaluations table
    echo "Updating parametres_evaluations table...\n";
    $db->exec("ALTER TABLE parametres_evaluations ADD COLUMN type_evaluation ENUM('devoir', 'composition', 'tous') NOT NULL DEFAULT 'tous' AFTER annee_academique_id");
    $db->exec("ALTER TABLE parametres_evaluations DROP INDEX unique_param_eval");
    $db->exec("ALTER TABLE parametres_evaluations ADD UNIQUE KEY unique_param_eval (classe_id, matiere_id, sequence_id, annee_academique_id, type_evaluation)");

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
