<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Events\Wallet;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionCreatedData;

final class WalletPaymentCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly WalletTransactionCreatedData $transaction,
        public readonly string $merchant,
    ) {}
}
