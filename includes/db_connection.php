<?php
require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error safely without exposing credentials
            error_log("Database Connection Error: " . $e->getMessage());
            die("SYSTEM ERROR: DATABASE CONNECTION FAILURE");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
    
    // Helper to prevent cloning
    private function __clone() {}
    
    // Helper to prevent unserializing
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
