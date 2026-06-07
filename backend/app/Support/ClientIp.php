<?php

declare(strict_types=1);

namespace App\Support;

class ClientIp
{
    public static function resolve(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded !== '') {
            $parts = array_map('trim', explode(',', $forwarded));
            $first = $parts[0] ?? '';
            if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }

        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
    }
}
