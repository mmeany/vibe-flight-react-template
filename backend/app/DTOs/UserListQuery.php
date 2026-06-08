<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class UserListQuery
{
    private const VALID_SORTS = ['username', 'email', 'user_alias'];
    private const VALID_ORDERS = ['asc', 'desc'];

    public function __construct(
        public bool $includeInactive,
        public int $page,
        public int $perPage,
        public string $search,
        public string $sort,
        public string $order,
    ) {}

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromRequestParams(array $params): self
    {
        $includeInactive = filter_var($params['include_inactive'] ?? '0', FILTER_VALIDATE_BOOLEAN);
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));
        $search = trim((string) ($params['search'] ?? ''));
        if (strlen($search) < 2) {
            $search = '';
        }

        $sort = (string) ($params['sort'] ?? 'username');
        if (!in_array($sort, self::VALID_SORTS, true)) {
            $sort = 'username';
        }

        $order = strtolower((string) ($params['order'] ?? 'asc'));
        if (!in_array($order, self::VALID_ORDERS, true)) {
            $order = 'asc';
        }

        return new self(
            includeInactive: $includeInactive,
            page: $page,
            perPage: $perPage,
            search: $search,
            sort: $sort,
            order: $order,
        );
    }
}
