<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditRepository;

class AuditService
{
    private AuditRepository $repo;

    public function __construct(AuditRepository $repo)
    {
        $this->repo = $repo;
    }

    public function log(int $userId, string $action, array $context = []): void
    {
        $this->repo->log($userId, $action, json_encode($context));
    }
}
