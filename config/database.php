<?php
/**
 * Database Configuration File
 * MotoTrack - Bike Tracker Application
 * PRODUCTION VERSION - Uses environment variables
 */

// Load environment variables
require_once __DIR__ . '/env.php';

// Database Configuration from environment variables
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'mototrack'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Create Database Connection
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = DB_CHARSET;
    private $conn;
    private $error;

    public function __construct() {
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=" . $this->charset;
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        );

        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            
            // Log error securely
            error_log("Database Connection Failed: " . $this->error);
            
            // Show user-friendly error in production
            if (env('APP_DEBUG', false)) {
                die("Database Connection Failed: " . $this->error . "<br>Please check your database configuration in .env file");
            } else {
                die("Service temporarily unavailable. Please try again later. If the problem persists, contact support.");
            }
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function close() {
        $this->conn = null;
    }
}

// Create global PDO connection
$database = new Database();
$pdo = $database->getConnection();

// NOTE: getDB() function is now in config.php (NOT here)
// This prevents "Cannot redeclare getDB()" errors
?>
