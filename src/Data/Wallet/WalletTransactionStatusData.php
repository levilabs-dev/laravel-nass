<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Wallet;

use LeviLabs\LaravelNass\Enums\WalletPaymentStatus;
use LeviLabs\LaravelNass\Support\WalletStatusMapper;

final readonly class WalletTransactionStatusData
{
    public function __construct(
        public string $transactionId,
        public WalletPaymentStatus $status,
        public ?string $orderId = null,
        public ?string $amount = null,
        public ?string $message = null,
        public ?int $responseCode = null,
        public ?string $transactionStatus = null,
    ) {}

    /**
     * @param  array<string, mixed>  $raw  Full JSON body from checkTransaction
     */
    public static function fromCheckTransactionResponse(string $transactionId, array $raw): self
    {
        $responseCode = $raw['responseCode'] ?? null;
        $code = is_int($responseCode) ? $responseCode : (is_numeric($responseCode) ? (int) $responseCode : null);

        $data = is_array($raw['data'] ?? null) ? $raw['data'] : [];

        $statusText = isset($data['transactionStatus']) ? (string) $data['transactionStatus'] : null;
        if ($statusText === null || $statusText === '') {
            $history = is_array($data['TransactionHistoryList'] ?? null) ? $data['TransactionHistoryList'] : [];
            $first = is_array($history[0] ?? null) ? $history[0] : [];
            $statusText = isset($first['TransactionStatus']) ? (string) $first['TransactionStatus'] : null;
            if (! isset($data['amount']) && isset($first['Amount'])) {
                $data['amount'] = $first['Amount'];
            }
        }

        $status = WalletStatusMapper::toPaymentStatus($statusText);
        if ($status === WalletPaymentStatus::Pending && $code !== null && $code !== 0) {
            $status = WalletPaymentStatus::Failed;
        }

        return new self(
            transactionId: (string) ($data['transactionId'] ?? $transactionId),
            status: $status,
            orderId: isset($data['orderId']) ? (string) $data['orderId'] : null,
            amount: isset($data['amount']) ? (string) $data['amount'] : null,
            message: is_string($raw['message'] ?? null) ? $raw['message'] : null,
            responseCode: $code,
            transactionStatus: $statusText,
        );
    }
}
