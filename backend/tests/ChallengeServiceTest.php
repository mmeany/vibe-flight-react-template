<?php

declare(strict_types=1);

namespace App\Tests;

use App\Config\AppConfig;
use App\Services\ChallengeService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ChallengeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['CHALLENGE_SECRET'] = 'test-challenge-secret-for-unit-tests';
        $ref = new \ReflectionClass(AppConfig::class);
        $loaded = $ref->getProperty('loaded');
        $loaded->setAccessible(true);
        $loaded->setValue(null, false);
        AppConfig::load();
    }

    public function testCreateChallengeReturnsQuestionAndToken(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new ChallengeService($logger);

        $challenge = $service->createChallenge();

        $this->assertArrayHasKey('question', $challenge);
        $this->assertArrayHasKey('token', $challenge);
        $this->assertArrayHasKey('form_loaded_at', $challenge);
        $this->assertStringContainsString('What is', $challenge['question']);
        $this->assertStringContainsString('.', $challenge['token']);
    }

    public function testVerifyRejectsMismatchedFormLoadedAt(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new ChallengeService($logger);

        $challenge = $service->createChallenge();
        preg_match('/What is (\d+) \+ (\d+)\?/', $challenge['question'], $matches);
        $answer = (string) ((int) $matches[1] + (int) $matches[2]);

        $formLoadedAt = $challenge['form_loaded_at'] - 4;

        $this->expectException(\InvalidArgumentException::class);
        $service->verify($challenge['token'], $answer, $formLoadedAt);
    }

    public function testVerifyRejectsWrongAnswer(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new ChallengeService($logger);

        $challenge = $service->createChallenge();

        $this->expectException(\InvalidArgumentException::class);
        $service->verify($challenge['token'], '99999', $challenge['form_loaded_at']);
    }
}
