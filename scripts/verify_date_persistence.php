<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

// 1. Insert test inventory item with Mfg Date "03-26" and Expiry Date "03-28"
$stmt = $conn->prepare("INSERT INTO inventory (name, generic_name, batch_number, mfg_date, expiry_date, mrp, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute(['TestBrandDate', 'TestGenericDate', 'BATCH-DATE-100', '03-26', '03-28', 50.00, 10]);
$inv_id = $conn->lastInsertId();

// Verify raw inventory table contents
$raw_stmt = $conn->prepare("SELECT id, name, generic_name, batch_number, mfg_date, expiry_date FROM inventory WHERE id = ?");
$raw_stmt->execute([$inv_id]);
$raw_inv = $raw_stmt->fetch(PDO::FETCH_ASSOC);
echo "Raw Inventory Table Row:\n";
print_r($raw_inv);

// 2. Sync to generic_mappings
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
    WHERE i.id = {$inv_id}
    ON DUPLICATE KEY UPDATE
        generic_name = VALUES(generic_name),
        mfg_date = VALUES(mfg_date),
        expiry_date = VALUES(expiry_date)
");

// Verify raw generic_mappings table contents
$raw_gm_stmt = $conn->prepare("SELECT id, brand_name, generic_name, batch_number, mfg_date, expiry_date FROM generic_mappings WHERE brand_name = 'TestBrandDate'");
$raw_gm_stmt->execute();
$raw_gm = $raw_gm_stmt->fetch(PDO::FETCH_ASSOC);
echo "\nRaw Generic Mappings Table Row:\n";
print_r($raw_gm);

// 3. Test /api/generics/brands exact SELECT query
$brands_stmt = $conn->prepare("
    SELECT
        gm.brand_name,
        gm.generic_name,
        COALESCE(i.category, gm.category) AS category,
        COALESCE(i.batch_number, gm.batch_number) AS batch_number,
        COALESCE(i.expiry_date, gm.expiry_date) AS expiry_date,
        COALESCE(i.mfg_date, gm.mfg_date) AS mfg_date,
        COALESCE(i.mrp, gm.mrp) AS mrp,
        COALESCE(i.stock, gm.stock) AS stock,
        i.id AS inventory_id
    FROM generic_mappings gm
    LEFT JOIN inventory i ON (TRIM(LOWER(i.name)) = TRIM(LOWER(gm.brand_name)) OR (gm.brand_name LIKE '%(Without Brand)%' AND TRIM(LOWER(i.generic_name)) = TRIM(LOWER(gm.generic_name)))) AND (TRIM(LOWER(i.batch_number)) = TRIM(LOWER(gm.batch_number)) OR i.batch_number IS NULL OR gm.batch_number IS NULL)
    WHERE TRIM(LOWER(gm.generic_name)) = TRIM(LOWER(?))
");
$brands_stmt->execute(['TestGenericDate']);
$fetched_brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nFetched /api/generics/brands Response Row:\n";
print_r($fetched_brands);

// Clean up
$conn->exec("DELETE FROM inventory WHERE id = {$inv_id}");
$conn->exec("DELETE FROM generic_mappings WHERE brand_name = 'TestBrandDate'");
echo "\nCleaned up test records.\n";
