<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class AuthenticatedSubmissionCreateDto
{
    public function __construct(
        public string $category,
        public string $question,
        public string $website,
    ) {}

    /**
     * @param array<string, mixed> $body
     */
    public static function fromRequestBody(array $body): self
    {
        return new self(
            category: trim((string) ($body['category'] ?? '')),
            question: trim((string) ($body['question'] ?? '')),
            website: trim((string) ($body['_website'] ?? $body['website'] ?? '')),
        );
    }
}
