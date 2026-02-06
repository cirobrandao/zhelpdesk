<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UpdateRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function lastApplied(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM updates ORDER BY applied_at DESC LIMIT 1');
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function recordApplied(string $version, string $previousVersion, int $userId): void
    {
        $stmt = $this->db->prepare('INSERT INTO updates (version, previous_version, applied_by, applied_at) VALUES (:version, :previous_version, :applied_by, NOW())');
        $stmt->execute([
            'version' => $version,
            'previous_version' => $previousVersion,
            'applied_by' => $userId,
        ]);
    }
}
