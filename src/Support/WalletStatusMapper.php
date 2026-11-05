<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Support;

use LeviLabs\LaravelNass\Enums\WalletPaymentStatus;

final class WalletStatusMapper
{
    public static function toPaymentStatus(?string $transactionStatus): WalletPaymentStatus
    {
        $key = strtoupper(trim((string) $transactionStatus));

        return match ($key) {
            'SUCCESS', 'SUCCEED', 'PAID', 'COMPLETED' => WalletPaymentStatus::Completed,
            'FAILED', 'FAIL', 'CANCELLED', 'CANCELED', 'REJECTED' => WalletPaymentStatus::Failed,
            default => WalletPaymentStatus::Pending,
        };
    }
}
