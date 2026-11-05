# levilabs/laravel-nass

<p>
<a href="https://packagist.org/packages/levilabs/laravel-nass"><img src="https://img.shields.io/packagist/v/levilabs/laravel-nass.svg?style=flat-square&label=Packagist&color=orange" alt="Latest Version on Packagist"></a>
<a href="https://github.com/levilabs-dev/laravel-nass/actions"><img src="https://img.shields.io/github/actions/workflow/status/levilabs-dev/laravel-nass/run-tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/levilabs/laravel-nass"><img src="https://img.shields.io/packagist/php-v/levilabs/laravel-nass.svg?style=flat-square&label=PHP&color=777bb4" alt="PHP Version"></a>
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?style=flat-square" alt="Laravel Version"></a>
<a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/levilabs/laravel-nass.svg?style=flat-square&color=success" alt="License"></a>
</p>

Modern Laravel SDK for **[NASS E-Payment Gateway](https://nass.iq/)** (hosted card checkout) and **[Nass Wallet](https://nw.iq/)** merchant checkout — portal payments, status polling, server notify callbacks, wallet init/check, typed DTOs, and optional persistence.

Maintained by **[Levi Labs](https://levilabs.dev)** ([GitHub](https://github.com/levilabs-dev)).

## Requirements

- PHP 8.2+
- Laravel 11.x, 12.x, or 13.x

## Installation

```bash
composer require levilabs/laravel-nass
php artisan vendor:publish --tag=nass-config
php artisan migrate
```

## Configuration

```env
# NASS E-Payment Gateway (portal)
NASS_GATEWAY_MERCHANT=default
NASS_GATEWAY_USERNAME=
NASS_GATEWAY_PASSWORD=
NASS_GATEWAY_BASE_URL=https://uat-gateway.nass.iq:9746
NASS_GATEWAY_BACK_REF=https://yourapp.test/payments/nass/return
NASS_GATEWAY_NOTIFY_URL=https://yourapp.test/payments/nass/notify

# Nass Wallet
NASS_WALLET_MERCHANT=default
NASS_WALLET_BASIC_TOKEN=
NASS_WALLET_USERNAME=
NASS_WALLET_PASSWORD=
NASS_WALLET_TRANSACTION_PIN=
NASS_WALLET_BASE_URL=https://uatgw1.nasswallet.com/payment/transaction
NASS_WALLET_PORTAL_URL=https://uatcheckout1.nasswallet.com
```

Multiple merchants are supported via `config/nass.php` (`gateway.merchants` / `wallet.merchants`).

## NASS Gateway (portal)

```php
use LeviLabs\LaravelNass\Data\Gateway\CreateTransactionData;
use LeviLabs\LaravelNass\Facades\NassGatewayPayment;

$created = NassGatewayPayment::create(new CreateTransactionData(
    orderId: 'ORDER-123',
    orderDesc: 'Checkout',
    amount: 150.0,
));

return redirect($created->paymentUrl);
```

Handle the gateway POST to your `notifyUrl`:

```php
use LeviLabs\LaravelNass\Facades\NassGatewayPayment;

public function notify(Request $request)
{
    $callback = NassGatewayPayment::parseCallback($request);

    if ($callback->isApproved()) {
        // Fulfill order — store $callback->rrn for long-term reference
    }
}
```

Poll status (within **24 hours** of initiation):

```php
$status = NassGatewayPayment::status('ORDER-123');
```

If webhooks are missed:

```bash
php artisan nass:sync-gateway-statuses
```

### UAT test card (gateway)

| PAN              | Expiry | CVV |
| ---------------- | ------ | --- |
| 5123450000000008 | 01/39  | 100 |

(Source: community Laravel SDK documentation; confirm with your Nass merchant PDF.)

## Nass Wallet

```php
use LeviLabs\LaravelNass\Data\Wallet\InitWalletTransactionData;
use LeviLabs\LaravelNass\Facades\NassWalletPayment;

$created = NassWalletPayment::init(new InitWalletTransactionData(
    orderId: 'W-100',
    amount: 25.00,
));

return redirect($created->checkoutUrl);
```

```php
$status = NassWalletPayment::status('TX-ID-FROM-INIT');
```

Wallet callbacks should be **verified with `checkTransaction`**, not by trusting the POST body alone:

```php
$status = NassWalletPayment::parseCallback($request);
```

Set `NASS_WALLET_PORTAL_PARAM=id` if your hosted checkout expects `id=` instead of `transactionId=` (official [sample-code-PHP](https://github.com/Nasswallet/sample-code-PHP) uses `transactionId`).

```bash
php artisan nass:sync-wallet-statuses
```

## API mapping

Public OpenAPI is not published on [nass.iq/resources/developers](https://nass.iq/resources/developers); the table below reflects **community and official sample code**. Validate against the integration PDF Nass provides for your merchant account.

### NASS E-Payment Gateway

| SDK method | HTTP | Path |
| ---------- | ---- | ---- |
| `NassGatewayAuth::login()` | POST | `/auth/merchant/login` |
| `NassGatewayPayment::create()` | POST | `/transaction` |
| `NassGatewayPayment::status()` | GET | `/transaction/{orderId}/checkStatus` |
| Notify callback | POST | Your `notifyUrl` (fields: `responseCode`, `actionCode`, `rrn`, …) |

| Environment | Base URL |
| ----------- | -------- |
| UAT | `https://uat-gateway.nass.iq:9746` |
| Production | `https://gateway.nass.iq:9746` |

**Approved payment:** `responseCode === '00'` and `actionCode === '0'`.

### Nass Wallet

| SDK method | HTTP | Path |
| ---------- | ---- | ---- |
| `NassWalletAuth::login()` | POST | `/login` (Basic `Authorization` + JSON `data`) |
| `NassWalletPayment::init()` | POST | `/initTransaction` |
| `NassWalletPayment::status()` | POST | `/checkTransaction` |
| `NassWalletPayment::parseCallback()` | — | Reads `data.InitTransactionId`, then calls `checkTransaction` |

| Environment | Transaction API base (trailing slash optional) |
| ----------- | --------------------------------------------- |
| UAT | `https://uatgw1.nasswallet.com/payment/transaction` (also seen: `https://uatgw.nasswallet.com/payment/transaction`) |
| Production | `https://gw-api.nasswallet.com/phase3/payment/transaction` |

**Official Nass Wallet sample repos:** [Nasswallet](https://github.com/Nasswallet) · [sample-code-PHP](https://github.com/Nasswallet/sample-code-PHP) · [sample-code-Laravel](https://github.com/Nasswallet/sample-code-Laravel) · [sample-code-nodejs](https://github.com/Nasswallet/sample-code-nodejs)

**NASS gateway community references:** [Rstacode/nass](https://github.com/Rstacode/nass) · [YadaCoder/nasspay](https://github.com/YadaCoder/nasspay)

GitHub repo metadata (description + topics): [.github/repository-metadata.md](.github/repository-metadata.md).

## Events

- Gateway: `GatewayPaymentCreated`, `GatewayPaymentStatusChecked`, `GatewayPaymentCompleted`, `GatewayPaymentFailed`, `GatewayNotifyReceived`
- Wallet: `WalletPaymentCreated`, `WalletPaymentStatusChecked`

Listeners upsert `nass_gateway_payments` and `nass_wallet_transactions` when migrations are run.

## Out of scope (v1)

- Direct API / card-on-server (PCI) — use portal integration instead.
- Tap to phone, terminals, dashboard-only refunds/reversals unless Nass documents REST endpoints in your merchant PDF.

## Testing

```bash
composer test
composer analyse
```

## License

MIT — see [LICENSE.md](LICENSE.md).
