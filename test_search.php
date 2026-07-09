<?php
require 'db.php';
$_SERVER['REQUEST_URI']='/api/inventory/search';
$_SERVER['REQUEST_METHOD']='GET';
$_GET['q']='p';
$_GET['category']='medicine';

// DO NOT USE session_start() because it blocks.
$_SESSION = ['user_id' => 1, 'role' => 'pharmacist'];

ob_start();
try {
    require 'api/api.php';
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
$out = ob_get_clean();
file_put_contents('test_search.json', $out);
echo "DONE";
