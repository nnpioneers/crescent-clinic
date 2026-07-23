<?php
$content = file_get_contents("api/api.php");
$content = str_replace(
    "WHERE LOWER(i.generic_name) IN (SELECT DISTINCT LOWER(generic_name) FROM generic_mappings WHERE LOWER(generic_name) LIKE ? OR LOWER(brand_name) LIKE ?) LIMIT 300",
    "WHERE LOWER(i.generic_name) IN (SELECT DISTINCT LOWER(generic_name) FROM generic_mappings WHERE LOWER(generic_name) LIKE ? OR LOWER(brand_name) LIKE ?) ORDER BY CASE WHEN LOWER(i.name) LIKE ? THEN 0 ELSE 1 END, i.name ASC LIMIT 300",
    $content
);
file_put_contents("api/api.php", $content);
echo "Fixed brand_query ORDER BY\n";

