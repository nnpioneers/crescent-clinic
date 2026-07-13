<?php
require_once __DIR__ . '/db.php';
$conn = get_db();

header('Content-Type: text/plain');

$queries = [
    "DELETE FROM agency_items 
     WHERE (item_name = '(Unmapped Brand)' OR item_name = '(unmapped brand)')
       AND TRIM(LOWER(generic_name)) IN (
           SELECT * FROM (
               SELECT DISTINCT TRIM(LOWER(generic_name)) FROM agency_items 
               WHERE item_name LIKE '%(Without Brand)%'
           ) AS tmp
       )",
       
    "DELETE FROM inventory 
     WHERE (name = '(Unmapped Brand)' OR name = '(unmapped brand)')
       AND TRIM(LOWER(generic_name)) IN (
           SELECT * FROM (
               SELECT DISTINCT TRIM(LOWER(generic_name)) FROM inventory 
               WHERE name LIKE '%(Without Brand)%'
           ) AS tmp
       )",
       
    "DELETE FROM generic_mappings 
     WHERE (brand_name = '(Unmapped Brand)' OR brand_name = '(unmapped brand)')
       AND TRIM(LOWER(generic_name)) IN (
           SELECT * FROM (
               SELECT DISTINCT TRIM(LOWER(generic_name)) FROM generic_mappings 
               WHERE brand_name LIKE '%(Without Brand)%'
           ) AS tmp
       )"
];

foreach ($queries as $i => $q) {
    try {
        echo "Executing Query " . ($i + 1) . "...\n";
        $res = $conn->exec($q);
        echo "Query " . ($i + 1) . " Success: affected $res rows\n\n";
    } catch (Throwable $e) {
        echo "Query " . ($i + 1) . " Error: " . $e->getMessage() . "\n\n";
    }
}
