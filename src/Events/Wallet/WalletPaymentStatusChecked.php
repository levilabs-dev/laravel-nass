<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Events\Wallet;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionStatusData;

final class WalletPaymentStatusChecked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly WalletTransactionStatusData $status,
        public readonly string $merchant,
    ) {}
}
