<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Enums;

enum GatewayResponseCode: string
{
    case Approved = '00';

    public function isApproved(): bool
    {
        return match ($this) {
            self::Approved => true,
        };
    }

    public static function tryFromString(?string $code): ?self
    {
        return $code === null ? null : self::tryFrom($code);
    }
}
