<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = new PDO("mysql:host=localhost;dbname=u988163119_crescent", "u988163119_nnp", "Namaraja@4");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Check generic_mappings
    $stmt = $conn->query("SELECT id, brand_name, batch_number, generic_name FROM generic_mappings WHERE TRIM(LOWER(brand_name)) IN ('nuol', 'annnaal')");
    $gm = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Check agency_items
    $stmt = $conn->query("SELECT id, item_name, batch_number, generic_name FROM agency_items WHERE TRIM(LOWER(item_name)) IN ('nuol', 'annnaal')");
    $ai = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Check inventory
    $stmt = $conn->query("SELECT id, name, batch_number, generic_name FROM inventory WHERE TRIM(LOWER(name)) IN ('nuol', 'annnaal')");
    $inv = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'generic_mappings' => $gm,
        'agency_items' => $ai,
        'inventory' => $inv
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
