<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

// Create or find a test medicine with 0 stock
$test_name = "TEST_SYNC_MED " . rand(1000, 9999);
$batch = "BATCH_TEST";

$conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, 0, ?, 'Tablet', 10, 100, 100, 50)")
     ->execute([$test_name, $test_name, $batch]);

$inv_id = $conn->lastInsertId();

echo "Initial inventory record ID $inv_id stock: 0\n";

// Now simulate sale via direct_sales logic
// 1. SELECT inventory WHERE id = ?
$stmt = $conn->prepare("SELECT name, batch_number, purchase_price, tablets_per_strip, mrp, selling_price FROM inventory WHERE id=?");
$stmt->execute([$inv_id]);
$row = $stmt->fetch();

$qty = 10;
$conn->prepare("UPDATE inventory SET stock = stock - ? WHERE id=?")->execute([$qty, $inv_id]);

// Check stock in inventory table directly
$check_inv = $conn->prepare("SELECT stock FROM inventory WHERE id=?");
$check_inv->execute([$inv_id]);
$new_stock = $check_inv->fetchColumn();

echo "Stock in inventory table after 'stock = stock - 10': $new_stock\n";

// Clean up test record
$conn->prepare("DELETE FROM inventory WHERE id=?")->execute([$inv_id]);
