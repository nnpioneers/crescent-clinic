<?php
/**
 * Diagnostic script for Hostinger deployment verification
 */
header('Content-Type: text/plain');

echo "=== Hostinger Deployment Diagnostics ===\n\n";

// 1. Check PHP and Server Info
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Current Script Path: " . __FILE__ . "\n\n";

// 2. Check Git Log via Shell Exec (if enabled)
if (function_exists('shell_exec')) {
    echo "=== Git Log (shell_exec) ===\n";
    $git_log = shell_exec('git log -n 5 --oneline 2>&1');
    echo $git_log ? $git_log : "shell_exec('git log') returned empty or failed.\n";
    
    echo "\n=== Git Status ===\n";
    $git_status = shell_exec('git status 2>&1');
    echo $git_status ? $git_status : "shell_exec('git status') returned empty or failed.\n";
    echo "\n";
} else {
    echo "shell_exec is disabled on this server.\n\n";
}

// 3. Inspect Static Files
$inventory_js = __DIR__ . '/static/js/inventory.js';
if (file_exists($inventory_js)) {
    echo "=== static/js/inventory.js details ===\n";
    echo "Exists: Yes\n";
    echo "Size: " . filesize($inventory_js) . " bytes\n";
    echo "Modified: " . date("Y-m-d H:i:s", filemtime($inventory_js)) . "\n";
    
    // Read last 200 characters of inventory.js to check for window.editMedModal
    $content = file_get_contents($inventory_js);
    echo "Contains 'window.editMedModal = editMedModal': " . (strpos($content, 'window.editMedModal = editMedModal') !== false ? "YES" : "NO") . "\n";
    echo "File ends with:\n";
    echo substr($content, -300) . "\n\n";
} else {
    echo "ERROR: static/js/inventory.js does not exist at: $inventory_js\n\n";
}

// 4. Inspect templates/management.html
$mgmt_html = __DIR__ . '/templates/management.html';
if (file_exists($mgmt_html)) {
    echo "=== templates/management.html details ===\n";
    echo "Exists: Yes\n";
    echo "Size: " . filesize($mgmt_html) . " bytes\n";
    echo "Modified: " . date("Y-m-d H:i:s", filemtime($mgmt_html)) . "\n";
    
    // Search for inventory.js reference
    $content = file_get_contents($mgmt_html);
    if (preg_match('/<script src=".*inventory.js.*?>/', $content, $matches)) {
        echo "Found tag: " . $matches[0] . "\n\n";
    } else {
        echo "WARNING: No inventory.js script tag found in management.html\n\n";
    }
} else {
    echo "ERROR: templates/management.html does not exist at: $mgmt_html\n\n";
}

// 5. OPcache Check and Reset
echo "=== OPcache Status ===\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status) {
        echo "OPcache Enabled: Yes\n";
        echo "Cached scripts count: " . (isset($status['scripts']) ? count($status['scripts']) : 'Unknown') . "\n";
        if (function_exists('opcache_reset')) {
            $reset = opcache_reset();
            echo "Resetting OPcache: " . ($reset ? "SUCCESS" : "FAILED") . "\n";
        } else {
            echo "opcache_reset() function is not available.\n";
        }
    } else {
        echo "OPcache is installed but status is not available.\n";
    }
} else {
    echo "OPcache extension is not loaded.\n";
}
echo "\n";
