<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Services\Gateway;

use LeviLabs\LaravelNass\Contracts\NassGatewayAuthServiceContract;
use LeviLabs\LaravelNass\Services\Concerns\TalksToNassGateway;

final class NassGatewayAuthService implements NassGatewayAuthServiceContract
{
    use TalksToNassGateway;

    public function login(?string $merchant = null): string
    {
        $merchant ??= $this->defaultGatewayMerchant();

        return $this->rememberGatewayToken($merchant, fn (): string => $this->loginForToken($merchant));
    }

    public function forgetToken(?string $merchant = null): void
    {
        $this->forgetGatewayToken($merchant);
    }
}
