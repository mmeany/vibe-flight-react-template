<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class SubmissionCreateDto
{
    public function __construct(
        public string $firstname,
        public string $surname,
        public string $email,
        public string $knownAs,
        public string $category,
        public string $question,
        public string $challengeToken,
        public string $challengeAnswer,
        public int $formLoadedAt,
        public string $website,
    ) {}

    /**
     * @param array<string, mixed> $body
     */
    public static function fromRequestBody(array $body): self
    {
        return new self(
            firstname: trim((string) ($body['firstname'] ?? '')),
            surname: trim((string) ($body['surname'] ?? '')),
            email: trim((string) ($body['email'] ?? '')),
            knownAs: trim((string) ($body['known_as'] ?? '')),
            category: trim((string) ($body['category'] ?? '')),
            question: trim((string) ($body['question'] ?? '')),
            challengeToken: trim((string) ($body['challenge_token'] ?? '')),
            challengeAnswer: trim((string) ($body['challenge_answer'] ?? '')),
            formLoadedAt: (int) ($body['form_loaded_at'] ?? 0),
            website: trim((string) ($body['_website'] ?? '')),
        );
    }

    /**
     * @return array<string, string>
     */
    public function payloadFields(): array
    {
        return [
            'firstname' => $this->firstname,
            'surname' => $this->surname,
            'known_as' => $this->knownAs,
            'category' => $this->category,
            'question' => $this->question,
        ];
    }
}
