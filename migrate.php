<?php
require_once __DIR__ . '/src/config/database.php';
$db = Database::getInstance();
try {
    $db->exec("ALTER TABLE param_lycee ADD COLUMN header_primary TEXT, ADD COLUMN header_secondary TEXT;");
    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Migration failed or already applied: " . $e->getMessage() . "\n";
}
