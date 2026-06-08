<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\UserListQuery;
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
        $passwordReminder = $body['password_reminder'] ?? '';
        $userAlias = isset($body['user_alias']) ? trim((string) $body['user_alias']) : null;

        $user = $this->userAdminService->createUser($username, $email, $password, $passwordReminder, $userAlias);
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
        $query = UserListQuery::fromRequestParams($_GET);
        $result = $this->userAdminService->listUsers($query);
        Response::successWithMeta(
            array_map(static fn ($user) => $user->toArray(), $result['items']),
            [
                'total' => $result['total'],
                'page' => $query->page,
                'per_page' => $query->perPage,
                'sort' => $result['sort'],
                'order' => $result['order'],
            ],
        );
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
        $passwordReminder = array_key_exists('password_reminder', $body)
            ? (string) $body['password_reminder']
            : null;

        $user = $this->userAdminService->updateUser(
            (int) $id,
            $username,
            $email,
            $password,
            $userAlias,
            $passwordReminder,
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
