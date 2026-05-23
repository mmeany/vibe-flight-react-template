<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Response;
use App\Utils\JwtUtil;

class JwtMiddleware
{
    public static function before(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            Response::unauthorized('Missing or invalid authorization header');
            exit;
        }

        $decoded = JwtUtil::validateToken($matches[1]);

        if ($decoded === null) {
            Response::unauthorized('Invalid or expired token');
            exit;
        }

        $_REQUEST['user_id'] = (int) $decoded->sub;
        $_REQUEST['username'] = $decoded->username;
    }
}
