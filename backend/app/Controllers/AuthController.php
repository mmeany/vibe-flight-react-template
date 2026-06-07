<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Services\AuthService;
use App\Services\RegistrationService;

class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RegistrationService $registrationService,
    ) {}

    public function register(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $passwordReminder = $body['password_reminder'] ?? '';

        $result = $this->registrationService->startRegistration(
            $username,
            $email,
            $password,
            $passwordReminder,
            trim($body['website'] ?? $body['_website'] ?? ''),
            trim($body['challenge_token'] ?? ''),
            trim($body['challenge_answer'] ?? ''),
            (int) ($body['form_loaded_at'] ?? 0),
        );
        Response::created($result);
    }

    public function verifyRegistration(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $pendingToken = trim($body['pending_token'] ?? '');
        $code = trim($body['code'] ?? '');

        if ($pendingToken === '' || $code === '') {
            Response::unprocessableEntity('Pending token and verification code are required.');
            exit;
        }

        $result = $this->registrationService->verifyRegistration($pendingToken, $code);
        Response::success($result);
    }

    public function resendVerification(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $pendingToken = trim($body['pending_token'] ?? '');

        if ($pendingToken === '') {
            Response::unprocessableEntity('Pending token is required.');
            exit;
        }

        $result = $this->registrationService->resendVerification($pendingToken);
        Response::success($result);
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

    public function changePassword(): void
    {
        $userId = (int) ($_REQUEST['user_id'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $currentPassword = $body['current_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';
        $passwordReminder = $body['password_reminder'] ?? '';

        $this->authService->changePassword($userId, $currentPassword, $newPassword, $passwordReminder);
        Response::success(['message' => 'Password updated']);
    }
}
