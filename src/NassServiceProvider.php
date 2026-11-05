<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass;

use Illuminate\Support\Facades\Event;
use LeviLabs\LaravelNass\Console\Commands\SyncNassGatewayStatuses;
use LeviLabs\LaravelNass\Console\Commands\SyncNassWalletStatuses;
use LeviLabs\LaravelNass\Contracts\NassGatewayAuthServiceContract;
use LeviLabs\LaravelNass\Contracts\NassGatewayPaymentServiceContract;
use LeviLabs\LaravelNass\Contracts\NassWalletAuthServiceContract;
use LeviLabs\LaravelNass\Contracts\NassWalletPaymentServiceContract;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentCompleted;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentCreated;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentFailed;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentStatusChecked;
use LeviLabs\LaravelNass\Events\Wallet\WalletPaymentCreated;
use LeviLabs\LaravelNass\Events\Wallet\WalletPaymentStatusChecked;
use LeviLabs\LaravelNass\Listeners\PersistNassGatewayPayment;
use LeviLabs\LaravelNass\Listeners\PersistNassWalletTransaction;
use LeviLabs\LaravelNass\Services\Gateway\NassGatewayAuthService;
use LeviLabs\LaravelNass\Services\Gateway\NassGatewayPaymentService;
use LeviLabs\LaravelNass\Services\Wallet\NassWalletAuthService;
use LeviLabs\LaravelNass\Services\Wallet\NassWalletPaymentService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NassServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('nass')
            ->hasConfigFile('nass')
            ->hasMigrations(
                'create_nass_gateway_payments_table',
                'create_nass_wallet_transactions_table',
            )
            ->runsMigrations()
            ->hasCommand(SyncNassGatewayStatuses::class)
            ->hasCommand(SyncNassWalletStatuses::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(NassGatewayAuthServiceContract::class, NassGatewayAuthService::class);
        $this->app->singleton(NassGatewayPaymentServiceContract::class, NassGatewayPaymentService::class);
        $this->app->singleton(NassWalletAuthServiceContract::class, NassWalletAuthService::class);
        $this->app->singleton(NassWalletPaymentServiceContract::class, NassWalletPaymentService::class);
    }

    public function packageBooted(): void
    {
        $gateway = PersistNassGatewayPayment::class;
        Event::listen(GatewayPaymentCreated::class, [$gateway, 'onCreated']);
        Event::listen(GatewayPaymentStatusChecked::class, [$gateway, 'onStatusChecked']);
        Event::listen(GatewayPaymentCompleted::class, [$gateway, 'onCompleted']);
        Event::listen(GatewayPaymentFailed::class, [$gateway, 'onFailed']);

        $wallet = PersistNassWalletTransaction::class;
        Event::listen(WalletPaymentCreated::class, [$wallet, 'onCreated']);
        Event::listen(WalletPaymentStatusChecked::class, [$wallet, 'onStatusChecked']);
    }
}
