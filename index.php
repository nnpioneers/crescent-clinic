<?php
/**
 * Hostinger Entry Point (Updated for React)
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Bypass specific API or action endpoints
if (strpos($uri, '/api') !== false || strpos($uri, '/reports_api') !== false || strpos($uri, '/control_access') !== false || strpos($uri, '/logout') !== false) {
    require __DIR__ . '/api/index.php';
    exit;
}

// Serve static assets from frontend/dist if they exist
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|svg|pdf|woff|woff2|ttf|eot|ico)$/', $uri)) {
    $distPath = __DIR__ . '/frontend/dist' . $uri;
    if (file_exists($distPath)) {
        $extension = pathinfo($distPath, PATHINFO_EXTENSION);
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        if (isset($mimes[$extension])) {
            header("Content-Type: " . $mimes[$extension]);
        }
        readfile($distPath);
        exit;
    }
}

// Serve React SPA for everything else
$reactIndex = __DIR__ . '/frontend/dist/index.html';
if (file_exists($reactIndex)) {
    echo file_get_contents($reactIndex);
    exit;
}

echo "React frontend not built. Please run 'npm run build' inside the 'frontend' directory.";
