<?php
require 'db.php';
$stmt = get_db()->query("DESCRIBE generic_mappings");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
