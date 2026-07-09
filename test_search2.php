<?php
require 'db.php';
$conn = get_db();
$q = 'ac';
$query = "SELECT i.name, i.generic_name FROM inventory i WHERE (LOWER(i.name) LIKE ? OR LOWER(i.generic_name) LIKE ?)";
$stmt = $conn->prepare($query);
$stmt->execute(["%ac%", "%ac%"]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('test_search2.json', json_encode($res));
echo "DONE";
