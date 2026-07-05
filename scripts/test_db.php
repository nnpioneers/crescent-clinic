<?php require "auth.php"; $conn = get_db(); $stmt = $conn->query("DESCRIBE direct_sales"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
