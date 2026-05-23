<?php

declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$ds = DIRECTORY_SEPARATOR;
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