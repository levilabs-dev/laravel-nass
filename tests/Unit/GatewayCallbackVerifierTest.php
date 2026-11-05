<?php

declare(strict_types=1);

use LeviLabs\LaravelNass\Support\GatewayCallbackVerifier;

it('detects approved gateway callback payloads', function () {
    expect(GatewayCallbackVerifier::isApprovedPayload([
        'responseCode' => '00',
        'actionCode' => '0',
    ]))->toBeTrue();

    expect(GatewayCallbackVerifier::isApprovedPayload([
        'responseCode' => '05',
        'actionCode' => '0',
    ]))->toBeFalse();
});
