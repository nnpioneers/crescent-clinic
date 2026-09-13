<?php
/**
 * End-to-end test for stock deduction on sales (0 -> -10 -> -20)
 */
require_once __DIR__ . '/../db.php';
$conn = get_db();

if (!function_exists('sync_stock_item')) {
    function sync_stock_item($conn, $item_name, $batch_number, $source) {}
}
if (!function_exists('ensure_synthesized_inventory')) {
    function ensure_synthesized_inventory($conn, $name) {}
}

// 1. Create a test medicine with stock = 0
$med_name = "E2E_STOCK_MED_" . rand(1000, 9999);
$stmt = $conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, 0, 'BATCH_E2E', 'Tablet', 10, 100, 100, 50)");
$stmt->execute([$med_name, $med_name]);
$inv_id = (int)$conn->lastInsertId();

echo "1. Created Test Item ID: $inv_id ($med_name) with stock = 0\n";

// Helper function to simulate sale logic in api.php
function process_sale($conn, $inv_id, $med_name, $qty, $batch_id_input) {
    // Exact logic from api.php
    $batch_id = $batch_id_input;
    if ($batch_id && (int)$batch_id > 0) {
        $stmt = $conn->prepare("SELECT name, batch_number, purchase_price, tablets_per_strip, mrp, selling_price FROM inventory WHERE id=?");
        $stmt->execute([$batch_id]);
        $row = $stmt->fetch();
        if ($row) {
            $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE id=?")->execute([$qty, $batch_id]);
            sync_stock_item($conn, $row['name'], $row['batch_number'], 'pharmacy');
        }
    } else {
        ensure_synthesized_inventory($conn, $med_name);
        $stmt = $conn->prepare("SELECT name, batch_number, purchase_price, tablets_per_strip, id, mrp, selling_price FROM inventory WHERE TRIM(LOWER(name))=TRIM(LOWER(?)) ORDER BY expiry_date ASC LIMIT 1");
        $stmt->execute([$med_name]);
        $row = $stmt->fetch();
        
        if (!$row && stripos(trim($med_name), '(Without Brand)') === false) {
            $check_name = trim($med_name) . ' (Without Brand)';
            $stmt->execute([$check_name]);
            $row = $stmt->fetch();
        }
        
        if ($row) {
            $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE id=?")->execute([$qty, $row['id']]);
            sync_stock_item($conn, $row['name'], $row['batch_number'], 'pharmacy');
        }
    }
}

// 2. Perform 1st sale of 10 tablets with valid positive $inv_id
process_sale($conn, $inv_id, $med_name, 10, $inv_id);

$stmt_check = $conn->prepare("SELECT stock FROM inventory WHERE id=?");
$stmt_check->execute([$inv_id]);
$stock1 = $stmt_check->fetchColumn();
echo "2. Stock after 1st sale (with positive batch_id): $stock1\n";

// 3. Perform 2nd sale of 10 tablets with negative $batch_id (simulating generic item search selection)
process_sale($conn, $inv_id, $med_name, 10, -99999);

$stmt_check->execute([$inv_id]);
$stock2 = $stmt_check->fetchColumn();
echo "3. Stock after 2nd sale (with negative batch_id): $stock2\n";

// Clean up
$conn->prepare("DELETE FROM inventory WHERE id=?")->execute([$inv_id]);

if ($stock1 == -10 && $stock2 == -20) {
    echo "SUCCESS: Stock correctly transitioned 0 -> -10 -> -20!\n";
} else {
    echo "FAILURE: Expected -10 and -20, got $stock1 and $stock2\n";
}
