# Laravel NASS SDK — Payment Gateway, Nass Wallet & Webhooks

<p>
<a href="https://packagist.org/packages/levilabs/laravel-nass"><img src="https://img.shields.io/packagist/v/levilabs/laravel-nass.svg?style=flat-square&label=Packagist&color=orange" alt="Latest Version on Packagist"></a>
<a href="https://github.com/levilabs-dev/laravel-nass/actions"><img src="https://img.shields.io/github/actions/workflow/status/levilabs-dev/laravel-nass/run-tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/levilabs/laravel-nass"><img src="https://img.shields.io/packagist/dt/levilabs/laravel-nass.svg?style=flat-square&label=Downloads&color=blue" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/levilabs/laravel-nass"><img src="https://img.shields.io/packagist/php-v/levilabs/laravel-nass.svg?style=flat-square&label=PHP&color=777bb4" alt="PHP Version"></a>
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?style=flat-square" alt="Laravel Version"></a>
<a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/levilabs/laravel-nass.svg?style=flat-square&color=success" alt="License"></a>
</p>

A modern Laravel SDK for the [NASS E-Payment Gateway](https://nass.iq) (Iraq) — **hosted card checkout**, server notify callbacks, and status polling — plus **Nass Wallet** merchant checkout when you need wallet-based payments. Typed DTOs and enums, cached merchant authentication, optional database persistence, and Artisan sync commands when notifications are missed.

Maintained by **[Levi Labs](https://levilabs.dev)** ([GitHub](https://github.com/levilabs-dev)) — built by [Nizam Omer](https://nizaamomer.com)

**NASS:** [nass.iq](https://nass.iq) · **Developers:** [nass.iq/resources/developers](https://nass.iq/resources/developers)

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Payment Gateway — creating a transaction](#payment-gateway--creating-a-transaction)
- [Payment Gateway — notify callback](#payment-gateway--notify-callback)
- [Payment Gateway — checking status](#payment-gateway--checking-status)
- [Missed notifications: gateway sync command](#missed-notifications-gateway-sync-command)
- [UAT test card (gateway)](#uat-test-card-gateway)
- [Nass Wallet — init checkout](#nass-wallet--init-checkout)
- [Nass Wallet — checking status & callbacks](#nass-wallet--checking-status--callbacks)
- [Missed updates: wallet sync command](#missed-updates-wallet-sync-command)
- [Multiple merchants](#multiple-merchants)
- [Automatic persistence](#automatic-persistence)
- [Events](#events)
- [Error handling](#error-handling)
- [Security](#security)
- [Testing](#testing)
- [NASS API reference](#nass-api-reference)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Author](#author)
- [License](#license)

## Requirements

- PHP 8.2+ (Laravel **13.x** requires PHP **8.3+** on the application)
- Laravel 11.x, 12.x, or 13.x
- NASS merchant credentials (gateway and/or wallet), as issued by Nass

## Installation

```bash
composer require levilabs/laravel-nass
php artisan vendor:publish --tag=nass-config
php artisan migrate
```

Publish step copies [`config/nass.php`](config/nass.php). Migrations create `nass_gateway_payments` and `nass_wallet_transactions`; gateway and wallet lifecycle events upsert rows automatically when listeners are registered (default).

## Configuration

Gateway settings are listed first because the **Payment Gateway** is the primary integration path for most apps. Wallet variables are separate under `NASS_WALLET_*`.

```env
# --- NASS E-Payment Gateway (portal / hosted checkout) ---
NASS_GATEWAY_MERCHANT=default
NASS_GATEWAY_ENVIRONMENT=uat
NASS_GATEWAY_USERNAME=
NASS_GATEWAY_PASSWORD=
NASS_GATEWAY_BASE_URL=https://uat-gateway.nass.iq:9746
NASS_GATEWAY_BACK_REF=https://your-app.test/payments/nass/return
NASS_GATEWAY_NOTIFY_URL=https://your-app.test/payments/nass/notify

# --- Nass Wallet (optional second product in the same package) ---
NASS_WALLET_MERCHANT=default
NASS_WALLET_BASIC_TOKEN=
NASS_WALLET_USERNAME=
NASS_WALLET_PASSWORD=
NASS_WALLET_TRANSACTION_PIN=
NASS_WALLET_GRANT_TYPE=password
NASS_WALLET_BASE_URL=https://uatgw1.nasswallet.com/payment/transaction
NASS_WALLET_PORTAL_URL=https://uatcheckout1.nasswallet.com
NASS_WALLET_PORTAL_PARAM=transactionId
NASS_WALLET_CALLBACK_URL=https://your-app.test/payments/nass-wallet/callback
NASS_WALLET_LANGUAGE=en
```

> **Production hosts:** UAT URLs are safe defaults for development. Production gateway and wallet base URLs are issued with your merchant account — set `NASS_GATEWAY_BASE_URL` and `NASS_WALLET_BASE_URL` explicitly before going live.

Currency code **368** (Iraqi Dinar) and transaction type **1** (sale) are the usual gateway defaults; see [`config/nass.php`](config/nass.php) for overrides per merchant.

## Payment Gateway — creating a transaction

Authenticates with cached bearer tokens, creates a portal transaction, and returns a hosted payment URL — redirect the customer there to pay.

```php
use LeviLabs\LaravelNass\Data\Gateway\CreateTransactionData;
use LeviLabs\LaravelNass\Facades\NassGatewayPayment;

$created = NassGatewayPayment::create(new CreateTransactionData(
    orderId: 'ORDER-'.uniqid(),
    orderDesc: 'Checkout',
    amount: 150.0,
));

return redirect($created->paymentUrl);
```

`backRef` and `notifyUrl` fall back to `NASS_GATEWAY_BACK_REF` and `NASS_GATEWAY_NOTIFY_URL` when omitted on the DTO.

## Payment Gateway — notify callback

NASS POSTs the result to your `notifyUrl`. Parse the payload and treat **`responseCode === '00'`** and **`actionCode === '0'`** as approval; store **`rrn`** for long-term reference (`orderId` is only retained on Nass side for a limited time).

```php
use LeviLabs\LaravelNass\Facades\NassGatewayPayment;

public function notify(Request $request)
{
    $callback = NassGatewayPayment::parseCallback($request);

    if ($callback->isApproved()) {
        // Fulfill order
    }
}
```

## Payment Gateway — checking status

Poll the gateway directly (available within **24 hours** of initiation):

```php
$status = NassGatewayPayment::status('ORDER-123');

if ($status->isApproved()) {
    // Paid — use $status->rrn
}
```

## Missed notifications: gateway sync command

```bash
php artisan nass:sync-gateway-statuses
```

Re-checks pending rows in `nass_gateway_payments` that are still inside the 24-hour status window.

## UAT test card (gateway)

Use only in UAT, with credentials Nass provides for testing:

| PAN              | Expiry | CVV |
| ---------------- | ------ | --- |
| 5123450000000008 | 01/39  | 100 |

Confirm test data with your Nass integration pack from [NASS Developers](https://nass.iq/resources/developers).

## Nass Wallet — init checkout

Separate product from the card gateway — wallet login, init, then redirect to the hosted wallet checkout URL.

```php
use LeviLabs\LaravelNass\Data\Wallet\InitWalletTransactionData;
use LeviLabs\LaravelNass\Facades\NassWalletPayment;

$created = NassWalletPayment::init(new InitWalletTransactionData(
    orderId: 'W-100',
    amount: 25.00,
));

return redirect($created->checkoutUrl);
```

If your checkout portal expects `id=` instead of `transactionId=`, set `NASS_WALLET_PORTAL_PARAM=id`.

## Nass Wallet — checking status & callbacks

Always confirm wallet payments with **`checkTransaction`**, not by trusting the callback body alone:

```php
$status = NassWalletPayment::status('TX-ID-FROM-INIT');

// Incoming callback — verifies via checkTransaction internally:
$status = NassWalletPayment::parseCallback($request);
```

Status mapping uses the `transactionStatus` field returned by `checkTransaction` (for example `SUCCESS` / `FAILED`).

## Missed updates: wallet sync command

```bash
php artisan nass:sync-wallet-statuses
```

## Multiple merchants

Add entries under `gateway.merchants` and `wallet.merchants` in [`config/nass.php`](config/nass.php), then pass the merchant key as the last argument:

```php
NassGatewayPayment::create($dto, merchant: 'second_store');
NassWalletPayment::init($dto, merchant: 'second_store');
```

## Automatic persistence

| Table | When rows update |
| ----- | ---------------- |
| `nass_gateway_payments` | Gateway create, status check, notify-driven completed/failed events |
| `nass_wallet_transactions` | Wallet init, status check |

## Events

**Gateway:** `GatewayPaymentCreated`, `GatewayPaymentStatusChecked`, `GatewayPaymentCompleted`, `GatewayPaymentFailed`, `GatewayNotifyReceived`

**Wallet:** `WalletPaymentCreated`, `WalletPaymentStatusChecked`

## Error handling

```php
use LeviLabs\LaravelNass\Exceptions\NassGatewayException;
use LeviLabs\LaravelNass\Exceptions\NassWalletException;

try {
    NassGatewayPayment::create($dto);
} catch (NassGatewayException $e) {
    // HTTP / gateway logical failure
}
```

## Security

- **Credentials belong in `.env`**, never in version control.
- **Gateway notify payloads** should drive fulfillment only after `isApproved()`; use idempotency on `rrn` / `orderId`.
- **Wallet callbacks** must be confirmed with `parseCallback()` (which calls `checkTransaction`) — do not trust unsigned POST bodies alone.
- **TLS verification is never disabled** in this SDK.

See [SECURITY.md](SECURITY.md) for vulnerability reporting.

## Testing

```bash
composer test        # Pest
composer analyse     # PHPStan level 8
composer format      # Laravel Pint
```

## NASS API reference

Integration details and environment URLs are provided by Nass for your merchant account. Start here:

- [NASS](https://nass.iq)
- [NASS Developers](https://nass.iq/resources/developers)

### SDK mapping (Payment Gateway)

| SDK | HTTP | Path |
| --- | ---- | ---- |
| `NassGatewayAuth::login()` | POST | `/auth/merchant/login` |
| `NassGatewayPayment::create()` | POST | `/transaction` |
| `NassGatewayPayment::status()` | GET | `/transaction/{orderId}/checkStatus` |

| Environment | Base URL |
| ----------- | -------- |
| UAT | `https://uat-gateway.nass.iq:9746` |
| Production | `https://gateway.nass.iq:9746` |

### SDK mapping (Nass Wallet)

| SDK | HTTP | Path |
| --- | ---- | ---- |
| `NassWalletAuth::login()` | POST | `/login` |
| `NassWalletPayment::init()` | POST | `/initTransaction` |
| `NassWalletPayment::status()` | POST | `/checkTransaction` |

Direct card-on-server (PCI) and tap-to-phone flows are out of scope for this package — use **Payment Gateway** portal checkout unless Nass documents additional REST endpoints in your merchant materials.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

Bug reports and feature ideas are welcome via [GitHub Issues](https://github.com/levilabs-dev/laravel-nass/issues). See [CONTRIBUTING.md](CONTRIBUTING.md).

## Author

**[Levi Labs](https://levilabs.dev)** — [GitHub](https://github.com/levilabs-dev) · [hello@levilabs.dev](mailto:hello@levilabs.dev)

Created and maintained by **Nizam Omer** — [nizaamomer.com](https://nizaamomer.com) · [nizam@nizaamomer.com](mailto:nizam@nizaamomer.com)

## License

MIT — see [LICENSE.md](LICENSE.md).
