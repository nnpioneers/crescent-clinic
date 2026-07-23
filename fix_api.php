<?php
$content = file_get_contents("api/api.php");
$content = str_replace("echo json_encode(\$data);", "echo json_encode(\$data, JSON_INVALID_UTF8_SUBSTITUTE);", $content);
file_put_contents("api/api.php", $content);
echo "Fixed json_encode\n";

