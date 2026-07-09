<?php
$_SERVER['REQUEST_URI'] = '/api/inventory/search';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['category'] = 'medicine';
$_GET['q'] = 'acec';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'pharmacist';

ob_start();
include 'api/api.php';
$out = ob_get_clean();
echo $out;
