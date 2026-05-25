<?php

use App\Controllers\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ConfigController;
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

    $router->post('/login', function () use ($container) {
        $container->get(AuthController::class)->login();
    });

    $router->get('/config', function () {
        $controller = new ConfigController();
        $controller->publicConfig();
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
}, [JwtMiddleware::class, AdminMiddleware::class]);
