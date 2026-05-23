<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Services\UserAdminService;

class AdminUserController
{
    public function __construct(
        private readonly UserAdminService $userAdminService,
    ) {}

    public function create(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $userAlias = isset($body['user_alias']) ? trim((string) $body['user_alias']) : null;

        $user = $this->userAdminService->createUser($username, $email, $password, $userAlias);
        Response::created($user->toArray());
    }

    public function import(): void
    {
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            Response::validationError('CSV file is required (field name: file)');
            return;
        }

        $content = file_get_contents($_FILES['file']['tmp_name']);
        if ($content === false) {
            Response::serverError('Failed to read uploaded file');
            return;
        }

        $result = $this->userAdminService->importCsv($content);
        Response::success($result);
    }

    public function index(): void
    {
        $includeInactive = filter_var($_GET['include_inactive'] ?? '0', FILTER_VALIDATE_BOOLEAN);
        $users = $this->userAdminService->listUsers($includeInactive);
        Response::success(array_map(static fn ($user) => $user->toArray(), $users));
    }

    public function show(string $id): void
    {
        $user = $this->userAdminService->getUser((int) $id);
        Response::success($user->toArray());
    }

    public function update(string $id): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $actingUserId = (int) ($_REQUEST['user_id'] ?? 0);

        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = isset($body['password']) ? (string) $body['password'] : null;
        $userAlias = isset($body['user_alias']) ? trim((string) $body['user_alias']) : null;

        $user = $this->userAdminService->updateUser(
            (int) $id,
            $username,
            $email,
            $password,
            $userAlias,
            $actingUserId,
        );
        Response::success($user->toArray());
    }

    public function deactivate(string $id): void
    {
        $actingUserId = (int) ($_REQUEST['user_id'] ?? 0);
        $this->userAdminService->deactivate((int) $id, $actingUserId);
        Response::success(['message' => 'User deactivated']);
    }

    public function restore(string $id): void
    {
        $actingUserId = (int) ($_REQUEST['user_id'] ?? 0);
        $user = $this->userAdminService->restore((int) $id, $actingUserId);
        Response::success($user->toArray());
    }
}
