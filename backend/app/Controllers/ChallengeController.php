<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Services\ChallengeService;

class ChallengeController
{
    public function __construct(
        private readonly ChallengeService $challengeService,
    ) {}

    public function show(): void
    {
        Response::success($this->challengeService->createChallenge());
    }
}
