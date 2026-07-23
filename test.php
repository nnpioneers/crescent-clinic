<?php
require 'db.php';
$conn = get_db();
$stmt = $conn->query('SELECT id, name, created_at FROM patients ORDER BY created_at DESC LIMIT 50');
print_r($stmt->fetchAll());
?>
