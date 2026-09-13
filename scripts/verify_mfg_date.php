<?php
define('IS_CLI', true);
require_once __DIR__ . '/../db.php';
$conn = get_db();
try {
    $conn->exec("ALTER TABLE generic_mappings ADD COLUMN mfg_date VARCHAR(100) DEFAULT NULL");
    echo "Column mfg_date added to generic_mappings successfully.\n";
} catch (Exception $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}

// Verify generic_mappings columns
$stmt = $conn->query("SHOW COLUMNS FROM generic_mappings");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "generic_mappings columns: " . implode(', ', $cols) . "\n";
