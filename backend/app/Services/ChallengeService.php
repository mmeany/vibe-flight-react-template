<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use Psr\Log\LoggerInterface;

class ChallengeService
{
    private const MIN_SUBMIT_SECONDS = 3;
    private const TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array{question: string, token: string, form_loaded_at: int}
     */
    public function createChallenge(): array
    {
        $a = random_int(1, 10);
        $b = random_int(1, 10);
        $formLoadedAt = time();

        $payload = [
            'a' => $a,
            'b' => $b,
            'form_loaded_at' => $formLoadedAt,
            'exp' => $formLoadedAt + self::TOKEN_TTL_SECONDS,
        ];

        return [
            'question' => "What is {$a} + {$b}?",
            'token' => $this->signPayload($payload),
            'form_loaded_at' => $formLoadedAt,
        ];
    }

    public function verify(string $token, string $answer, int $formLoadedAt): void
    {
        if ($token === '' || $answer === '') {
            $this->logRejected('malformed_token');
            throw new \InvalidArgumentException('Please complete the security check.');
        }

        $payload = $this->verifyToken($token);
        if ($payload === null) {
            $this->logRejected('invalid_signature');
            throw new \InvalidArgumentException('Please complete the security check.');
        }

        if ((int) ($payload['form_loaded_at'] ?? 0) !== $formLoadedAt) {
            $this->logRejected('timing_mismatch');
            throw new \InvalidArgumentException('Please complete the security check.');
        }

        $elapsed = time() - $formLoadedAt;
        if ($elapsed < self::MIN_SUBMIT_SECONDS) {
            $this->logRejected('submit_too_fast');
            throw new \InvalidArgumentException('Please take a moment before submitting.');
        }

        $expected = (int) ($payload['a'] ?? 0) + (int) ($payload['b'] ?? 0);
        if ((int) $answer !== $expected) {
            $this->logRejected('wrong_answer');
            throw new \InvalidArgumentException('The security answer is incorrect.');
        }
    }

    /**
     * @return array<string, int>|null
     */
    private function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encoded, $signature] = $parts;
        $expected = hash_hmac('sha256', $encoded, $this->secret());
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $json = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        /** @var array<string, int>|null $payload */
        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    /**
     * @param array<string, int> $payload
     */
    private function signPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, $this->secret());

        return $encoded . '.' . $signature;
    }

    private function secret(): string
    {
        $secret = AppConfig::getChallengeSecret();
        if ($secret === '') {
            throw new \RuntimeException('CHALLENGE_SECRET is not configured');
        }

        return $secret;
    }

    private function logRejected(string $reason): void
    {
        $this->logger->info('challenge_rejected', ['event' => 'challenge_rejected', 'reason' => $reason]);
    }
}
