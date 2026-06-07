<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class SubmissionListQuery
{
    private const VALID_SORTS = ['created_at', 'email', 'status'];
    private const VALID_ORDERS = ['asc', 'desc'];
    private const VALID_STATUSES = ['all', 'new', 'replied'];

    public function __construct(
        public bool $includeIgnored,
        public int $page,
        public int $perPage,
        public string $search,
        public string $sort,
        public string $order,
        public string $status,
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
        $includeIgnored = filter_var($params['include_ignored'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));
        $search = trim((string) ($params['search'] ?? ''));
        if (strlen($search) < 2) {
            $search = '';
        }

        $sort = (string) ($params['sort'] ?? 'created_at');
        if (!in_array($sort, self::VALID_SORTS, true)) {
            $sort = 'created_at';
        }

        $order = strtolower((string) ($params['order'] ?? 'desc'));
        if (!in_array($order, self::VALID_ORDERS, true)) {
            $order = 'desc';
        }

        $status = strtolower((string) ($params['status'] ?? 'all'));
        if (!in_array($status, self::VALID_STATUSES, true)) {
            $status = 'all';
        }

        return new self(
            includeIgnored: $includeIgnored,
            page: $page,
            perPage: $perPage,
            search: $search,
            sort: $sort,
            order: $order,
            status: $status,
        );
    }
}
