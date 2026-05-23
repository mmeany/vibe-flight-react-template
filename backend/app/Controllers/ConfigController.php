<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\AppConfig;
use App\Http\Response;

class ConfigController
{
    public function publicConfig(): void
    {
        Response::success([
            'registration' => [
                'enabled' => AppConfig::isRegistrationEnabled(),
            ],
        ]);
    }
}
