<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com https://via.placeholder.com; img-src 'self' data: https://via.placeholder.com; font-src 'self' https://fonts.gstatic.com;");

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

// Start session
session_start();

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Login required']);
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

// CSRF Protection Functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRFToken() {
    $token = null;
    
    // Check for token in POST data
    if (isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }
    // Check for token in JSON data
    elseif ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['csrf_token'])) {
            $token = $input['csrf_token'];
        }
    }
    // Check for token in headers
    elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    
    if (!$token || !validateCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }
}

// XSS Protection Functions
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Session Security Functions
function regenerateSession() {
    session_regenerate_id(true);
}

function destroySession() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Rate limiting function (basic implementation)
function checkRateLimit($action, $limit = 5, $window = 300) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = array();
    }
    
    $now = time();
    
    // Clean old entries
    foreach ($_SESSION['rate_limit'] as $k => $v) {
        if ($v['time'] < ($now - $window)) {
            unset($_SESSION['rate_limit'][$k]);
        }
    }
    
    // Count current attempts
    $attempts = 0;
    foreach ($_SESSION['rate_limit'] as $entry) {
        if ($entry['action'] === $action && $entry['time'] > ($now - $window)) {
            $attempts++;
        }
    }
    
    if ($attempts >= $limit) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
        exit;
    }
    
    // Record this attempt
    $_SESSION['rate_limit'][] = array(
        'action' => $action,
        'time' => $now
    );
}
?>
