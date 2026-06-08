<?php

use App\Controllers\AdminSubmissionController;
use App\Controllers\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ChallengeController;
use App\Controllers\ConfigController;
use App\Controllers\ContactController;
use App\Http\Response;
use App\Middleware\AdminMiddleware;
use App\Middleware\JwtMiddleware;

/** @var \flight\net\Router $router */
/** @var \flight\Engine $app */
$container = $app->get('container');

$router->group('/api/v1', function (\flight\net\Router $router) use ($container) {
    $router->post('/register', function () use ($container) {
        $container->get(AuthController::class)->register();
    });

    $router->post('/register/verify', function () use ($container) {
        $container->get(AuthController::class)->verifyRegistration();
    });

    $router->post('/register/resend', function () use ($container) {
        $container->get(AuthController::class)->resendVerification();
    });

    $router->post('/login', function () use ($container) {
        $container->get(AuthController::class)->login();
    });

    $router->get('/config', function () {
        $controller = new ConfigController();
        $controller->publicConfig();
    });

    $router->get('/challenge', function () use ($container) {
        $container->get(ChallengeController::class)->show();
    });

    $router->post('/contact', function () use ($container) {
        $container->get(ContactController::class)->submit();
    });
}, []);

$router->group('/api/v1', function (\flight\net\Router $router) use ($container) {
    $router->get('/me', function () use ($container) {
        $container->get(AuthController::class)->me();
    });

    $router->patch('/settings', function () use ($container) {
        $container->get(AuthController::class)->updateSettings();
    });

    $router->post('/password', function () use ($container) {
        $container->get(AuthController::class)->changePassword();
    });

    $router->post('/contact/authenticated', function () use ($container) {
        $container->get(ContactController::class)->submitAuthenticated();
    });
}, [JwtMiddleware::class]);

$router->group('/api/v1', function (\flight\net\Router $router) use ($container) {
    $router->post('/admin/users', function () use ($container) {
        $container->get(AdminUserController::class)->create();
    });

    $router->post('/admin/users/import', function () use ($container) {
        $container->get(AdminUserController::class)->import();
    });

    $router->get('/admin/users', function () use ($container) {
        $container->get(AdminUserController::class)->index();
    });

    $router->get('/admin/users/@id:\d+', function (string $id) use ($container) {
        $container->get(AdminUserController::class)->show($id);
    });

    $router->patch('/admin/users/@id:\d+', function (string $id) use ($container) {
        $container->get(AdminUserController::class)->update($id);
    });

    $router->delete('/admin/users/@id:\d+', function (string $id) use ($container) {
        $container->get(AdminUserController::class)->deactivate($id);
    });

    $router->post('/admin/users/@id:\d+/restore', function (string $id) use ($container) {
        $container->get(AdminUserController::class)->restore($id);
    });

    $router->get('/admin/submissions/export', function () use ($container) {
        $container->get(AdminSubmissionController::class)->export();
    });

    $router->get('/admin/submissions', function () use ($container) {
        $container->get(AdminSubmissionController::class)->index();
    });

    $router->patch('/admin/submissions/@id:\d+/ignore', function (string $id) use ($container) {
        $container->get(AdminSubmissionController::class)->ignore($id);
    });

    $router->post('/admin/submissions/@id:\d+/reply', function (string $id) use ($container) {
        $container->get(AdminSubmissionController::class)->reply($id);
    });
}, [JwtMiddleware::class, AdminMiddleware::class]);
