<?php
/**
 * Database Configuration for Eden Miracle Church Management System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'eden_church_db';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ];
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $exception) {
                error_log("DB Connection error: " . $exception->getMessage());
                // For debugging during development:
                die("DB Connection error: " . $exception->getMessage());
            }
        }
        return $this->conn;
    }
}

// Database config constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'eden_church_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', 'http://localhost/eden-church/');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB

// Enhanced session settings
if (session_status() === PHP_SESSION_NONE) {
    // Security-focused session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_lifetime', 86400); // 1 day
    ini_set('session.gc_maxlifetime', 86400);
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) { // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

date_default_timezone_set('Africa/Kampala');