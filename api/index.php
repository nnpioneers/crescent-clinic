<?php
/**
 * Main Router / Entry Point for Hospital Portal (PHP Version)
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../app/Core/template_parser.php';

// Schema initialization has been delegated to explicit admin endpoints or CLI
// to prevent Turso/SQLite latency spikes on every Vercel serverless HTTP request.

// Background process spawning has been delegated to Vercel Cron
// GET /api/cron/backup is triggered automatically by Vercel.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper to send JSON response
function json_response($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// ═══════════════════════════════════════════
// PAGE ROUTES
// ═══════════════════════════════════════════

if ($uri === '/' || $uri === '') {
    header('Location: /login');
    exit;
}

if ($uri === '/login') {
    if ($method === 'POST') {
        // Input validation
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            echo TemplateParser::render(__DIR__ . '/../templates/login.html', [
                'error' => 'Username and password are required.'
            ]);
            exit;
        }
        if (strlen($username) > 64 || strlen($password) > 64) {
            echo TemplateParser::render(__DIR__ . '/../templates/login.html', [
                'error' => 'Input exceeds maximum allowed length.'
            ]);
            exit;
        }
        // Brute-force protection: limit to 5 attempts within 15 minutes
        $maxAttempts = 5;
        $lockoutMinutes = 15;
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['first_attempt_time'] = time();
        }
        if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['first_attempt_time']) < ($lockoutMinutes * 60)) {
            echo TemplateParser::render(__DIR__ . '/../templates/login.html', [
                'error' => 'Too many failed login attempts. Please try again later.'
            ]);
            exit;
        }

        $conn = get_db();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        $authenticated = false;
        if ($user) {
            $user_pw = $user['password'] ?? $user['password_hash'] ?? '';
            if (strpos($user_pw, '$2') === 0 || strpos($user_pw, '$argon2') === 0) {
                if (password_verify($password, $user_pw)) {
                    $authenticated = true;
                }
            } else {
                if ($user_pw === $password) {
                    $authenticated = true;
                    // Transparently upgrade plaintext password to secure hash
                    try {
                        $new_hash = password_hash($password, PASSWORD_BCRYPT);
                        $upStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $upStmt->execute([$new_hash, $user['id']]);
                        $user['password'] = $new_hash;
                    } catch (Exception $e) {}
                }
            }
        }
        
        if (!$authenticated) {
            $user = false;
        }
        
        if ($user && $user['is_active'] == 0) {
            echo TemplateParser::render(__DIR__ . '/../templates/login.html', [
                'error' => 'Account is inactive. Please contact admin.'
            ]);
            exit;
        }

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['doctor_type'] = $user['doctor_type'];
            if ($user['role'] === 'doctor') {
                $_SESSION['doctor_id'] = $user['id'];
            }
            
            $display_name = $user['display_name'] ?: $user['username'];
            $_SESSION['display_name'] = $display_name;
            $_SESSION['last_activity'] = time();
            // Reset brute-force counters on successful login
            unset($_SESSION['login_attempts'], $_SESSION['first_attempt_time']);

            $dest = [
                'receptionist' => '/receptionist',
                'doctor' => '/doctor',
                'pharmacist' => '/pharmacy',
                'management' => '/management',
                'monitor' => '/monitor'
            ];
            $redirect_url = $dest[$user['role']] ?? '/login';
            
            // Critical fix: commit and close session lock before sending redirect header
            session_write_close();
            header('Location: ' . $redirect_url);
            exit;
        } else {
            echo TemplateParser::render(__DIR__ . '/../templates/login.html', [
                'error' => 'Invalid username or password'
            ]);
            // Increment failed attempts
            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 1;
                $_SESSION['first_attempt_time'] = time();
            } else {
                $_SESSION['login_attempts']++;
            }
            exit;
        }
    }
    
    echo TemplateParser::render(__DIR__ . '/../templates/login.html', [
        'error' => ''
    ]);
    exit;
}

if ($uri === '/logout') {
    // Prevent browser prefetching from destroying the session
    $isPrefetch = (isset($_SERVER['HTTP_X_PURPOSE']) && $_SERVER['HTTP_X_PURPOSE'] === 'prefetch') ||
                  (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] === 'prefetch') ||
                  (isset($_SERVER['HTTP_SEC_PURPOSE']) && strpos($_SERVER['HTTP_SEC_PURPOSE'], 'prefetch') !== false) ||
                  (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && in_array($_SERVER['HTTP_SEC_FETCH_DEST'], ['empty', 'image'])) ||
                  (isset($_SERVER['HTTP_PURPOSE']) && $_SERVER['HTTP_PURPOSE'] === 'prefetch');
                  
    if ($isPrefetch) {
        http_response_code(200);
        exit;
    }

    session_destroy();
    header('Location: /login');
    exit;
}

if ($uri === '/receptionist') {
    login_required('receptionist');
    $display_name = $_SESSION['display_name'];
    session_write_close();
    echo TemplateParser::render(__DIR__ . '/../templates/receptionist.html', ['display_name' => $display_name]);
    exit;
}

if ($uri === '/doctor') {
    login_required('doctor');
    $display_name = $_SESSION['display_name'];
    $doctor_type = $_SESSION['doctor_type'];
    session_write_close();
    $formatted_name = format_doctor_name($display_name, $doctor_type);
    echo TemplateParser::render(__DIR__ . '/../templates/doctor.html', [
        'display_name' => $formatted_name,
        'doctor_name' => $formatted_name,
        'doctor_type' => $doctor_type
    ]);
    exit;
}

if ($uri === '/pharmacy') {
    login_required('pharmacist');
    $display_name = $_SESSION['display_name'];
    session_write_close();
    echo TemplateParser::render(__DIR__ . '/../templates/pharmacy.html', ['display_name' => $display_name]);
    exit;
}

if ($uri === '/management') {
    login_required('management');
    $display_name = $_SESSION['display_name'];
    session_write_close();
    echo TemplateParser::render(__DIR__ . '/../templates/management.html', ['display_name' => $display_name]);
    exit;
}

if ($uri === '/monitor') {
    login_required('monitor');
    session_write_close();
    $conn = get_db();
    $stmt = $conn->prepare("SELECT * FROM users WHERE role='monitor' AND is_active=1 LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>Monitor module is currently disabled. Please enable it in the Manage Staff section.</p></div>";
        exit;
    }
    echo TemplateParser::render(__DIR__ . '/../templates/monitor.html', []);
    exit;
}

if ($uri === '/portfolio') {
    session_write_close();
    echo TemplateParser::render(__DIR__ . '/../templates/portfolio.html', []);
    exit;
}

// ═══════════════════════════════════════════
// API ROUTES — Dispatch to api.php
// ═══════════════════════════════════════════

if ($uri === '/control_access') {
    login_required('management');
    $module = $_GET['module'] ?? '';

    if ($module === 'receptionist') {
        echo TemplateParser::render(__DIR__ . '/../templates/receptionist.html', ['display_name' => $_SESSION['display_name'] ?? 'Reception']);
        exit;
    }

    if ($module === 'doctor') {
        $type = $_GET['type'] ?? 'Gents';
        $id = $_GET['id'] ?? null;
        $conn = get_db();
        
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='doctor' AND is_active=1");
            $stmt->execute([$id]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE role='doctor' AND doctor_type=? AND is_active=1 LIMIT 1");
            $stmt->execute([$type]);
        }
        
        $doc = $stmt->fetch();
        if ($doc) {
            $_SESSION['doctor_id'] = $doc['id'];
            $_SESSION['doctor_type'] = $doc['doctor_type'];
            $formatted_name = format_doctor_name($doc['display_name'], $doc['doctor_type']);
            echo TemplateParser::render(__DIR__ . '/../templates/doctor.html', [
                'display_name' => $formatted_name,
                'doctor_name' => $formatted_name,
                'doctor_type' => $doc['doctor_type']
            ]);
        } else {
            echo "<h2>Doctor Not Found</h2>";
        }
        exit;
    }

    if ($module === 'pharmacy') {
        echo TemplateParser::render(__DIR__ . '/../templates/pharmacy.html', ['display_name' => $_SESSION['display_name'] ?? 'Pharmacy']);
        exit;
    }

    if ($module === 'monitor') {
        $conn = get_db();
        $stmt = $conn->prepare("SELECT * FROM users WHERE role='monitor' AND is_active=1 LIMIT 1");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo TemplateParser::render(__DIR__ . '/../templates/monitor.html', []);
        } else {
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>Monitor module is currently disabled. Please enable it in the Manage Staff section.</p></div>";
        }
        exit;
    }
}

if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/api.php';
    exit;
}

if (strpos($uri, '/reports_api') === 0) {
    require_once __DIR__ . '/reports_api.php';
    exit;
}

// 404
http_response_code(404);
echo "404 Not Found";
