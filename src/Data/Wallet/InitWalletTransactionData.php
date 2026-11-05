<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Wallet;

final readonly class InitWalletTransactionData
{
    public function __construct(
        public string $orderId,
        public float $amount,
        public ?string $languageCode = null,
        public ?string $userIdentifier = null,
        public ?string $transactionPin = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toApiData(string $defaultUserIdentifier, string $defaultTransactionPin, string $defaultLanguage): array
    {
        return [
            'userIdentifier' => $this->userIdentifier ?? $defaultUserIdentifier,
            'transactionPin' => $this->transactionPin ?? $defaultTransactionPin,
            'orderId' => $this->orderId,
            'amount' => number_format($this->amount, 2, '.', ''),
            'languageCode' => $this->languageCode ?? $defaultLanguage,
        ];
    }
}
