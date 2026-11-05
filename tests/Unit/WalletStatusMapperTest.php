<?php

declare(strict_types=1);

use LeviLabs\LaravelNass\Enums\WalletPaymentStatus;
use LeviLabs\LaravelNass\Support\WalletStatusMapper;

it('maps nass wallet transaction statuses', function () {
    expect(WalletStatusMapper::toPaymentStatus('SUCCESS'))->toBe(WalletPaymentStatus::Completed)
        ->and(WalletStatusMapper::toPaymentStatus('FAILED'))->toBe(WalletPaymentStatus::Failed)
        ->and(WalletStatusMapper::toPaymentStatus(''))->toBe(WalletPaymentStatus::Pending);
});
