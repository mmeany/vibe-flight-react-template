<?php

use App\Config\AppConfig;
use App\Controllers\AdminUserController;
use App\Controllers\AuthController;
use App\Database\Database;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\UserAdminService;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Tracy\Debugger;
use flight\debug\tracy\TracyExtensionLoader;

/** @var array $config */
/** @var \flight\Engine $app */

$logDir = __DIR__ . $ds . '..' . $ds . AppConfig::getLogDir();

if (!AppConfig::isProduction()) {
    Debugger::enable();
    Debugger::$logDirectory = $logDir;
    Debugger::$strictMode = true;

    if (Debugger::$showBar === true && php_sapi_name() !== 'cli') {
        (new TracyExtensionLoader($app));
    }
}
if (!is_dir($logDir)) {
    if (!mkdir($logDir, 0755, true)) {
        throw new \RuntimeException("Cannot create log directory: $logDir");
    }
}

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler("$logDir/app.log", AppConfig::resolveLogLevel()));

try {
    $db = new Database();
    $db->migrate();
    $logger->info('Database migration completed successfully');
} catch (\Throwable $e) {
    $logger->error('Database migration failed: ' . $e->getMessage());
    throw $e;
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);

$containerBuilder->addDefinitions([
    LoggerInterface::class => $logger,
    Database::class => $db,
    UserRepository::class => \DI\autowire()->constructor(\DI\get(Database::class)),
    AuthService::class => \DI\autowire()->constructor(\DI\get(UserRepository::class), \DI\get(LoggerInterface::class)),
    AuthController::class => \DI\autowire()->constructor(\DI\get(AuthService::class)),
    UserAdminService::class => \DI\autowire()->constructor(\DI\get(UserRepository::class), \DI\get(LoggerInterface::class)),
    AdminUserController::class => \DI\autowire()->constructor(\DI\get(UserAdminService::class)),
]);

$container = $containerBuilder->build();
$app->set('container', $container);
