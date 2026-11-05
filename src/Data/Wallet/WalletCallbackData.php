<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Wallet;

use Illuminate\Http\Request;

/**
 * Nass Wallet posts callbacks with nested `data.InitTransactionId` (see community integrations).
 * Treat the body as untrusted — always confirm via checkTransaction.
 */
final readonly class WalletCallbackData
{
    public function __construct(
        public string $initTransactionId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $id = $request->input('data.InitTransactionId')
            ?? $request->input('InitTransactionId')
            ?? $request->input('transactionId');

        $initTransactionId = is_string($id) || is_numeric($id) ? (string) $id : '';

        return new self(initTransactionId: $initTransactionId);
    }

    public function isValid(): bool
    {
        return $this->initTransactionId !== '';
    }
}
