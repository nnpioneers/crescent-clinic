<?php
$content = file_get_contents("api/api.php");

$find = <<<'EOD'
if ($uri === '/api/generics/search' && $method === 'GET') {
    enforce_api_auth(['pharmacist', 'doctor', 'receptionist']);
    $q = trim($_GET['q'] ?? '');
    $conn = get_db();
    
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT generic_name FROM generic_mappings 
            WHERE generic_name LIKE ? AND generic_name != '' AND generic_name IS NOT NULL
            ORDER BY generic_name ASC LIMIT 15
        ");
        $stmt->execute(["%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        json_response($results);
    } catch (Exception $e) {
        json_response([], 500);
    }
}
EOD;

$replace = <<<'EOD'
if ($uri === '/api/generics/search' && $method === 'GET') {
    enforce_api_auth(['pharmacist', 'doctor', 'receptionist']);
    $q = trim($_GET['q'] ?? '');
    $conn = get_db();
    
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT generic_name FROM generic_mappings 
            WHERE LOWER(generic_name) LIKE ? AND generic_name != '' AND generic_name IS NOT NULL
            ORDER BY generic_name ASC LIMIT 15
        ");
        $stmt->execute([strtolower("$q%")]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        json_response($results);
    } catch (Exception $e) {
        json_response([], 500);
    }
}
EOD;

$content = str_replace($find, $replace, $content);
file_put_contents("api/api.php", $content);
echo "Fixed generics search\n";
