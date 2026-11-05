<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Contracts;

use Illuminate\Http\Request;
use LeviLabs\LaravelNass\Data\Gateway\CreateTransactionData;
use LeviLabs\LaravelNass\Data\Gateway\GatewayCallbackData;
use LeviLabs\LaravelNass\Data\Gateway\TransactionCreatedData;
use LeviLabs\LaravelNass\Data\Gateway\TransactionStatusData;

interface NassGatewayPaymentServiceContract
{
    public function create(CreateTransactionData $transaction, ?string $merchant = null): TransactionCreatedData;

    public function status(string $orderId, ?string $merchant = null): TransactionStatusData;

    public function parseCallback(Request $request): GatewayCallbackData;
}
