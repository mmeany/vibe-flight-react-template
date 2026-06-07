<?php

declare(strict_types=1);

namespace App\Tests;

use App\Config\AppConfig;
use App\Services\MailService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MailServiceTest extends TestCase
{
    public function testCategoryLabelMapping(): void
    {
        $_ENV['SMTP_HOST'] = '127.0.0.1';
        $_ENV['SMTP_PORT'] = '19999';
        $_ENV['SMTP_SECURE'] = 'none';
        $_ENV['SMTP_FROM_EMAIL'] = 'test@example.com';
        $_ENV['SMTP_FROM_NAME'] = 'Test';
        $ref = new \ReflectionClass(AppConfig::class);
        $loaded = $ref->getProperty('loaded');
        $loaded->setAccessible(true);
        $loaded->setValue(null, false);
        AppConfig::load();

        $logger = $this->createMock(LoggerInterface::class);
        $service = new MailService($logger);

        $this->assertFalse($service->sendAutoResponse('invalid@localhost', 'Test', 'general_enquiry'));
    }
}
