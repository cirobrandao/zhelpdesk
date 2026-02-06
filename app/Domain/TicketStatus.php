<?php

declare(strict_types=1);

namespace App\Domain;

final class TicketStatus
{
    public const OPEN = 'open';
    public const IN_PROGRESS = 'in_progress';
    public const ON_HOLD = 'on_hold';
    public const RESOLVED = 'resolved';
    public const CLOSED = 'closed';

    public static function all(): array
    {
        return [self::OPEN, self::IN_PROGRESS, self::ON_HOLD, self::RESOLVED, self::CLOSED];
    }
}
