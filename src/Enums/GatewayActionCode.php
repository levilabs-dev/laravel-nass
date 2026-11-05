<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Enums;

enum GatewayActionCode: string
{
    case Success = '0';

    public function isSuccess(): bool
    {
        return match ($this) {
            self::Success => true,
        };
    }

    public static function tryFromString(?string $code): ?self
    {
        return $code === null ? null : self::tryFrom($code);
    }
}
