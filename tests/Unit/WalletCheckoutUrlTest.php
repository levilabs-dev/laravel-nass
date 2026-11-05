<?php

declare(strict_types=1);

use LeviLabs\LaravelNass\Support\WalletCheckoutUrl;

it('builds wallet checkout urls', function () {
    $url = WalletCheckoutUrl::build(
        'https://uatcheckout1.nasswallet.com',
        'TX-1',
        'token-1',
        'merchant@shop',
    );

    expect($url)->toBe('https://uatcheckout1.nasswallet.com/payment-gateway?transactionId=TX-1&token=token-1&userIdentifier=merchant%40shop');
});
