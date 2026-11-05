<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use LeviLabs\LaravelNass\Data\Gateway\GatewayCallbackData;
use LeviLabs\LaravelNass\Events\Gateway\GatewayNotifyReceived;
use LeviLabs\LaravelNass\Events\Gateway\GatewayPaymentCompleted;
use LeviLabs\LaravelNass\Facades\NassGatewayPayment;

it('parses approved gateway callbacks', function () {
    Event::fake([GatewayNotifyReceived::class, GatewayPaymentCompleted::class]);

    $request = Request::create('/notify', 'POST', [
        'responseCode' => '00',
        'actionCode' => '0',
        'orderId' => 'ORD-99',
        'rrn' => 'RRN-99',
    ]);

    $callback = NassGatewayPayment::parseCallback($request);

    expect($callback)->toBeInstanceOf(GatewayCallbackData::class)
        ->and($callback->isApproved())->toBeTrue();

    Event::assertDispatched(GatewayNotifyReceived::class);
    Event::assertDispatched(GatewayPaymentCompleted::class);
});
