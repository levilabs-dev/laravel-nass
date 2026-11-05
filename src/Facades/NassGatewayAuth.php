<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Facades;

use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelNass\Contracts\NassGatewayAuthServiceContract;

/**
 * @method static string login(?string $merchant = null)
 * @method static void forgetToken(?string $merchant = null)
 *
 * @see NassGatewayAuthServiceContract
 */
class NassGatewayAuth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NassGatewayAuthServiceContract::class;
    }
}
