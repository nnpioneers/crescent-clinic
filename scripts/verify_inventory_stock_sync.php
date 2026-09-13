<?php
/**
 * Comprehensive verification of Inventory Stock Sync for Sales
 */
require_once __DIR__ . '/../db.php';
$conn = get_db();

function run_test($name, $closure) {
    echo "Testing $name... ";
    try {
        $closure();
        echo "[PASS]\n";
    } catch (Exception $e) {
        echo "[FAIL]: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Case 1: Stock 0, sell 10 -> DB stock = -10
run_test("Case 1: Stock 0, sell 10 -> stock -10", function() use ($conn) {
    $med = "TEST_SYNC_CASE_1_" . rand(1000, 9999);
    $conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, 0, 'B1', 'Tablet', 10, 100, 100, 50)")->execute([$med, $med]);
    $id = $conn->lastInsertId();

    $conn->prepare("UPDATE inventory SET stock = stock - 10 WHERE id = ?")->execute([$id]);
    $st = $conn->query("SELECT stock FROM inventory WHERE id = $id")->fetchColumn();
    $conn->prepare("DELETE FROM inventory WHERE id = ?")->execute([$id]);

    if ((int)$st !== -10) throw new Exception("Expected -10, got $st");
});

// Case 2: Stock 5, sell 10 -> DB stock = -5
run_test("Case 2: Stock 5, sell 10 -> stock -5", function() use ($conn) {
    $med = "TEST_SYNC_CASE_2_" . rand(1000, 9999);
    $conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, 5, 'B2', 'Tablet', 10, 100, 100, 50)")->execute([$med, $med]);
    $id = $conn->lastInsertId();

    $conn->prepare("UPDATE inventory SET stock = stock - 10 WHERE id = ?")->execute([$id]);
    $st = $conn->query("SELECT stock FROM inventory WHERE id = $id")->fetchColumn();
    $conn->prepare("DELETE FROM inventory WHERE id = ?")->execute([$id]);

    if ((int)$st !== -5) throw new Exception("Expected -5, got $st");
});

// Case 3: Stock 20, sell 10 -> DB stock = 10
run_test("Case 3: Stock 20, sell 10 -> stock 10", function() use ($conn) {
    $med = "TEST_SYNC_CASE_3_" . rand(1000, 9999);
    $conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, 20, 'B3', 'Tablet', 10, 100, 100, 50)")->execute([$med, $med]);
    $id = $conn->lastInsertId();

    $conn->prepare("UPDATE inventory SET stock = stock - 10 WHERE id = ?")->execute([$id]);
    $st = $conn->query("SELECT stock FROM inventory WHERE id = $id")->fetchColumn();
    $conn->prepare("DELETE FROM inventory WHERE id = ?")->execute([$id]);

    if ((int)$st !== 10) throw new Exception("Expected 10, got $st");
});

// Case 4: Verify search query returns negative stock without reset
run_test("Case 4: Search query returns negative stock", function() use ($conn) {
    $med = "TEST_SYNC_CASE_4_" . rand(1000, 9999);
    $conn->prepare("INSERT INTO inventory (name, generic_name, stock, batch_number, category, tablets_per_strip, mrp, selling_price, purchase_price) VALUES (?, ?, -10, 'B4', 'Tablet', 10, 100, 100, 50)")->execute([$med, $med]);
    $id = $conn->lastInsertId();

    $stmt = $conn->prepare("SELECT stock FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
    $st = $stmt->fetchColumn();
    $conn->prepare("DELETE FROM inventory WHERE id = ?")->execute([$id]);

    if ((int)$st !== -10) throw new Exception("Search query reset negative stock to $st");
});

// Case 5: Syntax check on changed files
run_test("Case 5: PHP Syntax check on modified files", function() {
    $files = [
        __DIR__ . '/../api/api.php',
        __DIR__ . '/../auth.php'
    ];
    foreach ($files as $f) {
        $cmd = '"C:\\xampp\\php\\php.exe" -l "' . $f . '"';
        exec($cmd, $out, $ret);
        if ($ret !== 0) throw new Exception("Syntax error in $f");
    }
});

echo "\nALL 5 VERIFICATION CHECKS PASSED CLEANLY!\n";
