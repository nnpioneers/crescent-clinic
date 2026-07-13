<?php
require 'api/db.php';
$conn = get_db();
$stmt = $conn->query("SELECT id, name, generic_name, category FROM inventory WHERE name LIKE '%para%' OR generic_name LIKE '%para%' LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
