<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

function test_search($q, $category = '') {
    global $conn;
    $url = "http://127.0.0.1:8005/api/inventory/search?q=" . urlencode($q) . ($category ? "&category=" . urlencode($category) : "");
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Use session cookie if needed or run DB direct logic
}

// Check DB count for generic 'AB - NEXT (INJ)'
$stmt_inv = $conn->query("SELECT id, name, generic_name, batch_number, stock, category FROM inventory WHERE generic_name = 'AB - NEXT (INJ)'");
$inv_rows = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);
echo "Inventory rows for 'AB - NEXT (INJ)':\n";
print_r($inv_rows);

$stmt_gm = $conn->query("SELECT id, brand_name, generic_name, batch_number, stock, category FROM generic_mappings WHERE generic_name = 'AB - NEXT (INJ)'");
$gm_rows = $stmt_gm->fetchAll(PDO::FETCH_ASSOC);
echo "\nGeneric Mappings rows for 'AB - NEXT (INJ)':\n";
print_r($gm_rows);
