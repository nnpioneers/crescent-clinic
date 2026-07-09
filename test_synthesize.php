<?php
require_once 'api/db.php';
require_once 'api/api.php';

$conn = get_db();
ensure_synthesized_inventory($conn, 'RABE (Without Brand)');
echo "Synthesized successfully!\n";
