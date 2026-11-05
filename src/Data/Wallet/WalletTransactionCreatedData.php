<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Wallet;

final readonly class WalletTransactionCreatedData
{
    public function __construct(
        public string $orderId,
        public string $transactionId,
        public string $token,
        public string $checkoutUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromInitResponse(string $orderId, array $data, string $checkoutUrl): self
    {
        return new self(
            orderId: $orderId,
            transactionId: (string) ($data['transactionId'] ?? ''),
            token: (string) ($data['token'] ?? ''),
            checkoutUrl: $checkoutUrl,
        );
    }
}
