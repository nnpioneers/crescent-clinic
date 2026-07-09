<?php
$host = 'localhost'; // Usually works if turso config isn't needed here
// Let's mock a sqlite connection just to see if the SQL syntax is valid!
try {
    $conn = new PDO('sqlite::memory:');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create dummy tables
    $conn->exec("CREATE TABLE inventory (id INTEGER PRIMARY KEY, name TEXT, generic_name TEXT, item_code TEXT, batch_number TEXT, category TEXT, supplier_id INTEGER, agency_name TEXT)");
    $conn->exec("CREATE TABLE agency_suppliers (id INTEGER PRIMARY KEY, name TEXT)");
    $conn->exec("CREATE TABLE generic_mappings (id INTEGER PRIMARY KEY, generic_name TEXT, category TEXT)");
    $conn->exec("CREATE TABLE agency_items (id INTEGER PRIMARY KEY, item_name TEXT, generic_name TEXT, category TEXT)");
    
    $q = 'AN';
    $category = 'medicine';

    // 1. inventory query
    $query = "SELECT i.*, COALESCE(NULLIF(i.agency_name,''), s.name) as agency_name FROM inventory i LEFT JOIN agency_suppliers s ON i.supplier_id = s.id";
    $params = [];
    $conditions = [];
    
    if ($q) {
        $conditions[] = "(LOWER(i.name) LIKE LOWER(?) OR LOWER(i.generic_name) LIKE LOWER(?) OR LOWER(i.item_code) LIKE LOWER(?) OR LOWER(i.batch_number) LIKE LOWER(?))";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($category === 'medicine') {
        $conditions[] = "i.category NOT IN ('Injection', 'INJ', 'IV')";
    }
    if ($conditions) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    if ($q) {
        $query .= " ORDER BY CASE WHEN LOWER(i.name) LIKE LOWER(?) THEN 0 ELSE 1 END, i.name ASC LIMIT 100";
        $params[] = "$q%";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    echo "Inventory query success!\n";

    // 2. gm_query
    $gm_conditions = [];
    $gm_params = [];
    if ($q) {
        $gm_conditions[] = "LOWER(generic_name) LIKE LOWER(?)";
        $gm_params[] = "%$q%";
    }
    if ($category === 'medicine') {
        $gm_conditions[] = "category NOT IN ('Injection', 'INJ', 'IV')";
    }
    $gm_query = "SELECT DISTINCT generic_name FROM generic_mappings";
    if ($gm_conditions) {
        $gm_query .= " WHERE " . implode(" AND ", $gm_conditions);
    }
    if ($q) {
        $gm_query .= " ORDER BY CASE WHEN LOWER(generic_name) LIKE LOWER(?) THEN 0 ELSE 1 END, generic_name ASC LIMIT 30";
        $gm_params[] = "$q%";
    }
    $stmt2 = $conn->prepare($gm_query);
    $stmt2->execute($gm_params);
    echo "GM query success!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
