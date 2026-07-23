<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/management/analytics';
$_GET = ['period' => 'today', 'doctor_type' => 'all'];

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'management';

ob_start();
require 'api/api.php';
$output = ob_get_clean();

if (strpos($output, 'error') !== false || strpos($output, 'Warning') !== false || strpos($output, 'Fatal') !== false) {
    echo "ERROR OUTPUT:\n" . $output;
} else {
    echo "SUCCESS! First 200 chars:\n" . substr($output, 0, 200);
}
