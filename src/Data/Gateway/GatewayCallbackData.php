<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Data\Gateway;

use Illuminate\Http\Request;
use LeviLabs\LaravelNass\Support\GatewayCallbackVerifier;

final readonly class GatewayCallbackData
{
    public function __construct(
        public ?string $terminal = null,
        public ?string $actionCode = null,
        public ?string $responseCode = null,
        public ?string $card = null,
        public ?string $amount = null,
        public ?string $currency = null,
        public ?string $rrn = null,
        public ?string $intRef = null,
        public ?string $orderId = null,
        public ?string $tranDate = null,
        public ?string $transactionOrigin = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->all();

        return new self(
            terminal: self::stringOrNull($data, 'terminal'),
            actionCode: self::stringOrNull($data, 'actionCode'),
            responseCode: self::stringOrNull($data, 'responseCode'),
            card: self::stringOrNull($data, 'card'),
            amount: self::stringOrNull($data, 'amount'),
            currency: self::stringOrNull($data, 'currency'),
            rrn: self::stringOrNull($data, 'rrn'),
            intRef: self::stringOrNull($data, 'intRef'),
            orderId: self::stringOrNull($data, 'orderId'),
            tranDate: self::stringOrNull($data, 'tranDate'),
            transactionOrigin: self::stringOrNull($data, 'transactionOrigin'),
        );
    }

    public function isApproved(): bool
    {
        return GatewayCallbackVerifier::isApprovedPayload([
            'responseCode' => $this->responseCode,
            'actionCode' => $this->actionCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function stringOrNull(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        return (string) $data[$key];
    }
}
