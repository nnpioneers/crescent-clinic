<?php
/**
 * Router script for PHP built-in server
 */
// Delegate everything to the newly updated index.php, which handles both static React assets and SPA fallback.
require_once __DIR__ . '/index.php';
