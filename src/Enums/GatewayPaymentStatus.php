<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Enums;

enum GatewayPaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return $this !== self::Pending;
    }
}
