<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Services\Gateway;

use Illuminate\Http\Request;
use LeviLabs\LaravelNass\Contracts\NassGatewayPaymentServiceContract;
use LeviLabs\LaravelNass\Data\Gateway\CreateTransactionData;
use LeviLabs\LaravelNass\Data\Gateway\GatewayCallbackData;
use LeviLabs\LaravelNass\Data\Gateway\TransactionCreatedData;
use LeviLabs\LaravelNass\Data\Gateway\TransactionStatusData;
use LeviLabs\LaravelNass\Events\Gateway\GatewayNotifyReceived;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentCompleted;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentCreated;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentFailed;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentStatusChecked;
use LeviLabs\LaravelNass\Exceptions\NassGatewayException;
use LeviLabs\LaravelNass\Services\Concerns\TalksToNassGateway;

final class NassGatewayPaymentService implements NassGatewayPaymentServiceContract
{
    use TalksToNassGateway;

    public function create(CreateTransactionData $transaction, ?string $merchant = null): TransactionCreatedData
    {
        if ($transaction->amount <= 0) {
            throw NassGatewayException::invalidAmount($transaction->amount);
        }

        $merchant ??= $this->defaultGatewayMerchant();
        $config = $this->gatewayMerchantConfig($merchant);

        $json = $this->gatewaySend(
            $merchant,
            'transaction creation',
            'post',
            'transaction',
            $transaction->toApiArray(
                (string) ($config['back_ref'] ?? ''),
                $config['notify_url'],
            ),
        );

        $created = TransactionCreatedData::fromGatewayResponse(
            $transaction->orderId,
            $this->unwrapGatewayData($json),
        );

        GatewayPaymentCreated::dispatch($created, $merchant);

        return $created;
    }

    public function status(string $orderId, ?string $merchant = null): TransactionStatusData
    {
        $merchant ??= $this->defaultGatewayMerchant();

        $json = $this->gatewaySend(
            $merchant,
            'transaction status',
            'get',
            "transaction/{$orderId}/checkStatus",
        );

        $status = TransactionStatusData::fromGatewayResponse(
            $orderId,
            $this->unwrapGatewayData($json),
        );

        GatewayPaymentStatusChecked::dispatch($status, $merchant);

        if ($status->isApproved()) {
            GatewayPaymentCompleted::dispatch($status, $merchant);
        } elseif ($status->status->isTerminal()) {
            GatewayPaymentFailed::dispatch($status, $merchant);
        }

        return $status;
    }

    public function parseCallback(Request $request): GatewayCallbackData
    {
        $callback = GatewayCallbackData::fromRequest($request);

        GatewayNotifyReceived::dispatch($callback);

        if ($callback->isApproved()) {
            $status = TransactionStatusData::fromGatewayResponse(
                (string) ($callback->orderId ?? ''),
                [
                    'orderId' => $callback->orderId,
                    'responseCode' => $callback->responseCode,
                    'actionCode' => $callback->actionCode,
                    'rrn' => $callback->rrn,
                    'intRef' => $callback->intRef,
                    'amount' => $callback->amount,
                    'currency' => $callback->currency,
                    'card' => $callback->card,
                    'terminal' => $callback->terminal,
                    'tranDate' => $callback->tranDate,
                    'transactionOrigin' => $callback->transactionOrigin,
                ],
            );
            GatewayPaymentCompleted::dispatch($status, $this->defaultGatewayMerchant());
        } elseif ($callback->responseCode !== null || $callback->actionCode !== null) {
            $status = TransactionStatusData::fromGatewayResponse(
                (string) ($callback->orderId ?? ''),
                [
                    'orderId' => $callback->orderId,
                    'responseCode' => $callback->responseCode,
                    'actionCode' => $callback->actionCode,
                ],
            );
            GatewayPaymentFailed::dispatch($status, $this->defaultGatewayMerchant());
        }

        return $callback;
    }
}
