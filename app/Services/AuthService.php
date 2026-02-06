<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class AuthService
{
    private UserRepository $users;
    private AuditService $audit;
    private array $config;

    public function __construct(UserRepository $users, AuditService $audit, array $config)
    {
        $this->users = $users;
        $this->audit = $audit;
        $this->config = $config;
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

        if (!empty($this->config['email_confirmation_enabled']) && empty($user['email_verified_at'])) {
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

    public function register(string $name, string $email, string $password): ?int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $token = null;
        $verifiedAt = null;

        if (!empty($this->config['email_confirmation_enabled'])) {
            $token = bin2hex(random_bytes(32));
        } else {
            $verifiedAt = date('Y-m-d H:i:s');
        }

        $userId = $this->users->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => $hash,
            'email_verify_token' => $token,
            'email_verified_at' => $verifiedAt,
        ]);

        $this->users->assignRole($userId, 'user');

        if ($token) {
            $logLine = sprintf("%s verify link: /verify-email/%s\n", date('c'), $token);
            file_put_contents(storage_path('logs/verify.log'), $logLine, FILE_APPEND);
        }

        $this->audit->log($userId, 'user_registered');
        return $userId;
    }

    public function verifyEmail(string $token): bool
    {
        $user = $this->users->findByVerifyToken($token);
        if (!$user) {
            return false;
        }
        $this->users->markEmailVerified((int) $user['id']);
        $this->audit->log((int) $user['id'], 'email_verified');
        return true;
    }
}
