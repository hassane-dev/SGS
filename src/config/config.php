<?php

// =================================================================
// Application Configuration File
// =================================================================

// --- Database Configuration ---
// Replace with your actual database credentials
define('DB_HOST', '127.0.0.1');       // Database host (e.g., 'localhost' or '127.0.0.1')
define('DB_NAME', 'gestion_lycee');  // Database name
define('DB_USER', 'root');           // Database username
define('DB_PASS', 'password');       // Database password
define('DB_CHARSET', 'utf8mb4');     // Character set

// --- Application Settings ---
define('APP_NAME', 'Gestion des Lycées');
define('APP_ENV', 'development'); // Environment: 'development' or 'production'

// --- Security ---
// It's highly recommended to use a long, random string for session security
define('SESSION_SECRET', 'a_very_secret_and_long_key_for_sessions');
define('CARD_SIGNATURE_SECRET', 'SECURE_SCHOOL_APP_2024');

// --- Upload Settings ---
define('UPLOAD_BASE_DIR', __DIR__ . '/../../public/uploads');
define('UPLOAD_PUBLIC_PATH', '/uploads');

?>
