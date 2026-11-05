<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Support;

final class WalletCheckoutUrl
{
    public static function build(string $portalUrl, string $transactionId, string $token, string $userIdentifier): string
    {
        $base = rtrim($portalUrl, '/');

        return $base.'/payment-gateway?'.http_build_query([
            'transactionId' => $transactionId,
            'token' => $token,
            'userIdentifier' => $userIdentifier,
        ]);
    }
}
