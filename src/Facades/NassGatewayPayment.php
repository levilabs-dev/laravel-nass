<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelNass\Contracts\NassGatewayPaymentServiceContract;
use LeviLabs\LaravelNass\Data\Gateway\CreateTransactionData;
use LeviLabs\LaravelNass\Data\Gateway\GatewayCallbackData;
use LeviLabs\LaravelNass\Data\Gateway\TransactionCreatedData;
use LeviLabs\LaravelNass\Data\Gateway\TransactionStatusData;

/**
 * @method static TransactionCreatedData create(CreateTransactionData $transaction, ?string $merchant = null)
 * @method static TransactionStatusData status(string $orderId, ?string $merchant = null)
 * @method static GatewayCallbackData parseCallback(Request $request)
 *
 * @see NassGatewayPaymentServiceContract
 */
class NassGatewayPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NassGatewayPaymentServiceContract::class;
    }
}
