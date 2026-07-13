<?php
header('Content-Type: text/plain');
echo "OPcache Status:\n";
if (function_exists('opcache_reset')) {
    echo "Resetting: " . (opcache_reset() ? "SUCCESS" : "FAILED") . "\n";
} else {
    echo "opcache_reset() is not available.\n";
}
