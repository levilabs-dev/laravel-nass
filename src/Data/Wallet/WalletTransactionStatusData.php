<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Wallet;

use LeviLabs\LaravelNass\Enums\WalletPaymentStatus;

final readonly class WalletTransactionStatusData
{
    public function __construct(
        public string $transactionId,
        public WalletPaymentStatus $status,
        public ?string $orderId = null,
        public ?string $amount = null,
        public ?string $message = null,
        public ?int $responseCode = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromWalletResponse(string $transactionId, array $data, ?int $responseCode = null): self
    {
        $status = WalletPaymentStatus::Pending;

        if ($responseCode === 0) {
            $status = WalletPaymentStatus::Completed;
        } elseif ($responseCode !== null) {
            $status = WalletPaymentStatus::Failed;
        }

        return new self(
            transactionId: (string) ($data['transactionId'] ?? $transactionId),
            status: $status,
            orderId: isset($data['orderId']) ? (string) $data['orderId'] : null,
            amount: isset($data['amount']) ? (string) $data['amount'] : null,
            message: isset($data['message']) ? (string) $data['message'] : null,
            responseCode: $responseCode,
        );
    }
}
