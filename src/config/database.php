<?php

// Include the configuration file
require_once __DIR__ . '/config.php';

/**
 * Class Database
 * Handles the database connection using PDO.
 * Implements a Singleton pattern to ensure only one connection is made.
 */
class Database {

    // Hold the class instance.
    private static $instance = null;
    private $conn;

    /**
     * The constructor is private to prevent initiation with 'new'.
     */
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In a real application, you would log this error and show a generic message.
            // For development, it's okay to show the error.
            if (APP_ENV === 'development') {
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            } else {
                // On production, don't reveal sensitive error details.
                die('Could not connect to the database. Please try again later.');
            }
        }
    }

    /**
     * Gets the single instance of the database connection.
     * @return PDO The PDO database connection.
     */
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }

    /**
     * Sets a mock database instance for testing purposes.
     * @param PDO $pdo The mock PDO connection.
     */
    public static function setInstance(?PDO $pdo) {
        if ($pdo === null) {
            self::$instance = null;
            return;
        }
        $mock = new stdClass();
        $mock->conn = $pdo;
        self::$instance = $mock;
    }

    /**
     * Private clone method to prevent cloning of the instance.
     */
    private function __clone() { }

    /**
     * Public wakeup method to prevent unserializing of the instance.
     * Must be public.
     */
    public function __wakeup() { }
}

?>
