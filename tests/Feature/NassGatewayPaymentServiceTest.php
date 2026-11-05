<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelNass\Data\Gateway\CreateTransactionData;
use LeviLabs\LaravelNass\Facades\NassGatewayPayment;
use LeviLabs\LaravelNass\Models\NassGatewayPayment as NassGatewayPaymentModel;

it('creates a portal transaction and persists it', function () {
    Http::fake([
        'uat-gateway.nass.iq:9746/auth/merchant/login' => Http::response([
            'success' => true,
            'data' => ['access_token' => 'gateway-token'],
        ]),
        'uat-gateway.nass.iq:9746/transaction' => Http::response([
            'success' => true,
            'data' => [
                'url' => 'https://3dsecure.nass.iq/gateway/pay',
                'pSign' => 'abc123',
                'transactionParams' => ['ORDER' => 'ORD-1'],
            ],
        ]),
    ]);

    $created = NassGatewayPayment::create(new CreateTransactionData(
        orderId: 'ORD-1',
        orderDesc: 'Test order',
        amount: 150.0,
    ));

    expect($created->paymentUrl)->toBe('https://3dsecure.nass.iq/gateway/pay')
        ->and($created->pSign)->toBe('abc123');

    expect(NassGatewayPaymentModel::query()->where('order_id', 'ORD-1')->exists())->toBeTrue();
});

it('checks gateway transaction status', function () {
    Http::fake([
        'uat-gateway.nass.iq:9746/auth/merchant/login' => Http::response([
            'data' => ['access_token' => 'gateway-token'],
        ]),
        'uat-gateway.nass.iq:9746/transaction/ORD-2/checkStatus' => Http::response([
            'success' => true,
            'data' => [
                'orderId' => 'ORD-2',
                'responseCode' => '00',
                'actionCode' => '0',
                'statusMsg' => 'Approved',
                'rrn' => 'RRN123',
            ],
        ]),
    ]);

    $status = NassGatewayPayment::status('ORD-2');

    expect($status->isApproved())->toBeTrue()
        ->and($status->rrn)->toBe('RRN123');
});

it('caches gateway access tokens between requests', function () {
    Http::fake([
        'uat-gateway.nass.iq:9746/auth/merchant/login' => Http::response([
            'access_token' => 'cached-token',
        ]),
        'uat-gateway.nass.iq:9746/transaction/ORD-3/checkStatus' => Http::response([
            'data' => ['orderId' => 'ORD-3', 'responseCode' => '05', 'actionCode' => '1'],
        ]),
    ]);

    NassGatewayPayment::status('ORD-3');
    NassGatewayPayment::status('ORD-3');

    Http::assertSentCount(3);
});
