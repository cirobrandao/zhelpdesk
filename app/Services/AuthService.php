<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class AuthService
{
    private UserRepository $users;
    private AuditService $audit;

    public function __construct(UserRepository $users, AuditService $audit)
    {
        $this->users = $users;
        $this->audit = $audit;
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_locale'] = $user['locale'] ?? 'pt_BR';
        $_SESSION['roles'] = $this->users->rolesForUser((int) $user['id']);

        $this->users->updateLastLogin((int) $user['id']);
        $this->audit->log((int) $user['id'], 'login');

        return true;
    }

    public function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return $this->users->findById((int) $_SESSION['user_id']);
    }

    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        session_destroy();
        if ($userId) {
            $this->audit->log((int) $userId, 'logout');
        }
    }

    public function sendResetLink(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $this->users->setResetToken((int) $user['id'], $token, $expires);

        $logLine = sprintf("%s reset link: /password/reset/%s\n", date('c'), $token);
        file_put_contents(storage_path('logs/reset.log'), $logLine, FILE_APPEND);
        $this->audit->log((int) $user['id'], 'password_reset_requested');
    }

    public function resetPassword(string $token, string $password): bool
    {
        $user = $this->users->findByResetToken($token);
        if (!$user) {
            return false;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->users->updatePassword((int) $user['id'], $hash);
        $this->audit->log((int) $user['id'], 'password_reset');
        return true;
    }
}
