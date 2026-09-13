<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

// Simulating POST /api/inventory/add for an existing Without Brand record
$name = 'AB - NEXT (INJ) (Without Brand)';
$generic_name = 'AB - NEXT (INJ)';
$batch_number = 'manual_default';

$chk_stmt = $conn->prepare("SELECT id FROM inventory WHERE TRIM(LOWER(name)) = TRIM(LOWER(?)) AND (TRIM(LOWER(batch_number)) = TRIM(LOWER(?)) OR batch_number LIKE 'ph_%' OR batch_number = 'manual_default' OR batch_number = 'BATCH-01' OR batch_number = '') ORDER BY CASE WHEN batch_number NOT LIKE 'ph_%' THEN 0 ELSE 1 END LIMIT 1");
$chk_stmt->execute([$name, $batch_number]);
$existing = $chk_stmt->fetch(PDO::FETCH_ASSOC);

echo "Found existing ID for /api/inventory/add: " . ($existing['id'] ?? 'NONE (WOULD INSERT DUPLICATE)') . "\n";

// Simulating POST /api/inventory/update for an existing Without Brand record
$chk_inv = $conn->prepare("SELECT id, stock FROM inventory WHERE TRIM(LOWER(name)) = TRIM(LOWER(?)) AND (TRIM(LOWER(batch_number)) = TRIM(LOWER(?)) OR batch_number LIKE 'ph_%' OR batch_number = 'manual_default' OR batch_number = 'BATCH-01' OR batch_number = '') AND id != ? ORDER BY CASE WHEN batch_number NOT LIKE 'ph_%' THEN 0 ELSE 1 END LIMIT 1");
$chk_inv->execute([$name, $batch_number, 99999]); // simulate different ID or check
$existing_inv = $chk_inv->fetch(PDO::FETCH_ASSOC);

echo "Found existing ID for /api/inventory/update merge: " . ($existing_inv['id'] ?? 'NONE') . "\n";
