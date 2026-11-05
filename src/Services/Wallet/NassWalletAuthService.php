<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Services\Wallet;

use LeviLabs\LaravelNass\Contracts\NassWalletAuthServiceContract;
use LeviLabs\LaravelNass\Services\Concerns\TalksToNassWallet;

final class NassWalletAuthService implements NassWalletAuthServiceContract
{
    use TalksToNassWallet;

    public function login(?string $merchant = null): string
    {
        $merchant ??= $this->defaultWalletMerchant();

        return $this->rememberWalletToken($merchant, fn (): string => $this->walletLoginForToken($merchant));
    }

    public function forgetToken(?string $merchant = null): void
    {
        $this->forgetWalletToken($merchant);
    }
}
