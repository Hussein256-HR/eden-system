<?php
/**
 * Database Configuration for Eden Miracle Church Management System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'eden_church_db';
    private $username = 'root'; // Change this to your database username
    private $password = '';     // Change this to your database password
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'eden_church_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('BASE_URL', 'http://localhost/eden-church/');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// Timezone
date_default_timezone_set('Africa/Kampala');
?>