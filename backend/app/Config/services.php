<?php

use App\Config\AppConfig;
use App\Controllers\AdminSubmissionController;
use App\Controllers\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ChallengeController;
use App\Controllers\ContactController;
use App\Database\Database;
use App\Repositories\PendingRegistrationRepository;
use App\Repositories\RateLimitRepository;
use App\Repositories\SubmissionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\ChallengeService;
use App\Services\ContactService;
use App\Services\MailService;
use App\Services\RateLimitService;
use App\Services\RegistrationService;
use App\Services\UserAdminService;
use DI\ContainerBuilder;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Tracy\Debugger;
use flight\debug\tracy\TracyExtensionLoader;

/** @var array $config */
/** @var \flight\Engine $app */

$logDir = dirname(__DIR__, 2) . $ds . AppConfig::getLogDir();

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
$logger->pushHandler(new RotatingFileHandler(
    "$logDir/app.log",
    AppConfig::getLogMaxFiles(),
    AppConfig::resolveLogLevel()
));

try {
    $db = new Database();
    $db->migrate();
    $db->runFileMigrations($logger);
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
    PendingRegistrationRepository::class => \DI\autowire()->constructor(\DI\get(Database::class)),
    RegistrationService::class => \DI\autowire()->constructor(
        \DI\get(PendingRegistrationRepository::class),
        \DI\get(UserRepository::class),
        \DI\get(MailService::class),
        \DI\get(RateLimitService::class),
        \DI\get(ChallengeService::class),
        \DI\get(LoggerInterface::class),
    ),
    AuthController::class => \DI\autowire()->constructor(
        \DI\get(AuthService::class),
        \DI\get(RegistrationService::class),
    ),
    UserAdminService::class => \DI\autowire()->constructor(\DI\get(UserRepository::class), \DI\get(LoggerInterface::class)),
    AdminUserController::class => \DI\autowire()->constructor(\DI\get(UserAdminService::class)),
    SubmissionRepository::class => \DI\autowire()->constructor(\DI\get(Database::class)),
    RateLimitRepository::class => \DI\autowire()->constructor(\DI\get(Database::class)),
    MailService::class => \DI\autowire()->constructor(\DI\get(LoggerInterface::class)),
    ChallengeService::class => \DI\autowire()->constructor(\DI\get(LoggerInterface::class)),
    RateLimitService::class => \DI\autowire()->constructor(
        \DI\get(Database::class),
        \DI\get(RateLimitRepository::class),
        \DI\get(LoggerInterface::class),
    ),
    ContactService::class => \DI\autowire()->constructor(
        \DI\get(SubmissionRepository::class),
        \DI\get(ChallengeService::class),
        \DI\get(RateLimitService::class),
        \DI\get(MailService::class),
        \DI\get(LoggerInterface::class),
    ),
    ChallengeController::class => \DI\autowire()->constructor(\DI\get(ChallengeService::class)),
    ContactController::class => \DI\autowire()->constructor(\DI\get(ContactService::class)),
    AdminSubmissionController::class => \DI\autowire()->constructor(\DI\get(ContactService::class)),
]);

$container = $containerBuilder->build();
$app->set('container', $container);
