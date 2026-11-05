<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Tests;

use LeviLabs\LaravelNass\NassServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NassServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('nass.gateway.merchants.default', [
            'environment' => 'uat',
            'username' => 'merchant_user',
            'password' => 'merchant_pass',
            'base_url' => 'https://uat-gateway.nass.iq:9746',
            'back_ref' => 'https://example.test/nass/return',
            'notify_url' => 'https://example.test/nass/notify',
        ]);

        $app['config']->set('nass.wallet.merchants.default', [
            'environment' => 'uat',
            'basic_token' => 'basic-token',
            'username' => 'wallet_user',
            'password' => 'wallet_pass',
            'transaction_pin' => '1234',
            'base_url' => 'https://uatgw1.nasswallet.com/payment/transaction',
            'portal_url' => 'https://uatcheckout1.nasswallet.com',
            'language_code' => 'en',
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
    }
}
