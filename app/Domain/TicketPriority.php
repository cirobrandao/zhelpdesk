<?php

declare(strict_types=1);

namespace App\Domain;

final class TicketPriority
{
    public const LOW = 'low';
    public const MEDIUM = 'medium';
    public const HIGH = 'high';
    public const URGENT = 'urgent';

    public static function all(): array
    {
        return [self::LOW, self::MEDIUM, self::HIGH, self::URGENT];
    }
}
