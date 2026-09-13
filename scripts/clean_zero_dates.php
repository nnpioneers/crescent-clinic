<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();
$conn->exec("UPDATE inventory SET mfg_date = NULL WHERE mfg_date = '0000-00-00'");
$conn->exec("UPDATE inventory SET expiry_date = NULL WHERE expiry_date = '0000-00-00'");
$conn->exec("UPDATE generic_mappings SET mfg_date = NULL WHERE mfg_date = '0000-00-00'");
$conn->exec("UPDATE generic_mappings SET expiry_date = NULL WHERE expiry_date = '0000-00-00'");
echo "Cleaned 0000-00-00 records.\n";
