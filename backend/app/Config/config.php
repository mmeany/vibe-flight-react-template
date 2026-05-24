<?php

use App\Config\AppConfig;

date_default_timezone_set('UTC');
error_reporting(E_ALL);

if (function_exists('mb_internal_encoding') === true) {
    mb_internal_encoding('UTF-8');
}

if (function_exists('setlocale') === true) {
    setlocale(LC_ALL, 'en_GB.UTF-8', 'en_US.UTF-8');
}

if (empty($app) === true) {
    $app = Flight::app();
}

define('PROJECT_ROOT', __DIR__ . '/../..');

AppConfig::load();

$app->set('flight.base_url', '/');
$app->set('flight.case_sensitive', false);
$app->set('flight.log_errors', true);
$app->set('flight.handle_errors', false);
$app->set('flight.content_length', false);

$nonce = bin2hex(random_bytes(16));
$app->set('csp_nonce', $nonce);

return [
    'runway' => [
        'index_root' => 'public/index.php',
        'app_root' => 'app/',
    ],
    'database' => [
        'host' => AppConfig::getDbHost(),
        'port' => AppConfig::getDbPort(),
        'dbname' => AppConfig::getDbDatabase(),
        'user' => AppConfig::getDbUsername(),
        'password' => AppConfig::getDbPassword(),
    ],
    'jwt' => [
        'secret' => AppConfig::getJwtSecret(),
        'expiration_days' => AppConfig::getJwtExpirationDays(),
    ],
    'registration' => [
        'enabled' => AppConfig::isRegistrationEnabled(),
    ],
];
