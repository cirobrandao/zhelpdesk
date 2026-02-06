<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function getUser(int $id): ?array
    {
        return $this->users->findById($id);
    }
}
