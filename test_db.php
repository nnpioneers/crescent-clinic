<?php
require 'db.php';
$conn = get_db();
try {
    $stmt = $conn->prepare("INSERT INTO inventory (name, generic_name, mrp, selling_price, purchase_price, stock, category, batch_number) VALUES (?, ?, ?, ?, ?, 0, 'TAB', ?)");
    $stmt->execute(['TEST_BRAND_123', 'TEST_GENERIC_123', 10, 10, 10, 'BATCH-01']);
    echo "Inventory Insert OK. \n";
} catch (Exception $e) {
    echo "Inventory Insert Error: " . $e->getMessage() . "\n";
}
try {
    $stmt2 = $conn->prepare("INSERT INTO agency_items (item_name, generic_name, mrp, selling_price, purchase_price, stock, batch_number) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $stmt2->execute(['TEST_BRAND_123', 'TEST_GENERIC_123', 10, 10, 10, 'BATCH-01']);
    echo "Agency Items Insert OK. \n";
} catch (Exception $e) {
    echo "Agency Items Insert Error: " . $e->getMessage() . "\n";
}
try {
    $stmt3 = $conn->prepare("INSERT INTO generic_mappings (brand_name, generic_name, mrp, stock, batch_number) VALUES (?, ?, ?, 0, ?) ON DUPLICATE KEY UPDATE generic_name = VALUES(generic_name), mrp = VALUES(mrp)");
    $stmt3->execute(['TEST_BRAND_123', 'TEST_GENERIC_123', 10, 'BATCH-01']);
    echo "Generic Mappings Insert OK. \n";
} catch (Exception $e) {
    echo "Generic Mappings Insert Error: " . $e->getMessage() . "\n";
}
