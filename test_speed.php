<?php
require 'db.php';
$start = microtime(true);
$q = 'an';
$category = 'medicine';
$conn = get_db();

// 1. Inventory query
$conditions = [];
$params = [];
$conditions[] = "(LOWER(i.name) LIKE ? OR LOWER(i.generic_name) LIKE ? OR LOWER(i.item_code) LIKE ? OR LOWER(i.batch_number) LIKE ?)";
$params[] = strtolower("%$q%");
$params[] = strtolower("%$q%");
$params[] = strtolower("%$q%");
$params[] = strtolower("%$q%");

if ($category === 'medicine') {
    $conditions[] = "(i.category IS NULL OR i.category NOT IN ('Injection', 'INJ', 'IV'))";
}

$query = "SELECT i.*, COALESCE(NULLIF(i.agency_name,''), s.name) as agency_name FROM inventory i LEFT JOIN agency_suppliers s ON i.supplier_id = s.id";
$query .= " WHERE " . implode(" AND ", $conditions);
$query .= " ORDER BY CASE WHEN LOWER(i.name) LIKE ? THEN 0 ELSE 1 END, i.name ASC LIMIT 100";
$params[] = strtolower("$q%");

$t0 = microtime(true);
$stmt = $conn->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Inventory query took: " . (microtime(true) - $t0) . " sec. Rows: " . count($results) . "\n";

// 2. gm_query
$gm_conditions = [];
$gm_params = [];
$gm_conditions[] = "LOWER(generic_name) LIKE ?";
$gm_params[] = strtolower("%$q%");

if ($category === 'medicine') {
    $gm_conditions[] = "(category IS NULL OR category NOT IN ('Injection', 'INJ', 'IV'))";
}

$gm_query = "SELECT DISTINCT generic_name FROM generic_mappings";
$gm_query .= " WHERE " . implode(" AND ", $gm_conditions);
$gm_query .= " ORDER BY CASE WHEN LOWER(generic_name) LIKE ? THEN 0 ELSE 1 END, generic_name ASC LIMIT 30";
$gm_params[] = strtolower("$q%");

$t0 = microtime(true);
$stmt2 = $conn->prepare($gm_query);
$stmt2->execute($gm_params);
$gm_generics = $stmt2->fetchAll(PDO::FETCH_COLUMN);
echo "GM query took: " . (microtime(true) - $t0) . " sec. Rows: " . count($gm_generics) . "\n";

// 3. brand query
$t0 = microtime(true);
$brand_query = "SELECT i.*, COALESCE(NULLIF(i.agency_name,''), s.name) as agency_name FROM inventory i LEFT JOIN agency_suppliers s ON i.supplier_id = s.id WHERE LOWER(i.generic_name) IN (SELECT DISTINCT LOWER(generic_name) FROM generic_mappings WHERE LOWER(generic_name) LIKE ?) LIMIT 300";
$stmt_brands = $conn->prepare($brand_query);
$stmt_brands->execute([strtolower("%$q%")]);
$brand_batches = $stmt_brands->fetchAll(PDO::FETCH_ASSOC);
echo "Brand query took: " . (microtime(true) - $t0) . " sec. Rows: " . count($brand_batches) . "\n";

