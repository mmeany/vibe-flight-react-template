<?php

declare(strict_types=1);

namespace App\Tests;

use App\DTOs\AuthenticatedSubmissionCreateDto;
use App\Models\User;
use App\Repositories\SubmissionRepository;
use App\Repositories\UserRepository;
use App\Services\ChallengeService;
use App\Services\ContactService;
use App\Services\MailService;
use App\Services\RateLimitService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContactServiceAuthenticatedTest extends TestCase
{
    public function testAuthenticatedSubmissionCreateDtoFromRequestBody(): void
    {
        $dto = AuthenticatedSubmissionCreateDto::fromRequestBody([
            'category' => 'general_enquiry',
            'question' => 'Hello',
            '_website' => '',
        ]);

        $this->assertSame('general_enquiry', $dto->category);
        $this->assertSame('Hello', $dto->question);
        $this->assertSame('', $dto->website);
    }

    public function testSubmitAuthenticatedMapsUserAliasToPayload(): void
    {
        $user = new User(
            id: 1,
            username: 'jdoe',
            email: 'jane@example.com',
            settings: ['user_alias' => 'Jane'],
        );

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findById')->with(1)->willReturn($user);

        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->expects($this->once())
            ->method('insert')
            ->with(
                'jane@example.com',
                [
                    'firstname' => 'Jane',
                    'surname' => '—',
                    'known_as' => 'Jane',
                    'category' => 'feature_request',
                    'question' => 'Add dark mode',
                ],
            )
            ->willReturn(42);

        $submissionRepo->expects($this->once())->method('markAutoResponseSent')->with(42);

        $rateLimit = $this->createMock(RateLimitService::class);
        $rateLimit->expects($this->once())->method('enforce')->with('jane@example.com', $this->anything());

        $mail = $this->createMock(MailService::class);
        $mail->expects($this->once())
            ->method('sendAutoResponse')
            ->with('jane@example.com', 'Jane', 'feature_request')
            ->willReturn(true);

        $service = $this->createService($submissionRepo, $userRepo, $rateLimit, $mail);

        $dto = new AuthenticatedSubmissionCreateDto(
            category: 'feature_request',
            question: 'Add dark mode',
            website: '',
        );

        $result = $service->submitAuthenticated(1, $dto);

        $this->assertSame(42, $result['submission_id']);
        $this->assertSame('Thank you — your message has been received.', $result['message']);
    }

    public function testSubmitAuthenticatedFallsBackToUsernameWhenNoAlias(): void
    {
        $user = new User(
            id: 2,
            username: 'bob',
            email: 'bob@example.com',
        );

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findById')->willReturn($user);

        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->expects($this->once())
            ->method('insert')
            ->with(
                'bob@example.com',
                $this->callback(function (array $payload): bool {
                    return $payload['firstname'] === 'bob'
                        && $payload['known_as'] === 'bob'
                        && $payload['surname'] === '—';
                }),
            )
            ->willReturn(7);

        $rateLimit = $this->createMock(RateLimitService::class);
        $mail = $this->createMock(MailService::class);
        $mail->method('sendAutoResponse')->willReturn(false);

        $service = $this->createService($submissionRepo, $userRepo, $rateLimit, $mail);

        $dto = new AuthenticatedSubmissionCreateDto(
            category: 'general_enquiry',
            question: '',
            website: '',
        );

        $service->submitAuthenticated(2, $dto);
        $this->addToAssertionCount(1);
    }

    public function testSubmitAuthenticatedAcceptsBugReportCategory(): void
    {
        $user = new User(
            id: 3,
            username: 'reporter',
            email: 'reporter@example.com',
        );

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findById')->willReturn($user);

        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->expects($this->once())
            ->method('insert')
            ->with(
                'reporter@example.com',
                $this->callback(fn (array $payload): bool => $payload['category'] === 'bug_report'),
            )
            ->willReturn(9);

        $rateLimit = $this->createMock(RateLimitService::class);
        $mail = $this->createMock(MailService::class);
        $mail->method('sendAutoResponse')->willReturn(false);

        $service = $this->createService($submissionRepo, $userRepo, $rateLimit, $mail);

        $dto = new AuthenticatedSubmissionCreateDto(
            category: 'bug_report',
            question: 'Button does not work',
            website: '',
        );

        $service->submitAuthenticated(3, $dto);
        $this->addToAssertionCount(1);
    }

    public function testValidateAuthenticatedFieldsRejectsInvalidCategory(): void
    {
        $service = $this->createService();
        $dto = new AuthenticatedSubmissionCreateDto(
            category: 'invalid',
            question: 'Hi',
            website: '',
        );

        $errors = $this->invokeValidateAuthenticatedFields($service, $dto);

        $this->assertContains('Please select a valid category.', $errors);
    }

    public function testValidateAuthenticatedFieldsRejectsLongQuestion(): void
    {
        $service = $this->createService();
        $dto = new AuthenticatedSubmissionCreateDto(
            category: 'general_enquiry',
            question: str_repeat('x', 251),
            website: '',
        );

        $errors = $this->invokeValidateAuthenticatedFields($service, $dto);

        $this->assertContains('Question must be 250 characters or fewer.', $errors);
    }

    private function createService(
        ?SubmissionRepository $submissionRepo = null,
        ?UserRepository $userRepo = null,
        ?RateLimitService $rateLimit = null,
        ?MailService $mail = null,
    ): ContactService {
        return new ContactService(
            $submissionRepo ?? $this->createMock(SubmissionRepository::class),
            $this->createMock(ChallengeService::class),
            $rateLimit ?? $this->createMock(RateLimitService::class),
            $mail ?? $this->createMock(MailService::class),
            $userRepo ?? $this->createMock(UserRepository::class),
            $this->createMock(LoggerInterface::class),
        );
    }

    /**
     * @return string[]
     */
    private function invokeValidateAuthenticatedFields(
        ContactService $service,
        AuthenticatedSubmissionCreateDto $dto,
    ): array {
        $method = new \ReflectionMethod(ContactService::class, 'validateAuthenticatedFields');
        $method->setAccessible(true);

        /** @var string[] $errors */
        $errors = $method->invoke($service, $dto);

        return $errors;
    }
}
