<?php
/**
 * Authentication and Session Management
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    ini_set('session.cookie_lifetime', 86400); // 24 hours
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // Set Secure flag only if connection is HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }

    require_once __DIR__ . '/app/Core/session_handler.php';
    session_set_save_handler(new DatabaseSessionHandler(), true);
    session_start();
    
    // Ensure CSRF token is available for all forms, including login
if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Global CSRF Protection for state-changing requests, now includes login
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Enforce CSRF on all POST endpoints, including login
    $client_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($client_token) && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            if (strtolower($k) === 'x-csrf-token') {
                $client_token = $v;
                break;
            }
        }
    }
    if (empty($client_token) && isset($_POST['csrf_token'])) {
        $client_token = $_POST['csrf_token'];
    }
    if (empty($client_token)) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $json = json_decode($rawInput, true);
            if (is_array($json) && !empty($json['csrf_token'])) {
                $client_token = $json['csrf_token'];
            }
        }
    }
    if (empty($client_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $client_token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
}


function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function login_required($role = null) {
    // Session timeout handling (30 minutes inactivity)
if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 1800) {
            // Inactive for more than 30 minutes
            session_unset();
            session_destroy();
            header('Location: /login');
            exit;
        }
    }
    // Refresh activity timestamp
    $_SESSION['last_activity'] = time();
    
    // Management/Admin has access to everything
    if ($_SESSION['role'] === 'management') {
        return;
    }

    if ($role && $_SESSION['role'] !== $role) {
        header('Location: /login');
        exit;
    }
}

function get_session_user() {
    // Reset inactivity timer if session is valid
    if (isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }
    return isset($_SESSION['user_id']) ? $_SESSION : null;
}
