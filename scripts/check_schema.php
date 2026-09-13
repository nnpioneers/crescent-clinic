<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

echo "--- INVENTORY TABLE COLUMNS ---\n";
$stmt = $conn->query("SHOW COLUMNS FROM inventory");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (in_array($row['Field'], ['stock', 'min_stock', 'opening_stock', 'tablets_per_strip'])) {
        echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Default: {$row['Default']}\n";
    }
}

echo "\n--- AGENCY_ITEMS TABLE COLUMNS ---\n";
$stmt = $conn->query("SHOW COLUMNS FROM agency_items");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (in_array($row['Field'], ['stock', 'min_stock', 'opening_stock'])) {
        echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Default: {$row['Default']}\n";
    }
}
