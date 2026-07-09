<?php
require_once __DIR__ . '/db.php';
$conn = get_db();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$generic_name = "SALINE NASAL SOLUTION";
$brand_name = "MilloTest";
$unit_price = 10.0;

try {
    $stmt = $conn->prepare("INSERT INTO generic_mappings (generic_name, brand_name, price) VALUES (?, ?, ?)");
    $stmt->execute([$generic_name, $brand_name, $unit_price]);
    
    $ph_batch = 'manual_default';
    $stmt2 = $conn->prepare("INSERT IGNORE INTO inventory (name, generic_name, brand_name, stock, batch_number, category, purchase_price) VALUES (?, ?, ?, 0, ?, 'Tablet', ?)");
    $stmt2->execute([$brand_name, $generic_name, $brand_name, $ph_batch, $unit_price]);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
