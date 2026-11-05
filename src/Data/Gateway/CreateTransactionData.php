<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Gateway;

use LeviLabs\LaravelNass\Enums\Currency;
use LeviLabs\LaravelNass\Enums\TransactionType;

final readonly class CreateTransactionData
{
    public function __construct(
        public string $orderId,
        public string $orderDesc,
        public float $amount,
        public string $currency = Currency::IraqiDinar->value,
        public string $transactionType = TransactionType::Sale->value,
        public ?string $backRef = null,
        public ?string $notifyUrl = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(string $defaultBackRef, ?string $defaultNotifyUrl): array
    {
        return array_filter([
            'orderId' => $this->orderId,
            'orderDesc' => $this->orderDesc,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transactionType' => $this->transactionType,
            'backRef' => $this->backRef ?? $defaultBackRef,
            'notifyUrl' => $this->notifyUrl ?? $defaultNotifyUrl,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
