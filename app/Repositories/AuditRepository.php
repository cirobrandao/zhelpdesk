<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AuditRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function log(int $userId, string $action, string $context): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_logs (user_id, action, context, created_at) VALUES (:user_id, :action, :context, NOW())');
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'context' => $context,
        ]);
    }
}
