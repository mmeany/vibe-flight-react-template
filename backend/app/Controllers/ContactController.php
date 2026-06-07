<?php

declare(strict_types=1);

namespace App\Controllers;

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
}
