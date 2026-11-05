<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Gateway;

use LeviLabs\LaravelNass\Enums\GatewayActionCode;
use LeviLabs\LaravelNass\Enums\GatewayPaymentStatus;
use LeviLabs\LaravelNass\Enums\GatewayResponseCode;

final readonly class TransactionStatusData
{
    public function __construct(
        public string $orderId,
        public GatewayPaymentStatus $status,
        public ?string $responseCode = null,
        public ?string $actionCode = null,
        public ?string $statusMsg = null,
        public ?string $rrn = null,
        public ?string $intRef = null,
        public ?string $amount = null,
        public ?string $currency = null,
        public ?string $card = null,
        public ?string $terminal = null,
        public ?string $tranDate = null,
        public ?string $transactionOrigin = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromGatewayResponse(string $orderId, array $payload): self
    {
        $responseCode = isset($payload['responseCode']) ? (string) $payload['responseCode'] : null;
        $actionCode = isset($payload['actionCode']) ? (string) $payload['actionCode'] : null;

        return new self(
            orderId: (string) ($payload['orderId'] ?? $orderId),
            status: self::resolveStatus($responseCode, $actionCode),
            responseCode: $responseCode,
            actionCode: $actionCode,
            statusMsg: isset($payload['statusMsg']) ? (string) $payload['statusMsg'] : null,
            rrn: isset($payload['rrn']) ? (string) $payload['rrn'] : null,
            intRef: isset($payload['intRef']) ? (string) $payload['intRef'] : null,
            amount: isset($payload['amount']) ? (string) $payload['amount'] : null,
            currency: isset($payload['currency']) ? (string) $payload['currency'] : null,
            card: isset($payload['card']) ? (string) $payload['card'] : null,
            terminal: isset($payload['terminal']) ? (string) $payload['terminal'] : null,
            tranDate: isset($payload['tranDate']) ? (string) $payload['tranDate'] : null,
            transactionOrigin: isset($payload['transactionOrigin']) ? (string) $payload['transactionOrigin'] : null,
        );
    }

    public function isApproved(): bool
    {
        return GatewayResponseCode::tryFromString($this->responseCode)?->isApproved() === true
            && GatewayActionCode::tryFromString($this->actionCode)?->isSuccess() === true;
    }

    private static function resolveStatus(?string $responseCode, ?string $actionCode): GatewayPaymentStatus
    {
        $approved = GatewayResponseCode::tryFromString($responseCode)?->isApproved() === true
            && GatewayActionCode::tryFromString($actionCode)?->isSuccess() === true;

        if ($approved) {
            return GatewayPaymentStatus::Completed;
        }

        if ($responseCode !== null || $actionCode !== null) {
            return GatewayPaymentStatus::Failed;
        }

        return GatewayPaymentStatus::Pending;
    }
}
