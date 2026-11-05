<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelNass\Data\Wallet\InitWalletTransactionData;
use LeviLabs\LaravelNass\Facades\NassWalletPayment;
use LeviLabs\LaravelNass\Models\NassWalletTransaction;

it('initializes wallet checkout and persists transaction', function () {
    Http::fake([
        'uatgw1.nasswallet.com/payment/transaction/login' => Http::response([
            'responseCode' => 0,
            'data' => ['access_token' => 'wallet-token'],
        ]),
        'uatgw1.nasswallet.com/payment/transaction/initTransaction' => Http::response([
            'responseCode' => 0,
            'data' => [
                'transactionId' => 'TX-100',
                'token' => 'checkout-token',
            ],
        ]),
    ]);

    $created = NassWalletPayment::init(new InitWalletTransactionData(
        orderId: 'W-ORD-1',
        amount: 25.50,
    ));

    expect($created->transactionId)->toBe('TX-100')
        ->and($created->checkoutUrl)->toContain('transactionId=TX-100')
        ->and($created->checkoutUrl)->toContain('userIdentifier=wallet_user');

    expect(NassWalletTransaction::query()->where('transaction_id', 'TX-100')->exists())->toBeTrue();
});

it('checks wallet transaction status', function () {
    Http::fake([
        'uatgw1.nasswallet.com/payment/transaction/login' => Http::response([
            'responseCode' => 0,
            'data' => ['access_token' => 'wallet-token'],
        ]),
        'uatgw1.nasswallet.com/payment/transaction/checkTransaction' => Http::response([
            'responseCode' => 0,
            'data' => [
                'transactionId' => 'TX-200',
                'orderId' => 'W-ORD-2',
                'amount' => '10.00',
                'transactionStatus' => 'SUCCESS',
            ],
        ]),
    ]);

    $status = NassWalletPayment::status('TX-200');

    expect($status->transactionId)->toBe('TX-200')
        ->and($status->orderId)->toBe('W-ORD-2')
        ->and($status->status->value)->toBe('completed');
});

it('maps wallet checkTransaction transactionStatus to payment status', function () {
    Http::fake([
        'uatgw1.nasswallet.com/payment/transaction/login' => Http::response([
            'responseCode' => 0,
            'data' => ['access_token' => 'wallet-token'],
        ]),
        'uatgw1.nasswallet.com/payment/transaction/checkTransaction' => Http::response([
            'responseCode' => 0,
            'data' => [
                'transactionId' => 'TX-201',
                'transactionStatus' => 'SUCCESS',
                'amount' => '15.00',
            ],
        ]),
    ]);

    $status = NassWalletPayment::status('TX-201');

    expect($status->transactionStatus)->toBe('SUCCESS')
        ->and($status->status->value)->toBe('completed');
});
