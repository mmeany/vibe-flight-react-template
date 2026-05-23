<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Services\AuthService;

class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $passwordReminder = $body['password_reminder'] ?? '';

        $user = $this->authService->register($username, $email, $password, $passwordReminder);
        Response::created($user->toArray());
    }

    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        $result = $this->authService->login($username, $password);
        Response::success($result);
    }

    public function me(): void
    {
        $userId = (int) ($_REQUEST['user_id'] ?? 0);
        $data = $this->authService->getMe($userId);
        Response::success($data);
    }

    public function updateSettings(): void
    {
        $userId = (int) ($_REQUEST['user_id'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $settings = $this->authService->updateSettings($userId, $body);
        Response::success(['settings' => $settings]);
    }
}
