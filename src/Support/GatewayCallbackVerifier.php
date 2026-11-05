<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Support;

use LeviLabs\LaravelNass\Enums\GatewayActionCode;
use LeviLabs\LaravelNass\Enums\GatewayResponseCode;

final class GatewayCallbackVerifier
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function isApprovedPayload(array $payload): bool
    {
        $responseCode = isset($payload['responseCode']) ? (string) $payload['responseCode'] : null;
        $actionCode = isset($payload['actionCode']) ? (string) $payload['actionCode'] : null;

        return GatewayResponseCode::tryFromString($responseCode)?->isApproved() === true
            && GatewayActionCode::tryFromString($actionCode)?->isSuccess() === true;
    }
}
