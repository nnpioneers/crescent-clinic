<?php
/**
 * Test CSRF token extraction and validation for Batch Save
 */

session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$validToken = $_SESSION['csrf_token'];

function simulate_csrf_check($server, $post, $rawInput = '') {
    $client_token = $server['HTTP_X_CSRF_TOKEN'] ?? '';
    
    // Check getallheaders / case-insensitive server keys if needed
    if (empty($client_token) && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            if (strtolower($k) === 'x-csrf-token') {
                $client_token = $v;
                break;
            }
        }
    }
    
    if (empty($client_token) && isset($post['csrf_token'])) {
        $client_token = $post['csrf_token'];
    }

    // Check JSON body if client_token is still empty
    if (empty($client_token) && !empty($rawInput)) {
        $json = json_decode($rawInput, true);
        if (is_array($json) && !empty($json['csrf_token'])) {
            $client_token = $json['csrf_token'];
        }
    }

    if (empty($client_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $client_token)) {
        return ['success' => false, 'error' => 'Invalid or missing CSRF token'];
    }
    return ['success' => true];
}

// Test 1: Header present in $_SERVER['HTTP_X_CSRF_TOKEN']
$res1 = simulate_csrf_check(['HTTP_X_CSRF_TOKEN' => $validToken], [], '');
echo "Test 1 (Valid Header): " . ($res1['success'] ? "PASS" : "FAIL") . "\n";

// Test 2: JSON POST body with csrf_token when HTTP header is missing/stripped
$jsonBody = json_encode(['batch_number' => 'B123', 'csrf_token' => $validToken]);
$res2 = simulate_csrf_check([], [], $jsonBody);
echo "Test 2 (Valid JSON Body): " . ($res2['success'] ? "PASS" : "FAIL") . "\n";

// Test 3: Invalid token
$res3 = simulate_csrf_check(['HTTP_X_CSRF_TOKEN' => 'invalid_token'], [], '');
echo "Test 3 (Invalid Token): " . (!$res3['success'] ? "PASS (Correctly Rejected)" : "FAIL") . "\n";

// Test 4: Missing token
$res4 = simulate_csrf_check([], [], '');
echo "Test 4 (Missing Token): " . (!$res4['success'] ? "PASS (Correctly Rejected)" : "FAIL") . "\n";
