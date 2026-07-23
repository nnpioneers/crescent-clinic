<?php
$content = file_get_contents("api/api.php");
$content = str_replace("\$params[] = strtolower(\"%\" . \$q . \"%\");", "\$params[] = strtolower(\$q . \"%\");", $content);
$content = str_replace("\$params[] = strtolower(\"%\$q%\");", "\$params[] = strtolower(\"\$q%\");", $content);
$content = str_replace("\$gm_params[] = strtolower(\"%\$q%\");", "\$gm_params[] = strtolower(\"\$q%\");", $content);
$content = str_replace("\$ai_params     = [strtolower(\"%\$q%\")];", "\$ai_params     = [strtolower(\"\$q%\")];", $content);
$content = str_replace("\$stmt_brands->execute([strtolower(\"%\$q%\"), strtolower(\"%\$q%\"), strtolower(\"\$q%\")]);", "\$stmt_brands->execute([strtolower(\"\$q%\"), strtolower(\"\$q%\"), strtolower(\"\$q%\")]);", $content);
file_put_contents("api/api.php", $content);
echo "Fixed prefix\n";

