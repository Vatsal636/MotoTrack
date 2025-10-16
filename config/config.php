<?php
/**
 * Main Configuration File
 * MotoTrack - Bike Tracker Application
 * PRODUCTION VERSION - Enhanced Security
 */

// Load environment configuration
require_once __DIR__ . '/env.php';

// Error Reporting - OFF in production
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Session Configuration - Enhanced Security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', env('APP_ENV') === 'production' ? 1 : 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.name', env('SESSION_NAME', 'MOTOTRACK_SESSION'));

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Application Settings
define('APP_NAME', env('APP_NAME', 'MotoTrack'));
define('APP_VERSION', '1.0.0');
define('APP_URL', env('APP_URL', 'http://localhost/mttt/'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', false));

// Path Settings
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes/');
define('CONFIG_PATH', ROOT_PATH . '/config/');
define('LOGS_PATH', ROOT_PATH . '/logs/');

// Create logs directory if it doesn't exist
if (!file_exists(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}

// Date and Time Settings
date_default_timezone_set(env('TIMEZONE', 'Asia/Kolkata'));
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd M Y');

// Include Database Configuration
require_once CONFIG_PATH . 'database.php';

// Security Settings
define('PASSWORD_MIN_LENGTH', env('PASSWORD_MIN_LENGTH', 8));
define('SESSION_TIMEOUT', env('SESSION_TIMEOUT', 3600)); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes lockout

// Pagination Settings
define('RECORDS_PER_PAGE', 10);

// Reminder Settings (days before due date)
define('REMINDER_WARNING_DAYS', 7);

// Helper Functions

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Escape output for display (XSS protection)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate input data
 */
function validateInput($data, $type = 'text', $options = []) {
    $data = trim($data);
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
        case 'number':
            return is_numeric($data);
        case 'integer':
            return filter_var($data, FILTER_VALIDATE_INT) !== false;
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT) !== false;
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL) !== false;
        case 'phone':
            return preg_match('/^[0-9]{10,15}$/', $data);
        case 'date':
            $d = DateTime::createFromFormat('Y-m-d', $data);
            return $d && $d->format('Y-m-d') === $data;
        case 'alpha':
            return preg_match('/^[a-zA-Z]+$/', $data);
        case 'alphanumeric':
            return preg_match('/^[a-zA-Z0-9]+$/', $data);
        default:
            return !empty($data);
    }
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) 
        || (time() - $_SESSION['csrf_token_time'] > 3600)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Require login with timeout check
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('Please login to continue', 'warning');
        redirect('login.php');
    }
    
    if (!checkSessionTimeout()) {
        setFlashMessage('Your session has expired. Please login again.', 'warning');
        redirect('login.php');
    }
}

/**
 * Get current user ID
 */
function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Check login attempts (rate limiting)
 */
function checkLoginAttempts($identifier) {
    $key = 'login_attempts_' . md5($identifier);
    $attempts = isset($_SESSION[$key]) ? $_SESSION[$key] : ['count' => 0, 'time' => time()];
    
    // Reset if timeout expired
    if (time() - $attempts['time'] > LOGIN_TIMEOUT) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
        return true;
    }
    
    // Check if locked out
    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        $remaining = LOGIN_TIMEOUT - (time() - $attempts['time']);
        return ['locked' => true, 'remaining' => ceil($remaining / 60)];
    }
    
    return true;
}

/**
 * Record login attempt
 */
function recordLoginAttempt($identifier, $success = false) {
    $key = 'login_attempts_' . md5($identifier);
    
    if ($success) {
        // Clear attempts on successful login
        unset($_SESSION[$key]);
    } else {
        // Increment failed attempts
        $attempts = isset($_SESSION[$key]) ? $_SESSION[$key] : ['count' => 0, 'time' => time()];
        $_SESSION[$key] = [
            'count' => $attempts['count'] + 1,
            'time' => $attempts['time']
        ];
    }
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Log activity for audit trail
 */
function logActivity($user_id, $action, $details = '', $level = 'INFO') {
    $log_file = LOGS_PATH . 'activity_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $log_entry = sprintf(
        "[%s] [%s] User: %s | IP: %s | Action: %s | Details: %s | User-Agent: %s\n",
        $timestamp,
        $level,
        $user_id,
        $ip,
        $action,
        $details,
        $user_agent
    );
    
    error_log($log_entry, 3, $log_file);
}

/**
 * Format date for display
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = sanitizeInput($message);
    $_SESSION['flash_type'] = in_array($type, ['success', 'error', 'warning', 'info']) ? $type : 'info';
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Redirect to URL
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit();
    }
}

/**
 * Calculate mileage
 */
function calculateMileage($distance, $fuel) {
    if ($fuel <= 0) return 0;
    return round($distance / $fuel, 2);
}

/**
 * Get database connection
 */
function getDB() {
    global $pdo;
    return $pdo;
}
?>
