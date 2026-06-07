<?php

declare(strict_types=1);

namespace App\Http;

use flight\Engine;

class Response
{
    public static function success(mixed $data = null, int $statusCode = 200): void
    {
        self::json(['data' => $data, 'error' => null], $statusCode);
    }

    public static function successWithMeta(mixed $data, array $meta, int $statusCode = 200): void
    {
        self::json(['data' => $data, 'meta' => $meta, 'error' => null], $statusCode);
    }

    public static function created(mixed $data = null): void
    {
        self::success($data, 201);
    }

    public static function error(string $message, string $code, int $statusCode): void
    {
        self::json([
            'data' => null,
            'error' => ['message' => $message, 'code' => $code],
        ], $statusCode);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 'UNAUTHORIZED', 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 'FORBIDDEN', 403);
    }

    public static function notFound(string $message = 'Not found'): void
    {
        self::error($message, 'NOT_FOUND', 404);
    }

    public static function conflict(string $message): void
    {
        self::error($message, 'CONFLICT', 409);
    }

    public static function validationError(string $message): void
    {
        self::error($message, 'VALIDATION_ERROR', 400);
    }

    public static function unprocessableEntity(string $message): void
    {
        self::error($message, 'VALIDATION_ERROR', 422);
    }

    public static function tooManyRequests(string $message): void
    {
        self::error($message, 'RATE_LIMIT', 429);
    }

    public static function gone(string $message): void
    {
        self::error($message, 'GONE', 410);
    }

    public static function serviceUnavailable(string $message): void
    {
        self::error($message, 'SERVICE_UNAVAILABLE', 503);
    }

    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, 'SERVER_ERROR', 500);
    }

    private static function json(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}
