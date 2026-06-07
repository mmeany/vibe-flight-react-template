<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Services\ContactService;

class AdminSubmissionController
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    public function index(): void
    {
        $includeIgnored = filter_var($_GET['include_ignored'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));

        $result = $this->contactService->listSubmissions($includeIgnored, $page, $perPage);
        Response::successWithMeta($result['items'], [
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function ignore(string $id): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $ignored = filter_var($body['ignored'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $submission = $this->contactService->setIgnored((int) $id, $ignored);
        Response::success($submission);
    }

    public function reply(string $id): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = (string) ($body['message'] ?? '');
        $submission = $this->contactService->reply((int) $id, $message);
        Response::success($submission);
    }
}
