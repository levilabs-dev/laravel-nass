<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Gateway;

final readonly class TransactionCreatedData
{
    /**
     * @param  array<string, mixed>  $transactionParams
     */
    public function __construct(
        public string $orderId,
        public string $paymentUrl,
        public ?string $pSign = null,
        public array $transactionParams = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromGatewayResponse(string $orderId, array $payload): self
    {
        return new self(
            orderId: $orderId,
            paymentUrl: (string) ($payload['url'] ?? ''),
            pSign: isset($payload['pSign']) ? (string) $payload['pSign'] : null,
            transactionParams: is_array($payload['transactionParams'] ?? null) ? $payload['transactionParams'] : [],
        );
    }
}
