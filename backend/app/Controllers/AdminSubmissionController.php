<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\SubmissionListQuery;
use App\Http\Response;
use App\Services\ContactService;

class AdminSubmissionController
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    public function index(): void
    {
        $query = SubmissionListQuery::fromRequestParams($_GET);
        $result = $this->contactService->listSubmissions($query);
        Response::successWithMeta($result['items'], [
            'total' => $result['total'],
            'page' => $query->page,
            'per_page' => $query->perPage,
            'sort' => $result['sort'],
            'order' => $result['order'],
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
