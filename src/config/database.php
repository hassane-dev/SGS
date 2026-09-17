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
            error_log("Database Connection Error (MySQL/MariaDB): " . $e->getMessage());
            if (defined('APP_ENV') && APP_ENV === 'development') {
                throw new PDOException("Connexion impossible à la base de données MySQL/MariaDB : " . $e->getMessage(), (int)$e->getCode());
            } else {
                die('Impossible de se connecter à la base de données principale MySQL/MariaDB. Veuillez vérifier votre configuration.');
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
