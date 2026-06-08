<?php

declare(strict_types=1);

namespace App\Tests;

use App\DTOs\SubmissionListQuery;
use App\Repositories\SubmissionRepository;
use App\Repositories\UserRepository;
use App\Services\ChallengeService;
use App\Services\ContactService;
use App\Services\MailService;
use App\Services\RateLimitService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContactServiceExportTest extends TestCase
{
    public function testExportSubmissionsCsvRejectsOverLimit(): void
    {
        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->method('countSubmissions')->willReturn(10_001);
        $submissionRepo->expects($this->never())->method('listSubmissionsForExport');

        $service = $this->makeService($submissionRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many rows to export; narrow your filters.');

        $service->exportSubmissionsCsv(new SubmissionListQuery(
            includeIgnored: false,
            page: 1,
            perPage: 25,
            search: '',
            sort: 'created_at',
            order: 'desc',
            status: 'all',
        ));
    }

    public function testExportSubmissionsCsvIncludesHeaderAndUtf8Bom(): void
    {
        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->method('countSubmissions')->willReturn(1);
        $submissionRepo->method('listSubmissionsForExport')->willReturn([
            [
                'id' => 1,
                'email' => 'user@example.com',
                'payload' => [
                    'known_as' => 'Alice',
                    'firstname' => 'Alice',
                    'surname' => 'Smith',
                    'category' => 'general_enquiry',
                    'question' => 'Hello, world',
                ],
                'ignored' => false,
                'follow_up_response' => null,
                'created_at' => '2026-01-01 12:00:00',
                'auto_response_sent_at' => '2026-01-01 12:00:01',
                'follow_up_sent_at' => null,
            ],
        ]);

        $service = $this->makeService($submissionRepo);
        $csv = $service->exportSubmissionsCsv(new SubmissionListQuery(
            includeIgnored: false,
            page: 1,
            perPage: 25,
            search: '',
            sort: 'created_at',
            order: 'desc',
            status: 'all',
        ));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('id,email,known_as,firstname,surname,category,question,status,ignored', $csv);
        $this->assertStringContainsString('user@example.com', $csv);
        $this->assertStringContainsString('General enquiry', $csv);
        $this->assertStringContainsString(',New,0,', $csv);
    }

    private function makeService(SubmissionRepository $submissionRepo): ContactService
    {
        return new ContactService(
            $submissionRepo,
            $this->createMock(ChallengeService::class),
            $this->createMock(RateLimitService::class),
            $this->createMock(MailService::class),
            $this->createMock(UserRepository::class),
            $this->createMock(LoggerInterface::class),
        );
    }
}
