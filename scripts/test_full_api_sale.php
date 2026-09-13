<?php
/**
 * Test full API execution of /api/direct_sales/add and /api/add_medicines
 */
require_once __DIR__ . '/../db.php';
$conn = get_db();

// 1. Insert a 0-stock test medicine
$med_name = "FULL_API_MED_" . rand(1000, 9999);
$stmt = $conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, 0, 'BATCH_FULL', 'Tablet', 10, 100, 100, 50)");
$stmt->execute([$med_name, $med_name]);
$inv_id = (int)$conn->lastInsertId();

echo "Initial inventory ID $inv_id ($med_name) stock: 0\n";

// 2. Call /api/direct_sales/add with batch_id = $inv_id
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/direct_sales/add';
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'pharmacist';
$_SESSION['csrf_token'] = 'test_token_123';
$_SERVER['HTTP_X_CSRF_TOKEN'] = 'test_token_123';

$post_data = [
    'customer_name' => 'Full Test Customer',
    'mobile_number' => '9876543210',
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

// Capture output of api.php
ob_start();
$input = $post_data;
$_POST = [];
// Run direct_sales endpoint logic
$uri = '/api/direct_sales/add';
$method = 'POST';

// Call the endpoint logic directly from api.php
require __DIR__ . '/../api/api.php';
$output = ob_get_clean();

// Check DB stock
$stmt_check = $conn->prepare("SELECT stock FROM inventory WHERE id = ?");
$stmt_check->execute([$inv_id]);
$stock1 = $stmt_check->fetchColumn();

echo "API Response: " . substr($output, 0, 200) . "\n";
echo "Stock after /api/direct_sales/add sale (qty 10): $stock1\n";

// Clean up
$conn->prepare("DELETE FROM inventory WHERE id = ?")->execute([$inv_id]);
