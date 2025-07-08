<?php
// router.php - Router file for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Log the incoming request for debugging
error_log("Router handling: " . $uri . " Method: " . $_SERVER['REQUEST_METHOD']);

// Check if it's a static file that exists (like CSS, JS, images)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    // Check if it's actually a static file (has extension)
    $pathInfo = pathinfo($uri);
    if (isset($pathInfo['extension']) && in_array($pathInfo['extension'], ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico'])) {
        return false; // Serve the file directly
    }
}

// Route everything else through index.php
require_once __DIR__ . '/index.php';
