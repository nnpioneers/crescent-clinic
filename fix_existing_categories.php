<?php
require 'db.php';
$conn = get_db();

// Extract category from generic_name like '... (INJ)'
// For INJ
$conn->exec("UPDATE generic_mappings SET category = 'INJ' WHERE generic_name LIKE '%(INJ)%'");
$conn->exec("UPDATE agency_items SET category = 'INJ' WHERE generic_name LIKE '%(INJ)%'");
$conn->exec("UPDATE inventory SET category = 'INJ' WHERE generic_name LIKE '%(INJ)%'");

// For TAB
$conn->exec("UPDATE generic_mappings SET category = 'TAB' WHERE generic_name LIKE '%(TAB)%'");
$conn->exec("UPDATE agency_items SET category = 'TAB' WHERE generic_name LIKE '%(TAB)%'");
$conn->exec("UPDATE inventory SET category = 'TAB' WHERE generic_name LIKE '%(TAB)%'");

// For SYP
$conn->exec("UPDATE generic_mappings SET category = 'SYP' WHERE generic_name LIKE '%(SYP)%'");
$conn->exec("UPDATE agency_items SET category = 'SYP' WHERE generic_name LIKE '%(SYP)%'");
$conn->exec("UPDATE inventory SET category = 'SYP' WHERE generic_name LIKE '%(SYP)%'");

// For CAP
$conn->exec("UPDATE generic_mappings SET category = 'CAP' WHERE generic_name LIKE '%(CAP)%'");
$conn->exec("UPDATE agency_items SET category = 'CAP' WHERE generic_name LIKE '%(CAP)%'");
$conn->exec("UPDATE inventory SET category = 'CAP' WHERE generic_name LIKE '%(CAP)%'");

echo "Fixed existing categories in DB\n";
