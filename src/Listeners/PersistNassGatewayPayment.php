<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Listeners;

use LeviLabs\LaravelNass\Enums\GatewayPaymentStatus;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentCompleted;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentCreated;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentFailed;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentStatusChecked;
use LeviLabs\LaravelNass\Models\NassGatewayPayment;

final class PersistNassGatewayPayment
{
    public function onCreated(GatewayPaymentCreated $event): void
    {
        NassGatewayPayment::query()->updateOrCreate(
            ['order_id' => $event->transaction->orderId],
            [
                'merchant' => $event->merchant,
                'status' => GatewayPaymentStatus::Pending->value,
                'payment_url' => $event->transaction->paymentUrl,
                'p_sign' => $event->transaction->pSign,
                'transaction_params' => $event->transaction->transactionParams,
            ],
        );
    }

    public function onStatusChecked(GatewayPaymentStatusChecked $event): void
    {
        $status = $event->status;

        NassGatewayPayment::query()->updateOrCreate(
            ['order_id' => $status->orderId],
            array_filter([
                'merchant' => $event->merchant,
                'status' => $status->status->value,
                'response_code' => $status->responseCode,
                'action_code' => $status->actionCode,
                'status_message' => $status->statusMsg,
                'rrn' => $status->rrn,
                'int_ref' => $status->intRef,
                'amount' => $status->amount,
                'currency' => $status->currency,
                'card' => $status->card,
                'terminal' => $status->terminal,
                'checked_at' => now(),
            ], fn ($value) => $value !== null),
        );
    }

    public function onCompleted(GatewayPaymentCompleted $event): void
    {
        $this->onStatusChecked(new GatewayPaymentStatusChecked($event->status, $event->merchant));
    }

    public function onFailed(GatewayPaymentFailed $event): void
    {
        $this->onStatusChecked(new GatewayPaymentStatusChecked($event->status, $event->merchant));
    }
}
