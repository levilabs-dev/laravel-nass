<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Events\Gateway;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LeviLabs\LaravelNass\Data\Gateway\TransactionStatusData;

final class GatewayPaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly TransactionStatusData $status,
        public readonly string $merchant,
    ) {}
}
