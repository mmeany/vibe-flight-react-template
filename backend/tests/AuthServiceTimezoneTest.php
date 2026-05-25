<?php

declare(strict_types=1);

namespace App\Tests;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AuthServiceTimezoneTest extends TestCase
{
    public function testUpdateSettingsPersistsValidTimezone(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->once())
            ->method('updateSettings')
            ->with(1, ['timezone' => 'Europe/London'])
            ->willReturn(['timezone' => 'Europe/London', 'theme_mode' => 'light']);

        $service = new AuthService($repo, $this->createMock(LoggerInterface::class));

        $result = $service->updateSettings(1, ['timezone' => 'Europe/London']);

        $this->assertSame('Europe/London', $result['timezone']);
    }
}
