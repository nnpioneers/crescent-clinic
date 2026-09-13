<?php
/**
 * Router script for PHP built-in server
 */
$uri = decodeURI($_SERVER['REQUEST_URI']);
$path = parse_url($uri, PHP_URL_PATH);

function decodeURI($uri) {
    return rawurldecode($uri);
}

// Serve static files directly with caching and compression
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|svg|pdf|ico)$/', $path)) {
    $filePath = __DIR__ . $path;
    if (file_exists($filePath)) {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'ico' => 'image/x-icon'
        ];
        
        $mime = $mimes[$extension] ?? 'application/octet-stream';
        header("Content-Type: " . $mime);
        
        // Browser Caching & ETag
        $lastModified = filemtime($filePath);
        $etag = '"' . md5($lastModified . filesize($filePath)) . '"';
        if (in_array($extension, ['js', 'css'])) {
            header("Cache-Control: no-cache, must-revalidate");
        } else {
            header("Cache-Control: public, max-age=604800"); // 7 days
        }

        if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
            (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified)) {
            http_response_code(304);
            exit;
        }

        // Gzip compression for text assets
        $compressible = in_array($extension, ['css', 'js', 'svg']);
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        if ($compressible && strpos($acceptEncoding, 'gzip') !== false && function_exists('gzencode')) {
            $content = file_get_contents($filePath);
            $compressed = gzencode($content, 6);
            if ($compressed !== false) {
                header("Content-Encoding: gzip");
                header("Content-Length: " . strlen($compressed));
                echo $compressed;
                return true;
            }
        }

        readfile($filePath);
        return true;
    }
}

// Enable output compression for dynamic responses if supported
if (!ob_get_level() && strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false && function_exists('ob_gzhandler')) {
    ob_start('ob_gzhandler');
}

// Otherwise, route to index.php
require_once __DIR__ . '/index.php';
