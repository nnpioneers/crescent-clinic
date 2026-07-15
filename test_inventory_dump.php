<?php
require_once __DIR__ . '/db.php';
$conn = get_db();

header('Content-Type: application/json');

$stmt = $conn->query("SELECT id, name, generic_name, purchase_price, tablets_per_strip, category, stock FROM inventory ORDER BY id DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($items, JSON_PRETTY_PRINT);
