<?php
require_once __DIR__ . '/includes/db.php';
$conn = get_db();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$name = "Test Med (Without Brand)";
$gen_name = "Test Med";
$ph_batch = 'ph_123456';

try {
    $stmt2 = $conn->prepare("INSERT IGNORE INTO inventory (name, generic_name, brand_name, stock, batch_number, category) VALUES (?, ?, '(Unmapped Brand)', 0, ?, 'Tablet')");
    $stmt2->execute([$name, $gen_name, $ph_batch]);
    echo "Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
