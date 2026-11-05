<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Exceptions;

use Exception;

final class NassWalletException extends Exception
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
        return new self("Unknown Nass Wallet merchant [{$merchant}].");
    }

    public static function missingCredentials(string $merchant): self
    {
        return new self("Nass Wallet merchant [{$merchant}] is missing required credentials.");
    }

    public static function requestFailed(string $action, ?int $statusCode = null, ?string $detail = null): self
    {
        $message = "Nass Wallet {$action} failed";
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
        return new self('Nass Wallet login did not return an access token.');
    }

    public static function apiRejected(string $action, ?string $message = null): self
    {
        return new self(trim("Nass Wallet {$action} rejected".($message ? ": {$message}" : '')));
    }
}
