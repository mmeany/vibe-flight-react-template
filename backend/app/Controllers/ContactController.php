<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\AuthenticatedSubmissionCreateDto;
use App\DTOs\SubmissionCreateDto;
use App\Http\Response;
use App\Services\ContactService;

class ContactController
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    public function submit(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = SubmissionCreateDto::fromRequestBody($body);
        $result = $this->contactService->submit($dto);
        Response::created($result);
    }

    public function submitAuthenticated(): void
    {
        $userId = (int) ($_REQUEST['user_id'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = AuthenticatedSubmissionCreateDto::fromRequestBody($body);
        $result = $this->contactService->submitAuthenticated($userId, $dto);
        Response::created($result);
    }
}
