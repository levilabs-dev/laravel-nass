<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Contracts;

interface NassGatewayAuthServiceContract
{
    public function login(?string $merchant = null): string;

    public function forgetToken(?string $merchant = null): void;
}
