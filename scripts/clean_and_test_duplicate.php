<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

// 1. Clean duplicate inventory/generic_mappings rows for AB - NEXT (INJ)
$conn->exec("DELETE FROM inventory WHERE name LIKE '%AB - NEXT%' AND batch_number LIKE 'ph_%'");
$conn->exec("DELETE FROM generic_mappings WHERE generic_name LIKE '%AB - NEXT%' AND batch_number LIKE 'ph_%'");
echo "Cleaned up stale placeholder row for AB - NEXT (INJ).\n";

// 2. Call sync_generic_mappings
require_once __DIR__ . '/../api/api.php';
sync_generic_mappings($conn, true);
echo "Ran sync_generic_mappings.\n";
