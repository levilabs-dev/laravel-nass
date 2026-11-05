<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelNass\Contracts\NassWalletPaymentServiceContract;
use LeviLabs\LaravelNass\Data\Wallet\InitWalletTransactionData;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionCreatedData;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionStatusData;

/**
 * @method static WalletTransactionCreatedData init(InitWalletTransactionData $transaction, ?string $merchant = null)
 * @method static WalletTransactionStatusData status(string $transactionId, ?string $merchant = null)
 * @method static WalletTransactionStatusData parseCallback(Request $request, ?string $merchant = null)
 *
 * @see NassWalletPaymentServiceContract
 */
class NassWalletPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NassWalletPaymentServiceContract::class;
    }
}
