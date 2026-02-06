<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class SettingsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM settings');
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE `key` = :key');
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();
        return $row ? (string) $row['value'] : $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE value = :value');
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
}
