<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

// 1. Sync from agency_items to generic_mappings
$conn->exec("
    INSERT INTO generic_mappings (brand_name, batch_number, generic_name, agency_name, stock, mrp, row_location, col_location, purchase_rate, selling_rate, category, pack_size, mfg_date, expiry_date, min_stock)
    SELECT 
        ai.item_name, 
        ai.batch_number, 
        ai.generic_name, 
        (SELECT name FROM agency_suppliers WHERE id = ai.supplier_id LIMIT 1),
        ai.stock, 
        ai.mrp, 
        COALESCE(ai.rack_location, '') AS row_location,
        '' AS col_location,
        ai.purchase_price,
        ai.selling_price,
        ai.category,
        ai.unit,
        ai.mfg_date,
        ai.expiry_date,
        ai.min_stock
    FROM agency_items ai
    WHERE ai.generic_name IS NOT NULL AND TRIM(ai.generic_name) != ''
    ON DUPLICATE KEY UPDATE
        generic_name = VALUES(generic_name),
        agency_name = VALUES(agency_name),
        stock = VALUES(stock),
        mrp = VALUES(mrp),
        row_location = VALUES(row_location),
        col_location = VALUES(col_location),
        purchase_rate = VALUES(purchase_rate),
        selling_rate = VALUES(selling_rate),
        category = VALUES(category),
        pack_size = VALUES(pack_size),
        mfg_date = VALUES(mfg_date),
        expiry_date = VALUES(expiry_date),
        min_stock = VALUES(min_stock)
");

// 2. Sync from inventory to generic_mappings
$conn->exec("
    INSERT INTO generic_mappings (brand_name, batch_number, generic_name, agency_name, stock, mrp, row_location, col_location, purchase_rate, selling_rate, category, pack_size, mfg_date, expiry_date, min_stock)
    SELECT 
        i.name, 
        i.batch_number, 
        i.generic_name, 
        i.agency_name,
        i.stock, 
        i.mrp, 
        i.row_location, 
        i.col_location,
        i.purchase_price,
        i.selling_price,
        i.category,
        i.tablets_per_strip,
        i.mfg_date,
        i.expiry_date,
        i.min_stock
    FROM inventory i
    WHERE i.generic_name IS NOT NULL AND TRIM(i.generic_name) != ''
    ON DUPLICATE KEY UPDATE
        generic_name = VALUES(generic_name),
        agency_name = VALUES(agency_name),
        stock = VALUES(stock),
        mrp = VALUES(mrp),
        purchase_rate = VALUES(purchase_rate),
        selling_rate = VALUES(selling_rate),
        row_location = VALUES(row_location),
        col_location = VALUES(col_location),
        category = VALUES(category),
        pack_size = VALUES(pack_size),
        mfg_date = VALUES(mfg_date),
        expiry_date = VALUES(expiry_date),
        min_stock = VALUES(min_stock)
");

echo "Sync completed successfully.\n";

// Query sample generic mapping row to verify mfg_date persistence
$stmt = $conn->query("SELECT brand_name, batch_number, mfg_date, expiry_date FROM generic_mappings LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
