<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

$conn->exec("UPDATE inventory SET mfg_date = '03-26' WHERE id = 1");
$stmt = $conn->query("SELECT id, name, mfg_date, expiry_date FROM inventory WHERE id = 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
