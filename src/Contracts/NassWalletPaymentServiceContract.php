<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Contracts;

use LeviLabs\LaravelNass\Data\Wallet\InitWalletTransactionData;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionCreatedData;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionStatusData;

interface NassWalletPaymentServiceContract
{
    public function init(InitWalletTransactionData $transaction, ?string $merchant = null): WalletTransactionCreatedData;

    public function status(string $transactionId, ?string $merchant = null): WalletTransactionStatusData;
}
