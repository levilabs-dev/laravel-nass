<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Listeners;

use LeviLabs\LaravelNass\Enums\WalletPaymentStatus;
use LeviLabs\LaravelNass\Events\Wallet\WalletPaymentCreated;
use LeviLabs\LaravelNass\Events\Wallet\WalletPaymentStatusChecked;
use LeviLabs\LaravelNass\Models\NassWalletTransaction;

final class PersistNassWalletTransaction
{
    public function onCreated(WalletPaymentCreated $event): void
    {
        NassWalletTransaction::query()->updateOrCreate(
            ['transaction_id' => $event->transaction->transactionId],
            [
                'merchant' => $event->merchant,
                'order_id' => $event->transaction->orderId,
                'status' => WalletPaymentStatus::Pending->value,
                'checkout_url' => $event->transaction->checkoutUrl,
                'wallet_token' => $event->transaction->token,
            ],
        );
    }

    public function onStatusChecked(WalletPaymentStatusChecked $event): void
    {
        $status = $event->status;

        NassWalletTransaction::query()->updateOrCreate(
            ['transaction_id' => $status->transactionId],
            array_filter([
                'merchant' => $event->merchant,
                'order_id' => $status->orderId ?? $status->transactionId,
                'status' => $status->status->value,
                'amount' => $status->amount,
                'checked_at' => now(),
            ], fn ($value) => $value !== null),
        );
    }
}
