<?php

declare(strict_types=1);

$ds = DIRECTORY_SEPARATOR;
$autoload = __DIR__ . $ds . 'vendor' . $ds . 'autoload.php';
if (!is_file($autoload)) {
    $autoload = __DIR__ . $ds . '..' . $ds . 'vendor' . $ds . 'autoload.php';
}
require $autoload;

use App\Config\AppConfig;

AppConfig::load();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = AppConfig::getCorsOrigins();
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Deployed bundle: index.php and app/ share the same directory (see build.sh).
// Local dev: index.php lives in public/ with app/ one level up.
$bootstrap = __DIR__ . $ds . 'app' . $ds . 'Config' . $ds . 'bootstrap.php';
if (!is_file($bootstrap)) {
    $bootstrap = __DIR__ . $ds . '..' . $ds . 'app' . $ds . 'Config' . $ds . 'bootstrap.php';
}
if (!is_file($bootstrap)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Application bootstrap not found.';
    exit;
}
require $bootstrap;
