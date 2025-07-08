<?php
// router.php - Router file for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Check if it's a file that exists
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // Serve the file directly
}

// Route everything else through index.php
require_once __DIR__ . '/index.php';