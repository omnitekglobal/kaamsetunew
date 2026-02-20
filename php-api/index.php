<?php

declare(strict_types=1);

// CORS for every request (sent first so errors still have CORS)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$baseDir = __DIR__;
require_once $baseDir . '/vendor/autoload.php';
require_once $baseDir . '/config/database.php';
require_once $baseDir . '/includes/response.php';
require_once $baseDir . '/includes/auth.php';

// Load .env
$envPath = $baseDir . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\"'");
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = $path === '' || $path === false ? '/' : $path;
$path = trim(preg_replace('#/+#', '/', trim($path, '/')), '/');
$path = $path === '' ? '/' : '/' . $path;
$method = $_SERVER['REQUEST_METHOD'];

// Remove base path only if API runs in a subdir (e.g. /php-api/api/...). Never strip if
// that would turn the path into "/" (e.g. request /api/services with SCRIPT_NAME /api/services/index.php).
$scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($scriptName !== '/' && $scriptName !== '\\' && $scriptName !== '' && strpos($path, $scriptName) === 0) {
    $after = trim(substr($path, strlen($scriptName)), '/');
    $path = $after === '' ? $path : '/' . $after;
}

// Route map: method + path => file (single space between method and path)
$routes = [
    'GET /api/roles' => 'api/roles/index.php',
    'POST /api/auth/register' => 'api/auth/register.php',
    'POST /api/auth/login' => 'api/auth/login.php',
    'GET /api/users' => 'api/users/index.php',
    'POST /api/users' => 'api/users/create.php',
    'GET /api/users/me' => 'api/users/me.php',
    'GET /api/users/{id}' => 'api/users/read.php',
    'PUT /api/users/{id}' => 'api/users/update.php',
    'DELETE /api/users/{id}' => 'api/users/delete.php',
    'GET /api/categories' => 'api/categories/index.php',
    'POST /api/categories' => 'api/categories/create.php',
    'GET /api/categories/{id}' => 'api/categories/read.php',
    'PUT /api/categories/{id}' => 'api/categories/update.php',
    'DELETE /api/categories/{id}' => 'api/categories/delete.php',
    'GET /api/services' => 'api/services/index.php',
    'POST /api/services' => 'api/services/create.php',
    'GET /api/services/{id}' => 'api/services/read.php',
    'PUT /api/services/{id}' => 'api/services/update.php',
    'DELETE /api/services/{id}' => 'api/services/delete.php',
    'POST /api/bookings' => 'api/bookings/create.php',
    'GET /api/bookings/{id}' => 'api/bookings/read.php',
    'POST /api/professionals/register' => 'api/professionals/register.php',
    'GET /api/professionals/view/{id}' => 'api/professionals/view.php',
];

$key = $method . ' ' . $path;
if (isset($routes[$key])) {
    require $baseDir . '/' . $routes[$key];
    exit;
}

// Match dynamic routes {id}
foreach ($routes as $route => $file) {
    [$routeMethod, $routePath] = explode(' ', $route, 2);
    if ($routeMethod !== $method) continue;
    $pattern = '#^' . preg_replace('#\{id\}#', '([^/]+)', preg_quote($routePath, '#')) . '$#';
    if (preg_match($pattern, $path, $m)) {
        $_GET['_id'] = $m[1];
        require $baseDir . '/' . $file;
        exit;
    }
}

jsonError('Not Found', 404);
