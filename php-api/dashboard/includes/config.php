<?php

declare(strict_types=1);

session_start();

$baseDir = dirname(__DIR__);
require_once $baseDir . '/../config/database.php';

// Load .env from php-api root
$envPath = dirname($baseDir) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val, " \t\"'");
    }
}

// When run via router, SCRIPT_NAME can be the request URI (e.g. /dashboard/assets/style.css); use request path so base is always /dashboard.
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
$reqPath = parse_url($reqUri, PHP_URL_PATH) ?: '';
$dashboardBase = (strpos($reqPath, '/dashboard') === 0)
    ? '/dashboard'
    : (rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') ?: '');
define('DASHBOARD_BASE', $dashboardBase);
define('DASHBOARD_ROLES', ['super_admin', 'team_leader', 'staff', 'professional', 'end_user']);
