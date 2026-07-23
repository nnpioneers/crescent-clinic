<?php
$content = file_get_contents("api/api.php");
$content = str_replace(
    "\$stmt_brands->execute([strtolower(\"%\" . \$q . \"%\"), strtolower(\"%\" . \$q . \"%\")]);",
    "\$stmt_brands->execute([strtolower(\"%\" . \$q . \"%\"), strtolower(\"%\" . \$q . \"%\"), strtolower(\$q . \"%\")]);",
    $content
);
$content = str_replace(
    "\$stmt_brands->execute([strtolower(\"%\$q%\"), strtolower(\"%\$q%\")]);",
    "\$stmt_brands->execute([strtolower(\"%\$q%\"), strtolower(\"%\$q%\"), strtolower(\"\$q%\")]);",
    $content
);
file_put_contents("api/api.php", $content);
echo "Fixed brand_query execute\n";

