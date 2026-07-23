<?php
require 'db.php';
$conn = get_db();
$s = $conn->query('SHOW COLUMNS FROM direct_sales');
print_r($s->fetchAll(PDO::FETCH_ASSOC));
