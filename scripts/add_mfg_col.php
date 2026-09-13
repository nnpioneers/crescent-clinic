<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../api/api.php';
$conn = get_db();
try {
    $conn->exec("ALTER TABLE generic_mappings ADD COLUMN mfg_date VARCHAR(100) DEFAULT NULL");
    echo "Column mfg_date added to generic_mappings successfully.\n";
} catch (Exception $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
try {
    sync_generic_mappings($conn, true);
    echo "sync_generic_mappings executed successfully.\n";
} catch (Exception $e) {
    echo "Sync error: " . $e->getMessage() . "\n";
}
