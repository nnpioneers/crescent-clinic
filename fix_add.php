<?php
$content = file_get_contents("api/api.php");

$find = <<<'EOD'
    $generic = trim($input['generic_name'] ?? '');

    if ($generic === '') {
        json_response(['error' => 'Item name cannot be empty.'], 400);
    }
    if (strlen($generic) <= 2) {
        json_response(['error' => 'Item name is too short.'], 400);
    }

    $conn = get_db();
    try {
        // Check if a placeholder for this generic already exists
        $check = $conn->prepare("SELECT COUNT(*) FROM agency_items WHERE TRIM(LOWER(generic_name)) = TRIM(LOWER(?)) AND item_name = '(Unmapped Brand)'");
        $check->execute([$generic]);
        if ($check->fetchColumn() > 0) {
            json_response(['success' => false, 'error' => "Item \"$generic\" already exists."], 409);
        }

        // Insert placeholder row (same as Scenario B import)
        $placeholder_batch = 'manual_' . uniqid() . '_' . substr(md5($generic), 0, 8);
        $stmt = $conn->prepare("
            INSERT INTO agency_items
                (item_name, generic_name, batch_number, stock, mrp, category, brand_name)
            VALUES ('(Unmapped Brand)', ?, ?, 0, 0.00, 'TAB', '(Unmapped Brand)')
        ");
        $stmt->execute([$generic, $placeholder_batch]);
EOD;

$replace = <<<'EOD'
    $generic = trim($input['generic_name'] ?? '');
    $category = trim($input['category'] ?? 'TAB');

    if ($generic === '') {
        json_response(['error' => 'Item name cannot be empty.'], 400);
    }
    
    // Append category to generic name if not already there
    $cat_suffix = " ($category)";
    if (substr($generic, -strlen($cat_suffix)) !== $cat_suffix) {
        $generic .= $cat_suffix;
    }

    if (strlen($generic) <= 2) {
        json_response(['error' => 'Item name is too short.'], 400);
    }

    $conn = get_db();
    try {
        // Check if a placeholder for this generic already exists
        $check = $conn->prepare("SELECT COUNT(*) FROM agency_items WHERE TRIM(LOWER(generic_name)) = TRIM(LOWER(?)) AND item_name = '(Unmapped Brand)'");
        $check->execute([$generic]);
        if ($check->fetchColumn() > 0) {
            json_response(['success' => false, 'error' => "Item \"$generic\" already exists."], 409);
        }

        // Insert placeholder row (same as Scenario B import)
        $placeholder_batch = 'manual_' . uniqid() . '_' . substr(md5($generic), 0, 8);
        $stmt = $conn->prepare("
            INSERT INTO agency_items
                (item_name, generic_name, batch_number, stock, mrp, category, brand_name)
            VALUES ('(Unmapped Brand)', ?, ?, 0, 0.00, ?, '(Unmapped Brand)')
        ");
        $stmt->execute([$generic, $placeholder_batch, $category]);
EOD;

$content = str_replace($find, $replace, $content);
file_put_contents("api/api.php", $content);
echo "Fixed add generics\n";
