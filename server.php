<?php
// Laravel router for php -S (dev server)
// If the requested file exists in public/, serve it directly
$public = __DIR__ . '/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = $public . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}
require $public . '/index.php';
