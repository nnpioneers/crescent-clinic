<?php
require_once __DIR__ . '/db.php';
$conn = get_db();

header('Content-Type: text/plain');

$generic = 'CONSEVEL';

echo "Running sync...\n";
try {
    sync_generic_mappings($conn);
    echo "Sync succeeded!\n";
} catch (Throwable $e) {
    echo "Sync failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "--- INVENTORY RECORDS FOR $generic ---\n";
$stmt = $conn->prepare("SELECT id, name, generic_name, brand_name, batch_number, stock FROM inventory WHERE TRIM(LOWER(generic_name)) = TRIM(LOWER(?)) OR TRIM(LOWER(name)) LIKE ?");
$stmt->execute([$generic, '%' . strtolower($generic) . '%']);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- AGENCY_ITEMS RECORDS FOR $generic ---\n";
$stmt = $conn->prepare("SELECT id, item_name, generic_name, brand_name, batch_number, stock FROM agency_items WHERE TRIM(LOWER(generic_name)) = TRIM(LOWER(?)) OR TRIM(LOWER(item_name)) LIKE ?");
$stmt->execute([$generic, '%' . strtolower($generic) . '%']);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- GENERIC_MAPPINGS RECORDS FOR $generic ---\n";
$stmt = $conn->prepare("SELECT id, brand_name, generic_name, batch_number, stock FROM generic_mappings WHERE TRIM(LOWER(generic_name)) = TRIM(LOWER(?)) OR TRIM(LOWER(brand_name)) LIKE ?");
$stmt->execute([$generic, '%' . strtolower($generic) . '%']);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
