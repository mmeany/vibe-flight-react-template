<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\AppConfig;
use App\Http\Response;

class AdminMiddleware
{
    public static function before(): void
    {
        $username = (string) ($_REQUEST['username'] ?? '');

        if (!AppConfig::isAdminUsername($username)) {
            Response::forbidden('Admin access required');
            exit;
        }
    }
}
