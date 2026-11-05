<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Exceptions;

use Exception;

final class NassGatewayException extends Exception
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        string $message,
        int $code = 0,
        public readonly array $response = [],
    ) {
        parent::__construct($message, $code);
    }

    public static function unknownMerchant(string $merchant): self
    {
        return new self("Unknown NASS gateway merchant [{$merchant}].");
    }

    public static function missingCredentials(string $merchant): self
    {
        return new self("NASS gateway merchant [{$merchant}] is missing username or password.");
    }

    public static function requestFailed(string $action, ?int $statusCode = null, ?string $detail = null): self
    {
        $message = "NASS gateway {$action} failed";
        if ($statusCode !== null) {
            $message .= " (HTTP {$statusCode})";
        }
        if ($detail !== null && $detail !== '') {
            $message .= ": {$detail}";
        }

        return new self($message, $statusCode ?? 0);
    }

    public static function missingAccessToken(): self
    {
        return new self('NASS gateway login did not return an access token.');
    }

    public static function invalidAmount(float $amount): self
    {
        return new self("NASS gateway amount must be greater than zero, got [{$amount}].");
    }
}
