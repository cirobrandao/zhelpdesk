<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }
        $user['roles'] = $this->rolesForUser($id);
        return $user;
    }

    public function findByApiToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE api_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function rolesForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :id');
        $stmt->execute(['id' => $userId]);
        return array_map(fn ($row) => $row['name'], $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password_hash, locale, created_at) VALUES (:name, :email, :password_hash, :locale, NOW())');
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'locale' => $data['locale'] ?? 'pt_BR',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function setResetToken(int $id, string $token, string $expiresAt): void
    {
        $stmt = $this->db->prepare('UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'token' => $token,
            'expires' => $expiresAt,
        ]);
    }

    public function findByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE reset_token = :token AND reset_expires_at > NOW() LIMIT 1');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updatePassword(int $id, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash, reset_token = NULL, reset_expires_at = NULL WHERE id = :id');
        $stmt->execute(['id' => $id, 'hash' => $hash]);
    }
}
