<?php
$content = file_get_contents("api/api.php");
// Find the block:
// $params[] = strtolower("$q%");
// $params[] = strtolower("$q%");
// $params[] = strtolower("$q%");
// $params[] = strtolower("$q%");
// And replace with just two.
$content = preg_replace("/\\\$params\[\] = strtolower\(\\\"\\\$q%\\\"\);\s+\\\$params\[\] = strtolower\(\\\"\\\$q%\\\"\);\s+\\\$params\[\] = strtolower\(\\\"\\\$q%\\\"\);\s+\\\$params\[\] = strtolower\(\\\"\\\$q%\\\"\);/", "\$params[] = strtolower(\"\$q%\");\n        \$params[] = strtolower(\"\$q%\");", $content);
file_put_contents("api/api.php", $content);
echo "Fixed params\n";

