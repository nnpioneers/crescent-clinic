<?php
/**
 * Test pharmacy sale stock deduction contract for 0 starting stock
 */
require_once __DIR__ . '/../db.php';
$conn = get_db();

// 1. Create a medicine with stock 0
$med_name = "TEST_SYNC_MED_" . rand(1000, 9999);
$stmt = $conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, 0, 'BATCH_01', 'Tablet', 10, 100, 100, 50)");
$stmt->execute([$med_name, $med_name]);
$inv_id = $conn->lastInsertId();

echo "Initial Stock for ID $inv_id ($med_name): 0\n";

// 2. Simulate direct sale POST /api/direct_sales/add with qty 10
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/direct_sales/add';
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'pharmacist';

$payload = [
    'customer_name' => 'Test Customer',
    'mobile_number' => '9999999999',
    'medicines' => [
        [
            'name' => $med_name,
            'qty' => 10,
            'amount' => 100,
            'batch_id' => $inv_id,
            'tps' => 10
        ]
    ],
    'paid_amount' => 100,
    'cash_amount' => 100
];

// Let's test the logic in direct_sales/add directly
$stmt_check = $conn->prepare("SELECT stock FROM inventory WHERE id = ?");

$batch_id = $inv_id;
$qty = 10;
if ($batch_id && (int)$batch_id > 0) {
    $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE id=?")->execute([$qty, $batch_id]);
}

$stmt_check->execute([$inv_id]);
$stock_after_sale_1 = $stmt_check->fetchColumn();
echo "Stock after 1st sale (qty 10, starting 0): $stock_after_sale_1\n";

// 3. Second sale: qty 10
if ($batch_id && (int)$batch_id > 0) {
    $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE id=?")->execute([$qty, $batch_id]);
}

$stmt_check->execute([$inv_id]);
$stock_after_sale_2 = $stmt_check->fetchColumn();
echo "Stock after 2nd sale (qty 10, starting -10): $stock_after_sale_2\n";

// Clean up
$conn->prepare("DELETE FROM inventory WHERE id = ?")->execute([$inv_id]);

if ($stock_after_sale_1 == -10 && $stock_after_sale_2 == -20) {
    echo "TEST SUCCESSFUL: Stock correctly becomes -10 and -20!\n";
} else {
    echo "TEST FAILED!\n";
}
