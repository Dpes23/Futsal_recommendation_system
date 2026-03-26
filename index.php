<?php
/**
 * Main Router for Futsal Recommendation System
 * This file is the entry point for all requests
 * It routes them to appropriate pages in /pages/ folder
 */

// Get the requested file path
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the URL to get just the path
$parsedUrl = parse_url($requestUri);
$requestPath = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';

// Remove the base path (handle both localhost:8000 and subdirectory cases)
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/' && $basePath !== '\\') {
    $requestPath = str_replace($basePath, '', $requestPath);
}

// Normalize path separators
$requestPath = str_replace('\\', '/', $requestPath);
$requestPath = trim($requestPath, '/');

// If empty path, default to home
if (empty($requestPath) || $requestPath === 'index.php') {
    $requestPath = 'home';
}

// Remove .php extension if present for clean URL routing
if (substr($requestPath, -4) === '.php') {
    $requestPath = substr($requestPath, 0, -4);
}

// Build the actual file path
$filePath = __DIR__ . '/pages/' . $requestPath . '.php';

// Security check: ensure the file is within the pages directory
$realPath = realpath($filePath);
$pagesDir = realpath(__DIR__ . '/pages/');

if ($realPath && $pagesDir && strpos($realPath, $pagesDir) === 0 && file_exists($realPath)) {
    // File exists in pages directory, include it
    require $realPath;
} else {
    // Try to serve static files (CSS, JS, images, etc.)
    $staticPath = __DIR__ . '/' . $requestPath;
    $staticRealPath = realpath($staticPath);
    $rootDir = realpath(__DIR__);
    
    if ($staticRealPath && $rootDir && strpos($staticRealPath, $rootDir) === 0 && is_file($staticRealPath)) {
        // Serve static file with correct content type
        $ext = pathinfo($staticRealPath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2'
        ];
        
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        readfile($staticRealPath);
    } else {
        // 404 - File not found, show home page
        header('HTTP/1.0 404 Not Found');
        require __DIR__ . '/pages/home.php';
    }
}
?>
