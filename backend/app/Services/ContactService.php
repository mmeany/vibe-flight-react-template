<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AuthenticatedSubmissionCreateDto;
use App\DTOs\SubmissionCreateDto;
use App\DTOs\SubmissionListQuery;
use App\Http\Response;
use App\Repositories\SubmissionRepository;
use App\Repositories\UserRepository;
use App\Support\ClientIp;
use App\Support\SubmissionLabels;
use Psr\Log\LoggerInterface;

class ContactService
{
    /** @var string[] */
    private const VALID_CATEGORIES = ['general_enquiry', 'feature_request', 'partnership', 'bug_report'];

    private const QUESTION_MAX_LENGTH = 250;

    private const EXPORT_MAX_ROWS = 10_000;

    public function __construct(
        private readonly SubmissionRepository $submissionRepository,
        private readonly ChallengeService $challengeService,
        private readonly RateLimitService $rateLimitService,
        private readonly MailService $mailService,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array{message: string, submission_id: int}
     */
    public function submit(SubmissionCreateDto $dto): array
    {
        if ($dto->website !== '') {
            $this->logger->info('honeypot_triggered', ['event' => 'honeypot_triggered']);
            Response::unprocessableEntity('Unable to process your request.');
            exit;
        }

        $errors = $this->validateFields($dto);
        if ($errors !== []) {
            Response::unprocessableEntity(implode(' ', $errors));
            exit;
        }

        try {
            $this->challengeService->verify($dto->challengeToken, $dto->challengeAnswer, $dto->formLoadedAt);
        } catch (\InvalidArgumentException $e) {
            Response::unprocessableEntity($e->getMessage());
            exit;
        }

        $ip = ClientIp::resolve();

        try {
            $this->rateLimitService->enforce($dto->email, $ip);
        } catch (\RuntimeException $e) {
            Response::tooManyRequests($e->getMessage());
            exit;
        }

        $submissionId = $this->submissionRepository->insert($dto->email, $dto->payloadFields());

        $sent = $this->mailService->sendAutoResponse($dto->email, $dto->knownAs, $dto->category);
        if ($sent) {
            $this->submissionRepository->markAutoResponseSent($submissionId);
        }

        $this->logger->info('contact.smtp', [
            'event' => 'contact.smtp',
            'submission_id' => $submissionId,
            'sent' => $sent,
        ]);

        return [
            'message' => 'Thank you — your message has been received.',
            'submission_id' => $submissionId,
        ];
    }

    /**
     * @return array{message: string, submission_id: int}
     */
    public function submitAuthenticated(int $userId, AuthenticatedSubmissionCreateDto $dto): array
    {
        if ($dto->website !== '') {
            $this->logger->info('honeypot_triggered', ['event' => 'honeypot_triggered']);
            Response::unprocessableEntity('Unable to process your request.');
            exit;
        }

        $errors = $this->validateAuthenticatedFields($dto);
        if ($errors !== []) {
            Response::unprocessableEntity(implode(' ', $errors));
            exit;
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            Response::notFound('User not found');
            exit;
        }

        $settings = $user->getSettings() ?? [];
        $alias = trim((string) ($settings['user_alias'] ?? ''));
        if ($alias === '') {
            $alias = $user->username;
        }

        $email = $user->email;
        $payload = [
            'firstname' => $alias,
            'surname' => '—',
            'known_as' => $alias,
            'category' => $dto->category,
            'question' => $dto->question,
        ];

        $ip = ClientIp::resolve();

        try {
            $this->rateLimitService->enforce($email, $ip);
        } catch (\RuntimeException $e) {
            Response::tooManyRequests($e->getMessage());
            exit;
        }

        $submissionId = $this->submissionRepository->insert($email, $payload);

        $sent = $this->mailService->sendAutoResponse($email, $alias, $dto->category);
        if ($sent) {
            $this->submissionRepository->markAutoResponseSent($submissionId);
        }

        $this->logger->info('contact.smtp', [
            'event' => 'contact.smtp',
            'submission_id' => $submissionId,
            'sent' => $sent,
            'authenticated' => true,
        ]);

        return [
            'message' => 'Thank you — your message has been received.',
            'submission_id' => $submissionId,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, sort: string, order: string}
     */
    public function listSubmissions(SubmissionListQuery $query): array
    {
        $items = $this->submissionRepository->listSubmissions($query);
        $total = $this->submissionRepository->countSubmissions($query);

        return [
            'items' => $items,
            'total' => $total,
            'sort' => $query->sort,
            'order' => $query->order,
        ];
    }

    public function exportSubmissionsCsv(SubmissionListQuery $query): string
    {
        $total = $this->submissionRepository->countSubmissions($query);
        if ($total > self::EXPORT_MAX_ROWS) {
            throw new \InvalidArgumentException('Too many rows to export; narrow your filters.');
        }

        $items = $this->submissionRepository->listSubmissionsForExport($query, self::EXPORT_MAX_ROWS);

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            Response::serverError('Failed to generate export.');
            exit;
        }

        $headers = [
            'id',
            'email',
            'known_as',
            'firstname',
            'surname',
            'category',
            'question',
            'status',
            'ignored',
            'created_at',
            'auto_response_sent_at',
            'follow_up_sent_at',
            'follow_up_response',
        ];
        fputcsv($handle, $headers);

        foreach ($items as $submission) {
            $payload = is_array($submission['payload']) ? $submission['payload'] : [];
            $category = (string) ($payload['category'] ?? '');
            $followUpSentAt = $submission['follow_up_sent_at'] ?? null;

            fputcsv($handle, [
                $submission['id'],
                $submission['email'],
                $payload['known_as'] ?? '',
                $payload['firstname'] ?? '',
                $payload['surname'] ?? '',
                SubmissionLabels::categoryLabel($category),
                $payload['question'] ?? '',
                SubmissionLabels::statusLabel((bool) $submission['ignored'], is_string($followUpSentAt) ? $followUpSentAt : null),
                $submission['ignored'] ? '1' : '0',
                $submission['created_at'] ?? '',
                $submission['auto_response_sent_at'] ?? '',
                $followUpSentAt ?? '',
                $submission['follow_up_response'] ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if ($csv === false) {
            Response::serverError('Failed to generate export.');
            exit;
        }

        return "\xEF\xBB\xBF" . $csv;
    }

    public function setIgnored(int $id, bool $ignored): array
    {
        $submission = $this->submissionRepository->findById($id);
        if ($submission === null) {
            Response::notFound('Submission not found');
            exit;
        }

        $this->submissionRepository->setIgnored($id, $ignored);

        return $this->submissionRepository->findById($id) ?? [];
    }

    public function reply(int $id, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            Response::unprocessableEntity('Reply message is required.');
            exit;
        }

        if (strlen($message) > 2000) {
            Response::unprocessableEntity('Reply message must be 2000 characters or fewer.');
            exit;
        }

        $submission = $this->submissionRepository->findById($id);
        if ($submission === null) {
            Response::notFound('Submission not found');
            exit;
        }

        $email = (string) $submission['email'];
        $payload = is_array($submission['payload']) ? $submission['payload'] : [];
        $knownAs = (string) ($payload['known_as'] ?? '');

        $sent = $this->mailService->sendFollowUp($email, $knownAs, $message);
        if (!$sent) {
            Response::serverError('Failed to send follow-up email. Please try again.');
            exit;
        }

        $this->submissionRepository->saveFollowUp($id, $message);

        return $this->submissionRepository->findById($id) ?? [];
    }

    /**
     * @return string[]
     */
    private function validateFields(SubmissionCreateDto $dto): array
    {
        $errors = [];

        if ($dto->firstname === '') {
            $errors[] = 'First name is required.';
        }
        if ($dto->surname === '') {
            $errors[] = 'Surname is required.';
        }
        if ($dto->email === '' || !filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if ($dto->knownAs === '') {
            $errors[] = 'Known as is required.';
        }
        if (!in_array($dto->category, self::VALID_CATEGORIES, true)) {
            $errors[] = 'Please select a valid category.';
        }
        if (strlen($dto->question) > self::QUESTION_MAX_LENGTH) {
            $errors[] = 'Question must be ' . self::QUESTION_MAX_LENGTH . ' characters or fewer.';
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validateAuthenticatedFields(AuthenticatedSubmissionCreateDto $dto): array
    {
        $errors = [];

        if (!in_array($dto->category, self::VALID_CATEGORIES, true)) {
            $errors[] = 'Please select a valid category.';
        }
        if (strlen($dto->question) > self::QUESTION_MAX_LENGTH) {
            $errors[] = 'Question must be ' . self::QUESTION_MAX_LENGTH . ' characters or fewer.';
        }

        return $errors;
    }
}
