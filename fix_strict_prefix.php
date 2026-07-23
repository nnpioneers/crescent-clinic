<?php
$content = file_get_contents("api/api.php");
$content = str_replace(
    "(LOWER(i.name) LIKE ? OR LOWER(i.generic_name) LIKE ? OR LOWER(i.item_code) LIKE ? OR LOWER(i.batch_number) LIKE ?)",
    "(LOWER(i.name) LIKE ? OR LOWER(i.generic_name) LIKE ?)",
    $content
);
file_put_contents("api/api.php", $content);
echo "Fixed strict prefix\n";

