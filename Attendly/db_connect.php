<?php
require_once 'config.php';

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            $this->logError("Connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    private function logError($message) {
        error_log("[" . date('Y-m-d H:i:s') . "] " . $message . "\n", 3, "error.log");
    }
}

// Créer l'instance globale
$database = new Database();
$pdo = $database->getConnection();
?>