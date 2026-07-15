<?php
require_once __DIR__ . '/db.php';

$conn = get_db();

// We need to delete the stale "Faaah (Without Brand)" where generic_name = "Faaah (TAB)".
// More generally, any mapping where brand_name ends with "(Without Brand)" but does NOT match the generic_name + " (Without Brand)".

$stmt = $conn->prepare("
    DELETE FROM generic_mappings 
    WHERE brand_name LIKE '%(Without Brand)' 
    AND brand_name != CONCAT(generic_name, ' (Without Brand)')
");
$stmt->execute();
$count = $stmt->rowCount();

echo "Deleted $count stale generic_mappings rows.\n";

// Also we should ensure sync_generic_mappings doesn't keep creating them?
// Actually, since we deleted the stale ones, and sync_generic_mappings deduplicates, it should be fine.
// Wait, is there any other table? agency_items or inventory with the stale name?
// The rename endpoint DID rename those, so they shouldn't exist unless rename failed.
$stmt = $conn->prepare("
    UPDATE agency_items 
    SET item_name = CONCAT(generic_name, ' (Without Brand)'),
        brand_name = CONCAT(generic_name, ' (Without Brand)')
    WHERE item_name LIKE '%(Without Brand)' 
    AND item_name != CONCAT(generic_name, ' (Without Brand)')
");
$stmt->execute();
$count2 = $stmt->rowCount();
echo "Fixed $count2 stale agency_items rows.\n";

$stmt = $conn->prepare("
    UPDATE inventory 
    SET name = CONCAT(generic_name, ' (Without Brand)')
    WHERE name LIKE '%(Without Brand)' 
    AND name != CONCAT(generic_name, ' (Without Brand)')
");
$stmt->execute();
$count3 = $stmt->rowCount();
echo "Fixed $count3 stale inventory rows.\n";
