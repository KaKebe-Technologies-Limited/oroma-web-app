<?php
/**
 * Database Configuration for Oroma TV
 * Supports both local development and shared hosting
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Auto-detect environment and set database credentials
        if ($this->isSharedHosting()) {
            // Shared hosting configuration
            $this->host = "localhost";
            $this->db_name = $_ENV['DB_NAME'] ?? "oromatv";
            $this->username = $_ENV['DB_USER'] ?? "root";
            $this->password = $_ENV['DB_PASS'] ?? "";
        } else {
            // Local development configuration
            $this->host = $_ENV['DB_HOST'] ?? "localhost";
            $this->db_name = $_ENV['DB_NAME'] ?? "oromatv";
            $this->username = $_ENV['DB_USER'] ?? "root";
            $this->password = $_ENV['DB_PASS'] ?? "";
        }
    }
    
    private function isSharedHosting() {
        // Detect shared hosting environment
        return isset($_SERVER['HTTP_HOST']) && 
               (strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false ||
                strpos($_SERVER['HTTP_HOST'], 'cpanel') !== false ||
                strpos($_SERVER['HTTP_HOST'], 'shared') !== false);
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            return $conn !== null;
        } catch(Exception $e) {
            return false;
        }
    }
}

// Load environment variables from .env if exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
?>