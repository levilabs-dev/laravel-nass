<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Support;

final class WalletCheckoutUrl
{
    /**
     * @param  'transactionId'|'id'  $transactionQueryKey
     */
    public static function build(
        string $portalUrl,
        string $transactionId,
        string $token,
        string $userIdentifier,
        string $transactionQueryKey = 'transactionId',
    ): string {
        $base = rtrim($portalUrl, '/');

        return $base.'/payment-gateway?'.http_build_query([
            $transactionQueryKey => $transactionId,
            'token' => $token,
            'userIdentifier' => $userIdentifier,
        ]);
    }
}
